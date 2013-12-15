<?php
if (!isset($_REQUEST['name']) || !isset($_REQUEST['user'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

#db
include 'config.php';
$mongo = new MongoClient(MONGO); // 连接
$db = $mongo->selectDB(DB);

$user_name = $_REQUEST['user']; #暂时向下兼容
$name = $_REQUEST['name'];

if(!isset($_REQUEST['legacy_decksync_compatible'])){
    if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $user_name) {
        header('WWW-Authenticate: Basic realm="MyCard API"');
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }
}

$user = $db->users->findOne(array("name" => $user_name));
if (!$user) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$deck = $db->decks_new->findOne(array(
    'user' => $user['_id'],
    'name' => $name,
));
if($deck){
    document_output($deck);
    document_output($user);
    $deck['user'] = $user;
    echo(json_encode($deck));
}else{
    header('HTTP/1.1 404 Not Found');
}