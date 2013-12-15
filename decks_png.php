<?php
//error_reporting(0);
define('KEY', "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_=");

define('DEFAULT_WIDTH', 518);
define('DEFAULT_HEIGHT', 442);

$main_types = array(0x1, 0x2, 0x4);
$extra_types = array(0x2000, 0x800000, 0x40);


if(isset($_GET['width'])){
    $width = $_GET['width'];
}else{
    if(isset($_GET['height'])){
        $width = $_GET['height'] * DEFAULT_WIDTH / DEFAULT_HEIGHT;
    }else{
        $width = DEFAULT_WIDTH;
    }
}

if(isset($_GET['height'])){
    $height = $_GET['height'];
}else{
    $height = $width * DEFAULT_HEIGHT / DEFAULT_WIDTH;
}


$main_deck = array();
$side_deck = array();
$extra_deck = array();

if(isset($_REQUEST['name'])){
    $name = $_REQUEST['name'];
    if(isset($_REQUEST['cards']) && !(isset($_REQUEST['url']) && ($_REQUEST['url'] === 'false'))){
        $long_url = 'http://my-card.in/decks/new?name='.rawurlencode($_REQUEST['name'])."&cards=$_REQUEST[cards]";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('longUrl' => $long_url )));
        curl_setopt($ch, CURLOPT_URL,'https://www.googleapis.com/urlshortener/v1/url?key=AIzaSyBZw7nZElp2l2BIiRdMgeFp-bhKAuaiIcY');
        $res = json_decode(curl_exec($ch), true);
        if(isset($res['id'])){
            $url = substr($res['id'], 7);
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

$query_cards = array_unique($main_deck);
$query = 'https://my-card.in/cards.json?f={"type":1}&q={"_id":{"$in":['.join($query_cards, ',').']}}';
$main_counts = array_fill(0, count($main_types), 0);
$extra_counts = array_fill(0, count($extra_types), 0);

$cards_type = array();

$extra_type = 0;
foreach ($extra_types as $type) {
    $extra_type |= $type;
}
$main_type = 0;
foreach ($main_types as $type) {
    $main_type |= $type;
}
$types_main = array_flip($main_types);
$types_extra = array_flip($extra_types);

foreach (json_decode(file_get_contents($query)) as $card) {
    $cards_type[$card->_id] = $card->type;
};


foreach ($main_deck as $index => $card_id) {
    $card_type = $cards_type[$card_id];
    if($card_type & $extra_type){
        unset($main_deck[$index]);
        $extra_deck[]=$card_id;
        $extra_counts[$types_extra[$card_type & $extra_type]]++;
    }else{
        $main_counts[$types_main[$card_type & $main_type]]++;
    }
}

$main_count = count($main_deck);
$extra_count = count($extra_deck);
$side_count = count($side_deck);

// Set the enviroment variable for GD
putenv('GDFONTPATH=' . realpath('./font/unifont'));

$color = 0xFFFFFF;
$shadow_color = 0;
$font = 'wqy-microhei001.TTF';
$numfont = 'ARIALBD.TTF';
$result = imagecreatetruecolor($width, $height);
//imageinterlace($result, true);

if($result === false){
    header("HTTP/1.1 406 Not Acceptable");
    echo('bad size');
    exit();
}

#bg
$bg = imagecreatefromjpeg("bg.jpg");
list($bg_width, $bg_height) = getimagesize("bg.jpg");

$aspect_ratio = $width / $height;
$scale = $height / 512;
if($aspect_ratio > $bg_width / $bg_height){
    imagecopyresampled($result, $bg, 0, 0, 0, ($bg_height - $bg_width/$aspect_ratio)/2, $width , $height , $bg_width, $bg_width/$aspect_ratio);
}else{
    imagecopyresampled($result, $bg, 0, 0, ($bg_width - $bg_height*$aspect_ratio)/2, 0, $width , $height , $bg_height*$aspect_ratio, $bg_height);
}
imagedestroy($bg);

#deckbg
$deckbg = imagecreatefrompng("deckbg.png");
list($deckbg_width, $deckbg_height) = getimagesize("deckbg.png");
#left
imagecopyresampled($result, $deckbg, 0, 0, 0, 0, $height*36/$deckbg_height, $height , 36, $deckbg_height);
#middle
imagecopyresampled($result, $deckbg, $height*36/$deckbg_height, 0, 36, 0, $width - $height*(36+12)/$deckbg_height + 0.2, $height , $deckbg_width-(36+12), $deckbg_height);
#right
imagecopyresampled($result, $deckbg, $width-$height*12/$deckbg_height, 0, $deckbg_width-12, 0, $height*12/$deckbg_height, $height , 12, $deckbg_height);
imagedestroy($deckbg);

#deck count
$deck_count_font_size = 13 * $scale;

foreach ($main_counts as $index => $count) {
    $box = imagettfbbox( $deck_count_font_size, 0, $numfont, $count);
    #imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2] + 1, (38+$index*22) * $scale + 1, $shadow_color, $numfont, $count);
    imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2], (38+$index*22) * $scale, $color, $numfont, $count);
}

foreach ($extra_counts as $index => $count) {
    $box = imagettfbbox( $deck_count_font_size, 0, $numfont, $count);
    #imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2] + 1, (115+$index*22) * $scale + 1, $shadow_color, $numfont, $count);
    imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2], (115+$index*22) * $scale, $color, $numfont, $count);
}
$box = imagettfbbox( $deck_count_font_size, 0, $numfont, $main_count);
#imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2] + 1, 272 * $scale + 1, $shadow_color, $numfont, $main_count);
imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2], 272 * $scale, $color, $numfont, $main_count);

$box = imagettfbbox( $deck_count_font_size, 0, $numfont, $extra_count);
#imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2] + 1, 351 * $scale + 1, $shadow_color, $numfont, $extra_count);
imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2], 351 * $scale, $color, $numfont, $extra_count);
$box = imagettfbbox( $deck_count_font_size, 0, $numfont, $side_count);
#imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2] + 1, 436 * $scale + 1, $shadow_color, $numfont, $side_count);
imagefttext($result, $deck_count_font_size, 0, 36 * $scale - $box[2], 436 * $scale, $color, $numfont, $side_count);

$card_height = 74 * $scale;
$card_width = $card_height * 177 / 254;

$card_images = array();
$card_images_size = array();

if($card_height <= 64){
    foreach (array_unique(array_merge($main_deck, $extra_deck, $side_deck)) as $card_id) {
        $card_images[$card_id] = imagecreatefromjpeg("/home/zh99998/ygopro-images/thumbnail/$card_id.jpg");
        $card_images_size[$card_id] = getimagesize ( "/home/zh99998/ygopro-images/thumbnail/$card_id.jpg" );
        if ($card_images[$card_id] === false) {
            header("HTTP/1.1 404 Not Found");
            echo('Unknown card  '.$card_id);
            exit();
        }
    }
}else if($card_height <= 254){
    foreach (array_unique(array_merge($main_deck, $extra_deck, $side_deck)) as $card_id) {
        $card_images[$card_id] = imagecreatefromjpeg("/home/zh99998/ygopro-images/$card_id.jpg");
        $card_images_size[$card_id] = getimagesize ( "/home/zh99998/ygopro-images/$card_id.jpg" );
        if ($card_images[$card_id] === false) {
            header("HTTP/1.1 404 Not Found");
            echo('Unknown card  '.$card_id);
            exit();
        }
    }
}else{
    $orenoturn_id = json_decode(file_get_contents('/home/zh99998/downloads/images_orenoturn.json'), true);
    $images_wikia = json_decode(file_get_contents('/home/zh99998/downloads/images_wikia.json'), true);
    $images_kanabell = json_decode(file_get_contents('/home/zh99998/downloads/images_kanabell.json'), true);
    foreach (array_unique(array_merge($main_deck, $extra_deck, $side_deck)) as $card_id) {
        if (file_exists('/home/acd00x0/images/'.$card_id.'.jpg')) {
            $file = "/home/acd00x0/images/".$card_id.'.jpg';
        }else if (file_exists('/home/acd00x0/images/'.$card_id.'.png')) {
            $file = "/home/acd00x0/images/".$card_id.'.png';
        }else if(isset($orenoturn_id[$card_id])){
            $file = "/home/zh99998/downloads/images/".$orenoturn_id[$card_id].'.jpg';
        }else if(isset($images_wikia[$card_id])){
            $file = "/home/zh99998/downloads/images_wikia/".$images_wikia[$card_id];
        }else if(isset($images_kanabell[$card_id])){
            $file = "/home/zh99998/downloads/images_kanabell/".$images_kanabell[$card_id];
        }else{
            $file = "/home/zh99998/ygopro-images/$card_id.jpg";
        }
        $card_images_size[$card_id] = getimagesize($file);
        list($image_width, $image_height, $image_type) = $card_images_size[$card_id];
        switch($image_type) {
            case IMAGETYPE_GIF:
            $card_images[$card_id] = imagecreatefromgif($file);
            break;
        case IMAGETYPE_JPEG:
            $card_images[$card_id] = imagecreatefromjpeg($file);
            break;
        case IMAGETYPE_PNG:
            $card_images[$card_id] = imagecreatefrompng($file);
            break;
        }
        if ($card_images[$card_id] === false) {
            header("HTTP/1.1 404 Not Found");
            echo('Unknown card  '.$card_id);
            exit();
        }
    }
}

$line_width = $width - (40+18+44) * $scale;

function draw_line($image, $y , $cards, $line_count){
    global $card_images, $card_images_size, $card_width, $card_height, $width, $height, $line_width, $scale;
    foreach ($cards as $index => $card_id) {
        list($card_width_orig, $card_height_orig) = $card_images_size[$card_id];
        imagecopyresampled($image, $card_images[$card_id], 40 * $scale + min($line_width / ($line_count-1), ($card_width + 4*$scale)) * $index, $y, 0, 0, $card_width, $card_height, $card_width_orig, $card_height_orig);
    }
}

#deck cards
$main_line_count = max(ceil($main_count / 4), floor($line_width / $card_width));
$main_deck_chunked = array_chunk ( $main_deck , $main_line_count);

foreach($main_deck_chunked as $index => $line){
    draw_line($result, (20 + $index * 77) * $scale, $line, $main_line_count);
}

draw_line($result, 336 * $scale, $extra_deck, max($extra_count,10));

draw_line($result, 422 * $scale, $side_deck, max($side_count,10));

#deck url
if(isset($url)){
    $box = imagettfbbox(10, 0, $font, $url);
    imagefttext($result, 10, 0, $width - $box[2] - 8 + 1, $height - 10 + 1, $shadow_color, $font, $url);
    imagefttext($result, 10, 0, $width - $box[2] - 8, $height - 10, $color, $font, $url);
}

#deck name
if(isset($name)){
    $box = imagettfbbox(10 , 0 , $font , $name );
    imagefttext($result, 10, 0, $width - $box[2] - 8 + 1, $height - (isset($url) ? 26 : 10) + 1, $shadow_color, $font, $name);    
    imagefttext($result, 10, 0, $width - $box[2] - 8, $height - (isset($url) ? 26 : 10), $color, $font, $name);    
}


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