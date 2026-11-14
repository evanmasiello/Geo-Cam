<?php

if (isset($_POST["lat"]) and isset($_POST["long"]) and is_numeric($_POST["lat"]) and is_numeric($_POST["long"])) {
    $latPost = $_POST["lat"];
    $longPost = $_POST["long"];

    $postData = file_get_contents("../posts/posts.json");

    //echo $postData;

    $posts = json_decode($postData);
    if (!is_array($posts)) $posts = [];
    $finalPosts = [];

    $finalPostCount = 0;

    $users = json_decode(file_get_contents("users.json"));
    if (!is_array($users)) $users = [];

    $minTime = null;
    $time = time();

    //foreach ($posts as $post) {
    for ($i = count($posts) - 1; $i >= 0; $i--) {
        if (
            ($finalPostCount < 20 or $time - $posts[$i]->time < 86400) &&
            $posts[$i]->visibility == "all" &&
            empty($posts[$i]->hidden)
        ) {
            $posts[$i]->commentCount = 0;

            $commentCount = 0;

            if ($posts[$i]->comments != null) {
                $postCommArr = json_decode($posts[$i]->comments);
                $visibleComments = [];

                for ($v = 0; $v < count($postCommArr); $v++) {
                    if (!empty($postCommArr[$v]->hidden)) continue;

                    for ($b = 0; $b < count($users); $b++) {
                        if ($postCommArr[$v]->user == $users[$b]->id) {
                            $postCommArr[$v]->userName = $users[$b]->user;
                        }
                    }

                    $visibleComments[] = $postCommArr[$v];
                    $commentCount++;
                }

                $posts[$i]->commentCount = $commentCount;

                $posts[$i]->comments = json_encode($visibleComments);
            } else {
                $posts[$i]->comments = json_encode([]);
            }

            array_push($finalPosts, $posts[$i]);

            if ($minTime === null || $posts[$i]->time < $minTime) {
                $minTime = $posts[$i]->time;
            }

            $userVar = "deleted user";

            for ($c = 0; $c < count($users); $c++) {
                if ($users[$c]->id == $posts[$i]->user) {
                    $userVar = $users[$c]->user;
                }
            }

            $posts[$i]->distance = distance(
                $latPost,
                $longPost,
                $posts[$i]->lat,
                $posts[$i]->long
            );
            $posts[$i]->lat = 0;
            $posts[$i]->long = 0;
            $finalPosts[$finalPostCount]->postedBy = $userVar;
            $finalPostCount++;
        }
    }

    if (count($finalPosts) > 0) {
        $maxDist = 1;

        $timeMean = 0;
        $distanceMean = 0;
        $likesMean = 0;

        $timeTotal = 0;
        $distanceTotal = 0;
        $likesTotal = 0;

        for ($i = count($finalPosts) - 1; $i >= 0; $i--) {
            $timeTotal = $timeTotal + $finalPosts[$i]->time / 10000;
            $distanceTotal = $distanceTotal + $finalPosts[$i]->distance;
            $likesTotal = $likesTotal + $finalPosts[$i]->likes;
            if ($finalPosts[$i]->distance > $maxDist) {
                $maxDist = $finalPosts[$i]->distance;
            }
        }

        $timeMean = $timeTotal / count($finalPosts);
        $distanceMean = $distanceTotal / count($finalPosts);
        $likesMean = $likesTotal / count($finalPosts);

        if ($timeMean == 0) {
            $timeMean = 0.0001;
        }
        if ($distanceMean == 0) {
            $distanceMean = 0.0001;
        }
        if ($likesMean == 0) {
            $likesMean = 0.0001;
        }

        $timeDevTotal = 0;
        $distDevTotal = 0;
        $likesDevTotal = 0;

        for ($i = count($finalPosts) - 1; $i >= 0; $i--) {
            $timeDevTotal += abs($finalPosts[$i]->time / 10000 - $timeMean);
            $distDevTotal += abs($finalPosts[$i]->distance - $distanceMean);
            $likesDevTotal += abs($finalPosts[$i]->likes - $likesMean);
        }

        $timeStDev = $timeDevTotal / count($finalPosts);
        $distStDev = $distDevTotal / count($finalPosts);
        $likesStDev = $likesDevTotal / count($finalPosts);

        if ($timeStDev == 0) {
            $timeStDev = 0.0001;
        }
        if ($distStDev == 0) {
            $distStDev = 0.0001;
        }
        if ($likesStDev == 0) {
            $likesStDev = 0.0001;
        }

        //console.log("likesStDev = " + likesStDev);

        $finalPosts = array_values($finalPosts);

        for ($i = count($finalPosts) - 1; $i >= 0; $i--) {
            if (!is_null($finalPosts[$i])) {
                $finalPosts[$i]->zTime = -(
                    ($finalPosts[$i]->time / 10000 - $timeMean) /
                    $timeStDev
                );
                $finalPosts[$i]->zDistance =
                    ($finalPosts[$i]->distance - $distanceMean) / $distStDev;
                $finalPosts[$i]->zLikes =
                    ($finalPosts[$i]->likes - $likesMean) / $likesStDev;
            }
        }

        if (isset($finalPosts[0])) {
            $finalPosts[0]->minTime = $minTime;
            $finalPosts[0]->maxDist = ceil($maxDist);
        }
    }

    echo json_encode($finalPosts);
} else {
    echo "noLocation";
}

function distance(
    $latitudeFrom,
    $longitudeFrom,
    $latitudeTo,
    $longitudeTo,
    $earthRadius = 3959
) {
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle =
        2 *
        asin(
            sqrt(
                pow(sin($latDelta / 2), 2) +
                    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
            )
        );
    return $angle * $earthRadius;
}
?>