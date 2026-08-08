# RFC: Add session expiry and invalidate sessions on password change

## Problem
Session rows (in `sessions.json`) store a `time` field but **no endpoint ever
reads it**, so a stolen session token is valid forever. Separately,
`execChangePass.php` changes the password but leaves every existing session in
`sessions.json` intact — so "I changed my password because I was hacked" does
not evict the attacker's session.

## Impact
Permanent session validity and failure to contain account takeover after a
password reset.

## Proposed fix
- In the session-lookup code (e.g. `checkSession.php:14`,
  `execChangePass.php:41`), reject sessions older than a limit:
  ```php
  if ($sessionHash == $sessions[$r]->key
      && (time() - intval($sessions[$r]->time)) < 60*60*24*90) { … }
  ```
- In `execChangePass.php`, after a successful change, remove every
  `sessions.json` entry whose `userId == $uID` and issue a fresh token:
  ```php
  $sessions = array_values(array_filter(
      $sessions,
      fn($s) => !($s->userId == $uID)
  ));
  file_put_contents("sessions.json", json_encode($sessions));
  ```

## References
- `php/checkSession.php:14`, `php/execChangePass.php:41-66`
- sessions created in `php/signIn.php:64` (stores `time`)
