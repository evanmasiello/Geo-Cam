<?php
    $lat = $_GET["lat"];
    $long = $_GET["long"];
    
    header("Location: https://www.google.com/maps/place/$lat,$long");
?>