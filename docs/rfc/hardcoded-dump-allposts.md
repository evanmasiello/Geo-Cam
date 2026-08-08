# RFC: Remove the hardcoded backdoor that dumps every post (with GPS) in getAllPosts.php

## Problem
`php/getAllPosts.php:3` gates a full dump of **every** post on a hardcoded
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
- `php/getAllPosts.php:3`, `php/getAllPosts.php:109`
- compare `php/newGetPost.php:283-284`, `php/getPostAdmin.php:183-184`
