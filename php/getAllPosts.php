<?php

if(isset($_POST["lat"]) and isset($_POST["long"]) and $_POST["superSecretPassword"] == "ligmaCock"){
//if (false) {
    
    $validSession = true;
    
    $response = 0;

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

//foreach ($posts as $post) {
for ($i = count($posts)-1; $i >= 0; $i--) {
        array_push($finalPosts, $posts[$i]);
        
        if ($posts[$i]->time < $minTime) $minTime = $posts[$i]->time;
        
        for ($c=0; $c < count($users); $c++) {
            if ($users[$c]->id == $posts[$i]->user) $userVar = $users[$c]->user;
        }
        
        if ($posts[$i]->user == -1) $userVar = "deleted user";
        
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
            $finalPosts[$i]->zLikes = ($finalPosts[$i]->likes - $likesMean) / $likesStDev;
        }
    }
    
    $finalPosts[0]->minTime = $minTime;

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