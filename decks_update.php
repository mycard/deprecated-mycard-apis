<?php
if(isset($_REQUEST['user'])){ #向下兼容，临时。
    $_SERVER['PHP_AUTH_USER'] = $_REQUEST['user'];
}

#param check
if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_REQUEST['user'])) {
    header('WWW-Authenticate: Basic realm="MyCard APIs"');
    header('HTTP/1.1 401 Unauthorized');
    exit;
}
if (!isset($_REQUEST['cards']) || !isset($_REQUEST['name']) || !isset($_REQUEST['updated_at'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}
$user_name = $_SERVER['PHP_AUTH_USER'];
$name = $_REQUEST['name'];
$updated_at = new MongoDate(min(strtotime($_REQUEST['updated_at']), time()));
$cards_encoded = $_REQUEST['cards'];

#db
include 'config.php';
$mongo = new MongoClient(MONGO); // 连接
$db = $conn->selectDB(DB);

$user = $db->users->findOne(array("name" => $user_name));
if(!$user){
    header('WWW-Authenticate: Basic realm="MyCard APIs"');
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

#main
include 'mycard.php';
$cards = MyCard::decode($cards_encoded);

$deck = $db->decks->findOne(array(
    'user' => $user['_id'],
    'name' => $name,
));

if ($deck) {
    /*var_dump($old_deck['updated_at']);
    var_dump($updated_at);
    var_dump($old_deck['updated_at'] <= $updated_at);
    if ($old_deck['updated_at'] <= $updated_at) {
        var_dump($coll->update(array(
            'user' => $user,
            'name' => $name
        ), array(
            'user' => $user,
            'name' => $name,
            'cards' => $cards,
            'updated_at' => $updated_at
        )));
    }*/
} else {
    $deck = $db->decks->insert(array(
        'name' => $name,
        'user' => $user,
        'created_at' => $updated_at,
        'updated_at' => $updated_at
    ));
    $db->$deck_versions->insert(array(
        'deck' => $deck['_id'],
        'cards' => $cards,
        'index' => 0,
        'created_at' => $updated_at
    ));
}

exit;