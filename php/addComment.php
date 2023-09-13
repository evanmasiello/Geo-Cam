<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

$response = 0;

if(isset($_POST["id"]) and isset($_POST["comment"]) and isset($_POST["session"]) and file_exists("sessions.json")) { 
    
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
            
        $containsBlocked = false;
        
        $blockedWords = json_decode(file_get_contents("blockedWords.json"));
            
        $testUname = str_replace(' ', '', strtolower($_POST["comment"]));
        
        for ($v=0; $v < count($blockedWords); $v++) {
            if (str_contains($testUname,$blockedWords[$v])) $containsBlocked = true;
            #error_log("blockedWord = " . $blockedWords[$v]);
        }
        
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
        
        if (strlen(trim($_POST["comment"])) <= 0) {
            $validSession = false;
            $response = "noComment";
        }
        
        if (strlen($_POST["uname"]) > 150) {
            $response = "tooLong";
            $validSession = false;
        }
        
        if ($containsBlocked) {
            $response = "containsBlocked";
            $validSession = false;
        }
        
        if ($validSession) {
        
            $id = $_POST["id"];
            $comment = htmlspecialchars(trim($_POST["comment"]));
            $filename = "../posts/posts.json";
            $posts = json_decode(file_get_contents($filename));
        
            $posts = array_values($posts);
            
            $time = strval(round(microtime(true)));
            
            $commentId = -1;
        
            if ($posts[$id]->comments != null) {
                $commentsArr = json_decode($posts[$id]->comments);
                
                $newComment = Array (
                        "id" => $commentsArr[count($commentsArr) - 1]->id + 1,
                        "time" => $time,
                        "user" => $uID,
                        "comment" => $comment,
                    );
                    
                $commentId = $commentsArr[count($commentsArr) - 1]->id + 1;
                
                array_push($commentsArr,$newComment);
            } else {
                $commentsArr = [];
                
                $newComment = Array (
                        "id" => 0,
                        "time" => $time,
                        "user" => $uID,
                        "comment" => $comment,
                    );
                    
                $commentId = $commentsArr[count($commentsArr) - 1]->id + 1;
                
                array_push($commentsArr,$newComment);
            }
            
            for ($i=0; $i < count($posts); $i++) {
                if ($posts[$i]->id == $id) {
                    $posts[$i]->comments = json_encode($commentsArr);
                }
            }
            
            for ($l=0; $l < count($users); $l++) {
                if ($users[$l]->id == $uID) {
                    if ($users[$l]->commentCount != null) {
                        $users[$l]->commentCount++;
                    } else {
                        $users[$l]->commentCount = 1;
                    }
                }
            }
        
            $json = json_encode($posts);
            
            if (file_put_contents($filename, $json) && file_put_contents("users.json", json_encode($users))) {
                    $response = json_encode($newComment);  
                    sendMessage("Oh yeah! $uName just commented \"$comment\" on a post!");   
                    logAction($uName . " (user id " . $uID . ") commented \"$comment\" on post #" . $id);
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