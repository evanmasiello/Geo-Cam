<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST["uname"]) and isset($_POST["pass"]) and isset($_POST["email"]) and (strlen($_POST["uname"]) > 0) and (strlen($_POST["pass"]) > 0) and (strlen($_POST["email"]) > 0)) {
    
    $blockedWords = json_decode(file_get_contents("blockedWords.json"));
    
    $containsBlocked = false;
    
    $testUname = str_replace(' ', '', strtolower($_POST["uname"]));
    
    if (strtolower($_POST["uname"]) == "undefined") $charactersAreOk = false;
    
    error_log("userName = " . $testUname);
    
    for ($v=0; $v < count($blockedWords); $v++) {
        if (str_contains($testUname,$blockedWords[$v])) $containsBlocked = true;
        #error_log("blockedWord = " . $blockedWords[$v]);
    }
    
    if (preg_match("#^[a-zA-Z0-9äöüÄÖÜ \.\]\_\-\=\+\,\(\)\!\@\#\$\%\&\*\"\'\[]+$#", $_POST["uname"])) {
       $charactersAreOk = true; 
    } else {
        $charactersAreOk = false; 
    }
    
    if (strlen($_POST["uname"]) > 25) {
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
        
        $nameUsers = "users";
        $file_nameUsers = $nameUsers . '.json';
       
        $locationUsers = $file_nameUsers;
        
        $uname = htmlspecialchars(trim($_POST['uname']));
        $pass = hash("sha256", $_POST['pass'], false);
        $email = $_POST['email'];
        
        $time = strval(round(microtime(true)));
        
        if (file_exists($locationUsers)) {
            
            $jsonDataUsers = file_get_contents($locationUsers);
            
            $jsonArrayUsers = json_decode($jsonDataUsers);
            
            $userID = $jsonArrayUsers[count($jsonArrayUsers)-1]->id + 1;
            
            $userNotExists = true;
            
            for ($x = 0; $x < count($jsonArrayUsers); $x++) {
                if (strtolower($jsonArrayUsers[$x]->user) == strtolower($uname)) {
                    $userNotExists = false;
                    $response = "badName";
                }
                
                $emailSub1 = str_replace(".", "", strtok(strtolower($jsonArrayUsers[$x]->email), '@'));
                $emailSub2 = str_replace(".", "", strtok(strtolower($email), '@'));
                  
                $emailEnd1 = substr(strtolower($jsonArrayUsers[$x]->email), strpos(strtolower($jsonArrayUsers[$x]->email), "@"));
                $emailEnd2 = substr(strtolower($email), strpos(strtolower($email), "@"));
                  
                $emailFilter1 = $emailSub1 . $emailEnd1;
                $emailFilter2 = $emailSub2 . $emailEnd2;
                
                if (strtolower($jsonArrayUsers[$x]->email) == strtolower($email) or $emailFilter1 == $emailFilter2) {
                    $userNotExists = false;
                    //echo "badEmail";
                    $response = "badEmail";
                }
                
                if (str_contains($emailFilter2, "@") and str_contains($emailFilter2, ".")) {
                    $emailValid = true;
                } else {
                    $emailValid = false;
                }
            }
            
            if ($userNotExists) {
                $newUser = Array (
                    "id" => $userID,
                    "user" => $uname,
                    "pass" => $pass,
                    "email" => $email,
                    "emailIsVerified" => false,
                    "time" => $time,
                    "postCount" => 0,
                    "likes" => json_encode([]),
                    "commentCount" => 0,
                );
                array_push($jsonArrayUsers, $newUser);
            } else {
                //echo ""; //bad";
            }
            
        } else {
            $userID = 0;
            
            $userNotExists = true;
            
            $jsonArrayUsers = Array (
                "0" => Array (
                    "id" => $userID,
                    "user" => $uname,
                    "pass" => $pass,
                    "email" => $email,
                    "emailIsVerified" => false,
                    "time" => $time,
                    "postCount" => 0,
                    "likes" => json_encode([]),
                    "commentCount" => 0,
                )
            );
        }
        
        $key = hash("sha256", $uname . $_POST["pass"] . $time, false);
        
        $nameMail = "mailKeys";
        $file_nameMail = $nameMail . '.json';
       
        $locationMail = $file_nameMail;
        
        if (file_exists($locationMail)) {
            
            $jsonDataMail = file_get_contents($locationMail);
            
            $jsonArrayMail = json_decode($jsonDataMail);
            
            $hashedKey = hash("sha256", $key, false);
            
            $newSet = Array (
                "key" => $hashedKey,
                "userId" => $userID,
                "time" => $time,
            );
            
            array_push($jsonArrayMail, $newSet);
            
        } else {
            
            $hashedKey = hash("sha256", $key, false);
            
            $jsonArrayMail = Array (
                "0" => Array (
                    "key" => $hashedKey,
                    "userId" => $userID,
                    "time" => $time,
                )
            );
            
        }
        
        $to_email = $email;
        $secondCount = $time % 6000000;
        $dateString = date("m/d/Y");
        $subject = "Confirm Geo Cam Email ($dateString - $secondCount)";
        // $body = "Hi $uname,\n\nPlease confirm your email for your Geo Cam account by clicking this link: https://geocam.app/php/confirmMail.php?key=" . $key . "\n\nThanks,\nGeo Cam Team";
        //$body = "Hi $uname,\n\nPlease confirm your email for your Geo Cam account by clicking this link: https://evanmasiello.com/geogram/php/confirmMail.php?key=" . $key . "\n\nThanks,\nGeo Cam Team";
        // $headers = "From: no-reply@geocam.app";
        $body = "<h2 style='text-align: center;'>Confirm Geo Cam Email:</h2><h3 style='text-align: center;'>Username: $uname</h3><h3 style='text-align: center;'><a href='https://geocam.app/php/confirmMail.php?key=" . $key . "'>Confirm Email</a></h3>";
        $headers = "From: no-reply@geocam.app\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
        // encode array to json
        $jsonUsers = json_encode($jsonArrayUsers);
        $jsonMail = json_encode($jsonArrayMail);
        
        $session = hash("sha256", "session" . $uname . $_POST["pass"] . $time, false);
            
        $nameSesh = "sessions";
        $file_nameSesh = $nameSesh . '.json';
           
        $locationSesh = $file_nameSesh;
            
        if (file_exists($locationSesh)) {
                
            $jsonDataSesh = file_get_contents($locationSesh);
                
            $jsonArraySesh = json_decode($jsonDataSesh);
                
            $newSet = Array (
                "key" => hash("sha256", $session, false),
                "userId" => $userID,
                "time" => $time,
            );
                
            array_push($jsonArraySesh, $newSet);
                
        } else {
                
            $jsonArraySesh = Array (
                "0" => Array (
                    "key" => hash("sha256", $session, false),
                    "userId" => $userID,
                    "time" => $time,
                )
            );
                
        }
            
        // encode array to json
        $jsonSesh = json_encode($jsonArraySesh);
        if ($emailValid) {
            if ($userNotExists and file_put_contents($locationUsers, $jsonUsers)) {
                if (file_put_contents($locationMail, $jsonMail) and mail($to_email, $subject, $body, $headers)) {
                    if (file_put_contents($locationSesh, $jsonSesh)) {
                        $response = $session;
                        sendMessage("Oh yeah! $uname just made their account!");
                        logAction($uname . " (user id " . $userID . ") made their account");
                    } else {
                        $response = "Session";
                    }
                } else {
                    $response = "mailSend";
                }
            }
        } else {
            $response = "mailSend";
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