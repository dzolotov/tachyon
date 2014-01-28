<?php
  session_start();
  //var_dump($_POST);

  $prefix = $_POST["prefix"];
  $title = $_POST["title"];
  $content = $_POST["content"];
  $data = "{ ".$content." }";
  $data = str_replace("\n", " ", $data);
  $data = str_replace("\r", " ", $data);
  if (json_decode($data)==NULL) {
  	echo "<hr/>";
  	echo "<pre>".$data."</pre>";
  	echo "Некорректные данные!";
  	die;
  }
  $id = $_POST["id"];
  if (empty($id)) {
  	//create new
  	$id = md5(rand());
    $_SESSION["bind_".$prefix]=$id;
  }

  $filename = "data/".$prefix."_".$id;
  $fx = fopen($filename,"wt");
  fwrite($fx, $title." {\n".trim($content)."\n} ");
  fclose($fx);
  header("Location: browse.php?ds:".$prefix);
?>