<?php

function shutdown()
{
    if ($weOpened) file_put_contents($statusFile, "CLOSED");
}

$weOpened = false;

register_shutdown_function('shutdown');

$reponse = "<h2 style='text-align:center;'>email confirmation failed</h2>";

if (isset($_GET["key"]) and (strlen($_GET["key"]) > 0)) {
    $key = $_GET["key"];
    
    $statusFile = "status.txt";
    
    $dataStatus = file_get_contents($statusFile);
    
    while ($dataStatus == "OPEN") {
        sleep(1);
    }
    
    file_put_contents($statusFile, "OPEN");
    
    try {
    
    $weOpened = true;
    
    $nameMail = "mailKeys";
    $file_nameMail = $nameMail . '.json';
       
    $locationMail = $file_nameMail;
    
    if (file_exists($locationMail)) {
        
        $mailArr = json_decode(file_get_contents($locationMail));
        
        $keyHash = hash("sha256", $key, false);
        
        $keyValid = false;
        
        for ($i=0; $i < count($mailArr); $i++) {
            if ($mailArr[$i]->key == $keyHash) {
                $userID = $mailArr[$i]->userId;
                $keyValid = true;
                $keyID = $i;
            }
        }
        
        if ($keyValid) {
            
            $userFile = "users.json";
            
            if (file_exists($userFile)) {
                $userArr = json_decode(file_get_contents($userFile));
                
                for ($i=0; $i < count($userArr); $i++) {
                    if ($userArr[$i]->id == $userID) $userIdLegit = $i;
                }
                
                $userArr[$userIdLegit]->emailIsVerified = true;
                
                $uname = $userArr[$userIdLegit]->user;
                
            } else {
                $reponse = "<h2 style='text-align:center;'>email confirmation failed</h2>";
            }
            
            if (file_put_contents($userFile, json_encode($userArr))) {
                $reponse = "<h2 style='text-align:center;'>Your email has been confirmed!</h2>";
                
                sendMessage("Look at that! " . $uname . " confirmed their email");
                
                unset($mailArr[$keyID]);
                
                if (file_put_contents($locationMail, json_encode(array_values($mailArr)))) {
                    
                }
            }
        } else {
            $reponse = "<h2 style='text-align:center;'>email confirmation failed</h2>";
        }
      
    } else {
       
        $reponse = "<h2 style='text-align:center;'>email confirmation failed</h2>";
     
    }    
        
    } catch (Throwable $e) {
       //$reponse = 'And my error is: ' . $e->getMessage();
       file_put_contents($statusFile, "CLOSED");
    }
    
    file_put_contents($statusFile, "CLOSED");

}

echo $reponse;

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

?>