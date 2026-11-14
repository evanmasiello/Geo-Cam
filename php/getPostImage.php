<?php

$id = $_GET["id"] ?? $_POST["id"] ?? null;

if (!is_numeric($id) || intval($id) < 0) {
    http_response_code(404);
    exit;
}

$id = intval($id);

$postFile = "../posts/posts.json";

if (!file_exists($postFile)) {
    http_response_code(404);
    exit;
}

$posts = json_decode(file_get_contents($postFile));
if (!is_array($posts)) $posts = [];
$users = file_exists("users.json") ? json_decode(file_get_contents("users.json")) : [];
if (!is_array($users)) $users = [];
$post = null;

for ($i = 0; $i < count($posts); $i++) {
    if ($posts[$i]->id == $id) {
        $post = $posts[$i];
        break;
    }
}

if ($post === null || !empty($post->hidden)) {
    http_response_code(404);
    exit;
}

$viewerId = null;

if (isset($_COOKIE["session"]) and file_exists("sessions.json")) {
    $sessions = json_decode(file_get_contents("sessions.json"));
    $sessionHash = hash("sha256", $_COOKIE["session"], false);

    for ($r = 0; $r < count($sessions); $r++) {
        if ($sessionHash == $sessions[$r]->key && (time() - intval($sessions[$r]->time)) < 7776000) {
            $viewerId = $sessions[$r]->userId;
        }
    }
}

if (!postVisible($post, $viewerId, $users)) {
    http_response_code(404);
    exit;
}

$isSmall = (($_GET["size"] ?? "") === "small");
$path = $isSmall ? "../posts/smallPost$id.jpg" : "../posts/post$id.png";
$contentType = $isSmall ? "image/jpeg" : "image/png";

if (!file_exists($path) && $isSmall) {
    $path = "../posts/post$id.png";
    $contentType = "image/png";
}

if (!file_exists($path)) {
    http_response_code(404);
    exit;
}

if (!is_readable($path)) {
    http_response_code(404);
    exit;
}

header("Content-Type: $contentType");
header("Content-Length: " . filesize($path));
header("Cache-Control: private, max-age=3600");
header("X-Content-Type-Options: nosniff");

readfile($path);

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
