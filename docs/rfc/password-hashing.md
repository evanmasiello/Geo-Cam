# RFC: Replace unsalted SHA-256 passwords with password_hash()

## Problem
Passwords are stored as a single, unsalted SHA-256 hash:

```php
$pass = hash("sha256", $_POST['pass'], false);
```

This appears in `createUser.php:64`, `signIn.php:21`, `execChangePass.php:37`,
`getTempPass.php:78`, `deletePost.php:47`, `deleteUser.php:51`. There is also
no minimum password length check (`createUser.php:12` only checks
`strlen > 0`).

## Impact
Unsalted SHA-256 is brute-forced at billions of guesses/second on a GPU.
Combined with the web-served `users.json` (secret-files RFC) and the hash
returned to the browser (PR #6), a leaked file is directly crackable.

## Proposed fix
- On write: `$pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);`
- On verify: `if (password_verify($_POST['pass'], $user->pass)) { … }`
- Enforce a minimum password length in `createUser.php` and
  `execChangePass.php`.
- Migrate existing accounts: when a user logs in with a still-SHA256 hash,
  re-hash it with `password_hash()` and store the new value.

Because this changes the on-disk format, the migration path above is required
so existing users are not locked out.

## References
- `php/createUser.php:64`, `php/signIn.php:21`, `php/execChangePass.php:37-38`,
  `php/getTempPass.php:78`, `php/deletePost.php:47`
