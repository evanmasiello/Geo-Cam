# RFC: Generate reset/verify tokens with a CSPRNG and enforce expiry

## Problem
Password-reset and email-verify tokens are derived deterministically from
values the attacker can know or choose:

```php
// forgotPassword.php:76
$key = hash("sha256", $uname . $_POST["email"] . $time . rand(10,1000), false);
// createUser.php:144 / resendEmailVerify.php:36 / execChangeMail.php:108
$key = hash("sha256", $uname . $_POST["pass"] . $time, false);
```

- `$uname` is public, `$email` is what the attacker submitted, `$time` is the
  request second (observable from the HTTP `Date` header), and `rand(10,1000)`
  has only 991 values (and `rand()` is a predictable MT seed). An attacker
  triggers a reset for a victim, then computes ~10⁴ candidate keys and wins.
- `getTempPass.php:46-47` never expires the token (the check is inverted — see
  PR #7), and `confirmMail.php` stores a `time` but never validates it.
- Email verification can be self-granted: all inputs to the key are known to the
  requester and `confirmMail.php` accepts the key from anyone.

## Impact
Account takeover via brute-forced reset tokens (combined with the never-expiring
check) and trivially self-confirmed email addresses (defeating the verification
gate on posting/liking/commenting).

## Proposed fix
- Generate tokens with a CSPRNG and store only their hash:
  ```php
  $raw = bin2hex(random_bytes(32));
  $key = hash("sha256", $raw); // store $key, email $raw to the user
  ```
- Record an issuance `time` and reject keys older than e.g. 10 min (reset) /
  24 h (verify). Compare with `hash_equals()`.
- Require a valid session for `resendEmailVerify.php` / `execChangeMail.php`
  and stop deriving the key from the user's own credentials.

## References
- `php/forgotPassword.php:76`, `php/getTempPass.php:46-47`
- `php/createUser.php:144`, `php/resendEmailVerify.php:36`, `php/execChangeMail.php:108`
- `php/confirmMail.php:44-50` (no expiry check)
