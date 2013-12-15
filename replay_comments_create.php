<?php

$m = new MongoClient(); // 连接
$db = $m->selectDB("mycard");
$coll = $db->selectCollection('replay_comments');
$comment = array(
	'replay_id' => new MongoId($_REQUEST['replay_id']),
	'user_id' => $_SERVER['PHP_AUTH_USER'],
	'action_id' => intval($_REQUEST['action_id']),
	'body' => $_REQUEST['body'],
	'class' => $_REQUEST['class'],
	'style' => $_REQUEST['style']
);
if(!$comment['replay_id'] || !$comment['action_id'] || !$comment['body']){
	header("HTTP/1.1 400 Bad Request");
	exit();
}
$rs = $coll->insert($comment);
echo json_encode($rs);
?>
