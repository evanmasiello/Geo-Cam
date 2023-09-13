<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

if(isset($_POST["session"]) and file_exists("sessions.json") and isset($_POST["vis"]) and isset($_POST["postId"])) {
    
        $postId = $_POST["postId"];
        
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
        
        //$newUname = htmlspecialchars($_POST["newName"]);
        
        $validSession = false;
        
        //do shit to make sure that files are stored with good error messages
        //verify mail is unique and proper
        //save new user info and resent verification state
        
        $users = json_decode(file_get_contents("users.json"));
        
        //$passHash = hash("sha256", $_POST["pass"], false);
        
        for ($r=0; $r < count($sessions); $r++) {
            if ($sessionHash == $sessions[$r]->key) {
                $uID = $sessions[$r]->userId;
        
                for ($m=0; $m < count($users); $m++) {
                    if ($users[$m]->id == $uID) {
                        $validSession = true;
                        $userIdSave = $m;
                        //error_log("user ID save index: " . $userIdSave);
                        //error_log("user ID: " . $users[$m]->id);
                    }
                }
                
            }
        }
        
        if ($validSession) {
            
            //$nameUsed = false;
            
            //$users = json_decode(file_get_contents("users.json"));
                        
                        $posts = json_decode(file_get_contents("../posts/posts.json"));
                        
                        $changeValid = false;
                        
                        $savePostId;
                        
                        for ($x=0; $x < count($posts); $x++) {
                            if ($posts[$x]->id == $postId) { 
                                if ($_POST["vis"] == "following") {
                                    $posts[$x]->visibility = "following";
                                    $visVar = "following";
                                } else {
                                    $posts[$x]->visibility = "all";
                                    $visVar = "all";
                                }
                                $changeValid = true;
                                $savePostId = $posts[$x]->id;
                            }
                        }
                        
                        if ($changeValid && file_put_contents("../posts/posts.json", json_encode(array_values($posts))) and file_put_contents("users.json", json_encode($users))) {
                            $response = 1;
                            sendMessage("Whoops! " . $users[$userIdSave]->user . " just changed the visibility of a post!");
                            logAction($users[$userIdSave]->user . " (user id " . $users[$userIdSave]->id . ") just made post #" . $savePostId . " visibile to: " . $visVar);
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