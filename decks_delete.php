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
    if($deck['deleted']){
        header('HTTP/1.1 410 Gone');
    }else{
        $db->decks_new->update(array(
            '_id' => $deck['_id'],
        ), array(
            '$set' => array(
                'updated_at' => new MongoDate(),
                'deleted' => true
            )
        ));
        header('HTTP/1.1 204 No Content');
    }
}else{
    header('HTTP/1.1 404 Not Found');
}