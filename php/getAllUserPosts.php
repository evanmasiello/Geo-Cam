<?php

if(isset($_POST["lat"]) and isset($_POST["long"]) and isset($_POST["session"])  and file_exists("sessions.json")){

$latPost = $_POST["lat"];
$longPost = $_POST["long"];

$session = $_POST["session"];

$sessions = json_decode(file_get_contents("sessions.json"));

$sessionHash = hash("sha256", $session, false);

$validSession = false;

$userName = "";

    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
            
            $stillNeedToLoop = true;
            
            $validSession = true;
            
            for ($m=0; $m < count($users) && $stillNeedToLoop; $m++) {
                if ($users[$m]->id == $uID) {
                    $userName = $users[$m]->user;
                }
            }
        }
    }
    
if ($validSession) {
    
    $postData = file_get_contents("../posts/posts.json");
    
    //echo $postData;
    
    $posts = json_decode($postData);
    $finalPosts = array();
    
    $postCount = 0;
    
    for ($i = count($posts); $i >= 0; $i--) {
        //if (distance($_POST["lat"], $_POST["long"], $post->lat, $post->long) > 1) {
        
        //error_log("distance is " . distance($latPost, $longPost, $posts[$i]->lat, $posts[$i]->long));
        
        if ($posts[$i] != null) {
        
        $distance = distance($latPost, $longPost, $posts[$i]->lat, $posts[$i]->long);
        
        $posts[$i]->distance = $distance;
        
        } else {
            $distance = 999999999;
        }
        
        if ($posts[$i]->user == $uID) {
            
            $userVar = $userName;
            
            $posts[$i]->postedBy = $userVar;
            
            $posts[$i]->distance = $distance;
            
            $posts[$i]->commentCount = 0;
            
            $commentCount = 0;
            
            if ($posts[$i]->comments != null) {
                
                $postCommArr = json_decode($posts[$i]->comments);
                
                for ($v=0; $v < count($postCommArr); $v++) {
                    
                    $notFound = true;
                    
                    for ($b=0; $b < count($users); $b++) {
                        if ($postCommArr[$v]->user == $users[$b]->id) {
                            $postCommArr[$v]->userName = $users[$b]->user;
                        }
                    }
                    
                    if ($postCommArr[$v]->hidden != true) {
                        $commentCount++;
                    }
                }
                
                $posts[$i]->commentCount = $commentCount;
                
                $posts[$i]->comments = json_encode($postCommArr);
                
            } else {
                $posts[$i]->comments = json_encode([]);
            }
            
            array_push($finalPosts, $posts[$i]);
            
            if ($posts[$i]->hidden != true && $posts[$i]->visibility != "following") {
                $postCount++;
            }
            
            unset($posts[$i]);
            
        } else {
            // unset($posts[$i]);
        }
    }
    
    $posts = array_values($posts);
    $posts = array_filter($posts);
    
    $maxDist = 1;
        
            $finalPosts = array_values($finalPosts);
            $finalPosts = array_filter($finalPosts);
    
    if (count($finalPosts) > 0) {
    
        $timeMean = 0;
        $distanceMean = 0;
        $likesMean = 0;
        
        $timeTotal = 0;
        $distanceTotal = 0;
        $likesTotal = 0;
        
        $minTime = null;
        
        //error_log("min time start is " . $minTime);
       //error_log("finalPostCount Is " . count($finalPosts));
       
       $maxDist = 1;
        
        for ($i = count($finalPosts); $i >= 0; $i--) {
            $timeTotal = $timeTotal + ($finalPosts[$i]->time / 10000);
            $distanceTotal = $distanceTotal + $finalPosts[$i]->distance;
            $likesTotal = $likesTotal + $finalPosts[$i]->likes;
            
            if ($finalPosts[$i] != null && ($minTime == null || $finalPosts[$i]->time < $minTime)) $minTime = $finalPosts[$i]->time;
            
            if ($finalPosts[$i]->distance > $maxDist) {
                $maxDist = $finalPosts[$i]->distance + 1;
            }
        }
        
        //error_log("min time end is " . $minTime);
        
        $timeMean = $timeTotal / count($finalPosts);
        $distanceMean = $distanceTotal / count($finalPosts);
        $likesMean = $likesTotal / count($finalPosts);
        
        if ($timeMean == 0) $timeMean = 0.0001;
        if ($distanceMean == 0) $distanceMean = 0.0001;
        if ($likesMean == 0) $likesMean = 0.0001;
        
        for ($i = count($finalPosts); $i >= 0; $i--) {
            $timeTotal = $timeTotal + ($finalPosts[$i]->time / 10000);
            $distanceTotal = $distanceTotal + $finalPosts[$i]->distance;
            $likesTotal = $likesTotal + $finalPosts[$i]->likes;
        }
        
        $timeDevTotal = 0;
        $distDevTotal = 0;
        $likesDevTotal = 0;
    
        for ($i = count($finalPosts); $i >= 0; $i--) {
            $timeDevTotal += abs(($finalPosts[$i]->time / 10000) - $timeMean);
            $distDevTotal += abs($finalPosts[$i]->distance - $distanceMean);
            $likesDevTotal += abs($finalPosts[$i]->likes - $likesMean);
        }
    
        $timeStDev = $timeDevTotal / count($finalPosts);
        $distStDev = $distDevTotal / count($finalPosts);
        $likesStDev = $likesDevTotal / count($finalPosts);
        
        if ($timeStDev == 0) $timeStDev = 0.0001;
        if ($distStDev == 0) $distStDev = 0.0001;
        if ($likesStDev == 0) $likesStDev = 0.0001;
        
       //console.log("likesStDev = " + likesStDev);
       
       $finalPosts = array_values($finalPosts);
    
        for ($i = count($finalPosts); $i >= 0; $i--) {
            if (!is_null($finalPosts[$i])) {
                $finalPosts[$i]->zTime = -(($finalPosts[$i]->time / 10000 - $timeMean) / $timeStDev);
                $finalPosts[$i]->zDistance = ($finalPosts[$i]->distance - $distanceMean) / $distStDev;
                //$posts[$i]->zLikes = ($posts[$i]->likes - $likesMean) / $likesStDev;
                $finalPosts[$i]->zLikes = 0;
                
                $finalPosts[$i]->lat = 0;
                $finalPosts[$i]->long = 0;
            }
        }
    
    }
    
    if ($finalPosts[0] != null) $finalPosts[0]->minTime = $minTime;
    if ($finalPosts[0] != null) $finalPosts[0]->maxDist = $maxDist;
    
    echo json_encode(array_values($finalPosts));

} else {
    echo "invalidSession";
}

// $message = "Yay! ";

// if (isset($_POST["uname"])) {
//     $message = $message . $_POST["uname"];
// } else {
//     $message = $message . "A user";
// }

// sendMessage($message . " just accessed the feed! from lat: " . $latPost . " long: " . $longPost . " view the place here: https://geocam.app/php/viewLocation.php?lat=$latPost&long=$longPost");

} else {
    echo "noLocation";
}

function distance(
  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 3959)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $latDelta = $latTo - $latFrom;
  $lonDelta = $lonTo - $lonFrom;

  $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
  return $angle * $earthRadius;
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
?>