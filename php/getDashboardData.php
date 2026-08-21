<?php

    $posts = json_decode(file_get_contents("../posts/posts.json"));
    $users = json_decode(file_get_contents("users.json"));
    
    $postTotal = count($posts);
    $usersTotal = count($users);
    
    $time = strval(round(microtime(true)));
    
    $usersToday = 0;
    $postsToday = 0;
    
    for ($i=0; $i < $postTotal; $i++) {
        if ($time - (strval($posts[$i]->time)) < 86400) {
            $postsToday++;
            // echo "\n time is $time and post time is " . $posts[$i]->time;
        }
    }
    
    for ($x=0; $x < $usersTotal; $x++) {
        if ($time - (strval($users[$x]->time)) < 86400) {
            $usersToday++;
        }
    }
    
    $response = Array (
                "totalPosts" => $postTotal,
                "totalUsers" => $usersTotal,
                "usersToday" => $usersToday,
                "postsToday" => $postsToday
            );
            
    echo json_encode($response);

?>