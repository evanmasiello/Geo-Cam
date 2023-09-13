<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST["email"]) and (strlen($_POST["email"]) > 0)) {
    
    $statusFile = "status.txt";
    
    $dataStatus = file_get_contents($statusFile);
    
    while ($dataStatus == "OPEN") {
        sleep(2);
    }
        
        $nameUsers = "users";
        $file_nameUsers = $nameUsers . '.json';
       
        $locationUsers = $file_nameUsers;
        
        //$uname = htmlspecialchars($_POST['uname']);
        $email = $_POST['email'];
        
        $time = strval(round(microtime(true)));
        
        if (file_exists($locationUsers)) {
            
            $jsonDataUsers = file_get_contents($locationUsers);
            
            $jsonArrayUsers = json_decode($jsonDataUsers);
            
            $userIsValid = false;
            
            for ($x = 0; $x < count($jsonArrayUsers); $x++) {
                //if (strtolower($jsonArrayUsers[$x]->user) == strtolower($uname)) {
                    $userNotExists = false;
                    
                    $emailSub1 = str_replace(".", "", strtok(strtolower($jsonArrayUsers[$x]->email), '@'));
                    $emailSub2 = str_replace(".", "", strtok(strtolower($email), '@'));
                      
                    $emailEnd1 = substr(strtolower($jsonArrayUsers[$x]->email), strpos(strtolower($jsonArrayUsers[$x]->email), "@"));
                    $emailEnd2 = substr(strtolower($email), strpos(strtolower($email), "@"));
                      
                    $emailFilter1 = $emailSub1 . $emailEnd1;
                    $emailFilter2 = $emailSub2 . $emailEnd2;
                    
                    //error_log("str pos 1" . strpos($emailSub1, "@"));
                    //error_log("str pos 2" . strpos($emailSub2, "@"));
                    
                    //error_log("emailSub1: " . $emailSub1);
                    //error_log("emailSub2: " . $emailSub2);
                    //error_log("emailEnd1: " . $emailEnd1);
                    //error_log("emailEnd2: " . $emailEnd2);
                      
                    //error_log("emailFilter1: " . $emailFilter1);
                    //error_log("emailFilter2: " . $emailFilter2);
                    
                    if (strtolower($jsonArrayUsers[$x]->email) == strtolower($email) or $emailFilter1 == $emailFilter2) {
                        $userIsValid = true;
                        $userID = $jsonArrayUsers[$x]->id;
                        $uname = $jsonArrayUsers[$x]->user;
                        //echo "badEmail";
                    } else {
                        $response = "badEmail";
                    }
                    
                //} else {
                //    $response = "badName";
                //}
            }
        
            $key = hash("sha256", $uname . $_POST["email"] . $time . rand(10,1000), false);
            
            $nameMail = "forgotKeys";
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
                
                for ($i=0; $i < count($jsonArrayMail); $i++) {
                    
                    error_log("maill ID " . $jsonArrayMail[$i]->userId);
                    error_log("user ID " . $userID);
                    
                    if ($jsonArrayMail[$i]->userId == $userID) {
                        error_log("in equals");
                        unset($jsonArrayMail[$i]);
                    }
                }
                
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
            $subject = "Forgot Geo Cam Password ($dateString - $secondCount)";
            //$body = "Hi $uname,\n\nPlease confirm your email for your Geo Cam account by clicking this link: https://geocam.app/php/getTempPass.php?key=" . $key . "\n\nThanks,\nGeo Cam Team";
            $unameString = '"'.$uname.'".';
            $body = "<h2 style='text-align: center;'>Geo Cam Account Information:</h2><h3 style='text-align: center;'>Username: $uname</h3><h3 style='text-align: center;'><a href='https://geocam.app/php/confirmTempPass.php?key=" . $key . "'>Get Temporary Password</a></h3>";
            $headers = "From: no-reply@geocam.app\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
            // encode array to json
            $jsonMail = json_encode(array_values($jsonArrayMail));
                
            if ($userIsValid) {
                if (file_put_contents($locationMail, $jsonMail) and mail($to_email, $subject, $body, $headers)) {
                    $response = 1;
                } else {
                    $response = "badLogin";
                }
            } else {
                $response = "badLogin";
            }
            
        }
        
    file_put_contents($statusFile, "CLOSED");

} else {
    $response = ""; //bad post";
}

echo $response;

?>