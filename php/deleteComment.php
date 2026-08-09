<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

$response = 0;

if(isset($_POST["id"]) and isset($_POST["commentId"]) and isset($_POST["session"]) and file_exists("sessions.json")) { 
    
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
        $commentId = $_POST["commentId"];
        $filename = "../posts/posts.json";
        $posts = json_decode(file_get_contents($filename));
    
        $posts = array_values($posts);
        
        $postIndex = -1;
        $low = 0;
        $high = count($posts) - 1;
        $postFound = false;
        while ($low <= $high && !$postFound) {
            $mid = floor(($low + $high) / 2);
            if ($posts[$mid]->id == $id) {
                $postFound = true;
                $postIndex = $mid;
            } else if ($id < $posts[$mid]->id) {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        $commentFound = false;
        $commentIndex = -1;
        if ($postFound && $posts[$postIndex]->comments != null) {
            $comments = json_decode($posts[$postIndex]->comments);
            $low = 0;
            $high = count($comments) - 1;
            while ($low <= $high && !$commentFound) {
                $mid = floor(($low + $high) / 2);
                if ($comments[$mid]->id == $commentId) {
                    $commentFound = true;
                    $commentIndex = $mid;
                } else if ($commentId < $comments[$mid]->id) {
                    $high = $mid - 1;
                } else {
                    $low = $mid + 1;
                }
            }
        }

        if ($commentFound) {
            if ($comments[$commentIndex]->user != $uID) {
                $response = "notYourComment";
            } else {
                $comments[$commentIndex]->hidden = true;
                $commentAuthor = $comments[$commentIndex]->user;
                $posts[$postIndex]->comments = json_encode($comments);
            }
        }

        if ($commentFound && $response != "notYourComment") {
            for ($l=0; $l < count($users); $l++) {
                if ($users[$l]->id == $commentAuthor) {
                    if ($users[$l]->commentCount != null) {
                        $users[$l]->commentCount = max(0, $users[$l]->commentCount - 1);
                    } else {
                        $users[$l]->commentCount = 0;
                    }
                }
            }

            $json = json_encode($posts);

            if (file_put_contents($filename, $json) && file_put_contents("users.json", json_encode($users))) {
                $response = 1;
                sendMessage("Oh nah! $uName just deleted a comment!");
                logAction($uName . " (user id " . $uID . ") deleted comment #$commentId on post #" . $id);
            } else {
                $response = 0;
            }
        } else if ($response != "notYourComment") {
            $response = 0;
        }
            
        } else {
            $response = 0;
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