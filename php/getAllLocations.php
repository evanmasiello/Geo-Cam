<?php

    $posts = json_decode(file_get_contents("../posts/posts.json"));
    
    $oldLinks = [];
    
    for ($i=0; $i<count($posts);$i++) {
        $link = "<a href='https://geocam.app/php/viewLocation.php?lat=" . $posts[$i]->lat . "&long=" . $posts[$i]->long;
        if (!in_array($link, $oldLinks)) {
            #echo $link . "' target='_blank'>link$i</a><br>";
            array_push($oldLinks, $link);
        }
    }

?>