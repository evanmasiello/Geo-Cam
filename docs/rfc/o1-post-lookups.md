# RFC: O(1) post lookups — stop compacting the array, or maintain an index?

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

## References

- `php/createJsonPost.php:81` — id assigned as `last_id + 1`, appended via
  `array_push`.
- `php/deletePost.php:86`, `php/newGetPost.php:110`, `php/getAllPosts.php:95`
  — `array_values` compaction.
- `php/createJsonPost.php:181-184` — existing `binarySearch()` helper.
- PRs #4, #5, #11, #12 — id-vs-index bugs fixed this session.
