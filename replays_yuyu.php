<?php
$ch = curl_init();
//rl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, 'http://122.0.65.70:8098/replay.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
    'name' => $_FILES['replay']['name'],
    "file" => '@'.$_FILES['replay']['tmp_name']
)); 
$yuyu = curl_exec($ch);

?>
