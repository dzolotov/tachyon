<?php
  session_start();
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
<body onload='document.getElementById("filename").focus();'>
<form action="editXMLAction.php" method="POST">
<label for="filename">Название файла</label><input type="text" name="filename" id="filename" required="required"/>
<textarea cols="80" rows="20" id="content" name="content"></textarea><br/>
<input type="submit" value="Сохранить изменения"></input>
</form>
</body>
</html>
