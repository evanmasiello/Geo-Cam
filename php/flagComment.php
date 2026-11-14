<?php
header("Access-Control-Allow-Origin: https://geocam.app");
header("Access-Control-Allow-Credentials: true");


function shutdown()
{
    global $weOpened, $statusFile;
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
        
    $lockWaitStart = time();
    while ($dataStatus == "OPEN") {
        // The holder died without releasing: normal hold time is
        // milliseconds, so treat a lock this old as stale and proceed.
        if (time() - $lockWaitStart >= 10) break;
        sleep(1);
        $dataStatus = file_get_contents($statusFile);
    }
        
    file_put_contents($statusFile, "OPEN");
    
    try {
    
    $weOpened = true;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key && (time() - intval($sessions[$r]->time)) < 7776000) {
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
        $reason = $_POST["reason"];
        $allowedReasons = ["vulgar", "hate", "bullying", "spam", "other"];
        $filename = "../posts/posts.json";
        $posts = json_decode(file_get_contents($filename));
        $posts = array_values($posts);
        $postSave = -1;
        $commentSave = -1;
        $commentArr = [];

        if (in_array($reason, $allowedReasons, true)) {
            for ($v = 0; $v < count($posts); $v++) {
                if ($posts[$v]->id != $id) continue;

                $postSave = $v;

                if ($posts[$v]->comments != null) {
                    $commentArr = json_decode($posts[$v]->comments);

                    for ($m = 0; $m < count($commentArr); $m++) {
                        if ($commentArr[$m]->id == $commentId) {
                            $commentSave = $m;
                        }
                    }
                }
            }
        }

        if ($postSave < 0 || $commentSave < 0) {
            $response = 0;
        } else {
            $locationFlag = "commentFlags.json";
            $jsonArrayFlag = [];

            if (file_exists($locationFlag)) {
                $jsonArrayFlag = json_decode(file_get_contents($locationFlag));

                if (!is_array($jsonArrayFlag)) $jsonArrayFlag = [];
            }

            $alreadyFlagged = false;
            $flagUsers = [];

            for ($v = 0; $v < count($jsonArrayFlag); $v++) {
                if ($jsonArrayFlag[$v]->postID == $posts[$postSave]->id && $jsonArrayFlag[$v]->commentID == $commentId) {
                    $flagUsers[strval($jsonArrayFlag[$v]->user)] = true;
                    if ($jsonArrayFlag[$v]->user == $uID) $alreadyFlagged = true;
                }
            }

            if ($alreadyFlagged) {
                $response = "alreadyChecked";
            } else {
                $flagID = 0;

                if (count($jsonArrayFlag) > 0) {
                    $flagID = $jsonArrayFlag[count($jsonArrayFlag) - 1]->id + 1;
                }

                $jsonArrayFlag[] = Array (
                    "id" => $flagID,
                    "postID" => $posts[$postSave]->id,
                    "postIndex" => $postSave,
                    "commentID" => $commentArr[$commentSave]->id,
                    "reason" => $reason,
                    "commentUser" => $commentArr[$commentSave]->user,
                    "comment" => $commentArr[$commentSave]->comment,
                    "user" => $uID,
                    "time" => time(),
                );

                $flagUsers[strval($uID)] = true;

                if (count($flagUsers) >= 3) {
                    $commentArr[$commentSave]->hidden = true;
                    $posts[$postSave]->comments = json_encode($commentArr);
                    file_put_contents($filename, json_encode($posts));
                }

                file_put_contents($locationFlag, json_encode($jsonArrayFlag));
                $response = 1;
                sendMessage("Alert! $uName just flagged a comment! click this link to review it: https://geocam.app/admin/reviewComment <@&984817096171061289>");
                logAction($uName . " (user id " . $uID . ") flagged comment #" . $commentId . " on post #" . $id . " for " . $reason);
            }
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
if (!file_exists("discord.txt")) return;

$webhookurl = trim(file_get_contents("discord.txt"));

if ($webhookurl === "") return;
$timestamp = date("c", strtotime("now"));
$json_data = json_encode([
    "content" => $msg,
    "tts" => false,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
$ch = curl_init( $webhookurl );
if ($ch === false) return;
curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
curl_setopt( $ch, CURLOPT_POST, 1);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt( $ch, CURLOPT_HEADER, 0);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
// A dead webhook must not pin a PHP worker or the write lock.
curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt( $ch, CURLOPT_TIMEOUT, 5);
curl_setopt( $ch, CURLOPT_NOSIGNAL, 1);
$response = curl_exec( $ch );
curl_close( $ch );
}

function logAction($msgLog) {
    $timeLog = strval(round(microtime(true)));
    
    $dateLog = gmdate("Y-m-d", $timeLog);
    
    $logFile = "../logs/log" . $dateLog . ".txt";
    
    $statusFileLog = "../logs/logStatus.txt";
    
    $dataStatusLog = file_get_contents($statusFileLog);
    
    $lockWaitStartLog = time();
    while ($dataStatusLog == "OPEN") {
        // A stale log lock must not stall a write while status.txt is held.
        if (time() - $lockWaitStartLog >= 10) break;
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