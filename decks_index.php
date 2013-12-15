<?php
if (!isset($_REQUEST['user'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

#db
include 'config.php';
$mongo = new MongoClient(MONGO); // 连接
$db = $mongo->selectDB(DB);

$user_name = $_REQUEST['user']; #暂时向下兼容

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

#main
$cursor = $db->decks_new->find(array('user' => $user['_id'], 'deleted' => array('$ne' => true)));

$decks = iterator_to_array($cursor, false);
document_output($decks);

document_output($user);

foreach($decks as &$deck){
    $deck['user'] = $user;
    unset($deck['deck']);
    if(isset($_REQUEST['legacy_decksync_compatible'])){
        $deck['cards'] = $deck['card_usages'];
        unset($deck['card_usages']);
    }
}

echo(json_encode($decks));