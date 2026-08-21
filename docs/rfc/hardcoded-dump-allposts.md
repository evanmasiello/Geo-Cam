# RFC: Remove the hardcoded backdoor that dumps every post (with GPS) in getAllPosts.php

> **Status as of 2026-08-21 (code at `0914871`): RESOLVED.**
> PR #33 replaced the backdoor condition with `if (false)`, so the entire endpoint body is dead code and the leak is closed. What remains is housekeeping - delete the dead endpoint - not a security issue. The line this RFC originally cited no longer exists.
> Line references below were re-verified against that commit.


## Problem
`php/getAllPosts.php` gates a full dump of **every** post on a hardcoded
secret committed to the public repository:

```php
if(isset($_POST["lat"]) and isset($_POST["long"]) and $_POST["superSecretPassword"] == "ligmaCock"){
```

Anyone can POST `lat`/`long`/`superSecretPassword=ligmaCock` and receive
`json_encode` of the entire `posts.json`. Unlike `newGetPost.php` and
`getPostAdmin.php`, this endpoint **never zeroes `lat`/`long`** and **never
filters `hidden`**, so it returns deleted posts and the **exact GPS
coordinates** of every photo every user ever took.

## Impact
Unauthenticated disclosure of the entire post history plus the precise
geolocation of all users — the worst-case leak for a location-based app.

## Proposed fix
- Either delete the endpoint, or
- gate it on a real authenticated **admin** session (reuse the
  `getPostAdmin.php` session + admin-id check), and
- scrub coordinates (`$post->lat = 0; $post->long = 0;`) and exclude
  `hidden` posts before echoing, exactly as the other feed endpoints do.

## References
- `php/getAllPosts.php`, `php/getAllPosts.php:114`
- compare `php/newGetPost.php:286-287`, `php/getPostAdmin.php:186-187`
