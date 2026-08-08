<?php

if (isset($_GET["key"]) and (strlen($_GET["key"]) > 0)) {
    $key = $_GET["key"];
    if (!preg_match('/^[a-f0-9]{64}$/', $key)) {
        echo "There was an error";
    } else {
        echo "<h2 style='text-align:center;'><a style=\"color: black;\" href='https://geocam.app/php/getTempPass.php?key=" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "'>Click Here to Generate Your New Password</a></h2>";
    }
} else {
    echo "There was an error";
}

?>