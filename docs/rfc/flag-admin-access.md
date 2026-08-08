# RFC: Fix flag endpoints and the predictable admin access code

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
`getPostAdmin.php:69-73` builds the code from a constant string plus the
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
- `php/flagPost.php:70-86`, `php/flagComment.php:68-98`
- `php/getPostAdmin.php:29` (id==4 mis-parenthesized), `php/getPostAdmin.php:69-74`
- `php/flagPost.php:106` stores array index instead of post id
