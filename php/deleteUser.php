<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

$response = "";

if(isset($_POST["session"]) and file_exists("sessions.json") and isset($_POST["pass"])) { 
        
        $statusFile = "status.txt";
        
        $dataStatus = file_get_contents($statusFile);
        
        //error_log("in 1");
        
        while ($dataStatus == "OPEN") {
            sleep(1);
            $dataStatus = file_get_contents($statusFile);
        }
            
        file_put_contents($statusFile, "OPEN");
        
        try {
        
        //error_log("in 2");
        
        $weOpened = true;
        
        $session = $_POST["session"];
        
        $sessions = json_decode(file_get_contents("sessions.json"));
        
        $sessionHash = hash("sha256", $session, false);
        
        // $newUname = htmlspecialchars($_POST["newName"]);
        
        $validSession = false;
        
        //do shit to make sure that files are stored with good error messages
        //verify mail is unique and proper
        //save new user info and resent verification state
        
        $users = json_decode(file_get_contents("users.json"));
        
        $passHash = hash("sha256", $_POST["pass"], false);
        
        for ($r=0; $r < count($sessions); $r++) {
            if ($sessionHash == $sessions[$r]->key) {
                $uID = $sessions[$r]->userId;
        
                for ($m=0; $m < count($users); $m++) {
                    if ($users[$m]->id == $uID and $passHash == $users[$m]->pass) {
                        $validSession = true;
                        $userIdSave = $m;
                        //error_log("in 3");
                        ////error_log("user ID save index: " . $userIdSave);
                        ////error_log("user ID: " . $users[$m]->id);
                    }
                }
                
            }
        }
        
        if ($validSession) {
            
            //error_log("in 4");
            
            $nameUsed = false;
            
            //$users = json_decode(file_get_contents("users.json"));
            
                for ($i=0; $i < count($users); $i++) {
                    
                    ////error_log("user id: " . $users[$i]->id);
                    ////error_log("uID: " . $uID);
                    ////error_log("pass: " . $users[$i]->pass);
                    ////error_log("old pass: " . $oldPass);
                    
                    if ($users[$i]->id == $uID) {
                        
                        //error_log("in 4.5");
                        
                        $indexSave = $i;
                        
                        $userName = $users[$i]->user;
                        
                        $userLikes = json_decode($users[$i]->likes);
                        
                        $posts = json_decode(file_get_contents("../posts/posts.json"));
                        
                        for ($x=0; $x < count($posts); $x++) {
                            
                            if (in_array($posts[$x]->id, $userLikes)) {
                                if ($posts[$x]->likes != 0) {
                                    $posts[$x]->likes--;
                                }
                            }
                            
                            if ($posts[$x]->user == $uID) { 
                                $posts[$x]->user = -1;
                                $posts[$x]->hidden = true;
                                $postId = $posts[$x]->id;
                                chmod("../posts/post" . $postId . ".png", 0640);
                                //error_log("in 5");
                            }
                            if (property_exists($posts[$x], "comments") && $posts[$x]->comments != null) {
                                $commentsArr = json_decode($posts[$x]->comments);
                                for ($c=0; $c < count($commentsArr); $c++) {
                                    if ($commentsArr[$c]->user == $uID) {
                                        $commentsArr[$c]->hidden = true;
                                        $commentsArr[$c]->user = -1;
                                    }
                                }
                                $posts[$x]->comments = json_encode($commentsArr);
                                //error_log("in 6");
                            }
                        }
            
                        $nameSesh = "sessions";
                        $file_nameSesh = $nameSesh . '.json';
                       
                        $locationSesh = $file_nameSesh;
                        
                        if (file_exists($locationSesh)) {
                            
                            //error_log("in 7");
                            
                            $jsonDataSesh = file_get_contents($locationSesh);
                            
                            $jsonArraySesh = json_decode($jsonDataSesh);
                            
                            for ($x=0; $x < count($jsonArraySesh); $x++) {
                                if ($jsonArraySesh[$x]->userId == $uID) unset($jsonArraySesh[$x]);
                                //sendUpdate("Oh no! " + document.getElementById("name").innerHTML + " deleted their account!");
                                // $url = file_get_contents("discord.txt");
                                // $data = json_encode("message Test");
                                // // use key 'http' even if you send the request to https://...
                                // $options = array(
                                //     'http' => array(
                                //         'header'  => "Content-type: application/json\r\n",
                                //         'method'  => 'POST',
                                //         'body' => $data
                                //     )
                                // );
                                // $context  = stream_context_create($options);
                                // $result = file_get_contents($url, false, $context);
                                //if ($result === FALSE) { /* Handle error */ }
                                
                                //var_dump($result);
                            }
                            
                            $jsonArraySesh = array_values($jsonArraySesh);
                        }
                        
                        array_splice($users, $i, 1);
                        
                        ////error_log("user that was deleted " . $users[$i]->user);
                        
                        $usersJson = json_encode(array_values($users));
                        
                        ////error_log("new json Users = " . $usersJson);
                        
                        if (file_put_contents("../posts/posts.json", json_encode(array_values($posts))) and file_put_contents("users.json", $usersJson) and file_put_contents($locationSesh, json_encode($jsonArraySesh))) {
                            $response = 1;
                            //error_log("in 8");
                            sendMessage("Oh no! " . $userName . " deleted their account!");
                            logAction($userName . " (user id " . $uID . ") deleted their account");
                        }
                        
                    }
                    
                }
        
        } else {
            $response = "badSession";
            //error_log("in 9");
        }    
        
    } catch (Throwable $e) {
       //echo 'And my error is: ' . $e->getMessage();
       //error_log($e);
       file_put_contents($statusFile, "CLOSED");
       //error_log("in 10");
    }
        
        file_put_contents($statusFile, "CLOSED");

} else {
    $response = ""; //bad post";
    //error_log("in 11");
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