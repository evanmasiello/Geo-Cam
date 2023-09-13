<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

$response = 0;

if(isset($_POST["id"]) and isset($_POST["reason"]) and isset($_POST["session"]) and file_exists("sessions.json")) { 
    
    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
    
    $validSession = false;
    
    $statusFile = "status.txt";
    
    $dataStatus = file_get_contents($statusFile);
        
    while ($dataStatus == "OPEN") {
        sleep(1);
        $dataStatus = file_get_contents($statusFile);
    }
        
    file_put_contents($statusFile, "OPEN");
    
    try {
    
    $weOpened = true;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
    
            for ($m=0; $m < count($users); $m++) {
                if ($users[$m]->id == $uID) {
                    if ($users[$m]->emailIsVerified) {
                        $validSession = true;   
                        $uName = $users[$m]->user;
                    } else {
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
        $reason = $_POST["reason"];
        $filename = "../posts/posts.json";
        $posts = json_decode(file_get_contents($filename));
    
        $posts = array_values($posts);
        
        $reasonName = "not" . $reason;
        
        $postSave;
        
        for ($v=0; $v < count($posts); $v++) {
            if ($posts[$v]->id == $id) {
                $postSave = $v;
            }
        }
        
        $postWasChecked = $posts[$postSave]->$reasonName;
        
        if($reason == "consent") $postWasChecked = false;
        
        if ($postWasChecked != true) {
    
        $posts[$postSave]->hidden = true;
        $userId = $posts[$postSave]->user;
    
        $json = json_encode($posts);
        file_put_contents($filename, $json); //generate json file
        
        $locationFlag = "flags.json";
            
            if (file_exists($locationFlag)) {
                
                $jsonDataFlag = file_get_contents($locationFlag);
                
                $jsonArrayFlag = json_decode($jsonDataFlag);
                
                $flagID = $jsonArrayFlag[count($jsonArrayFlag)-1]->id + 1;
                
                $userNotExists = true;
                
                $newFlag = Array (
                    "id" => $flagID,
                    "postID" => $postSave,
                    "reason" => $reason,
                    "postUser" => $userId,
                    "user" => $uID,
                );
                    
                array_push($jsonArrayFlag, $newFlag);
                
            } else {
                $flagID = 0;
                
                if (isset($_POST["email"])) {
                    $jsonArrayFlag = Array (
                        "0" => Array (
                            "id" => $flagID,
                            "postID" => $postSave,
                            "reason" => $reason,
                            "postUser" => $userId,
                            "user" => $uID,
                        )
                    );
                } else {
                    $jsonArrayFlag = Array (
                        "0" => Array (
                            "id" => $flagID,
                            "postID" => $postSave,
                            "reason" => $reason,
                            "postUser" => $userId,
                            "user" => $uID,
                        )
                    );
                }
            }
        
            // encode array to json
            $jsonFlag = json_encode($jsonArrayFlag);
            
            if (!$emailProblem) {
                file_put_contents($locationFlag, $jsonFlag);
                $response = 1;   
                sendMessage("Alert! $uName just flagged a post! click this link to review it: https://geocam.app/admin/review <@&984817096171061289>");   
                logAction($uName . " (user id " . $uID . ") flagged post #" . $id . " for " . $reason);
            } else {
                $response = 0;
            }
            
            } else {
                $response = "alreadyChecked";
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