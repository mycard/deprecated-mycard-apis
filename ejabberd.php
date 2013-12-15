<?php
  pg_connect("host=127.0.0.1 dbname=mycard-chat user=zh99998 password=zh112998");
  $query = pg_query("select password from users where username=lower('$_GET[name]') LIMIT 1");
  if(pg_num_rows($query)){
    pg_query("update users set password='$_GET[password]' where username=lower('$_GET[name]')");
  }else{
    pg_query("insert into users (username, password) VALUES (lower('$_GET[name]'),'$_GET[password]')");
  }
  echo(pg_last_error());
?>
