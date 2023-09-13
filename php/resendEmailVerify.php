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
                    $uname = $users[$m]->user;
                } else {
                    $response = 0;
                }
            }
            
        }
    }
    
    if ($validSession) {
        
        $time = strval(round(microtime(true)));
        
        $key = hash("sha256", $userObj->user . $_POST["session"] . $time, false);
        
        $nameMail = "mailKeys";
        $file_nameMail = $nameMail . '.json';
       
        $locationMail = $file_nameMail;
        
        if (file_exists($locationMail)) {
            
            $jsonDataMail = file_get_contents($locationMail);
            
            $jsonArrayMail = json_decode($jsonDataMail);
            
            $hashedKey = hash("sha256", $key, false);
            
            $newSet = Array (
                "key" => $hashedKey,
                "userId" => $uID,
                "time" => $time,
            );
            
            array_push($jsonArrayMail, $newSet);
            
        } else {
            
            $hashedKey = hash("sha256", $key, false);
            
            $jsonArrayMail = Array (
                "0" => Array (
                    "key" => $hashedKey,
                    "userId" => $uID,
                    "time" => $time,
                )
            );
            
        }
        
        $to_email = $userObj->email;
        $secondCount = $time % 6000000;
        $dateString = date("m/d/Y");
        $subject = "Confirm Geo Cam Email ($dateString - $secondCount)";
        // $body = "Hi $uname,\n\nPlease confirm your email for your Geo Cam account by clicking this link: https://geocam.app/php/confirmMail.php?key=" . $key . "\n\nThanks,\nGeo Cam Team";
        //$body = "Hi " . $userObj->user . ",\n\nPlease confirm your email for your Geo Cam account by clicking this link: https://evanmasiello.com/geogram/php/confirmMail.php?key=" . $key . "\n\nThanks,\nGeo Cam Team";
        // $headers = "From: no-reply@geocam.app";
        
        $body = "<h2 style='text-align: center;'>Confirm Geo Cam Email:</h2><h3 style='text-align: center;'>Username: $uname</h3><h3 style='text-align: center;'><a href='https://geocam.app/php/confirmMail.php?key=" . $key . "'>Confirm Email</a></h3>";
        $headers = "From: no-reply@geocam.app\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
        // encode array to json
        $jsonMail = json_encode($jsonArrayMail);
        
        if (file_put_contents($locationMail, $jsonMail) and mail($to_email, $subject, $body, $headers)) {
            $response = 1;
        } else {
            $response = "mailSend";
        }
    
    }

} else {
    $response = ""; //bad post";
}

echo $response;

?>