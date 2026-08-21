# RFC: Fix flag endpoints and the predictable admin access code

> **Status as of 2026-08-21 (code at `0914871`): OPEN - unchanged.**
> Both defects verified live: the flag guard still builds a property name from user input (`flagPost.php`), and the admin access code is still derived from a constant string plus the current second (`getPostAdmin.php`).
> Line references below were re-verified against that commit.


## Problem

### Flagging instantly hides any content, and the guard is bypassable
`flagPost.php` / `flagComment.php` hide a post/comment the moment **one**
ordinary user flags it — no threshold, no per-user dedupe, no admin step:

```php
$reasonName = "not" . $reason;             // $reason = raw $_POST["reason"]
$postWasChecked = $posts[$postSave]->$reasonName;
if ($postWasChecked != true) {
    $posts[$postSave]->hidden = true;      // any user can hide anything
}
```

The "already reviewed" guard reads a **dynamic property built from user
input** (`"not" . $_POST["reason"]`), so an attacker simply re-flags with a
different `reason` (e.g. `reason=zzz`) and the guard passes again.

### Admin access code is fully predictable
`getPostAdmin.php:72-76` builds the code from a constant string plus the
current Unix second, so anyone can forge it:

```php
$string = "wow I really wish I could see all of the posts!!!!!" . $time;
$accessCode = hash("sha256", $string, false);
```

It is also overwritten on every admin feed load, so concurrent admins
invalidate each other.

## Impact
Instant, repeatable censorship of any post/comment by any user, and a
forgeable admin code that grants access to the full moderation feed.

## Proposed fix
- Whitelist `reason` values; treat a flag as a record, not an immediate hide.
  Only hide after N distinct reporters (or an explicit moderator action).
- Store flags per `(user, post)` so re-flagging by the same user is a no-op.
- For the admin code: `bin2hex(random_bytes(32))` stored hashed with an
  expiry; compare with `hash_equals()`. Better still, drive admin access from
  an `isAdmin` flag on the user record rather than hardcoded ids 1 and 4.

## References
- `php/flagPost.php:73-89`, `php/flagComment.php:71-101`
- `php/getPostAdmin.php:32` (id==4 mis-parenthesized), `php/getPostAdmin.php:72-77`
- `php/flagPost.php:109` stores array index instead of post id
