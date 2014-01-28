<?php
  require_once "render.php";

  $render = new Render("ru");
  $render->invalidateFragment("abit:header");
  $render->invalidateFragment("abit:hello");
  $render->invalidateFragment("abit:content");
  $_SESSION["var_person"] = "Person12";
  echo $render->render("abit:public",array("abit:name"=>"Welcome2"));
?>

