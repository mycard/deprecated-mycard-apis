<?php
$conn = new Mongo();
$db = $conn->mycard;

$grid = $db->getGridFS('replays');
$id = new MongoId($_GET['id']);
$file = $grid->get($id);
if(isset($_GET['format']) && $_GET['format']=='json'){
    $grid->update(array("_id" => $id), array('$inc' => array("views" => 1)));
    $meta = json_decode(json_encode($file), true);  //WTF! how to get it directly.
    echo(json_encode($meta['file']));
}else{
    $grid->update(array("_id" => $id), array('$inc' => array("downloads" => 1)));
    header("Content-type:application/x-ygopro-replay");
    header("Content-Disposition:attachment;filename='".$file->getFilename()."'");
    echo($file->getBytes());
}
?>
