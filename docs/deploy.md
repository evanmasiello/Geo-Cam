# Deploying Geo-Cam to Bluehost

Deployment runs from `.github/workflows/deploy.yml` on every push to `main`.
No more dragging files into the cPanel File Manager.

## One-time Bluehost setup

1. **Create a dedicated FTP account.** cPanel → *Files* → *FTP Accounts*.
   Make a user just for deploys (e.g. `deploy@yourdomain.com`) and set its
   directory to the web root you deploy into. A dedicated account means you
   can revoke it without touching your cPanel login.
2. **Use the box hostname, not your domain.** Bluehost's FTP certificate is a
   publicly-trusted Sectigo wildcard for `*.bluehost.com`, so your domain will
   fail verification with `curl: (60)`. Take the *Server Name* from cPanel's
   *Server Information* (e.g. `box2261`) and set `FTP_SERVER` to
   `box2261.bluehost.com`. That matches the wildcard, so both the backup step
   and the deploy step verify strictly. Explicit FTPS on port 21 is used
   throughout; plain `ftp` sends the password in cleartext, so don't.
3. **Confirm the web root path.** For a primary domain it is
   `/public_html/`. Addon domains and subdomains live under
   `/public_html/<something>/`.

## GitHub configuration

Repo → *Settings* → *Secrets and variables* → *Actions*.

All four live under **Repository secrets** — no Actions *variables* are used.

| Name              | Value                                                     |
|-------------------|-----------------------------------------------------------|
| `FTP_SERVER`      | FTP hostname (no `ftp://` prefix)                          |
| `FTP_USERNAME`    | Full FTP username                                          |
| `FTP_PASSWORD`    | FTP account password                                       |
| `FTP_REMOTE_DIR`  | Deploy directory, leading and trailing slash — e.g. `/public_html/geocam/` |

`FTP_REMOTE_DIR` has **no default**. If it is unset, or missing either slash,
the backup job fails immediately rather than guessing at a directory. Because
it is a secret it is masked in logs, so a wrong path shows up as a failed
`curl` on `php/users.json` rather than a readable path — check the value in
cPanel's File Manager if that happens.

## The dry-run safety switch

`dry-run` is **hardcoded to `true`** in `.github/workflows/deploy.yml`. Every
push to `main` will lint, take a real backup, connect to Bluehost, and log
exactly which files it *would* transfer — without writing a single one.

To go live, edit that one line:

```yaml
          dry-run: true     # <-- change to false
```

Leave it on `true` until a rehearsal run shows the file list you expect. The
`backup` job runs for real either way, so a dry run is also a genuine test of
your credentials and remote path.

The first run with `dry-run: false` has no sync state on the server, so it
uploads every tracked file. That is expected — it will not delete anything it
doesn't know about.

## What the workflow will not touch

The app keeps all its state in flat JSON files and **has no backup logic of
its own**. Two layers keep deploys away from that data:

1. Every runtime-written file is in `.gitignore`, so it isn't in the checkout
   the deploy job syncs from.
2. The same files are listed under `exclude:` in the workflow, so even if one
   got committed by accident it would still be skipped.

Protected paths: `php/users.json`, `php/sessions.json`, `php/mailKeys.json`,
`php/forgotKeys.json`, `php/flags.json`, `php/commentFlags.json`,
`php/blockedWords.json`, `php/discord.txt`, `php/accessCode.txt`,
`php/status.txt`, and the whole `posts/` and `logs/` trees.

`dangerous-clean-slate` is never enabled. Never enable it — it wipes the
entire remote directory, `posts/` included.

## TLS certificate mismatch

Bluehost's FTP certificate is issued for the physical server
(`boxNNNN.bluehost.com`), not for your domain. Pointing `FTP_SERVER` at your
domain therefore fails verification:

```
curl: (60) SSL: no alternative certificate subject name matches target host name
```

The `Inspect FTP server certificate` step prints the names the certificate
actually covers. Prefer setting `FTP_SERVER` to one of those — usually the
server hostname shown in cPanel's sidebar under *General Information*. That
keeps verification on.

Only if no usable hostname matches, flip `TLS_INSECURE` to `"true"` in
`.github/workflows/deploy.yml`. Traffic stays encrypted, but the server is no
longer authenticated, so the credentials become vulnerable to an active
man-in-the-middle. Note the deploy step already runs this way — the action's
`security` input defaults to `loose`.

## Login rejected (530 / curl exit 67)

`530` is an authentication failure, not a permissions problem — the server
rejects the credentials before any path is considered. File permission errors
are `550`/`553` and name the file. If TLS succeeded and this is what you get,
the certificate and hostname are fine and only the credentials are wrong.

The `Verify FTP credentials` step reports whether any secret carries stray
whitespace, whether the username has the expected `@`, and — once login
works — lists the FTP home directory so the correct `FTP_REMOTE_DIR` is
visible.

Two usual causes:

- **Username too short.** cPanel lists the login as `deploy`, but the real FTP
  username is `deploy@yourdomain.com`. Copy the full string from
  *FTP Accounts*.
- **Whitespace in a secret.** A trailing space or newline pasted into GitHub is
  invisible in the UI and fatal at login. Re-paste it.

## Empty FTP home / cannot change directory (curl exit 9)

`curl: (9) Server denied you to change to the given directory` means login
succeeded but the path was refused — credentials are fine, `FTP_REMOTE_DIR` is
not.

cPanel FTP accounts are **jailed** to their configured directory: after login
that directory *is* `/`. If the *Directory* field was left at its default when
the account was created, cPanel creates a new empty folder named after the
account (e.g. `public_html/deploy`) and locks the account into it. The
`Verify FTP credentials` step shows this as a home containing nothing but
`.ftpquota`.

Fix it in cPanel → *FTP Accounts* → *Actions* → *Change Directory*, pointing
the account at the app folder (e.g. `public_html/geocam`, relative to home, no
leading slash). Because the account remains jailed there, set
`FTP_REMOTE_DIR` to `/`.

Using the main cPanel account instead gives full home access and a
`FTP_REMOTE_DIR` of `/public_html/<folder>/`, but hands the pipeline your
primary hosting credentials. Prefer the scoped account.

## Backups

The `backup` job runs before every deploy. It pulls the live JSON store down
over FTPS, checks that `users.json` and `posts.json` are non-empty and parse
as JSON, and uploads the result as a workflow artifact kept for 90 days. If
that check fails the deploy is aborted before a single file is written.

To restore: open the run in *Actions*, download the `data-backup-*` artifact,
and upload the files back over the live ones.

Note this covers JSON state only, **not** the post images in `posts/`. Those
are large and never touched by deploys; back them up separately via cPanel.

## Housekeeping

The action writes `.ftp-deploy-sync-state.json` into the web root to track
what it has already uploaded. It contains a file listing, so deny it over
HTTP by adding to `.htaccess` in the web root:

```apache
<Files ".ftp-deploy-sync-state.json">
  Require all denied
</Files>
```

If the remote state ever drifts out of sync with reality, delete that file on
the server and the next run will re-upload everything.
