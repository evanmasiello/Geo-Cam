<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST["uname"]) and isset($_POST["pass"]) and (strlen($_POST["uname"]) > 0) and (strlen($_POST["pass"]) > 0)) {
    
        $statusFile = "status.txt";
    
        $dataStatus = file_get_contents($statusFile);
    
        while ($dataStatus == "OPEN") {
            sleep(2);
        }
        
        file_put_contents($statusFile, "OPEN");
        
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
        
            $session = hash("sha256", "session" . $uname . $_POST["pass"] . $time, false);
            
            $nameSesh = "sessions";
            $file_nameSesh = $nameSesh . '.json';
           
            $locationSesh = $file_nameSesh;
            
            if (file_exists($locationSesh)) {
                
                $jsonDataSesh = file_get_contents($locationSesh);
                
                $jsonArraySesh = json_decode($jsonDataSesh);
                
                for ($x=0; $x < count($jsonArraySesh); $x++) {
                    if ($jsonArraySesh[$x]->userId == $userID) unset($jsonArraySesh[$x]);
                }
                
                $jsonArraySesh = array_values($jsonArraySesh);
                
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