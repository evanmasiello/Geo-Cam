<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

if(isset($_POST["session"]) and file_exists("sessions.json") and isset($_POST["pass"]) and isset($_POST["newName"]) and strlen($_POST["newName"])) { 
    
    $blockedWords = json_decode(file_get_contents("blockedWords.json"));
    
    $containsBlocked = false;
    
    $testUname = str_replace(' ', '', strtolower($_POST["newName"]));
    
    if (strtolower($_POST["newName"]) == "undefined") $charactersAreOk = false;
    
    error_log("userName = " . $testUname);
    
    for ($v=0; $v < count($blockedWords); $v++) {
        if (str_contains($testUname,$blockedWords[$v])) $containsBlocked = true;
        error_log("blockedWord = " . $blockedWords[$v]);
    }
    
    if (preg_match("#^[a-zA-Z0-9äöüÄÖÜ \.\]\_\-\=\+\,\(\)\!\@\#\$\%\&\*\"\'\[]+$#", $_POST["newName"])) {
       $charactersAreOk = true; 
    } else {
        $charactersAreOk = false; 
    }
    
    if (strlen($_POST["newName"]) > 25) {
        $response = "nameTooLong";
    } else if ($containsBlocked) {
        $response = "containsBlocked";
    } else if (!$charactersAreOk) {
        $response = "containsBadChars";
    } else {
        
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
        
        $newUname = htmlspecialchars($_POST["newName"]);
        
        $newName = $_POST["newName"];
        
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
                        $oldName = $users[$m]->user;
                        $userIdSave = $m;
                    } else {
                        $response = 0;
                    }
                }
                
            }
        }
        
        if ($validSession) {
            
            $nameUsed = false;
            
            for ($i=0; $i < count($users); $i++) {
                
                if (strtolower($users[$i]->user) == strtolower($newUname)) {
                    $nameUsed = true;
                    if ($users[$i]->id == $userIdSave) {
                        $response = "badNameUsed";
                    } else {
                        $response = "badName";
                    }
                }
    
            }
            
            $users = json_decode(file_get_contents("users.json"));
            
            if (!$nameUsed) {
            
                for ($i=0; $i < count($users); $i++) {
                    
                    //error_log("user id: " . $users[$i]->id);
                    //error_log("uID: " . $uID);
                    //error_log("pass: " . $users[$i]->pass);
                    //error_log("old pass: " . $oldPass);
                    
                    if ($users[$i]->id == $uID) {
                        
                        if ($users[$i]->user != $newUname) {
                            $users[$i]->user = $newUname;
                            
                            if (file_put_contents("users.json", json_encode($users))) {
                                $response = 1;
                                sendMessage("Look at that! " . $oldName . " changed their username to " . $newName);
                                logAction($oldName . " (user id " . $uID . ") changed their name to " . $newName);
                            }
                        } else {
                            $response = "badNameUsed";
                        }
                        
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
    }

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