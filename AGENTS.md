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
