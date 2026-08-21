# RFC index

Design proposals for Geo-Cam. Each file states a problem, its impact, and a
proposed fix; none of them are decisions. This index records what is still
worth deciding, because several were written before the fixes of August 2026
and have been partly or wholly overtaken by them.

**Status verified 2026-08-21 against `0914871`.** Every claim below was
re-checked against the code rather than carried over from the RFC text, and
the line references inside each document were re-mapped to that commit.

## Still worth your time

| RFC | Status | Why it still matters |
|---|---|---|
| [password-hashing](password-hashing.md) | open | Passwords are still unsalted SHA-256. `password_hash` appears nowhere. Needs a migration path, so it wants deciding before the user table grows. |
| [flag-admin-access](flag-admin-access.md) | open | Any single user can still hide any post, and the guard is bypassable by varying `reason`. Admin access code is still forgeable from the clock. |
| [image-upload-validation](image-upload-validation.md) | open | Unbounded, unvalidated base64 written to a public directory before any check. |
| [server-side-privacy](server-side-privacy.md) | narrow it | `newGetPost.php` now filters correctly; only `getPostsComments.php` is still exposed. Rewrite around that one endpoint. |
| [user-registration](user-registration.md) | open | First-ever signup against an empty `users.json` still fails. Small, self-contained fix. |
| [secret-files-webroot](secret-files-webroot.md) | partly mitigated | HTTP access is now denied by rule, but the datastore still lives in the web root. The proposal itself - move it out - is undecided. |

## Forward-looking, nothing has overtaken them

| RFC | Note |
|---|---|
| [rate-limiting](rate-limiting.md) | No throttling anywhere; brute-force and scraping are unbounded. |
| [usage-metrics](usage-metrics.md) | Argues the SQLite decision is currently a guess without read/write ratios. |
| [rest-api-openapi](rest-api-openapi.md) | Formal contract for the ~37 ad-hoc endpoints. |
| [mobile-push-notifications](mobile-push-notifications.md) | Product feature, not a defect. |

## Closed - no decision needed

| RFC | Outcome |
|---|---|
| [csprng-tokens](csprng-tokens.md) | Implemented (PR #36). All cited lines are gone. |
| [session-expiry](session-expiry.md) | Implemented (PR #22, with a fix to its invalidation loop). |
| [hardcoded-dump-allposts](hardcoded-dump-allposts.md) | Resolved (PR #33). Endpoint is `if (false)`; only dead-code removal remains. |
| [status-file-lock](status-file-lock.md) | Superseded by the SQLite decision. |
| [o1-post-lookups](o1-post-lookups.md) | Superseded by the SQLite decision. |

## A caution on line numbers

They rot. The CORS change alone shifted every `php/*.php` reference by three
lines, and PR #36 shifted several files by a different amount - so no single
offset would have been correct. The numbers here were re-derived per file by
diffing each RFC against the code it was written for. Treat the surrounding
quoted code as the real anchor; if a line number looks wrong, it probably is.
