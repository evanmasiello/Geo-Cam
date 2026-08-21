<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

#error_log("in follow user");

if(isset($_POST["name"]) and isset($_POST["session"]) and file_exists("sessions.json")){
    
    $statusFile = "status.txt";
    
    $dataStatus = file_get_contents($statusFile);
        
    while ($dataStatus == "OPEN") {
        sleep(1);
        $dataStatus = file_get_contents($statusFile);
    }
        
    file_put_contents($statusFile, "OPEN");
    
    try {
        
    $FollowId = $_POST["name"];
        
    #error_log("status was closed");
    
    $weOpened = true;
    
    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
    
    $validSession = false;
    
    $response = 0;
    
    $userNameExists = false;
    
    $shouldPush = true;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
            
            $stillNeedToLoop = true;
            
            for ($m=0; $m < count($users) && $stillNeedToLoop; $m++) {
                if (strtolower($users[$m]->user) == strtolower($FollowId)) {
                    $FollowId = $users[$m]->id;
                    $userNameExists = true;
                    $stillNeedToLoop = false;
                }
            }
            
            if ($userNameExists) {
                for ($m=0; $m < count($users); $m++) {
                    if ($users[$m]->id == $uID) {
                        
                        //#error_log("user id is: " . $users[$m]->id);
                        
                        //#error_log("email is verified: " . $users[$m]->emailIsVerified);
                        
                        if ($users[$m]->emailIsVerified) {
                            $validSession = true;
                            $userIDStore = $m;
                            
                            if ($users[$m]->id == $FollowId) $shouldPush = false;
                            
                            #error_log("in email is verified");
                            
                            if ($users[$m]->blocked !== null) {
                                $userBlocked = json_decode($users[$m]->blocked);
                                #error_log("user blocked is " . $userBlocked);
                            } else {
                                $userBlocked = array();
                            }
                            
                            for ($i=0; $i < count($userBlocked); $i++) {
                                if (intval($userBlocked[$i]) == intval($FollowId)) {
                                    $shouldPush = false;
                                }
                            }
                            
                        } else {
                            
                            //#error_log("email is not confirmed");
                            
                            $response = "emailNotConfirmed";
                        }
                    } else {
                        //$response = 0;
                    }
                }
            } else {
                $response = "userNotExists";
            }
        }
    }
    
    if ($validSession) {
        
        array_push($userBlocked, intval($FollowId));
        
        $users[$userIDStore]->blocked = json_encode($userBlocked);
        
        #error_log("users blocked " . $users[$userIDStore]->blocked);
        
        #if (!strpos($users[$userIDStore]->blocked,"[")) $users[$userIDStore]->blocked = "[" . $FollowId;
        #if (!strpos($users[$userIDStore]->blocked,"]")) $users[$userIDStore]->blocked = $users[$userIDStore]->blocked . "]";
        
        $blockedUsersHold = $userBlocked;
        
        if (file_put_contents("users.json", json_encode($users))) {
            for ($b=0; $b < count($users); $b++) {
                for ($c=0; $c < count($userBlocked); $c++) {
                    if ($users[$b]->id == $userBlocked[$c]) {
                        $userBlocked[$c] = Array (
                            "id" => $users[$b]->id,
                            "name" => $users[$b]->user,
                        );
                    }
                }
            }
            
            $response = json_encode($userBlocked);
            
            sendMessage("Oh boy! " . $users[$userIDStore]->user . " just blocked user #" . $FollowId);
            logAction($users[$userIDStore]->user . " (user id " . $users[$userIDStore]->id . ") blocked user #" . $FollowId);
        } else {
            $response = 0;
        }
    
    }
    
        
    } catch (Throwable $e) {
       //echo 'And my error is: ' . $e->getMessage();
       #error_log($e->getMessage());
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