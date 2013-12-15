<?php
	header("Content-type: application/x-ygopro-replay");
	header('Content-Disposition: attachment; filename*=utf-8\'\''.rawurlencode($_REQUEST['name'].'.yrp')); 
	function base64url_decode($data) { 
  		return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
	} 
    echo(base64url_decode($_GET['replay']));
?>