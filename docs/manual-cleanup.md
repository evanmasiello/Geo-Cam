# Manual cleanup

Things the deploy pipeline cannot do for you. The FTP deploy only *uploads*
— it never deletes files on the server unless it uploaded them itself — so
everything under "On the server" has to be done by hand in cPanel File
Manager.

Ordered by priority. Tick them off as you go.

---

## 1. Protect the JSON datastore  (highest priority)

Only `users.json` and `discord.txt` are protected. Everything else in `php/`
is readable by anyone:

| File | Status |
|------|--------|
| `php/users.json` | 403 — protected |
| `php/discord.txt` | 403 — protected |
| `php/sessions.json` | **200 — public** |
| `php/mailKeys.json` | **200 — public** |
| `php/forgotKeys.json` | **200 — public** |
| `php/flags.json` | **200 — public** |
| `php/commentFlags.json` | **200 — public** |
| `php/userLikes.json` | **200 — public** |
| `php/blockedWords.json` | **200 — public** |
| `php/accessCode.txt` | **200 — public** |
| `php/status.txt`, `logStatus.txt`, `postWonContest.txt` | **200 — public** |

`forgotKeys.json` is the serious one. It stores password-reset keys as
`sha256($key)`, and the key is built as:

```php
$key = hash("sha256", $uname . $_POST["email"] . $time . rand(10,1000));
```

`rand(10,1000)` is 991 possibilities. Username and email are known for a
target and `$time` is a timestamp, so the raw key can be brute-forced offline
from the published hash in seconds — and the raw key completes a password
reset. Public file + weak entropy = account takeover.

**Fix:** create `php/.htaccess` containing:

```apache
<FilesMatch "\.(json|txt)$">
  Require all denied
</FilesMatch>
```

Verify afterwards that `https://evanmasiello.com/geocam/php/forgotKeys.json`
returns 403 and that the app still works (the PHP reads these from disk, so
denying HTTP access does not affect it).

---

## 2. Delete superseded script and stylesheet versions

87 orphaned files, all still served. They were removed from the repo but
remain on the server.

Delete from `js/`: every `indexScript*.js` except `indexScriptV80.js`, every
`sharedScript*.js` except `sharedScriptV22.js`, every `cameraScript*.js`
except `cameraScriptV22.js`.

Delete from `css/`: every `style*.css` except `styleV53.css` and
`styleV85.css`.

Note `sharedScriptV14`–`V21` each contain the old Discord webhook. It has
been revoked, so this is now hygiene rather than a leak.

---

## 3. Delete dev/test artifacts from the web root

These are live PHP endpoints, unauthenticated, one uncommented line away
from doing something real:

- `php/checkCharacters.php` — hardcoded test string, echoes a fixed result
- `php/testMessage.php` — reads `discord.txt`, `curl_exec` commented out
- `php/getAllLocations.php` — builds links from every post's GPS, `echo`
  commented out

They are kept in the repo so it mirrors production. Delete them here and
from the repo together, or leave both — do not delete only one side, or the
drift starts again.

---

## 4. Delete large junk from the web root

Found by the snapshot; 17 GB in total.

- `geocam.zip`, `admin.zip`, `geoCamBackup11_8_22.zip`
- `ziG2kC0Q` (extensionless, ~1.5 GB)
- `error_log` (web root) and `php/error_log`
- `.htaccess.20220816102010.old`

Old backup archives inside the web root are also worth checking for
downloadability before you delete them.

---

## 5. Empty folders left behind by the FTP setup

cPanel created these when FTP accounts were made with the default directory:

- `evanmasiello.com/deploy`
- `public_html/geocam` — only if it was created by the second attempt

---

## 6. Rotate and verify

- [x] Discord webhook — revoked
- [ ] Admin passwords that were in `testBranches/*/admin/pass.txt` and
      `pass2.txt` (those files were publicly readable before deletion)
- [ ] Check cPanel → *Metrics* → *Raw Access Logs* for any request to
      `/geocam/testBranches/` predating 2026-08-20. Hits only from that day
      are the audit described above. Older hits from unfamiliar user agents
      mean the exposed `users.json` should be treated as compromised and
      password resets forced.
- [ ] Put a new webhook in `php/discord.txt` if you want Discord
      notifications back — server-side only; the client-side copy is now
      `var url = ""` and must stay that way.

---

## 7. GitHub housekeeping

- [ ] Delete the 7.9 GB `live-code-snapshot-4` artifact (run 32445085122).
      Artifact storage is billed.
- [ ] Delete the `FTP_REMOTE_DIR` secret. The workflow defaults to `/`, and
      while the secret is literally `/` every forward slash in every log line
      is masked as `***`.
- [ ] Delete the `production-baseline` branch once the reconciliation is
      merged.

---

## 8. Code follow-ups (separate PRs, not manual)

- `getGlobalFeed.php` filters on `visibility == "all"` but never checks
  `hidden`, so soft-deleted posts appear in the global feed.
  `newGetPost.php:99` shows the pattern it should follow.
- Reset-key entropy — replace `rand(10,1000)` with `random_bytes()`. There
  is already an RFC branch for this (`rfc/csprng-tokens`).
- Moving the datastore out of the web root entirely — RFC branch
  `rfc/secret-files-webroot`. That would make item 1 unnecessary.
- `shop/` is a WordPress install inside the app folder. Unrelated to Geo-Cam,
  but unpatched WordPress plugins are the most common way shared hosting gets
  compromised. Decide whether you still want it.

---

## 9. After the cache-busting change deploys

Renaming to stable filenames leaves one final set of orphans on the server —
the last of them, since filenames stop changing after this:

- `js/indexScriptV80.js`, `js/sharedScriptV22.js`, `js/cameraScriptV22.js`
- `css/styleV85.css`, `css/styleV53.css`

Delete them only **after** confirming the site works on the new
`indexScript.js` / `sharedScript.js` / `cameraScript.js` / `style.css` /
`styleDashboard.css` files, so there is something to fall back to.
