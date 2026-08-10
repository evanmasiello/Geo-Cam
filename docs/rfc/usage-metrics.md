# RFC: Usage metric tracking for scalability and database migration decisions

## Problem

The current flat-file JSON store gives us no telemetry. We cannot answer basic
questions like:

- How many requests per hour/day hit the feed vs auth vs write endpoints?
- What is the P95 / P99 latency of `newGetPost.php` as `posts.json` grows?
- How often does the `status.txt` mutex cause contention or deadlock?
- How fast are `posts.json`, `users.json`, and the `../posts/` image directory
  growing?
- What is the read-to-write ratio? (This is the single most important number
  for choosing between flat-file, SQLite, or a client-server DB.)

Without these numbers, the SQLite migration decision is a guess, and we have no
way to know whether a code-level optimization (e.g. binary search, sparse
arrays) is actually the bottleneck or just rearranging deck chairs.

## What to measure

### Request-level metrics (lightweight, per-request)

| Metric | Why it matters |
|--------|----------------|
| Endpoint + method | Distinguish feed reads (`newGetPost.php`) from writes (`likePost.php`, `addComment.php`, `deletePost.php`). |
| Response status / error string | Track auth failures (`badSession`, `emailNotConfirmed`), validation failures (`tooLong`, `containsBlocked`), and 5xx from fatals. |
| Elapsed time (microtime) | P50 / P95 / P99 latency per endpoint. Reveals whether the bottleneck is I/O, JSON encode/decode, or lock contention. |
| Session id (hashed) | Cohort analysis: how many unique sessions per hour, session lifetime, churn. Do not log the raw token. |
| Post id (if applicable) | Which posts are hot? Are a few posts dominating traffic? |
| User id (if applicable) | Power-user identification; correlating with postCount / likes. |
| Image served / uploaded flag | Track the `../posts/post<N>.png` hot-path; detect missing files or upload failures. |

### System-level metrics (periodic, cheap)

| Metric | Why it matters |
|--------|----------------|
| `posts.json` size | File-size growth rate; predicts when I/O becomes the dominant cost. |
| `../posts/` directory entry count / total bytes | Storage growth; image cleanup needs. |
| `users.json` size | Same, for the user table. |
| `sessions.json` length | Session churn; stale-session accumulation. |
| `status.txt` lock wait time | How often does the mutex cause serialization? If writers spend significant time sleeping on `OPEN`, that is a concrete signal that the locking scheme is broken in production, not just in theory. |
| PHP error log rate | Fatals, warnings, notices per minute. A rising rate often precedes data corruption. |

### Derived / aggregate metrics (computed offline)

- **Read-to-write ratio**: feed-reads vs like/comment/delete. If reads dominate by >10:1, SQLite (with its read-optimized B-tree) wins big. If writes are frequent, SQLite still helps but the gain is smaller.
- **Posts per session**: average engagement depth.
- **Latency distribution per endpoint**: P95 feed latency > 2s is a clear "migrate now" signal, even at modest scale.
- **Growth rate**: posts/day, users/day, images/day. Extrapolate to 6-month storage needs.

## Proposed instrumentation

### Option A — Append-only log file (minimal dependency)

Write one JSON-line per request to `php/metrics.jsonl`:

```json
{"ts":1754798765.123,"ep":"newGetPost.php","status":200,"ms":12.4,"session":"ab12...","postId":42}
```

Pros:
- Zero dependencies; works with the existing flat-file stack.
- Easy to `tail` and `grep` for debugging.
- Safe to drop during high write volume — lose old metrics, not app data.

Cons:
- No built-in aggregation; analysis requires a separate script.
- Grows without bound; needs rotation (`mv metrics.jsonl metrics.1.jsonl` when > 50 MB).

### Option B — SQLite `metrics` table (recommended if migration proceeds)

If PR #30 (SQLite migration) happens, add a `metrics` table:

```sql
CREATE TABLE IF NOT EXISTS metrics (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ts REAL NOT NULL,
  endpoint TEXT NOT NULL,
  status INTEGER,
  ms REAL,
  session_hash TEXT,
  post_id INTEGER,
  user_id INTEGER
);
CREATE INDEX IF NOT EXISTS idx_metrics_ts ON metrics(ts);
CREATE INDEX IF NOT EXISTS idx_metrics_ep ON metrics(endpoint);
```

Pros:
- B-tree index on `ts`/`endpoint` makes aggregation queries instant even at millions of rows.
- Atomic writes; no risk of corrupting metrics taking down the app.
- `PRAGMA journal_mode=WAL` lets reads proceed while appending metrics.

Cons:
- Adds write I/O per request (one extra INSERT), though this is negligible compared to the existing post-write path under WAL.

### Option C — No-op (status quo)

Keep guessing. Not recommended.

## Thresholds that should trigger (or accelerate) a database migration

These are starting points to calibrate once we have data:

- **Feed P95 latency > 2 s** at any sustained traffic level → migrate or cache.
- **`posts.json` > 5 MB** (or growing > 1 MB/week) → I/O is the bottleneck.
- **`status.txt` lock wait > 100 ms median** → the mutex is the bottleneck; SQLite (or `flock()`) is the fix.
- **Read-to-write ratio > 20:1** → SQLite read performance is the obvious win.
- **Auth failures > 5% of requests** → likely session invalidation / expiry issue, but worth tracking separately.
- **Disk usage of `../posts/` > 1 GB** → consider image offload + cleanup; unrelated to DB choice but affects overall hosting cost.

## How this informs the SQLite RFC

The SQLite RFC (PR #30) lists qualitative benefits. Metrics let us make the
decision quantitative:

- If metrics show tiny traffic and small files, staying on JSON + binary search
  is perfectly fine and SQLite is unnecessary complexity.
- If metrics show growing latency, file size, or lock contention, SQLite becomes
  the clear winner because it attacks the measured bottleneck directly.
- Metrics also tell us *which* endpoints to prioritize in the migration. If
  `newGetPost.php` is 90% of requests, rewriting it first yields the biggest
  win. If `likePost.php`/`addComment.php` are the latency outliers, those are
  the write-path hot spots where SQLite transactions help most.

## Implementation sketch

Add a tiny helper, e.g. `php/metric.php`:

```php
<?php
function logMetric(string $endpoint, int $status, float $ms, ?string $sessionHash = null, ?int $postId = null): void {
    $line = json_encode([
        'ts' => microtime(true),
        'ep' => $endpoint,
        'status' => $status,
        'ms' => round($ms, 1),
        'session' => $sessionHash,
        'postId' => $postId,
    ]) . PHP_EOL;
    @file_put_contents(__DIR__ . '/metrics.jsonl', $line, FILE_APPEND | LOCK_EX);
}
```

Then in each endpoint, wrap the handler:

```php
$start = microtime(true);
// ... existing logic ...
$status = is_numeric($response) ? (int)$response : 500;
logMetric(basename(__FILE__), $status, (microtime(true) - $start) * 1000, $sessionHash ?? null, $postId ?? null);
echo $response;
```

This is intentionally opt-in per endpoint. Start with the hot paths
(`newGetPost.php`, `getAllPosts.php`, `createJsonPost.php`, `signIn.php`,
`likePost.php`, `addComment.php`) and expand as needed.

## References

- PR #30 — SQLite migration RFC
- `php/status.txt` — current broken mutex
- `php/createJsonPost.php:81` — id generation pattern
- PRs #4, #5, #11, #12 — id-vs-index bugs; the same lookup pattern appears in
  every feed/write endpoint and is the primary driver of latency.
