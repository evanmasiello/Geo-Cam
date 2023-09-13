<?php

$posts = json_decode(file_get_contents("../posts/posts.json"));

for ($i=0; $i < count($posts); $i++) {
    if ($posts[$i]->hidden == true) {
        chmod("../posts/post" . $posts[$i]->id . ".png", 0640);
    }
}

?>