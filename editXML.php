<?php
  session_start();
  $filename = $_GET["filename"];
  $data = "";
  $file = fopen("xml/".$filename,"rb");
  while ($entry = fread($file, 4096)) {
  	$data.=$entry;
  }
?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<script language="javascript" type="text/javascript" src="js/editarea/edit_area_full.js"></script>
<script>
editAreaLoader.init({
    id: "content",
    syntax: "xml",
    language: "ru",
    start_highlight: true
});
</script>
<link rel="stylesheet" type="text/css" href="browse.css"/>
</head>
<body>
<form action="editXMLAction.php" method="POST">
<input type="hidden" name="filename" value="<? echo $filename ?>"/>
<textarea cols="80" rows="20" id="content" name="content"><? echo $data ?></textarea><br/>
<input type="submit" value="Сохранить изменения"></input>
</form>
</body>
</html>
