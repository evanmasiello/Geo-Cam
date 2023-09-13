<?php

$statusFile = "status.txt";
    
$dataStatus = file_get_contents($statusFile);
    
while ($dataStatus == "OPEN") {
    sleep(2);
}
    
file_put_contents($statusFile, "OPEN");

$filename = "users.json";

if(isset($_POST["likes"]) and file_exists($filename) and isset($_POST["session"]) and file_exists("sessions.json")){
    
    $likes = $_POST["likes"];
    $users = json_decode(file_get_contents($filename));
    
    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessions = array_values($sessions);
    
    $sessionHash = hash("sha256", $session, false);
    
    $validSession = false;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $validSession = true;
            $userIDNum = $sessions[$r]->userId;
        }
    }
    
    error_log( "session is valid: $validSession");
    
    error_log( "count users: " . count($users));
    
    if ($validSession) {
        for ($m=0; $m < count($users); $m++) {
            if ($users[$m]->id == $userIDNum and $users[$m]->emailIsVerified) {
                error_log("in if");
                
                //echo "likes: ";
                error_log("m is $m");
                error_log("likes is $likes");
                error_log("user likes is " . $users[$m]->likes);
                
                $users[$m]->likes = $likes;
            }
        }
    }

    $json = json_encode($users);
    file_put_contents($filename, $json); //generate json file
  
    echo $user;
    
}

file_put_contents($statusFile, "CLOSED");

?>