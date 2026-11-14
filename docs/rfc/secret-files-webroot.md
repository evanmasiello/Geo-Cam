# RFC: Move the datastore & secret files out of the web root

> **Status as of 2026-11-14: PARTIALLY MITIGATED.**
> `php/.htaccess` now also denies `error_log`, `*.log`, `*.bak`, `*.old`, `*.sql` and `*.zip`; the root `.htaccess` denies the deploy state, logs/backups and direct `posts/` access. Password-hash logging was removed.
> Still open: the datastore and webhook secret remain inside the web root; moving them out is the durable fix.

## Problem
All application state lives in flat files written by the PHP scripts using
relative paths into `php/`:

- `users.json`, `sessions.json`, `mailKeys.json`, `forgotKeys.json`,
  `flags.json`, `commentFlags.json`, `blockedWords.json`, `status.txt`
- `discord.txt` (the Discord webhook URL used by every `sendMessage()`)

There is no `.htaccess` anywhere in the tree, so:

- `https://…/php/users.json` returns every user's **email + password hash**
- `https://…/php/discord.txt` returns the webhook integration secret

## Impact
Anyone can download the full user table (emails + unsalted SHA-256 hashes)
and the webhook URL. Combined with the weak hashing (see the
password-hashing RFC) and the hash returned to the browser (PR #6), a leaked
`users.json` is directly crackable and the webhook can be abused.

## Proposed fix
- Move the datastore outside the document root, e.g.
  `json_decode(file_get_contents(__DIR__ . "/../data/users.json"))`, and
  update every reader/writer (`createUser.php`, `checkSession.php`,
  `likePost.php`, `deleteUser.php`, `newGetPost.php`, …).
- OR add `php/.htaccess` denying direct access to data files:
  `<FilesMatch "\.(json|txt)$"> Require all denied </FilesMatch>`
- Move the Discord webhook URL into an environment variable / outside the web
  root.

## References
- `php/users.json` (committed), `php/discord.txt`, `php/status.txt`
- every `file_get_contents("users.json")` / `file_get_contents("discord.txt")`
