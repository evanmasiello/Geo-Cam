<?php

$posts = json_decode(file_get_contents("./posts/posts.json"));

for ($i=count($posts)-1; $i >= 0; $i--) {
    $id = $posts[$i]->id;
    // echo "<img src='./posts/post$id.png'>";
}

?>