<?php

if(isset($_POST["session"]) and file_exists("sessions.json")) { 
    
    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
    
    $validSession = false;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
    
            for ($m=0; $m < count($users); $m++) {
                if ($users[$m]->id == $uID) {
                    
                    $validSession = true;
                    
                    $userObj = $users[$m];
                    
                    if ($users[$m]->blocked != null) {
                        $userBlocked = json_decode($users[$m]->blocked);   
                    } else {
                        $userBlocked = [];
                    }
                    
                    if ($users[$m]->followed != null) {
                        $userFollowed = json_decode($users[$m]->followed);
                    } else {
                        $userFollowed = [];
                    }
                    
                    for ($b=0; $b < count($users); $b++) {
                        for ($c=0; $c < count($userBlocked); $c++) {
                            if ($users[$b]->id == $userBlocked[$c]) {
                                $userBlocked[$c] = Array (
                                    "id" => $users[$b]->id,
                                    "name" => $users[$b]->user,
                                );
                            }
                        }
                        for ($c=0; $c < count($userFollowed); $c++) {
                            if ($users[$b]->id == $userFollowed[$c]) {
                                $userFollowed[$c] = Array (
                                    "id" => $users[$b]->id,
                                    "name" => $users[$b]->user,
                                );
                            }
                        }
                    }
                    
                    $userObj->blocked = json_encode($userBlocked);
                    $userObj->followed = json_encode($userFollowed);
                    
                }
            }
            
        }
    }
    
    if ($validSession) {
    
        //$userObj
        
        echo json_encode($userObj);
    
} else {
    echo "invalidSession";
}

} else {
    echo "invalidSession";
}

?>