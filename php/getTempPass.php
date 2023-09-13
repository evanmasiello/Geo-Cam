<?php
    
function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

if (isset($_GET["key"]) and (strlen($_GET["key"]) > 0)) {
    $key = $_GET["key"];
    
    $statusFile = "status.txt";
    
    $dataStatus = file_get_contents($statusFile);
    
    while ($dataStatus == "OPEN") {
        sleep(2);
    }
    
    file_put_contents($statusFile, "OPEN");
    
    $weOpened = true;
    
    $nameMail = "forgotKeys";
    $file_nameMail = $nameMail . '.json';
       
    $locationMail = $file_nameMail;
    
    if (file_exists($locationMail)) {
        
        $mailArr = json_decode(file_get_contents($locationMail));
        
        $keyHash = hash("sha256", $key, false);
        
        $keyValid = false;
        
        $time = round(microtime(true));
        
        for ($i=0; $i < count($mailArr); $i++) {
            if ($mailArr[$i]->key == $keyHash) {
                error_log("key time: " . intval($mailArr[$i]->time));
                error_log("time: " . $time);
                $difference = intval($mailArr[$i]->time) - $time;
                if ($difference < 600) {
                    $userID = $mailArr[$i]->userId;
                    $keyValid = true;
                    $keyID = $i;
                }
            }
        }
        
        if ($keyValid) {
            
            $alphas = range('A', 'Z');
            
            $tempPass = "";
            
            for ($i=0; $i < rand(7, 12); $i++) {
                $tempPass = $tempPass . $alphas[rand(0, 26)];
            }
            
            $tempPass = $tempPass . rand(100, 299);
            
            $random_position = rand(0,strlen($tempPass)-1);
            $chars = "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789-_";
            $random_char = $chars[rand(0,strlen($chars)-1)];
            $tempPass = substr($tempPass,0,$random_position).$random_char.substr($tempPass,$random_position);
            
            $userFile = "users.json";
            
            if (file_exists($userFile)) {
                $userArr = json_decode(file_get_contents($userFile));
                
                for ($i=0; $i < count($userArr); $i ++) {
                    if ($userArr[$i]->id == $userID) $userArr[$i]->pass = hash("sha256", $tempPass, false);
                }
                
            } else {
                echo "<h2 style='text-align:center;'>temporary password failed, please request another email</h2>";
            }
            
            if ($userID != null and file_put_contents($userFile, json_encode($userArr))) {
                echo "<h2 style='text-align:center;'>Your temporary password is: \"$tempPass\", please reset your password once you are able to access your account</h2>";
                
                unset($mailArr[$keyID]);
                
                for ($i=0; $i < count($mailArr); $i++) {
                    
                    error_log("maill ID " . $mailArr[$i]->userId);
                    error_log("user ID " . $userID);
                    
                    if ($mailArr[$i]->userId == $userID) {
                        error_log("in equals");
                        unset($mailArr[$i]);
                    }
                }
                
                file_put_contents($locationMail, json_encode(array_values($mailArr)));
            }
        } else {
            echo "<h2 style='text-align:center;'>temporary password failed, please request another email</h2>";
        }
      
    } else {
       
        echo "<h2 style='text-align:center;'>temporary password failed, please request another email</h2>";
     
    }
    
    file_put_contents($statusFile, "CLOSED");

} else {
    echo "<h2 style='text-align:center;'>temporary password failed, please request another email</h2>";
}

?>