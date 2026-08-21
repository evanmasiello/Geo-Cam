# RFC: Rate limiting for Geo-Cam endpoints

> **Status as of 2026-08-21 (code at `0914871`): OPEN.**
> Forward-looking; nothing in the recent fixes affects it. Already written with the CSPRNG work in mind.
> Line references below were re-verified against that commit.


## Problem

The current API has no throttling. A single client (or a botnet) can fire
unlimited requests at any endpoint:

- Brute-force `signIn.php` with password guesses at network speed.
- Spam `createUser.php` to fill `users.json` with garbage accounts.
- Flood `likePost.php` / `addComment.php` / `followUser.php` to inflate counts
  or harass users.
- Hammer `forgotPassword.php` / `getTempPass.php` to generate unlimited reset
  tokens (even after the CSPRNG fix, unlimited attempts still leak timing
  information and spam the email queue).
- Scrape `newGetPost.php` / `getAllPosts.php` to enumerate every post and
  user in seconds.

The flat-file store makes this worse: every request reads and rewrites the
whole file, so a flood directly competes with legitimate users for I/O and
lock time.

## What to limit

| Endpoint | Limit rationale |
|----------|-----------------|
| `signIn.php` | Prevent credential stuffing. |
| `createUser.php` | Prevent account spam. |
| `forgotPassword.php`, `getTempPass.php`, `resendEmailVerify.php` | Prevent token spam and email abuse. |
| `likePost.php`, `addComment.php`, `followUser.php`, `blockUser.php` | Prevent interaction inflation and harassment. |
| `newGetPost.php`, `getAllPosts.php`, `getPostsComments.php` | Prevent enumeration/scraping. |
| `execChangePass.php`, `execChangeMail.php`, `execChangeName.php` | Prevent account takeover via setting changes. |

## Options

### A. In-memory token bucket (fastest, simplest)

Use a static/shared in-memory store keyed by `session_hash` or IP:

```php
$key = ($_SESSION['user'] ?? '') . '|' . ($_SERVER['REMOTE_ADDR'] ?? '');
$bucket = apcu_fetch($key) ?: ['tokens' => 60, 'ts' => time()];
// refill + consume on each request
```

- **Pro**: zero disk I/O; sub-millisecond check.
- **Con**: not shared across multiple PHP-FPM workers unless APCu is
  configured as a shared cache; resets on deploy/restart.

### B. File-backed token bucket (flat-file friendly)

Store a small JSON file per key in `php/rate_limits/`:

```php
$path = __DIR__ . "/rate_limits/" . md5($key) . ".json";
$state = json_decode(file_get_contents($path) ?: '{"tokens":60,"ts":0}', true);
```

- **Pro**: works on any host, including shared BlueHost; survives restarts.
- **Con**: one `fopen`/`fwrite` per check; under heavy flood this adds I/O.
  Mitigate with `LOCK_SH` reads and `LOCK_EX` writes, and expire files after
  the window.

### C. SQLite `rate_limits` table (if migration proceeds)

```sql
CREATE TABLE IF NOT EXISTS rate_limits (
  key TEXT PRIMARY KEY,
  tokens INTEGER NOT NULL,
  ts REAL NOT NULL
);
```

- **Pro**: atomic increments via `UPDATE ... SET tokens = tokens - 1`; no
  race conditions; survives restarts; works across PHP-FPM workers.
- **Pro**: fits naturally into the SQLite migration (PR #30).
- **Con**: one extra write per request, but this is negligible under WAL.

## Recommended approach

Start with **option B** (file-backed) because:
- It works immediately on the current flat-file stack.
- It is easy to audit and debug (plain JSON files in `php/rate_limits/`).
- It can be swapped for option C later with no API changes when SQLite lands.

## Thresholds (starting points, tune with metrics from PR #31)

| Endpoint | Window | Max requests |
|----------|--------|--------------|
| `signIn.php` | 15 min | 10 per session/IP |
| `createUser.php` | 1 hour | 5 per IP |
| `forgotPassword.php` / `resendEmailVerify.php` | 1 hour | 3 per session |
| `getTempPass.php` | 10 min | 5 per key |
| `likePost.php` / `addComment.php` / `followUser.php` | 1 min | 30 per user |
| `newGetPost.php` / feed reads | 1 min | 120 per session |
| `execChangePass.php` / `execChangeMail.php` | 1 hour | 5 per user |

## Interaction with other RFCs

- **PR #24 (CSPRNG tokens)** — rate limiting makes token brute-force
  impractical even if a token is somehow predictable.
- **PR #30 (SQLite)** — SQLite makes option C trivial; migrate the
  `rate_limits` table when migrating the rest of the data.
- **PR #31 (metrics)** — instrument rate-limit rejections so we can tune
  thresholds and detect abuse patterns.

## Implementation sketch

Add `php/rateLimit.php`:

```php
<?php
function checkRateLimit(string $key, int $limit, int $windowSeconds): bool {
    $dir = __DIR__ . "/rate_limits";
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    $path = $dir . "/" . md5($key) . ".json";
    $fp = fopen($path, "c+");
    if (!$fp) return true; // fail open on file error
    if (!flock($fp, LOCK_EX)) { fclose($fp); return true; }
    $state = json_decode(stream_get_contents($fp) ?: '{"tokens":0,"ts":0}', true);
    $now = time();
    $elapsed = $now - ($state['ts'] ?? 0);
    if ($elapsed > $windowSeconds) {
        $state = ['tokens' => $limit, 'ts' => $now];
    }
    $state['tokens']--;
    $state['ts'] = $now;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($state));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $state['tokens'] >= 0;
}
```

Call it from each endpoint:

```php
$key = ($_SESSION['user'] ?? '') . '|' . ($_SERVER['REMOTE_ADDR'] ?? '');
if (!checkRateLimit($key, 30, 60)) {
    http_response_code(429);
    echo 'rateLimited';
    exit;
}
```

## References

- PR #24 — CSPRNG token generation
- PR #30 — SQLite migration
- PR #31 — usage metrics
