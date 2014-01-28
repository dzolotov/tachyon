<?php
  session_start();
  require_once "sessionds.php";

  $fragment = $_SERVER["REQUEST_URI"];
  if (strpos($fragment, "?")!==FALSE) {
  	$fragment = substr($fragment,strpos($fragment,"?")+1);
  }
  require_once "render.php";
  $ds = new DataSource();
  $render = new Render($_SESSION["language"],$_SESSION["theme"],$ds);
  echo $render->render($fragment,"/",array());		//define locator and parameters
?>