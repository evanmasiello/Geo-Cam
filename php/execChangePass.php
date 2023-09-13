<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

if(isset($_POST["session"]) and file_exists("sessions.json") and isset($_POST["pass"]) and isset($_POST["passNew"]) and strlen($_POST["passNew"])) { 
    
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
    
    $validSession = false;
    
    $oldPass = hash("sha256", $_POST["pass"], false);
    $newPass = hash("sha256", $_POST["passNew"], false);
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            $validSession = true;
        }
    }
    
    if ($validSession) {
        $users = json_decode(file_get_contents("users.json"));
        
        $response = "badPass";
        
        for ($i=0; $i < count($users); $i++) {
            
            //error_log("user id: " . $users[$i]->id);
            //error_log("uID: " . $uID);
            //error_log("pass: " . $users[$i]->pass);
            //error_log("old pass: " . $oldPass);
            
            error_log("old password is: " . $users[$i]->pass);
            error_log("old password input is: " . $oldPass);
            
            if ($users[$i]->id == $uID and $users[$i]->pass == $oldPass) {
                
                $users[$i]->pass = $newPass;
                
                if (file_put_contents("users.json", json_encode($users))) {
                    $response = 1;
                    sendMessage("Look at that! " . $users[$i]->user . " changed their password");
                    logAction($users[$i]->user . " (user id " . $uID . ") changed their password");
                }
                
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