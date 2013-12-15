<?php
	header("Content-type: application/x-ygopro-replay");
	header('Content-Disposition: attachment; filename*=utf-8\'\''.rawurlencode($_REQUEST['name'].'.lua')); 
	echo($_REQUEST['script']);
?>