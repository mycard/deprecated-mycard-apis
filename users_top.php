<?php
$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 16;

#db
include 'config.php';
$mongo = new MongoClient(MONGO); // 连接
$db = $mongo->selectDB(DB);

$cursor = $db->users->find(array(),array('name'=>1,'points'=>1,'_id'=>0))->sort(array('points'=>-1))->limit($limit);
$users = iterator_to_array($cursor);
echo(json_encode($users));