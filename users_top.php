<?php
$conn = new MongoClient("mongodb://phoenix.my-card.in/"); // 连接
$db = $conn->mycard;
$coll = $db->selectCollection('users');

$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 16;

$cursor = $coll->find(array(),array('name'=>1,'points'=>1,'_id'=>0))->sort(array('points'=>-1))->limit($limit);
$users = iterator_to_array($cursor);
echo(json_encode($users));

?>
