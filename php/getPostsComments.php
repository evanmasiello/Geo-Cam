<?php
header("Access-Control-Allow-Origin: https://geocam.app");
header("Access-Control-Allow-Credentials: true");


if(isset($_POST["id"])) {

$postData = file_get_contents("../posts/posts.json");

$posts = json_decode($postData);
if (!is_array($posts)) $posts = [];

$users = json_decode(file_get_contents("users.json"));
if (!is_array($users)) $users = [];

$viewerId = null;

if (isset($_POST["session"]) and file_exists("sessions.json")) {
    $sessions = json_decode(file_get_contents("sessions.json"));
    $sessionHash = hash("sha256", $_POST["session"], false);

    for ($r = 0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key && (time() - intval($sessions[$r]->time)) < 7776000) {
            $viewerId = $sessions[$r]->userId;
        }
    }
}

$searchId = $_POST["id"];
$commentsResponse = json_encode([]);

for ($i = count($posts) - 1; $i >= 0; $i--) {
    if ($posts[$i]->id != $searchId) continue;

    if (!postVisible($posts[$i], $viewerId, $users)) {
        $commentsResponse = json_encode([]);
        break;
    }

    if ($posts[$i]->comments == null) break;

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
    }

    $commentsResponse = json_encode($visibleComments);
    break;
}

echo $commentsResponse;

} else {
    echo json_encode([]);
}

function postVisible($post, $viewerId, $users) {
    if (!empty($post->hidden)) return false;

    if ($post->visibility != "following") return true;

    if ($viewerId === null) return false;

    if ($post->user == $viewerId) return true;

    for ($i = 0; $i < count($users); $i++) {
        if ($users[$i]->id == $post->user) {
            $followed = [];

            if (isset($users[$i]->followed) && $users[$i]->followed != null) {
                $followed = json_decode($users[$i]->followed);
            }

            return in_array($viewerId, $followed);
        }
    }

    return false;
}

?>
