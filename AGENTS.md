# AGENTS.md

Guidance for AI coding agents (and humans) working in this repository.

## Code style preferences

These are **style** preferences, not correctness rules. Prefer them for new
code and when refactoring; do not "fix" working code solely to satisfy them.

- **Avoid `break` / `break 2` for search-and-act loops.** Prefer a found/owner
  flag that the loop condition checks (e.g. `for ($i=0; $i < count($x) && !$found; $i++)`)
  or an early `return`. This is especially important for `break 2` across nested
  loops, which couples the levels and is easy to misread.
- **Keep similar fixes consistent.** When the same bug pattern appears in several
  endpoints (e.g. missing ownership checks, post-id-used-as-index), apply the same
  control-flow shape in each so the codebase reads uniformly.

## Bug-fixing workflow

- **One PR per issue.** Each distinct bug gets its own branch + PR against `main`.
- **Verify against the real source** before changing it; don't trust summaries.
- **Separate "quick fixes" from "bigger problems".** For invasive or
  architectural changes (data-format migrations, moving files out of the web
  root, replacing the locking scheme), open a discussion RFC PR (a `docs/rfc/*.md`
  proposal) instead of a code change, so the approach can be agreed first.

## Security posture (context for reviews)

This app is a flat-file JSON store (no SQL). Treat these as sensitive:
`users.json`, `sessions.json`, `mailKeys.json`, `forgotKeys.json`, `discord.txt`.
Be suspicious of: hardcoded secrets, unauthenticated endpoints, missing
ownership/authorization checks (IDOR), unvalidated user input written to the
datastore, and `eval()` / string-built `onclick` handlers (XSS).

## Codebase overview

Geo-Cam is a location-based social app built with **vanilla PHP + JS — no
framework and no build step**. All persistence is a **flat-file JSON store**
(no SQL).

### Layout
- `php/` — one API endpoint per file (e.g. `likePost.php`, `deletePost.php`,
  `newGetPost.php`). Each typically reads/writes JSON files and returns JSON
  (but several return bare strings like `"badSession"`, `"tooLong"`, `"1"`).
- `js/` — `indexScriptV71.js` (main feed), `cameraScriptV21.js` (camera page),
  and `sharedScriptV21.js` (shared helpers: `checkSession`, geo/`distance`,
  UI helpers). Load order matters: `sharedScriptV21.js` is loaded last on
  `index.html` and its definitions win on duplicates.
- HTML entry points: `index.html`, `camera.html`, `pc.html`, `link.html`.

### Data model / files
- `../posts/posts.json` — posts. Key fields: `id`, `user` (owner id),
  `lat`, `long`, `time`, `comments`, `likes`, `visibility` (`"all"` |
  `"following"`), `hidden` (soft-delete flag).
- `php/users.json` — users. Key fields: `id`, `user` (username), `pass`
  (unsalted SHA-256), `email`, `emailIsVerified`, `postCount`, `commentCount`,
  `likes`, `followed`, `blocked`.
- `php/sessions.json` — `{ key (sha256 of token), userId, time }`.
- Other state: `mailKeys.json`, `forgotKeys.json`, `flags.json`,
  `commentFlags.json`, `blockedWords.json`, `discord.txt` (webhook URL),
  `status.txt` (broken mutex — see RFC).

### Auth model
- Session token is stored in a **non-HttpOnly cookie**; validated by
  `sha256(token)` matching a `sessions.json` key.
- Admin is **hardcoded to user ids 1 and 4** (no role flag).
- Ownership is enforced by comparing post/comment `user` id to the session's
  `userId` — but only on endpoints that actually check it (many did not; see
  the IDOR PRs).

### Conventions & gotchas
- `id` vs array-index confusion is a recurring bug pattern: clients send a
  post/comment **id**, but some endpoints use it directly as a positional
  **index** into the posts array. The two diverge once the array is compacted
  (`array_values`/`array_splice`).
- Soft deletes use the `hidden` flag; images are `chmod`'d, not deleted.
- Concurrency is serialized by a `status.txt` "OPEN"/"CLOSED" file mutex that
  is known-broken (infinite waits, dead `shutdown()` handlers, lock stealing) —
  see the `flock()` RFC.
- No test suite or linter is configured; changes are verified by reading the
  source and reasoning about behavior.
