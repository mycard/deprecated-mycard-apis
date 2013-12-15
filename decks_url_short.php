<?php
    $name = $_REQUEST['name'];
    if(isset($_REQUEST['cards'])){
        $long_url = 'https://my-card.in/decks/new?name='.rawurlencode($_REQUEST['name'])."&cards=$_REQUEST[cards]";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('longUrl' => $long_url )));
	curl_setopt($ch, CURLOPT_URL,'https://www.googleapis.com/urlshortener/v1/url?key=AIzaSyBZw7nZElp2l2BIiRdMgeFp-bhKAuaiIcY');
	$result = json_decode(curl_exec($ch), true);
	echo(str_replace("http://","https://",$result['id']));
    }

