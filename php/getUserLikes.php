<?php

if (file_exists("users.json") and isset($_POST["session"]) and file_exists("sessions.json")) {
    
    $users = json_decode(file_get_contents("users.json"));
    
    $session = $_POST["session"];
    
    //echo "session is " . $session;
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
    
    $validSession = false;
    
    for ($r=0; $r < count($sessions); $r++) {
        //echo "session hash: $sessionHash and key" . $sessions[$r]->key;
        if ($sessionHash == $sessions[$r]->key) {
            $validSession = true;
            $userIdNum = $sessions[$r]->userId;
        }
    }
    
    if ($validSession) {
        
        for ($i = 0; $i < count($users); $i++) {
            if ($users[$i]->id == $userIdNum) echo $users[$i]->likes;
        }
    
    } else {
        echo "[]";
    }
    
} else {
    echo "[]";
}

?>