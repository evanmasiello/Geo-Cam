<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

if(isset($_POST["session"]) and file_exists("sessions.json") and isset($_POST["pass"]) and isset($_POST["newMail"])) { 
    
    $statusFile = "status.txt";
    
    $dataStatus = file_get_contents($statusFile);
    
    while ($dataStatus == "OPEN") {
        sleep(1);
        $dataStatus = file_get_contents($statusFile);
    }
        
    file_put_contents($statusFile, "OPEN");
    
    try {
    
    $weOpened = true;
    
    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
    
    $email = $_POST["newMail"];
    
    $validSession = false;
    
    //do shit to make sure that files are stored with good error messages
    //verify mail is unique and proper
    //save new user info and resent verification state
    
    $passHash = hash("sha256", $_POST["pass"], false);
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
    
            for ($m=0; $m < count($users); $m++) {
                if ($users[$m]->id == $uID and $passHash == $users[$m]->pass) {
                    $validSession = true;
                    $userObj = $users[$m];
                    $userIdSave = $m;
                    $uname = $users[$m]->user;
                } else {
                    $response = 0;
                }
            }
            
        }
    }
    
    $emailNotUsed = true;
    
    for ($x=0; $x < count($users); $x++) {
        //if ($x != $userIdSave) {
                
            $emailSub1 = str_replace(".", "", strtok(strtolower($users[$x]->email), '@'));
            $emailSub2 = str_replace(".", "", strtok(strtolower($email), '@'));
                
            $emailEnd1 = substr(strtolower($users[$x]->email), strpos(strtolower($users[$x]->email), "@"));
            $emailEnd2 = substr(strtolower($email), strpos(strtolower($email), "@"));
                
            $emailFilter1 = $emailSub1 . $emailEnd1;
            $emailFilter2 = $emailSub2 . $emailEnd2;
                
            if (strtolower($users[$x]->email) == strtolower($email) or $emailFilter1 == $emailFilter2) {
                if ($x == $userIdSave) {
                    $emailNotUsed = false;
                    //echo "badEmail";
                    $response = "badEmailUsed";
                } else {
                    $emailNotUsed = false;
                    //echo "badEmail";
                    $response = "mailUsed";
                }
            }
                
            if (str_contains($emailFilter2, "@") and str_contains($emailFilter2, ".")) {
                $emailValid = true;
            } else {
                $emailValid = false;
            }
        
        //}
    }
    
    if ($validSession) {
        
        if ($emailNotUsed) {
            
            if ($emailValid) {
        
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
                
                $to_email = $_POST["newMail"];
                $secondCount = $time % 6000000;
                $dateString = date("m/d/Y");
                $subject = "Confirm Geo Cam Email ($dateString - $secondCount)";
                // $body = "Hi $uname,\n\nPlease confirm your email for your Geo Cam account by clicking this link: https://geocam.app/php/confirmMail.php?key=" . $key . "\n\nThanks,\nGeo Cam Team";
                $body = "<h2 style='text-align: center;'>Confirm Geo Cam Email:</h2><h3 style='text-align: center;'>Username: $uname</h3><h3 style='text-align: center;'><a href='https://geocam.app/php/confirmMail.php?key=" . $key . "'>Confirm Email</a></h3>";
                $headers = "From: no-reply@geocam.app\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
                // encode array to json
                $jsonMail = json_encode($jsonArrayMail);
                
                $users[$userIdSave]->email = $_POST["newMail"];
                $users[$userIdSave]->emailIsVerified = false;
                
                if (file_put_contents($locationMail, $jsonMail) and mail($to_email, $subject, $body, $headers) and file_put_contents("users.json", json_encode($users))) {
                    $response = 1;
                    sendMessage("Look at that! " . $users[$userIdSave]->user . " changed their email");
                    logAction($users[$userIdSave]->user . " (user id " . $uID . ") changed their email");
                } else {
                    $response = "mailSend";
                }
            
            } else {
                $response = "badMail";
            }
        
        }
    
    } else {
        $response = "badSession";
    }
    
    } catch (Throwable $e) {
       //echo 'And my error is: ' . $e->getMessage();
       file_put_contents($statusFile, "CLOSED");
    }
    
    file_put_contents($statusFile, "CLOSED");

} else {
    $response = ""; //bad post";
}

echo $response;

function sendMessage($msg) {
$webhookurl = file_get_contents("discord.txt");
$timestamp = date("c", strtotime("now"));
$json_data = json_encode([
    "content" => $msg,
    "tts" => false,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
$ch = curl_init( $webhookurl );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
curl_setopt( $ch, CURLOPT_POST, 1);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt( $ch, CURLOPT_HEADER, 0);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec( $ch );
curl_close( $ch );
}

function logAction($msgLog) {
    $timeLog = strval(round(microtime(true)));
    
    $dateLog = gmdate("Y-m-d", $timeLog);
    
    $logFile = "../logs/log" . $dateLog . ".txt";
    
    $statusFileLog = "../logs/logStatus.txt";
    
    $dataStatusLog = file_get_contents($statusFileLog);
    
    while ($dataStatusLog == "OPEN") {
        sleep(1);
        $dataStatusLog = file_get_contents($statusFileLog);
    }
    
    file_put_contents($statusFileLog, "OPEN");
    
    if (file_exists($logFile)) {
        $logContents = file_get_contents($logFile);
        $logContents = $logContents . "\n" . $timeLog . " " . $msgLog;
    }  else {
        $logContents = $timeLog . " " . $msgLog;
    }
    
    file_put_contents($logFile, $logContents);    
    chmod($logFile, 0640);
    file_put_contents($statusFileLog, "CLOSED");
}

?>