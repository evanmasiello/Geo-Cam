# RFC: O(1) post lookups — stop compacting the array, or maintain an index?

> **Status as of 2026-08-21 (code at `0914871`): SUPERSEDED.**
> Self-marked: superseded by the SQLite decision. Retained for reference.
> Line references below were re-verified against that commit.


> **Status: superseded by SQLite migration.** The maintainer has decided to
> proceed with SQLite (see the SQLite migration RFC), which provides O(1)
> primary-key lookups natively via its B-tree index. The options below are
> retained for reference but are no longer under active consideration.

## The uncomfortable truth: I/O dominates

Every request that touches posts currently does:

1. `file_get_contents("../posts/posts.json")` — O(n) disk read of the whole file
2. `json_decode(...)` — O(n) parse
3. Search by id — O(1), O(log n), or O(n)
4. `json_encode(array_values($posts))` — O(n) serialize
5. `file_put_contents(...)` — O(n) disk write

Step 1 + 2 + 4 + 5 dwarf step 3. Whether you do a linear scan, binary search,
or `$posts[$id]` direct lookup, the wall-clock time is effectively the same.
The array compaction (`array_values`) that breaks the `id == index` invariant
is not the performance bottleneck — it is a **correctness and simplicity**
problem.

The id-vs-index confusion is already a recurring bug pattern (see PRs #4, #5,
#11, #12). Removing the divergence removes an entire class of defects and
makes the code easier to read and audit. Performance-wise, no JSON-side option
will beat the status quo by more than a constant factor.

## Options

### A. Stop compacting (`array_values`) — sparse arrays
Leave hidden/deleted posts in the array as `null` (or keep them with
`hidden: true`). Post id continues to equal its array index.

- **Pro**: O(1) lookup; zero new data structures; trivial to implement — drop
  `array_values` in each writer and add a `hidden`/null skip in readers.
- **Pro**: eliminates the id-vs-index bug class.
- **Con**: file grows monotonically; `count($posts)` no longer equals visible
  post count; every loop must tolerate or skip holes.
- **Con**: no actual I/O speedup (still read/write the whole file).

### B. Maintain a separate `id => index` map
Store an `index` object alongside `posts` (or as a top-level field in
`posts.json`): `{ "1": 0, "2": 1, "4": 2 }`. On write/delete, update the map.

- **Pro**: O(1) lookup without sparse arrays; file stays compact.
- **Pro**: eliminates the id-vs-index bug class.
- **Con**: every mutation must update two structures (posts + index), doubling
  write-path complexity and the chance of inconsistency.
- **Con**: no actual I/O speedup (still read/write the whole file + index).

### C. Status quo — keep compacting + binary search
Keep the current `array_values` compaction and use the existing
`binarySearch()` helper (already in `createJsonPost.php`).

- **Pro**: simplest data model; smallest files; no new invariants.
- **Con**: id-vs-index bug class remains possible in new code.
- **Con**: O(log n) lookup, but this is not the bottleneck.

### D. Switch to SQLite
Replace the flat-file JSON store with a single SQLite database.

- **Pro**: real B-tree indexes → O(log n) targeted reads; `SELECT` with
  `WHERE` loads only matching rows, so the I/O cost scales with result size,
  not total data size.
- **Pro**: transactions give atomic multi-file consistency (users + posts +
  sessions in one DB) and eliminate the broken `status.txt` mutex entirely.
- **Pro**: prepared statements eliminate the SQL-injection risk from
  string-concatenated queries.
- **Pro**: `post.id` is the primary key — `SELECT * FROM posts WHERE id = ?`
  is a direct, index-backed lookup with no id-vs-index confusion.
- **Con**: invasive migration — every PHP endpoint must be rewritten.
- **Con**: single file becomes a single point of failure (mitigated by
  backups); the DB file must live outside the web root.
- **Con**: still needs WAL/row-level locking tuned for concurrent writes, but
  this is far simpler than the current `status.txt` scheme.

## Questions for reviewers

1. Is the simplicity of compact arrays worth the recurring id-vs-index bug
   surface, even though it is not a performance issue?
2. If we want to eliminate the bug class without SQLite, do we prefer sparse
   arrays (option A) or a maintained index map (option B)?
3. Is SQLite worth the migration cost, given that it is the only option that
   meaningfully reduces I/O and also solves the locking / injection / consistency
   problems in one move?

## Alternatives considered

Flat-file PHP libraries that were evaluated:

- **noneDB** — JSONL with byte-offset indexing for O(1) key lookups and an
  R-tree for geospatial queries (good fit for a geo app). Atomic `flock`-based
  locking. Still rewrites whole documents under the hood, so the read/write
  cost is unchanged; adds a dependency to evaluate/maintain.
- **SleekDB** — multi-file JSON store with a query cache. Cache invalidation
  is the hard part; the cache-first model is better for read-heavy than for
  the write-heavy social model here.
- **FlatFileDB / JsonDB** — indexed JSON with transaction logging. Effectively
  "current codebase but with a library." The library still rewrites whole files,
  so the fundamental O(n) read/write cost remains.
- **storh** — append-only segmented log plus an optional SQLite mirror. More
  complex than needed for this project; the mirror path is essentially option D
  (SQLite) with extra machinery.

These are viable if the goal is "stay flat-file but fix correctness." SQLite
(option D) is the only option that meaningfully reduces I/O cost while also
solving locking, injection, and consistency.

## SQLite integration notes

### Do you need to run it?
No. SQLite is **serverless** — there is no daemon or service to start. PHP's
`PDO_SQLITE` extension reads and writes a single file directly, exactly like
`file_get_contents`/`file_put_contents`, but with transactions, prepared
statements, and a B-tree index built in. The extension is bundled with PHP
and enabled by default on most distributions; if missing, install
`php8.1-sqlite3` (or the equivalent for your PHP version).

### How it integrates into the current PHP files
1. **One `connect.php` (or included config)** replaces the per-request
   `file_get_contents`/`json_decode`/`file_put_contents` dance:
   ```php
   $pdo = new PDO('sqlite:' . __DIR__ . '/../data/app.sqlite');
   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   ```
   The DB file is created automatically on first open.

2. **Schema**: a single `CREATE TABLE IF NOT EXISTS` block (run once, e.g. on
   install or first request) defines users, posts, comments, sessions, etc.
   `id INTEGER PRIMARY KEY` gives you auto-increment and index-backed lookups.

3. **Replace read/write with prepared statements**:
   ```php
   // read
   $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
   $stmt->execute(['id' => $postId]);
   $post = $stmt->fetch(PDO::FETCH_OBJ);

   // write
   $stmt = $pdo->prepare('UPDATE posts SET hidden = 1 WHERE id = :id');
   $stmt->execute(['id' => $postId]);
   ```

4. **Transactions** replace the broken `status.txt` mutex. A write that touches
   posts + users + sessions becomes:
   ```php
   $pdo->beginTransaction();
   // ... multiple statements ...
   $pdo->commit();
   ```
   On error, `$pdo->rollBack()` — no orphan "OPEN" file, no dead `shutdown()`.

5. **Auth / cookies** stay the same. The only material change is that session
   lookups become `SELECT * FROM sessions WHERE key = :hash` instead of a
   PHP-loop over decoded JSON.

6. **Migration**: import the existing `users.json` / `posts.json` once into
   SQLite tables, then delete (or archive) the JSON files. After that, every
   endpoint speaks SQL; no more `json_encode(array_values(...))` compaction.

### What does *not* change
- The HTML/JS frontend is untouched. API responses can stay JSON
  (`json_encode($row)` or `echo json_encode(...)`).
- No build step, no external server, no new OS packages beyond the PHP
  sqlite3 extension (already present on most hosts).

## References

- `php/createJsonPost.php:86` — id assigned as `last_id + 1`, appended via
  `array_push`.
- `php/deletePost.php:89`, `php/newGetPost.php:113`, `php/getAllPosts.php:100`
  — `array_values` compaction.
- `php/createJsonPost.php:186-189` — existing `binarySearch()` helper.
- PRs #4, #5, #11, #12 — id-vs-index bugs fixed this session.
