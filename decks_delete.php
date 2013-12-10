<?php
if (!isset($_REQUEST['name']) || !isset($_REQUEST['user'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$user = $_REQUEST['user'];
$name = $_REQUEST['name'];

$conn = new MongoClient(); // 连接
$db = $conn->mycard;
$coll = $db->selectCollection('decks');

$coll->remove(array(
    'user' => $user,
    'name' => $name
), array(
    'justOne' => true
));


?>
