<?php

$m = new MongoClient();
$db = $m->mycard;
$collection = $db->replay_comments;
$cursor = $collection->find(array('replay_id' => new MongoId($_REQUEST['replay_id'])));
echo(json_encode(iterator_to_array($cursor, false)));
?>
