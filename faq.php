<?
	$site_content = json_decode(file_get_contents('https://forum.my-card.in/admin/site_contents/faq.json'));
	$markdown = $site_content['site_content']['content'];
	var_dump($markdown);
?>
