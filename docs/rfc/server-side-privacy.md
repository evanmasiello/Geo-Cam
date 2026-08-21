# RFC: Enforce post/comment privacy server-side

> **Status as of 2026-08-21 (code at `0914871`): PARTIALLY RESOLVED.**
> `newGetPost.php` now filters server-side on both `hidden` and `visibility`, so the central claim about that endpoint is no longer true. `getPostsComments.php` is unchanged: still no session requirement and still returns hidden comments. Narrow this RFC to that endpoint.
> Line references below were re-verified against that commit.


## Problem
Privacy filtering for posts and comments happens **only in the browser**:
- `php/newGetPost.php` pushes every nearby post into `$finalPosts` regardless
  of `hidden` or `visibility` (the flags only affect the `$postCount`
  counter), then the client hides them (`indexScript.js`).
- `php/getPostsComments.php` returns the full comment array including
  `hidden` comments, and `imageArray[i].visibility != "following"` is the only
  thing protecting follower-only posts client-side.

A raw `curl -d "lat=..&long=.."` to `newGetPost.php` therefore returns
follower-only posts, their image filenames (`post<id>.png`, world-readable),
and all comments including hidden ones. `getPostsComments.php` requires no
session at all.

## Impact
Follower-only / deleted content and hidden comments are exposed to anyone who
talks to the API directly, bypassing the intended privacy model entirely.

## Proposed fix
Filter server-side before building the response, e.g. in `newGetPost.php`:
```php
if ($posts[$i]->hidden == true) continue;
if ($posts[$i]->visibility == "following"
    && $posts[$i]->user != $uID
    && !in_array($uID, json_decode($owner->followed ?? '[]'))) {
    continue;
}
```
and in `getPostsComments.php` drop `hidden` comments and require a valid
session. The client-side guards can stay as defense-in-depth.

## References
- `php/newGetPost.php:100-104` (no `hidden`/`visibility` filter)
- `php/getPostsComments.php:23-54` (no session, returns hidden comments)
- client: `js/indexScript.js` (`visibility != "following"`, `hidden != true`)
