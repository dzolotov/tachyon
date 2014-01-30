<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
  <title>jQuery UI Tabs - Default functionality</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.9.1.js"></script>
  <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
  <link rel="stylesheet" type="text/css" href="browse.css"/>
  <script>
  $(function() {
    $( "#tabs" ).tabs();
  });
  </script>
  </head>
<body>
<?php
session_start();
require_once "render.php";
?>
<script>
<!--
function change(definition) {
    oldHref = document.location.href;
    hash = "";
    if (oldHref.indexOf("#")>=0) hash=oldHref.substring(oldHref.indexOf("#"));
	document.location.href="?"+definition+hash;
}

function selectTemplate() {
    oldHref = document.location.href;
    hash = "";
    if (oldHref.indexOf("#")>=0) hash=oldHref.substring(oldHref.indexOf("#"));
	t = document.getElementById("template");
	vl = t.options[t.selectedIndex].value;
	document.location.href="?template:"+vl+hash;
}

function selectData(obj) {
  oldHref = document.location.href;
  hash = "";
  if (oldHref.indexOf("#")>=0) hash=oldHref.substring(oldHref.indexOf("#"));
  value = obj.options[obj.selectedIndex].value;
  document.location.href="?data:"+value+hash;
}

function createInstance(prefix) {
  document.location.href = 'modifyInstance.php?prefix='+prefix;
}

function editInstance(prefix,instance) {
  document.location.href = 'modifyInstance.php?prefix='+prefix+'&instance='+instance;
}

function createDatasource() {
  prefix = document.getElementById("datasource").value;
  document.location.href = 'modifyInstance.php?prefix='+prefix;
}

function editXML(id) {
    window.open('editContent.php?type=xml&filename='+id,"_blank","location,width=800,height=800");
//    document.location.href = ;
}

function newTemplate() {
    window.open('editContent.php?type=xml',"_blank","location,width=800,height=800");
}

function editCSS(id) {
    window.open('editContent.php?type=css&filename='+id,"_blank","location,width=800,height=800");
//    document.location.href = ;
}

function newCSS() {
    window.open('editContent.php?type=css',"_blank","location,width=800,height=800");
}

-->
</script>
<?php


function sublist($xmls, $parent, &$fragments, &$descriptions, &$params, &$parents) {
    echo "<ul>";
    foreach ($xmls as $file) {
        if (is_dir("xml/".$file)) continue;
        $file = trim($file);
        if (empty($parent) && !empty($parents[$file])) continue;
        if ($parents[$file]!=$parent) continue;
        $fragment = $fragments[$file];
        $description = $descriptions[$file];
        echo "<li><div class=\"xmlreference\"><a href='view.php?" . $fragment . "'>" . $file . (($description !== FALSE) ? " (" . $description . ")" : "") . "</a></div>";
        echo '<img id="xmlchange_button" src="edit-5-m.png" onclick="editXML(\''.$file.'\')"/>';
        $parameters = $params[$file];
        // var_dump($params);
        foreach (explode(",", $params) as $param) {
            if (substr($param, 0, 4) == "env:") {
                echo "&nbsp;<i>" . $param . "</i>";
                $pm = substr($param, 4);
                if (array_search($pm, $envs) === FALSE) $envs[] = $pm;
            } else {
                echo "&nbsp;" . $param;
            }
        }
        sublist($xmls, $fragment, $fragments, $descriptions, $params, $parents);
        echo "</li>";
    }
    echo "</ul>";
} 

require_once "sessionds.php";
$langDescription = array();
$langDescription["ru"] = "Русский";
$langDescription["en"] = "Английский";
$langDescription[""] = "Любой";
$css = scandir("css");
$themes = array();
$languages = array();
//scan for defined themes

$shift = 0;

$css_selectors = array();

foreach ($css as $file) {
    $css_selectors[$file] = "";
    if (is_dir("css/".$file)) continue;
    if (substr($file, strlen($file) - 4, 4) == ".css") {
        $cssContent = file("css/" . $file);
        // var_dump($cssContent);
        foreach ($cssContent as $str) {
            $inlinePos = 0;
            $closingTagFound = false;
            while (strpos($str,"}",$inlinePos)!==FALSE) {
                // echo "minus $shift in <$str>";
                $shift--;
                $closingTagFound = true;
                $inlinePos = strpos($str,"}",$inlinePos)+1;
            }
            if ($closingTagFound) continue;
            $selectorStart = strpos($str, "{");
            if ($selectorStart !== FALSE) {
                // echo "plus $shift in <$str>";
                $shift++;
                if ($shift!=1) continue;        //not the first level
                $selector = trim(substr($str, 0, $selectorStart));
                $css_selectors[$file][] = $selector;
                //bind selector with file

                $themeDelimiter = strpos($selector, "@");
                if ($themeDelimiter !== FALSE) {
                    //theme defined
                    $theme = "";
                    $fragmentDelimiter = strpos($selector, "#", $themeDelimiter);
                    $languageDelimiter = strpos($selector, "!", $themeDelimiter);
                    if ($languageDelimiter !== FALSE) {
                        $theme = substr($selector, $themeDelimiter + 1, $languageDelimiter - $themeDelimiter - 1);
                    } else if ($fragmentDelimiter !== FALSE) {
                        $theme = substr($selector, $themeDelimiter + 1, $fragmentDelimiter - $themeDelimiter - 1);
                    } else $theme = substr($selector, $themeDelimiter + 1);
                    $theme = trim($theme);
                    if (array_search($theme, $themes) === FALSE) $themes[] = $theme;
                }
            }
        }
    }
}

//scan for languages
$xmls = scandir("xml");
$fragments = array();
foreach ($xmls as $file) {
    if (is_dir("xml/".$file)) continue;
    if (substr($file, strlen($file) - 4, 4) == ".xml") {
        $xmlContent = file("xml/" . $file);
        $firstTag = FALSE;
        foreach ($xmlContent as $str) {
            if ($firstTag === FALSE && strpos($str, "<!") === FALSE && strpos($str, "<") !== FALSE) { //search for first tag
                $tagStart = strpos($str, "<");
                $endpos = strpos($str, ">", $tagStart);
                $endposAttr = strpos($str, " ", $tagStart);
                if ($endposAttr !== FALSE && $endposAttr < $endpos) $endpos = $endposAttr;
                $firstTag = substr($str, $tagStart + 1, $endpos - $tagStart - 1);
            }
            $pos = strpos($str, "lang=\"");
            if ($pos !== FALSE) {
                $endpos = strpos($str, "\"", $pos + 6);
                $languageCode = substr($str, $pos + 6, $endpos - ($pos + 6));
                if (array_search($languageCode, $languages) === FALSE) $languages[] = $languageCode;
            }
        }
        $fragments[$file] = $firstTag;
    }
}
// var_dump($fragments);
// die;
// 		$languageStart = strpos($str,"!");
// if ($languageStart!==FALSE) {
// 	$languageCode = substr($str,$languageStart+1,strpos($str,"{")-$languageStart-2);
// 	$fragmentStart = strpos($str,"#",$languageStart);
// 	if ($fragmentStart!==FALSE) $languageCode = substr($str,$languageStart,$fragmentStart-$languageStart);
// 	$languageCode = trim($languageCode);
//
// }
function applyTemplate() {
    global $render;
    $template = $_SESSION["activeTemplate"];
    $data = file("presets/" . $template);
    foreach ($data as $rowid => $row) {
        if ($rowid == 0) continue;
        $render->invalidateSession($item);
    }
}
//extract themes
$dataSource = null;
$invalidateDatasource = false;
$uri = $_SERVER["REQUEST_URI"];
if (strpos($uri, "?") !== FALSE) {
    $uripart = trim(substr($uri, strpos($uri, "?") + 1));
    if ($uripart != "") {
        if (substr($uripart, 0, 5) == "lang:") {
            $_SESSION["language"] = substr($uripart, 5);
        };
        if (substr($uripart, 0, 6) == "theme:") {
            $_SESSION["theme"] = substr($uripart, 6);
        };
        if (substr($uripart, 0, 9) == "template:") {
            $_SESSION["activeTemplate"] = substr($uripart, 9);
            //        		applyTemplate();
            
        }
        if (substr($uripart, 0, 5) == "data:") {
            $dataRef = substr($uripart, 5);
            $alias = substr($dataRef, 0, strpos($dataRef, "_"));
            $ref = substr($dataRef, strpos($dataRef, "_") + 1);
            //echo "Assigning ".$alias." to ".$ref;
            $_SESSION["bind_" . $alias] = $ref;
        }
        if (substr($uripart, 0, 3) == "ds:") {
            $dataSource = new DataSource();
            $ds = substr($uripart, 3);
            $objId = $dataSource->getObjectId($ds);
            // echo "OID: ".$objId;
            $invalidateDatasource = true;
        }
    }
}
if (empty($dataSource)) $dataSource = new DataSource();
$render = new Render($_SESSION["language"], $_SESSION["theme"], $dataSource);
if ($invalidateDatasource) $render->invalidateObject($ds, $objId);
if (!isset($_SESSION["language"])) $_SESSION["language"] = "ru";
?>
<div id="tabs">
<ul>
<li><a href="#xml">XML шаблоны</a></li>
<li><a href="#css">CSS правила</a></li>
<li><a href="#context">Контекст</a></li>
<li><a href="#data">Связанные данные</a></li>
</ul>
<div id="xml">
<div id="languages">
<?php
foreach ($languages as $language) {
    echo '<span style="cursor:pointer; font-weight: ' . ($language == $_SESSION["language"] ? "bold" : "normal") . '"';
    echo ' onclick="change(\'lang:' . $language . '\')">' . $langDescription[$language] . '</span>&nbsp';
}
?>
</div>
<div id="themes">
<?php
foreach ($themes as $theme) {
    echo '<span style="cursor:pointer; font-weight: ' . ($theme == $_SESSION["theme"] ? "bold" : "normal") . '"';
    echo ' onclick="change(\'theme:' . $theme . '\')">' . $theme;
    echo '</span>&nbsp;';
}
?>
</div>
<div id="templatename">Шаблоны верстки</div>
<div id="templates">
<?php
$envs = array();
$render->refreshXML();
//applyTemplate();

$descriptions = array();
$params = array();
$parents = array();

foreach ($xmls as $file) {
    $file = trim($file);
    if (substr($file, strlen($file) - 4, 4) == ".xml") {
        $description = false;
        $f = file("xml/" . $file);
        foreach ($f as $fp) {
            $dpos = strpos($fp, "<!--");
            if ($dpos !== FALSE) {
                $dend = strpos($fp, "-->", $dpos);
                $description = trim(substr($fp, $dpos + 4, $dend - $dpos - 4));
                break;
            }
        }
        $descriptions[$file] = $description;
        $fragment = $fragments[$file];
        $params[$file] = $render->getParams($fragment);
        $parents[$file] = $render->getParent($fragment);
    }
}

sublist($xmls, null, $fragments, $descriptions, $params, $parents);
// foreach ($xmls as $file) {
//     if ($file[0]==".") continue;
//     $file=trim($file);
// }
// echo "</ul>";
?>
<button onclick="newTemplate()" class="action_button">Создать новый шаблон</button>
</div>
</div>
<div id="css">
<h3>Фильтрация</h3>
<div id="languages">
<?php
$planguages = $languages;
$planguages[] = "";
foreach ($planguages as $language) {
    echo '<span style="cursor:pointer; font-weight: ' . ($language == $_SESSION["language"] ? "bold" : "normal") . '"';
    echo ' onclick="change(\'lang:' . $language . '\')">' . $langDescription[$language] . '</span>&nbsp';
}
?>
</div>
<div id="themes">
<?php
$pthemes = $themes;
$pthemes[] = "";
foreach ($pthemes as $theme) {
    echo '<span style="cursor:pointer; font-weight: ' . ($theme == $_SESSION["theme"] ? "bold" : "normal") . '"';
    echo ' onclick="change(\'theme:' . $theme . '\')">' . (empty($theme) ? "любая":$theme);
    echo '</span>&nbsp;';
}
?>
</div>
<ul>
<?php

$activeLanguage = $_SESSION["language"];
$activeTheme = $_SESSION["theme"];

foreach ($css_selectors as $filename=>$selectors) {
    if ($filename[0]==".") continue;
    $firstMatch = true;

    foreach ($selectors as $selector) {
        $theme = "";
        $language = "";
        $variant = "";
        $fragment = "";

        $originalSelector = $selector;

        $variantDelimiter = strpos($selector,"#");
        $langDelimiter = strpos($selector,"!");
        $themeDelimiter = strpos($selector,"@");

        if ($variantDelimiter!==FALSE) {
            $variant = substr($selector,$variantDelimiter+1);
            $selector = substr($selector, 0, $variantDelimiter);
        }
        if ($langDelimiter!==FALSE) {
            $language = substr($selector,$langDelimiter+1);
            $selector = substr($selector, 0, $langDelimiter);
        }

        if ($themeDelimiter!==FALSE) {
            $theme = substr($selector, $themeDelimiter+1);
            $selector = substr($selector, 0, $themeDelimiter);
        }

        $fragment = $selector;

        $match = true;
        if (!($language==$activeLanguage || $language=="" || $activeLanguage=="")) $match = false;
        if (!($theme==$activeTheme || $theme=="" || $activeTheme=="")) $match = false;
        
        if ($match) {
            if ($firstMatch) {
                echo "<li><a href=\"#\" onclick=\"editCSS('".$filename."')\">".$filename."</a><ul>";
                $firstMatch = false;
            }
            echo "<li>".$originalSelector."</li>";
        }
    }
    if (!$firstMatch) echo "</ul>";
}
?>
</ul>
</div>
<div id="context">
<strong>Активный шаблон сеанса</strong><br/>
<select id='template' onchange='selectTemplate()'>
//todo: selected
<?
$activeTemplate = $_SESSION["activeTemplate"];
echo "<option value=''" . (empty($activeTemplate) ? " SELECTED" : "") . ">-</option>";
$presets = scandir("presets");
foreach ($presets as $preset) {
    if (is_dir("presets/".$preset)) continue;
//    if ($preset[0] == ".") continue;
    $content = file("presets/" . $preset);
    $description = $content[0];
    echo '<option value="' . $preset . '"' . ($activeTemplate == $preset ? " SELECTED" : "") . '>' . $description . '</option>';
}
echo "</select>";
if (!empty($activeTemplate)) echo '<a href="editTemplate.php?id=' . $activeTemplate . '">Изменить шаблон</a>';
echo '<br/><a href="editTemplate.php">Создать шаблон</a><br/>';
echo "<ul>";
$_SESSION["envs"] = implode(",", $envs);
foreach ($envs as $env) {
    echo "<li>" . $env . "=" . $dataSource->getEnv($env) . "</li>";
}
echo "</ul>";
echo "</div>";
echo "<div id=\"data\">";
echo "<strong>Источники данных</strong><br/>";
$data = scandir("data");
$prefixes = array();
$entries = array();
$description = "";
foreach ($data as $file) {
    if (is_dir("data/".$file)) continue;
    $prefix = substr($file, 0, strpos($file, "_"));
    $key = substr($file, strpos($file, "_") + 1);
    if (array_search($prefix, $prefixes) === FALSE) $prefixes[] = $prefix;
    $fileContent = file("data/" . $file);
    foreach ($fileContent as $row) {
        if (strpos($row, "{") === FALSE) continue;
        $description = trim(substr($row, 0, strpos($row, "{")));
    }
    $entries[$prefix][$key] = $file . ":" . $description;
}
echo "<ul>";
foreach ($prefixes as $prefix) {
    $selection = $_SESSION["bind_" . $prefix];
    if (empty($selection)) $selection = "";
    echo "<li>" . $prefix;
    if (!empty($selection)) {
        $refx = $entries[$prefix][$selection];
        echo ": " . substr($refx, strpos($refx, ":") + 1);
    }
    echo "&nbsp;<em>Выбрать: </em>";
    echo "<select id='data_" . $prefix . "' onchange='selectData(this)'>";
    echo '<option value="' . $prefix . '_"' . (empty($selection) ? ' SELECTED' : '') . '></option>';
    foreach ($entries[$prefix] as $fileref) {
        $file = substr($fileref, 0, strpos($fileref, ":"));
        $desc = substr($fileref, strpos($fileref, ":") + 1);
        echo '<option value="' . $file . '"' . (($prefix . "_" . $selection == $file) ? ' SELECTED' : '') . '>' . $desc . '</option>';
    }
    echo "</select>";
    if (!empty($selection)) {
        echo '<input type="button" value="Изменить экземпляр" onclick="editInstance(\'' . $prefix . '\',\'' . $selection . '\')"/>';
    }
    echo '<input type="button" value="Создать экземпляр" onclick="createInstance(\'' . $prefix . '\')"/>';
    echo '</li>';
}
echo "</ul>";
?>
</ul>
<input id="datasource" name="datasource" type="text"></input><button onclick='createDatasource()'>Создать новый тип источника данных</button>
</div></div>
</body>
</html>
