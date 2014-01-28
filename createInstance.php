<?php
  session_start();
  $prefix = $_GET["prefix"];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
</head>
<body>
<form action="modifyInstanceAction.php" method="POST">
<input type="hidden" name="prefix" value="<? echo $prefix ?>"/>
<label for="title">Название экземпляра объекта</label>
<input type="text" name="title"></input><br/>
<label for="content">Содержание экземпляра объекта</label>
<textarea name="content" cols="40" rows="10"></textarea><br/>
<input type="submit" value="Отправить"/>
</form>
</body>
</html>
