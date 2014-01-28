<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
</head>
<body>
<?php
  session_start();
  if (!empty($_GET["id"])) {
  	$id = $_GET["id"];
  	$file = file("presets/".$id);
  	$description = $file[0];

  	echo "<h1>Редактирование шаблона ".$description."</h1>";
  	echo '<form action="editTemplateAction.php" method="GET">';
  	echo '<input type="hidden" name="id" value="'.$id.'"/>';
  	echo '<label for="title">Название шаблона</label><input type="text" name="title" value="'.$description.'"></input><br/>';
  	$envs = $_SESSION["envs"];
  	$envs = explode(",",$envs);
  	$infile = array();
  	foreach ($file as $rowid=>$row) {
  		if ($rowid==0) continue;
  		$item = substr($row,0,strpos($row,"="));
  		$data = substr($row,strpos($row,"=")+1);
  		$infile[] = $item;
  		echo '<label for="c'.$item.'">'.$item.'</label>';
  		echo '<input name="c'.$item.'" type="text" value="'.$data.'"></input><br/>';
  	}
  	foreach ($envs as $env) {
  		if (array_search($env, $infile)===FALSE) {
  			//new entry from environment
    		echo '<label for="c'.$env.'">'.$env.'</label><input type="text" name="c'.$env.'"></input><br/>';  			
  		}
  	}
  	//template id
  	//preload
  	$newTemplate = false;
  } else {
  	echo "<h1>Создание нового шаблона</h1>";
  	echo '<form action="editTemplateAction.php" method="GET">';
  	echo '<label for="title">Название шаблона</label><input type="text" name="title"></input><br/>';
  	$newTemplate = true;
    $envs = $_SESSION["envs"];
    foreach (explode(",",$envs) as $env) {
    	echo '<label for="c'.$env.'">'.$env.'</label><input type="text" name="c'.$env.'"></input><br/>';
    }
  }
?>
<input type="submit" value="Отправить"></input>
</form>
</body>
</html>
