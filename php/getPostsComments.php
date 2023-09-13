<?php

if(isset($_POST["id"])) {
//if (false) {

$postData = file_get_contents("../posts/posts.json");

//echo $postData;

$posts = json_decode($postData);

//foreach ($posts as $post) {

$users = json_decode(file_get_contents("users.json"));

$notFound = true;

$searchId = $_POST["id"];

for ($i = count($posts); $i >= 0 && $notFound; $i--) {
    
    if ($posts[$i]->id == $searchId) {
        
        $notFound = false;
        
        if ($posts[$i]->comments != null) {
            $posts[$i]->commentCount = count(json_decode($posts[$i]->comments));
            
            $postCommArr = json_decode($posts[$i]->comments);
            
            for ($v=0; $v < count($postCommArr); $v++) {
                
                $notFound = true;
                
                for ($b=0; $b < count($users) && $notFound; $b++) {
                    if ($postCommArr[$v]->user == $users[$b]->id) {
                        $notFound = false;
                        $postCommArr[$v]->userName = $users[$b]->user;
                    }
                }
            }
            
            $posts[$i]->comments = json_encode($postCommArr);
            $commentsResponse = json_encode($postCommArr);
            
        } else {
            $commentsResponse = json_encode([]);
        }
        
    }
}

echo $commentsResponse;

} else {
    echo json_encode([]);
}

?>