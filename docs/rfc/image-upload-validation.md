# RFC: Validate uploaded images and enforce size limits

> **Status as of 2026-08-21 (code at `0914871`): OPEN - unchanged.**
> Verified live: still a non-strict `base64_decode` written to disk before any validation, with no size cap.
> Line references below were re-verified against that commit.


## Problem
`php/createJsonPost.php` accepts an unbounded, unvalidated base64 image and
writes it to disk **before** any validation:

```php
$img = str_replace('data:image/png;base64,', '', $img);
$fileData = base64_decode($img);                 // non-strict: junk decodes to junk
file_put_contents($location, $fileData);         // written before it is ever checked
$image = imagecreatefrompng("../posts/post$id.png"); // false on non-PNG
imagejpeg($image, "../posts/smallPost$id.jpg", 15);   // throws on false
```

- No size cap: a large POST is a cheap disk-fill.
- `base64_decode` is non-strict, so arbitrary bytes are written into the
  publicly served `../posts/` directory under a `.png` name.
- When the data is not a valid PNG, `imagecreatefrompng` returns `false`,
  `imagejpeg(false, …)` throws, the `catch` swallows it, a junk `.png` is left
  behind, and `posts.json` is never updated — so the next post reuses the id
  and silently overwrites it.

## Impact
Disk exhaustion, orphaned/garbage files in the web root, and failed posts that
quietly corrupt the post id sequence.

## Proposed fix
- Cap `strlen($_POST['image'])` (e.g. reject > ~8 MB).
- Decode strictly (`base64_decode($img, true)`) and verify with
  `getimagesize()` / `finfo` before writing.
- Re-encode through GD (`imagecreatefromstring` → `imagepng` /
  `imagejpeg`) so EXIF/payloads are stripped, and only write the file **after**
  validation succeeds.

## References
- `php/createJsonPost.php:73-103`, `php/createJsonPost.php:162` (empty error response)
