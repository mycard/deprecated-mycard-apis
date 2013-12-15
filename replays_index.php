<?php
$conn = new Mongo();
$db = $conn->mycard;

$grid = $db->getGridFS('replays');
$cursor = $grid->find(array(), array('filename' => true));
$result = array();
foreach($cursor as $file){
  $result[]= array('id' => $file->file['_id']->__toString(), 'name' => pathinfo($file->getFilename(), PATHINFO_FILENAME));
}
echo(json_encode($result));
?>
