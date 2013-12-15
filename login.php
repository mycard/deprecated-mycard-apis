<?php
  $name = $_REQUEST['name'];
  $password = $_REQUEST['password'];
  //auth from mycard
  $conn = pg_connect("host=127.0.0.1 dbname=mycard-chat user=zh99998 password=zh112998");
  $result= pg_select($conn, 'users', array('username' => strtolower($name), 'password' => $password));
  pg_close();
  if($result){
    echo('true');
  }else{
    //auth from google
    include("XMPPHP/XMPP.php");
    $conn = new XMPPHP_XMPP('xmpp.l.google.com', 5222, $name, $password, 'mycard-server', 'gmail.com');
    try {  
      $conn->connect();  
      $conn->processUntil('session_start');  
      $conn->disconnect();  
      echo('true');
    } catch(XMPPHP_Exception $e) {  
      echo('false');
    }  
  }
?>
