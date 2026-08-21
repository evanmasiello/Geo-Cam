<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

if(isset($_POST["id"]) and isset($_POST["session"]) and file_exists("sessions.json")){
    
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
    
    $response = 0;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
    
            for ($m=0; $m < count($users); $m++) {
                if ($users[$m]->id == $uID) {
                    
                    //error_log("user id is: " . $users[$m]->id);
                    
                    //error_log("email is verified: " . $users[$m]->emailIsVerified);
                    
                    if ($users[$m]->emailIsVerified) {
                        $validSession = true;
                        $userIDStore = $m;
                        
                        $userLikes = json_decode($users[$m]->likes);
                        
                        for ($i=0; $i < count($userLikes); $i++) {
                            if ($userLikes[$i] == $_POST["id"]) $validSession = false;
                        }
                        
                    } else {
                        
                        //error_log("email is not confirmed");
                        
                        $response = "emailNotConfirmed";
                    }
                } else {
                    //$response = 0;
                }
            }
            
        }
    }
    
    if ($validSession) {
    
        $id = $_POST["id"];
        $filename = "../posts/posts.json";
        $posts = json_decode(file_get_contents($filename));
    
        $posts = array_values($posts);
    
        $posts[$id]->likes = $posts[$id]->likes + 1;
        
        array_push($userLikes, $posts[$id]->id);
        
        $users[$userIDStore]->likes = json_encode($userLikes);
    
        $json = json_encode($posts);
        
        if (file_put_contents($filename, $json) and file_put_contents("users.json", json_encode($users))) {
            $response = 1;
            sendMessage("Nice! " . $users[$userIDStore]->user . " just liked a post! It was the " . ordinal(count(json_decode($users[$userIDStore]->likes))) . " post that they've liked");
            logAction($users[$userIDStore]->user . " (user id " . $users[$userIDStore]->id . ") liked post #" . $posts[$id]->id . " It was the " . ordinal(count(json_decode($users[$userIDStore]->likes))) . " post that they've liked");
        } else {
            $response = 0;
        }
    
    }
    
        
    } catch (Throwable $e) {
       //echo 'And my error is: ' . $e->getMessage();
       file_put_contents($statusFile, "CLOSED");
    }
    
    file_put_contents($statusFile, "CLOSED");
    
} else {
    $response = 0;
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

function ordinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
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