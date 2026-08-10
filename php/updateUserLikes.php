<?php
header("Access-Control-Allow-Origin: https://geocam.app");
header("Access-Control-Allow-Credentials: true");


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
        $decoded = json_decode($_POST["likes"], true);
        if (!is_array($decoded)) {
            $response = 0;
        } else {
            $decoded = array_values(array_unique(array_map('intval', $decoded)));
            for ($m=0; $m < count($users); $m++) {
                if ($users[$m]->id == $userIDNum and $users[$m]->emailIsVerified) {
                    $users[$m]->likes = json_encode($decoded);
                }
            }
            $response = 1;
        }

        if ($response == 1) {
            $json = json_encode($users);
            file_put_contents($filename, $json); //generate json file
        }
    } else {
        $response = 0;
    }

    echo $response;
    
}

file_put_contents($statusFile, "CLOSED");

?>