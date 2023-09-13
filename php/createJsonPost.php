<?php

// check if file is able to be written to
// if yes then set no and proceed
// if not wait

if(isset($_POST["image"]) and isset($_POST["lat"]) and isset($_POST["long"]) and isset($_POST["session"])) {
    
    $statusFile = "status.txt";
    
    $dataStatus = file_get_contents($statusFile);
    
    while ($dataStatus == "OPEN") {
        sleep(1);
        $dataStatus = file_get_contents($statusFile);
    }
    
    file_put_contents($statusFile, "OPEN");
    
    try {
    
    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
    
    $validSession = false;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
    
            for ($m=0; $m < count($users); $m++) {
                if ($users[$m]->id == $uID) {
                    if ($users[$m]->emailIsVerified) {
                        $validSession = true;
                    
                        $userIDgood = $m;
                    } else {
                        $response = "emailNotConfirmed";
                    }
                }
            }
            
        }
    }
    
    if ($userIDgood == null or $uID == null) {
        $validSession = false;
    }
    
    if (isset($_POST["vis"])) {
        if ($_POST["vis"] == "following") {
            $postVis = "following";
        } else {
            $postVis = "all";
        }
    } else {
        $postVis = "all";
    }
    
    if ($validSession) {
    
       // file name
       $img = $_POST['image'];
       $latPost = $_POST['lat'];
       $longPost = $_POST['long'];
       
       $img = str_replace('data:image/png;base64,', '', $img);
       $img = str_replace(' ', '+', $img);
       $fileData = base64_decode($img);
       //saving
       
       $jsonData = file_get_contents("../posts/posts.json");
    
       $jsonArray = json_decode($jsonData);
       
       $id = $jsonArray[count($jsonArray)-1]->id + 1;
       //$id = count($jsonArray);
       
       $filename = 'post' .$id .'.png';
       
       //while (strpos($jsonData, $filename) != false) {
       //   $filename = preg_replace('/^' . preg_quote("post", '/') . '/', 'post0', $filename);
       //}
       
       //Add function to use binary search for post Id and image file name
       
       $location = '../posts/'.$filename;
       
       file_put_contents($location, $fileData);
       
        $image = imagecreatefrompng("../posts/post$id.png");
        
        imagejpeg($image, "../posts/smallPost$id.jpg", 15);
        
        imagedestroy($image);   
           
    //   chmod($location, 0640);
       
       $filenameJson = "../posts/posts.json";
    
        $time = strval(round(microtime(true)));
        
        $likes = 0;
        
        // if ($time >= 1675486800 && $time < 1675573200) {
        //     $likes = 365;
        // }
    
        if (file_exists($filenameJson)) {
           $posts = $jsonArray;
            $newPost = Array (
                "id" => $id,
                "image" => $filename,
                "lat" => $latPost,
                "long" => $longPost,
                "time" => $time,
                "likes" => $likes,
                "user" => $uID,
                "visibility" => $postVis,
            );
            array_push($posts, $newPost);
        } else {
            $posts = Array (
                "0" => Array (
                    "id" => 0,
                    "image" => $filename,
                    "lat" => $latPost,
                    "long" => $longPost,
                    "time" => $time,
                    "likes" => $likes,
                    "user" => $uID,
                    "visibility" => $postVis,
                )
            );
        }
    
        // encode array to json
        $json = json_encode($posts);
        file_put_contents($filenameJson, $json); //generate json file
        
        $users[$userIDgood]->postCount++;
       
        file_put_contents("users.json", json_encode($users)); //generate json file
       
       $response = 1;
       
       sendMessage("Epic! " . $users[$userIDgood]->user . " just made a post! Its the " . ordinal($users[$userIDgood]->postCount) . " post that they've made");
       logAction($users[$userIDgood]->user . " (user id " . $users[$userIDgood]->id . ") made post #" . $id . ", it was the " . ordinal($users[$userIDgood]->postCount) . " post that they made");
   
    }
    
    echo $response;
    
        
    } catch (Throwable $e) {
       //echo 'And my error is: ' . $e->getMessage();
       file_put_contents($statusFile, "CLOSED");
    }
   
// change status file so that data is write able
   file_put_contents($statusFile, "CLOSED");
   
   exit;
}

function binarySearch(Array $arr, $x)
{
	// check for empty array
	if (count($arr) === 0) return false;
	$low = 0;
	$high = count($arr) - 1;
	
	while ($low <= $high) {
		
		// compute middle index
		$mid = floor(($low + $high) / 2);

		// element found at mid
		if($arr[$mid]->id == $x) {
			return true;
		}

		if ($x < $arr[$mid]->id) {
			// search the left side of the array
			$high = $mid -1;
		}
		else {
			// search the right side of the array
			$low = $mid + 1;
		}
	}
	
	// If we reach here element x doesnt exist
	return false;
}

function ordinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}

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