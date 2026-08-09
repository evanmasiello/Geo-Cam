# RFC: O(1) post lookups — stop compacting the array, or maintain an index?

## Problem

Every post write path (`deletePost.php`, `changePostVis.php`, `deleteComment.php`,
`newGetPost.php:110`, `getAllPosts.php:95`, etc.) rewrites `posts.json` with
`json_encode(array_values($posts))`. This renumbers the array so that
**post id no longer matches array index**, which forces every lookup to do a
linear or binary scan by `id` instead of a direct `$posts[$id]`.

## Why it matters

- **Performance**: binary search is `O(log n)`, which is fine today, but a
  direct index lookup is `O(1)` and eliminates the scan entirely.
- **Correctness surface**: the id-vs-index confusion is already a recurring bug
  pattern in this codebase (see PRs #4, #5, #11, #12). Removing the
  divergence removes an entire class of defects.
- **Simplicity**: `$posts[$id]` is easier to read and audit than a search loop.

## Options

### A. Stop compacting (`array_values`) — sparse arrays
Leave hidden/deleted posts in the array as `null` (or keep them with
`hidden: true`). Post id continues to equal its array index.

- **Pro**: zero-cost O(1) lookup; no new data structure to keep in sync.
- **Pro**: trivial to implement — drop `array_values` in each writer and add
  a `hidden`/null check in readers that iterate the array.
- **Con**: the file grows monotonically as posts accumulate; `count($posts)`
  no longer reflects the visible post count.
- **Con**: iteration and `count()` include holes, so every loop must tolerate
  or skip `null`/hidden entries.

### B. Maintain a separate `id => index` map
Store an `index` object alongside `posts` (or as a top-level field in
`posts.json`): `{ "1": 0, "2": 1, "4": 2 }`. On write/delete, update the map.

- **Pro**: O(1) lookup without sparse arrays; file stays compact.
- **Con**: every mutation must update two structures (posts + index), doubling
  the write-path complexity and the chance of inconsistency.
- **Con**: the index itself must be read and rewritten under the same lock,
  adding contention.

### C. Status quo — keep compacting + binary search
Keep the current `array_values` compaction and use the existing
`binarySearch()` helper (already in `createJsonPost.php`).

- **Pro**: simplest data model; smallest files; no new invariants.
- **Con**: O(log n) is still slower than O(1), and the id-vs-index bug class
  remains possible in new code.

## Questions for reviewers

1. Is the simplicity of compact arrays worth the recurring lookup cost and bug
   surface?
2. If we want O(1), do we prefer sparse arrays (option A) or a maintained
   index (option B)?
3. Should `newGetPost.php` and `getAllPosts.php` (the read-heavy endpoints)
   drive the decision, or should the write paths lead?

## References

- `php/createJsonPost.php:81` — id assigned as `last_id + 1`, appended via
  `array_push`.
- `php/deletePost.php:86`, `php/newGetPost.php:110`, `php/getAllPosts.php:95`
  — `array_values` compaction.
- `php/createJsonPost.php:181-184` — existing `binarySearch()` helper.
- PRs #4, #5, #11, #12 — id-vs-index bugs fixed this session.
