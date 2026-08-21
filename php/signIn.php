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


if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST["uname"]) and isset($_POST["pass"]) and (strlen($_POST["uname"]) > 0) and (strlen($_POST["pass"]) > 0)) {
    
        $statusFile = "status.txt";
    
        $dataStatus = file_get_contents($statusFile);
    
        $lockWaitStart = time();
        while ($dataStatus == "OPEN") {
            // The holder died without releasing: normal hold time is
            // milliseconds, so treat a lock this old as stale and proceed.
            if (time() - $lockWaitStart >= 10) break;
            sleep(2);
            $dataStatus = file_get_contents($statusFile);
        }
        
        file_put_contents($statusFile, "OPEN");
        
        $weOpened = true;
        
        $nameUsers = "users";
        $file_nameUsers = $nameUsers . '.json';
       
        $locationUsers = $file_nameUsers;
        
        $uname = htmlspecialchars($_POST['uname']);
        $pass = hash("sha256", $_POST['pass'], false);
        
        $time = strval(round(microtime(true)));
        
        if (file_exists($locationUsers)) {
            
            $jsonDataUsers = file_get_contents($locationUsers);
            
            $jsonArrayUsers = json_decode($jsonDataUsers);
            
            for ($x = 0; $x < count($jsonArrayUsers); $x++) {
                if (strtolower($jsonArrayUsers[$x]->user) == strtolower($uname) and $jsonArrayUsers[$x]->pass == $pass) {
                    $userIsLegit = true;
                    $userID = $jsonArrayUsers[$x]->id;
                }
            }
            
        }
        
        if ($userIsLegit) {
        
            $session = bin2hex(random_bytes(32));
            
            $nameSesh = "sessions";
            $file_nameSesh = $nameSesh . '.json';
           
            $locationSesh = $file_nameSesh;
            
            if (file_exists($locationSesh)) {
                
                $jsonDataSesh = file_get_contents($locationSesh);
                
                $jsonArraySesh = json_decode($jsonDataSesh);
                
                $keptSesh = [];
                for ($x=0; $x < count($jsonArraySesh); $x++) {
                    if ($jsonArraySesh[$x]->userId != $userID) array_push($keptSesh, $jsonArraySesh[$x]);
                }
                
                $jsonArraySesh = $keptSesh;
                
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
            
            if (file_put_contents($locationSesh, $jsonSesh)) {
                echo $session;
            } else {
                echo "bad";
            }
        
        }
        
        file_put_contents($statusFile, "CLOSED");
        
} else {
    echo "bad";
}

?>