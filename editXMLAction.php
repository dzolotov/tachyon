<?php
  $filename = $_POST["filename"];
  $content = $_POST["content"];
  $f = fopen("xml/".$filename,"w");
  fwrite($f, $content);
  fclose($f);
?>
<body onload="window.close()"></body>