# RFC: Fix registration failure on empty users.json and weak email validation

## Problem
`php/createUser.php` only computes `$emailValid` **inside** the loop that
iterates existing users:

```php
for ($x = 0; $x < count($jsonArrayUsers); $x++) {
    // ...
    if (str_contains($emailFilter2, "@") and str_contains($emailFilter2, ".")) {
        $emailValid = true;
    } else {
        $emailValid = false;
    }
}
// ...
if ($emailValid) {            // undefined when there are zero users
    // create the account
}
```

If `users.json` is missing or decodes to an empty array (the `else` branch at
`createUser.php:124` that handles a missing file never runs the loop),
`$emailValid` is never assigned, so the first-ever account can never be
created — the endpoint returns `"mailSend"` and creates nothing. The shipped
`users.json` is currently a single newline, so this is the live state.

Separately, the email check is far too weak (`contains "@" and contains "."`),
permitting CR/LF and other characters to reach `mail()`, and there is no
`filter_var($email, FILTER_VALIDATE_EMAIL)`.

## Impact
Registration is impossible in a fresh/empty deployment, and malformed addresses
(including header-injection characters) can be stored and emailed.

## Proposed fix
- Validate the email **once**, before the user loop, with:
  ```php
  $emailValid = (filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
              && !preg_match('/[\r\n]/', $email);
  ```
- Ensure `createUser.php` handles the empty/missing `users.json` case so the
  first user can register.
- Use the same strict check in `execChangeMail.php:91-95`.

## References
- `php/createUser.php:79-105, 124, 232, 246`
- `php/execChangeMail.php:91-95`
- repo `users.json` currently contains a single newline
