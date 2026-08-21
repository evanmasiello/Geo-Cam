# Migration plan: flat-file JSON → SQLite

> Written 2026-08-21 against `main` @ `1c23bfe`.
> Supersedes RFC #20 (status.txt mutex) and RFC #30 (O(1) post lookups),
> both of which the maintainer marked SUPERSEDED in favour of this work.

## 1. The one hard constraint

**The deploy pipeline cannot execute anything on the server.**

`.github/workflows/deploy.yml` is FTPS file sync. It can replace any file and
can run arbitrary computation *in the GitHub runner* before upload (that is how
`scripts/stamp-assets.py` works), but there is no SSH step and no way to add
one over FTP. Confirmed: the only `-exec` in the workflow is a local `find`.

Two consequences shape everything below:

- **Code ships fine.** Any pure-PHP change is a normal deploy.
- **Data does not.** Converting the live JSON into a populated `.sqlite` needs
  code to *run* on Bluehost. It also needs to read files the deploy
  deliberately excludes (`php/*.json`, `posts/**`) — that exclusion is the
  safety property protecting the datastore and must not be weakened.

**You do not need SSH or cPanel.** Deploy a `migrate.php`, load its URL once,
delete it in the next deploy. Execution comes from the HTTP request; FTP just
delivers the file. The data never leaves the server, so there is no staleness
window — strictly better than converting in the runner.

## 2. Preconditions — verify before planning anything else

A throwaway `php/probe.php`, deployed once and removed immediately. Everything
downstream depends on its answers.

| Question | Why it decides something |
|---|---|
| Is `PDO_SQLITE` available? | If not, the whole plan is void — reconsider MySQL, which Bluehost definitely offers via cPanel. |
| Can PHP write **above** the web root? | If yes, put the DB there and the exposure problem disappears entirely (this is RFC #19's proposal). If no, the DB sits in `php/` and *must* be denied in `.htaccess`. |
| `SQLite3::version()` | Determines whether WAL mode and `UPSERT` are usable. |
| Is the filesystem NFS? | SQLite locking is unreliable on some shared-host NFS mounts. If so, prefer MySQL. |

```php
<?php // php/probe.php — DELETE AFTER READING
header("Content-Type: text/plain");
echo "pdo_sqlite: " . (extension_loaded('pdo_sqlite') ? "YES" : "NO") . "\n";
echo "sqlite3:    " . (class_exists('SQLite3') ? SQLite3::version()['versionString'] : "n/a") . "\n";
$above = dirname(__DIR__, 2) . "/geocam-data";
echo "above-root writable: " . (@mkdir($above, 0700, true) || is_dir($above) ? "YES ($above)" : "NO") . "\n";
echo "php user:   " . get_current_user() . "\n";
```

**Do not leave this file deployed.** It discloses paths. Remove it in the very
next push.

## 3. The atomicity problem, and the fix

FTP sync is **not atomic**. It uploads file by file. A migration touching ~37
endpoints means a window — seconds to minutes — where some endpoints are
SQLite-aware and others still write JSON. Concurrent traffic in that window
writes to the wrong store. `concurrency: deploy-production` serialises
*deploys*, not users.

**Fix: deploy the new code dormant, behind a one-line flag.**

```php
// php/config.php
$USE_SQLITE = false;   // flip to true at cutover
```

Every migrated endpoint branches on `$USE_SQLITE`. Uploading thirty-seven files
then changes nothing observable. The cutover becomes a single small file write,
which *is* effectively atomic — and gives a real rollback: flip it back, and
the JSON store is still intact and current.

## 4. Do NOT use `status.txt` as the maintenance window

`status.txt` is the existing global write mutex across 23 endpoints. It looks
like a maintenance switch. It is not a safe one:

- Two endpoints write without ever taking the lock (`getPostAdmin.php`,
  `resendEmailVerify.php`), so they would keep writing.
- Even after the `1c23bfe` repairs, a held lock makes writers *wait* — they
  consume a PHP worker while waiting. On Bluehost's small shared worker pool,
  two minutes of held lock plus normal traffic can exhaust the pool and take
  down reads as well.

**Use `.htaccess` instead.** One small file, effectively atomic, returns 503
immediately rather than making anyone wait, and covers every endpoint
regardless of whether it participates in the lock:

```apache
# BEGIN Geo-Cam maintenance
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^/php/
    RewriteCond %{REQUEST_URI} !^/php/migrate\.php$
    RewriteRule ^ - [E=MAINT:1,R=503,L]
</IfModule>
<IfModule mod_headers.c>
    # mod_rewrite prefixes env vars with REDIRECT_ on an internal redirect,
    # so both spellings are needed for the header to actually appear.
    Header always set Retry-After "120" env=MAINT
    Header always set Retry-After "120" env=REDIRECT_MAINT
</IfModule>
# END Geo-Cam maintenance
```

Validated against Apache 2.4.66: `php/newGetPost.php` → 503 with
`Retry-After: 120`, `php/migrate.php` → 200, `/` and `index.html` → 200 with no
stray header. Re-run that check before using it — a syntax error in `.htaccess`
500s the entire site.

Note the carve-out so `migrate.php` itself stays reachable. Reads are blocked
too — that is intentional; a read served from JSON after the cutover would be
stale.

## 5. Schema

Derived from the record shapes actually written by `createUser.php`,
`createJsonPost.php` and `addComment.php`.

```sql
PRAGMA journal_mode = WAL;      -- only if the probe says the FS supports it
PRAGMA foreign_keys = ON;

CREATE TABLE users (
    id              INTEGER PRIMARY KEY,
    user            TEXT    NOT NULL UNIQUE COLLATE NOCASE,
    pass            TEXT    NOT NULL,
    email           TEXT    NOT NULL,
    emailIsVerified INTEGER NOT NULL DEFAULT 0,
    time            INTEGER NOT NULL,
    postCount       INTEGER NOT NULL DEFAULT 0,
    commentCount    INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE posts (
    id         INTEGER PRIMARY KEY,
    image      TEXT    NOT NULL,
    lat        REAL    NOT NULL,
    long       REAL    NOT NULL,
    time       INTEGER NOT NULL,
    user       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    visibility TEXT    NOT NULL
);
CREATE INDEX idx_posts_user ON posts(user);
CREATE INDEX idx_posts_loc  ON posts(lat, long);

CREATE TABLE comments (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    postId  INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    time    INTEGER NOT NULL,
    comment TEXT    NOT NULL
);
CREATE INDEX idx_comments_post ON comments(postId);

-- these three are JSON strings on the user record today
CREATE TABLE likes   (userId INTEGER NOT NULL, postId INTEGER NOT NULL,
                      PRIMARY KEY (userId, postId));
CREATE TABLE blocks  (userId INTEGER NOT NULL, blockedId INTEGER NOT NULL,
                      PRIMARY KEY (userId, blockedId));
CREATE TABLE follows (userId INTEGER NOT NULL, followedId INTEGER NOT NULL,
                      PRIMARY KEY (userId, followedId));

-- key is already a sha256 hash of the token, never the token itself
CREATE TABLE sessions    (key TEXT PRIMARY KEY, userId INTEGER NOT NULL, time INTEGER NOT NULL);
CREATE TABLE mailKeys    (key TEXT PRIMARY KEY, userId INTEGER NOT NULL, time INTEGER NOT NULL);
CREATE TABLE forgotKeys  (key TEXT PRIMARY KEY, userId INTEGER NOT NULL, time INTEGER NOT NULL);
CREATE INDEX idx_sessions_user ON sessions(userId);
```

Notes:

- `posts.comments` becomes a real table. That alone kills the
  `Undefined property: $comments` warning class and the O(n) scans in RFC #30.
- `users.likes` / `blocked` / `followed` are JSON strings today. As join tables
  they become indexable, which is the actual performance win.
- `lat`/`long` become `REAL`. This is where the original `deg2rad()` fatal came
  from — a typed column makes `"null"` impossible to store.
- Keep `flags.json`, `commentFlags.json` and `blockedWords.json` as files for
  now. They are small, rarely written, and out of scope.

## 6. Sequence

| # | Step | Deploy? | Reversible? |
|---|---|---|---|
| 1 | Probe `PDO_SQLITE` + above-root write | yes | n/a |
| 2 | Remove probe; extend `php/.htaccess` to deny `sqlite\|sqlite3\|db` (skip if the DB lives above the root) | yes | yes |
| 3 | Add `config.php` with `$USE_SQLITE = false` and the schema bootstrap | yes | yes |
| 4 | Migrate endpoints in batches, each branching on the flag, flag still `false` | many | yes |
| 5 | Dry-run: `migrate.php?dry=1` writes to `geocam-test.sqlite`, reports row counts, changes nothing | yes | yes |
| 6 | **Cutover** — see below | manual | yes |
| 7 | Delete `migrate.php`, remove the JSON branches, retire `status.txt` | yes | no |

### Step 6, the two-minute window

1. Deploy the maintenance `.htaccess`. Confirm `php/` returns 503.
2. `GET /php/migrate.php` — reads the live JSON, writes the DB, prints row
   counts per table.
3. **Compare counts against the JSON.** If any table disagrees, stop and roll
   back; do not flip the flag.
4. Flip `$USE_SQLITE = true`.
5. Remove the maintenance `.htaccess`.
6. Smoke test: sign in, load the feed, post, like, comment.

Steps 1–5 are minutes of work. Step 3 is the gate — a silent partial migration
is the one outcome that is genuinely hard to unwind.

## 7. Rollback

Cheap, until step 7:

- Flip `$USE_SQLITE` back to `false`. JSON is untouched and current, because
  nothing wrote to it while the flag was on and the site was in maintenance.
- **What is lost:** anything written *after* the cutover. Rolling back an hour
  later loses an hour of posts, likes and comments. Roll back in minutes or not
  at all — after that, fix forward.
- The `backup` job snapshots the JSON on every deploy and retains it 90 days,
  so a pre-cutover copy exists regardless.

## 8. Risks

| Risk | Severity | Mitigation |
|---|---|---|
| No `PDO_SQLITE` on Bluehost | fatal to plan | Step 1 probe, before anything else |
| DB file downloadable from the web root | **critical** — one GET yields the entire datastore, far worse than the JSON exposure | Put it above the web root, or deny it in `.htaccess` at step 2 |
| SQLite locking unreliable on shared NFS | high | Probe the FS; fall back to MySQL |
| Partial migration, silently | high | Row-count gate at step 6.3 |
| `migrate.php` left deployed | high — re-running it could clobber live data | Guard it with a one-time token; delete at step 7 |
| Write during the non-atomic sync | medium | The dormant flag removes this entirely |
| Concurrent write during migration | medium | Maintenance `.htaccess` blocks all `php/` |

## 9. Open questions

- **MySQL instead?** Bluehost provides it, it needs no above-root trickery, it
  cannot be downloaded as a single file, and cPanel gives real backup tooling.
  The cost is a connection config and losing the "just a file" simplicity.
  Worth deciding *before* step 3, not after.
- **Is two minutes actually enough?** Unknown until the dry run at step 5 times
  the conversion against real data volume. RFC #31 (usage metrics) is relevant:
  without read/write ratios, the window's cost is a guess.
- **What happens to `status.txt`?** SQLite transactions replace it. Retire it at
  step 7 — do not leave two locking mechanisms alive.
