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
        
    #error_log("status was closed");
    
    $weOpened = true;
    
    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
    
    $validSession = false;
    
    $response = 0;
    
    $FollowId = $_POST["name"];
    
    $shouldPush = true;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            error_log("in 1");
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
            
            $shouldStillLoop = true;
            
            for ($m=0; $m < count($users) && $shouldStillLoop; $m++) {
                if (strtolower($users[$m]->user) == strtolower($FollowId)) {
                    error_log("in 2");
                    error_log("follow ID $FollowId");
                    $FollowId = $users[$m]->id;
                    error_log("follow ID $FollowId");
                    $userNameExists = true;
                    $shouldStillLoop = false;
                }
            }
            
            if ($userNameExists) {
                error_log("in 3");
                for ($m=0; $m < count($users); $m++) {
                    if ($users[$m]->id == $uID) {
                        error_log("in 3");
                        //#error_log("user id is: " . $users[$m]->id);
                        
                        //#error_log("email is verified: " . $users[$m]->emailIsVerified);
                        
                        if ($users[$m]->emailIsVerified) {
                            error_log("in 4");
                            $validSession = true;
                            $userIDStore = $m;
                            
                            if ($users[$m]->id == $FollowId) $shouldPush = false;
                            
                            #error_log("in email is verified");
                            
                            if ($users[$m]->followed !== null) {
                                $userFollowed = json_decode($users[$m]->followed);
                                #error_log("user followed is " . $userFollowed);
                            } else {
                                $userFollowed = array();
                            }
                            
                            for ($i=0; $i < count($userFollowed); $i++) {
                                if (intval($userFollowed[$i]) == intval($FollowId)) {
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
        error_log("in 5");
        
        if ($shouldPush) {
            array_push($userFollowed, intval($FollowId));
        }
        
        $users[$userIDStore]->followed = json_encode($userFollowed);
        
        #error_log("users followed " . $users[$userIDStore]->followed);
        
        #if (!strpos($users[$userIDStore]->followed,"[")) $users[$userIDStore]->followed = "[" . $FollowId;
        #if (!strpos($users[$userIDStore]->followed,"]")) $users[$userIDStore]->followed = $users[$userIDStore]->followed . "]";
        
        if (file_put_contents("users.json", json_encode($users))) {
            
            error_log("in 6");
            
            for ($b=0; $b < count($users); $b++) {
                for ($c=0; $c < count($userFollowed); $c++) {
                    if ($users[$b]->id == $userFollowed[$c]) {
                        $userFollowed[$c] = Array (
                            "id" => $users[$b]->id,
                            "name" => $users[$b]->user,
                        );
                    }
                }
            }
            
            $response = json_encode($userFollowed);
            
            sendMessage("Oh nice! " . $users[$userIDStore]->user . " just followed user #" . $FollowId);
            logAction($users[$userIDStore]->user . " (user id " . $users[$userIDStore]->id . ") followed user #" . $FollowId);
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