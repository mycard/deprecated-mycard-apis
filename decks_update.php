<?php
if (!isset($_REQUEST['user']) || !isset($_REQUEST['cards']) || !isset($_REQUEST['name']) || !isset($_REQUEST['updated_at'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

#db
include 'config.php';
$mongo = new MongoClient(MONGO); // 连接
$db = $mongo->selectDB(DB);

$user_name = $_REQUEST['user']; #暂时向下兼容
$name = $_REQUEST['name'];
$updated_at = new MongoDate(min(strtotime($_REQUEST['updated_at']), time()));
$cards = $_REQUEST['cards'];

if(!isset($_REQUEST['legacy_decksync_compatible'])){
    if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $user_name) {
        header('WWW-Authenticate: Basic realm="MyCard API"');
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }
}

$user = $db->users->findOne(array("name" => $user_name));
if (!$user) {
    $user = array(
        'name' => $user_name,
        'points' => 0
    );
    $db->users->insert($user);
}

#main
include 'mycard.php';
$card_usages = MyCard::decode($cards);

$deck = $db->decks_new->findOne(array(
    'user' => $user['_id'],
    'name' => $name,
));

if ($deck) {
    if ($deck['updated_at'] <= $updated_at) {
        if($deck['card_usages'] == $card_usages){
            $db->decks_new->update(array(
                '_id' => $deck['_id'],
            ), array(
                '$set' => array(
                    'updated_at' => $updated_at,
                    'deleted' => false
                )
            ));
            header('HTTP/1.1 204 No Content');
        } else {
            $db->deck_versions->insert(array(
                'deck' => $deck['_id'],
                'card_usages' => $card_usages,
                'version' => $deck['version']+1,
                'created_at' => $updated_at
            ));

            $db->decks_new->update(array(
                '_id' => $deck['_id'],
            ), array(
                '$set' => array(
                    'updated_at' => $updated_at,
                    'card_usages' => $card_usages,
                    'version' => $deck['version'] + 1,
                    'deleted' => false
                )
            ));
            $deck['user'] = $user;
            document_output($deck);
            echo(json_encode($deck));
        }
    } else {
        header('HTTP/1.1 409 Conflict');
    }
} else {
    $deck = array(
        'name' => $name,
        'user' => $user['_id'],
        'created_at' => $updated_at,
        'updated_at' => $updated_at,
        'deleted' => false,
        'card_usages' => $card_usages,
        'version' => 1
    );
    $db->decks_new->insert($deck);

    $db->deck_versions->insert(array(
        'deck' => $deck['_id'],
        'card_usages' => $card_usages,
        'version' => 1,
        'created_at' => $updated_at
    ));

    header('HTTP/1.1 201 Created');
    header("Location: https://my-card.in/decks/$user_name/$name.json");
}