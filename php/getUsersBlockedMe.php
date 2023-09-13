<?php

$response = 0;

if(isset($_POST["session"]) and file_exists("sessions.json")) { 

    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
        
    $validSession = false;
    
    $response = 0;
    
    $userFollowing = [];
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
    
            for ($m=0; $m < count($users); $m++) {
                if ($users[$m]->id == $uID) {
                    $validSession = true;
                    $userObj = $users[$m];
                    $userIdSave = $m;
                } else {
                    $response = 0;
                }
            }
            
        }
    }
    
    if ($validSession) {
        
        $responseArr = [];
        
        $users = json_decode(file_get_contents("users.json"));
        
        //$uID
        
        for ($i=0; $i<count($users); $i++) {
            
            $userBlocked = [];
            
            if ($users[$i]->blocked != null) $userBlocked = json_decode($users[$i]->blocked);
            
            if (in_array($uID,$userBlocked)) array_push($responseArr, $users[$i]->id);
        }
        
        $response = json_encode($responseArr);
        
    }

}

echo $response;

?>