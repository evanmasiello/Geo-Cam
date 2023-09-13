<?php

if(isset($_POST["lat"]) and isset($_POST["long"]) and isset($_POST["session"]) and file_exists("sessions.json")){
//if (false) {

    $session = $_POST["session"];
    
    $sessions = json_decode(file_get_contents("sessions.json"));
    
    $sessionHash = hash("sha256", $session, false);
    
    $validSession = false;
    
    $response = 0;
    
    for ($r=0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key) {
            $uID = $sessions[$r]->userId;
            
            $users = json_decode(file_get_contents("users.json"));
    
            for ($m=0; $m < count($users); $m++) {
                if ($users[$m]->id == $uID) {
                    
                    //error_log("user id is: " . $users[$m]->id);
                    
                    //error_log("email is verified: " . $users[$m]->emailIsVerified);
                    
                    if ($users[$m]->emailIsVerified and (intval($users[$m]->id) == 1 or intval($users[$m]->id == 4))) {
                        $validSession = true;
                        $userIDStore = $m;
                        
                    } else {
                        
                        //error_log("email is not confirmed");
                        
                        //$response = "emailNotConfirmed";
                    }
                } else {
                    //$response = 0;
                }
            }
            
        }
    }

if ($validSession) {
    
$latPost = $_POST["lat"];
$longPost = $_POST["long"];
    
$postData = file_get_contents("../posts/posts.json");

//echo $postData;

$posts = json_decode($postData);
$finalPosts = array();

$finalPostCount = 0;

$users = json_decode(file_get_contents("users.json"));

$minTime = $posts[0]->time;

$accessCodeFile = "accessCode.txt";

$time = time();

if (true) {
    $string = "wow I really wish I could see all of the posts!!!!!" . $time;
    $accessCode = hash("sha256", $string, false);
    $accessHash = hash("sha256", $accessCode, false);
    file_put_contents($accessCodeFile, hash("sha256", $accessHash, false));
}

//foreach ($posts as $post) {
for ($i = count($posts)-1; $i >= 0; $i--) {
        array_push($finalPosts, $posts[$i]);
        
        if ($posts[$i]->time < $minTime) $minTime = $posts[$i]->time;
        
        for ($c=0; $c < count($users); $c++) {
            if ($users[$c]->id == $posts[$i]->user) $userVar = $users[$c]->user;
        }
        
        if ($posts[$i]->user == -1) $userVar = "deleted user";
        
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
        
        $finalPosts[$finalPostCount]->postedBy = $userVar;
        $finalPosts[$finalPostCount]->distance = distance($latPost, $longPost, $posts[$i]->lat, $posts[$i]->long);
        $finalPostCount ++;
}

if (count($finalPosts) > 0) {

    $timeMean = 0;
    $distanceMean = 0;
    $likesMean = 0;
    
    $timeTotal = 0;
    $distanceTotal = 0;
    $likesTotal = 0;
    
    for ($i = count($finalPosts); $i >= 0; $i--) {
        $timeTotal = $timeTotal + ($finalPosts[$i]->time / 10000);
        $distanceTotal = $distanceTotal + $finalPosts[$i]->distance;
        $likesTotal = $likesTotal + $finalPosts[$i]->likes;
    }
    
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
            // $finalPosts[$i]->zLikes = ($finalPosts[$i]->likes - $likesMean) / $likesStDev;
            $finalPosts[$i]->zLikes = 0;
            
            $finalPosts[$i]->lat = 0;
            $finalPosts[$i]->long = 0;
        }
    }
    
    $finalPosts[0]->minTime = $minTime;
    
    $finalPosts[0]->accessCode = hash("sha256", $accessCode, false);

}

echo json_encode($finalPosts);    
} else {
    echo "bad Session";
}

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
?>