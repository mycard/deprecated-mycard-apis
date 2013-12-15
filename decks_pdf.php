<?php
//error_reporting(0);
require('tfpdf.php');
	define('KEY', "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_=");
class DeckPDF extends tFPDF
{
    public $count = 0;
    public $name;
    public $url;
    function Footer()
    {
        $this->Image('logo.png', 144, 281, 12, 12);

        $this->SetFont('wqy-microhei','',14);
        $this->SetXY(157, 285);
        $this->Write(0, 'MyCard 打印助手');
        $this->SetXY(157, 290);
        $this->SetFont('wqy-microhei','',10);
        $this->Write(0, 'my-card.in');

        $this->SetFont('wqy-microhei','',16);
        $this->SetXY(100, 287);
        $this->Write(0, '- '.$this->PageNo().' -');


	if($this->name){
          $this->SetFont('wqy-microhei','',14);
          $this->SetXY(20, 285);
          $this->Write(0, $this->name);
        }
	if($this->url){
        $this->SetXY(20, 290);
        $this->SetFont('wqy-microhei','',10);
        $this->Write(0, $this->url);
        }

    }
    function add_card($path, $type=null, $copyright=null){
        if($this->count % 9 == 0){
            $this->AddPage(); //增加一页
        }
	#if($copyright){
	#    $this->SetXY(80 + ($this->count % 3) * 66, 98 + floor(($this->count % 9) / 3) * 90);
	#    $this->SetFont('wqy-microhei','',8);
        #    $this->Write(0, "卡图来源: $copyright");
	#}
        $this->Image($path, 10 + ($this->count % 3) * 66, 10 + floor(($this->count % 9) / 3) * 90, 59, 86, $type);
        $this->count++;
    }
}


$pdf = new DeckPDF('P', 'mm', 'A4'); //创建新的FPDF 对象，竖向放纸，单位为毫米，纸张大小A4
$pdf->AddFont('wqy-microhei','','wqy-microhei001.TTF',true);

$pdf->Open(); //开始创建PDF
$orenoturn_id = json_decode(file_get_contents('/home/zh99998/downloads/images_orenoturn.json'), true);
$images_wikia = json_decode(file_get_contents('/home/zh99998/downloads/images_wikia.json'), true);
$images_kanabell = json_decode(file_get_contents('/home/zh99998/downloads/images_kanabell.json'), true);
if(isset($_REQUEST['name'])){
    $pdf->name = $_REQUEST['name'];
    //header('Content-Disposition: attachment; filename=' . $pdf->name .'.pdf');
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
		$pdf->url = substr($result['id'], 7);
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
                if (file_exists('/home/acd00x0/images/'.$card_id.'.jpg')) {
                        $pdf->add_card("/home/acd00x0/images/".$card_id.'.jpg', null, 'codei');
                }else if (file_exists('/home/acd00x0/images/'.$card_id.'.png')) {
                        $pdf->add_card("/home/acd00x0/images/".$card_id.'.png', null, 'codei');
		}else if(isset($orenoturn_id[$card_id])){
			$pdf->add_card("/home/zh99998/downloads/images/".$orenoturn_id[$card_id].'.jpg', null, 'orenoturn.com');
		}else if(isset($images_wikia[$card_id])){
			$pdf->add_card("/home/zh99998/downloads/images_wikia/".$images_wikia[$card_id], null, 'yugioh.wikia.com');
                }else if(isset($images_kanabell[$card_id])){
                        $pdf->add_card("/home/zh99998/downloads/images_kanabell/".$images_kanabell[$card_id], null, 'ka-nabell.com');
		}else{
			$pdf->add_card("/home/zh99998/ygopro-images/$card_id.jpg", null, 'ygopro');
		}
    	}
}
}

foreach( $_FILES as $name => $file){
    if($file['size']){
        list($width, $height, $type) = getimagesize($file["tmp_name"]);
        if($type){
            $type = image_type_to_extension($type, false);
            $pdf->add_card($file["tmp_name"], $type);
        }
    }
}

$pdf->Output(); //输出PDF 到浏览器


?>
