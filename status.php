<?php
	$images_orenoturn = json_decode(file_get_contents('/home/zh99998/downloads/images_orenoturn.json'), true);
	$images_wikia = json_decode(file_get_contents('/home/zh99998/downloads/images_wikia.json'), true);
	$images_kanabell = json_decode(file_get_contents('/home/zh99998/downloads/images_kanabell.json'), true);
	$database_ygopro = json_decode(file_get_contents('http://my-card.in/cards.json'), true);
	$ids = array();
	foreach($database_ygopro as $card){
		$ids[]=$card['_id'];
	}
	echo(json_encode(array(
		'orenoturn_count' => count($images_orenoturn),
		'wikia_count' => count($images_wikia),
		'kanabell_count' => count($images_kanabell),
		'used_orenoturn_count' => count($images_orenoturn),
                'used_wikia_count' => count($images_wikia),
                'used_kanabell_count' => count($images_kanabell),
		'missing' => array_values(array_diff($ids, array_keys($images_orenoturn), array_keys($images_wikia), array_keys($images_kanabell)))
	)));
?>
