# RFC: Replace the status.txt mutex with flock()

## Problem
Concurrency between the read-modify-write PHP scripts is serialized by a
`status.txt` file containing `"OPEN"`/`"CLOSED"`. Several defects make this
both unreliable and a denial-of-service vector:

- `signIn.php`, `forgotPassword.php`, `getTempPass.php`, `confirmMail.php`
  and `updateUserLikes.php` spin **forever** because their wait loop never
  re-reads the file: `while ($dataStatus == "OPEN") { sleep(2); }`.
- Every `shutdown()` handler references function-local (undefined)
  `$weOpened`/`$statusFile`, so a fatal error leaves `status.txt = "OPEN"`
  forever, hanging all subsequent sign-ins / registrations / writes.
- `forgotPassword.php` waits for the lock but never takes it, then writes
  `"CLOSED"`, clobbering a lock held by a concurrent writer and letting two
  requests interleave on `users.json`/`posts.json`.

## Impact
A single stuck or fatally-aborting request can deadlock the entire
authentication / registration / like / comment path (availability loss) and
cause data corruption from interleaved read-modify-write.

## Proposed fix
Drop the file-based lock entirely and use an OS advisory lock on a lock file
(or the data file itself):

```php
$fp = fopen(__DIR__ . "/data.lock", "c");
flock($fp, LOCK_EX);
// read-modify-write users.json / posts.json
flock($fp, LOCK_UN);
```

`flock()` is released automatically when the request ends, even on fatal
error, so the fragile `shutdown()` handlers and "OPEN forever" deadlock
disappear. `register_shutdown_function` blocks can then be removed.

## References
- `php/signIn.php:9-11`, `php/forgotPassword.php:16-18`, `php/getTempPass.php:19-21`
- `function shutdown()` duplicated across ~20 endpoints
- `php/forgotPassword.php:150` (releases a lock it never acquired)
