<?php
	foreach(json_decode(file_get_contents("http://my-card.in/links.json")) as $link){
		if($link->id == $_GET['id']){
			header('Expires: ' . date('D, d M Y H:i:s', time() + (60*60*24)) . ' GMT');
			header('Content-type: image/png');
			$curl = curl_init(); 
			curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.57 Safari/537.36");
			curl_setopt($curl, CURLOPT_URL, $link->logo);
			curl_exec($curl);
			curl_close($curl);
			exit();
		}
	}
	header("HTTP/1.1 404 Not Found");
?>
