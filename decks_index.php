<?php
#param check
if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_REQUEST['user'])) {
    header('WWW-Authenticate: Basic realm="MyCard APIs"');
    header('HTTP/1.1 401 Unauthorized');
    exit;
}
$user_name = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : $_REQUEST['user'];   #暂时向下兼容

#db
include 'config.php';
$mongo = new MongoClient(MONGO); // 连接
$db = $conn->selectDB(DB);

$user = $db->users->findOne(array("name" => $user_name));
if(!$user){
    header('HTTP/1.1 403 Forbidden');
    exit;
}

#main
$cursor = $db->decks->find(array('user' => $user['_id']));
$decks = array();
foreach ($cursor as $deck) {
    $deck['updated_at'] = date(DateTime::W3C, $deck['updated_at']->sec);
    $decks[]= $deck;
}
$cursor = $db->deck_versions->find(array('deck' => array('$in' => array_column($decks, '_id'))));

echo(json_encode($decks));