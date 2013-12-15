<?php
//error_reporting(0);
define('KEY', "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_=");
define('EXTRA_TYPE', 0x40 | 0x2000 | 0x800000);

$main_deck = array();
$side_deck = array();
$extra_deck = array();

if(isset($_REQUEST['name'])){
    $name = $_REQUEST['name'];
    if(isset($_REQUEST['cards'])){
        $long_url = 'http://my-card.in/decks/new?name='.rawurlencode($_REQUEST['name'])."&cards=$_REQUEST[cards]";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('longUrl' => $long_url )));
        curl_setopt($ch, CURLOPT_URL,'https://www.googleapis.com/urlshortener/v1/url?key=AIzaSyBZw7nZElp2l2BIiRdMgeFp-bhKAuaiIcY');
        $result = json_decode(curl_exec($ch), true);
        if(isset($result['id'])){
            $url = substr($result['id'], 7);
        }
    }
}

if(isset($_REQUEST['cards'])){
    $cards_encoded = $_REQUEST['cards'];
    for($i=0; $i < strlen($cards_encoded); $i += 5){
        $decoded = 0;
        foreach(str_split(substr($cards_encoded, $i, 5)) as $char){
                $decoded = ($decoded << 6) + strpos(KEY, $char);
        }
        $card_id = $decoded & 0x07FFFFFF;
        $side = $decoded >> 29;
        $count = $decoded >> 27 & 0x3;
        for($j=0; $j<$count; $j++){
            if($side){
                $side_deck[]=$card_id;
            }else{
                $main_deck[]=$card_id;
            }
            
        }
    }
}

$card_images = array();
$card_images_size = array();
foreach (array_unique(array_merge($main_deck, $side_deck)) as $card_id) {
    $card_images[$card_id] = imagecreatefromjpeg("/home/zh99998/ygopro-images/thumbnail/$card_id.jpg");
    $card_images_size[$card_id] = getimagesize ( "/home/zh99998/ygopro-images/thumbnail/$card_id.jpg" );
}

$query_cards = array_unique($main_deck);
$query = 'https://my-card.in/cards.json?f={"type":1}&q={"_id":{"$in":['.join($query_cards, ',').']}}';
$extra_cards = array();
foreach (json_decode(file_get_contents($query)) as $card) {
    if($card->type & EXTRA_TYPE){
        $extra_cards[]=$card->_id;
    }
};

$extra_deck = array_values(array_intersect($main_deck, $extra_cards));
$main_deck = array_diff($main_deck, $extra_cards);

$main_count = count($main_deck);
$extra_count = count($extra_deck);
$side_count = count($side_deck);

// Set the enviroment variable for GD
putenv('GDFONTPATH=' . realpath('./font/unifont'));

$width = 500;
$height = 500;
$color = 0xFFFFFF;
$font = 'wqy-microhei001.TTF';
$numfont = 'ARIALBD.TTF';
$result = imagecreatefrompng("bg.png");

#deck url
$box = imagettfbbox( 11 , 0 , $font , $url );
imagefttext($result, 11, 0, 488 - $box[2], 18, $color, $font, $url);

#deck name
$url_width = $box[2];
$box = imagettfbbox( 11 , 0 , $font , $name );
imagefttext($result, 11, 0, 488 - $url_width - 20 - $box[2], 18, $color, $font, $name);

#deck title
imagefttext($result, 11, 0, 12, 18, $color, $font, '主卡组: ');
imagefttext($result, 11, 0, 12, 321, $color, $font, '额外卡组: ');
imagefttext($result, 11, 0, 12, 418, $color, $font, '副卡组: ');

#deck title count
$box = imagettfbbox( 12 , 0 , $font , $main_count );
imagefttext($result, 12, 0, 100 - $box[2], 19, $color, $numfont, $main_count);

$box = imagettfbbox( 12 , 0 , $font , $extra_count );
imagefttext($result, 12, 0, 100 - $box[2], 419, $color, $numfont, $extra_count);

$box = imagettfbbox( 12 , 0 , $font , $side_count );
imagefttext($result, 12, 0, 100 - $box[2], 322, $color, $numfont, $side_count);

#deck title border
imagerectangle ($result , 6 , 2 , 107, 23 , $color);
imagerectangle ($result , 6 , 305 , 107, 326 , $color);
imagerectangle ($result , 6 , 402 , 107, 423 , $color);

#deck border
imagerectangle ($result , 6 , 25 , 494, 302 , $color);
imagerectangle ($result , 6 , 328 , 494, 399 , $color);
imagerectangle ($result , 6 , 425 , 494, 496 , $color);

#deck cards
$main_line_count = ceil($main_count / 4);
$main_deck_chunked = array_chunk ( $main_deck , $main_line_count);

function draw_line($image, $y , $cards, $line_count){
    global $card_images, $card_images_size;
    foreach ($cards as $index => $card_id) {
        list($width, $height) = $card_images_size[$card_id];
        imagecopyresized ( $image , $card_images[$card_id] , 11+436*$index/($line_count-1) , $y , 0 , 0 , 44 , 64 , $width, $height );
    }
}

foreach($main_deck_chunked as $index => $line){
    draw_line($result, 30 + $index * 68, $line, $main_line_count);
}

draw_line($result, 332, $extra_deck, $extra_count);

draw_line($result, 430, $side_deck, $side_count);

$expires = 60*60*24*365;
header("Pragma: public");
header("Cache-Control: public, max-age=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

header("Content-type: image/jpeg");
imagepng($result);
imagedestroy($result);
foreach($card_images as $card_image){
    imagedestroy($card_image);
}
?>
