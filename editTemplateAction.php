<?php
  session_start();
  if (!empty($_GET["id"])) {
  	$id = $_GET["id"];
  } else {
  	$id = md5(rand());
  }
  $title = $_GET["title"];
  $f = fopen("presets/".$id,"wt");
  fwrite($f,$title."\n");
  foreach (array_keys($_GET) as $key) {
  	if (substr($key,0,1)=="c") {
  		$key = substr($key,1);
  		fwrite($f,$key."=".$_GET["c".$key]."\n");
  	}
  }
  fclose($f);
  header("Location: browse.php?template:".$id);
?>