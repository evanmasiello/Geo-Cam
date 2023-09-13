<?php

if (isset($_GET["key"]) and (strlen($_GET["key"]) > 0)) {
    $key = $_GET["key"];
    echo "<h2 style='text-align:center;'><a style=\"color: black;\" href='https://geocam.app/php/getTempPass.php?key=" . $key . "'>Click Here to Generate Your New Password</a></h2>";
} else {
    echo "There was an error";
}

?>