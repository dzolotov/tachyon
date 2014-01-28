<?php
  session_start();
  $prefix = $_GET["prefix"];
  $instance = $_GET["instance"];
  $file = $prefix."_".$instance;
  if (!empty($instance)) {
    $fileContent = file("data/".$file);
    $acceptData = false;
    $data = "";
    foreach ($fileContent as $row) {
      if (strlen(trim($row))==0) continue;		//skip entries
      if (strpos($row,"}")!==FALSE) break;		//file ends
      if (strpos($row,"{")) {
      	$description = trim(substr($row,0,strpos($row,"{")));
      	$acceptData = true;
      	continue;
      }
      if ($acceptData) {
      	$data.=$row;
      }
    }
  }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
</head>
<body>
<form action="modifyInstanceAction.php" method="POST">
<input type="hidden" name="id" value="<? echo $instance ?>" />
<input type="hidden" name="prefix" value="<? echo $prefix ?>"/>
<label for="title">Название экземпляра объекта</label>
<input type="text" name="title" value="<? echo $description ?>"></input><br/>
<label for="content">Содержание экземпляра объекта</label>
<textarea name="content" cols="40" rows="10"><? echo $data ?></textarea><br/>
<input type="submit" value="Отправить"/>
</form>
</body>
</html>
