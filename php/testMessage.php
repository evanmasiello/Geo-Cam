<?php
$webhookurl = file_get_contents("discord.txt");
$timestamp = date("c", strtotime("now"));
$json_data = json_encode([
    "content" => "Hello World! This is message line ;) And here is the mention, use userID <@12341234123412341>",
    "tts" => false,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
$ch = curl_init( $webhookurl );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
curl_setopt( $ch, CURLOPT_POST, 1);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt( $ch, CURLOPT_HEADER, 0);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
//$response = curl_exec( $ch );
curl_close( $ch );