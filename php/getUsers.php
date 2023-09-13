<?php

if(isset($_POST["key"])){
    if($_POST["key"] == "yungleandoer2002" && false) {
        $userArr = json_decode(file_get_contents("users.json"));
        
        for ($i=0; $i < count($userArr); $i++) {
            $userArr[$i]->email = "";
            $userArr[$i]->pass = "";
        }
        
        echo json_encode($userArr);
        
    } else {
        echo "noKey";
    }
} else {
    echo "noKey";
}

?>