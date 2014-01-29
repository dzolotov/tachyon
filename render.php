<?php
session_start();
class Render {
    /**
     * Construct renderer instance
     * @param $language Render language
     * @param $theme Theme name
     *
     */
    function __construct($language, $theme, $dataSource) {
        $this->memcache = new Memcached;
        $this->memcache->setOption(\Memcached::OPT_COMPRESSION, false);
        $this->memcache->setOption(\Memcached::OPT_TCP_NODELAY, true);
        $this->memcache->addServer("127.0.0.1", 11215);
        $this->language = $language;
        $this->theme = $theme;
        $this->dataSource = $dataSource;
    }
    function getParams($fragment) {
        if ($this->memcache->get("params:" . $fragment) === FALSE) {
            $this->depends(null, $fragment);
        }
        return $this->memcache->get("params:" . $fragment);
    }

    function getParent($fragment) {
        return $this->memcache->get("parent:" . $fragment);

    }
    //internal function - add to comma separated list
    function append($key, $value) {
        if (($this->memcache->append($key, "," . $value)) === FALSE) {
            $this->memcache->set($key, $value);
        }
    }
    //resolve expression to value
    function resolve($el) {
        // echo "Resolving ".$el."\n";
        $el = trim($el);
        if (strpos($el, ":") === FALSE && strpos($el, "//") === FALSE) {
            return $el; //direct string

        }
        $el = trim($el);
        if (strpos($el, "//") !== FALSE) {
            //define binded data
            $alias = substr($el, 2, strpos($el, "/", 2) - 2);
            $path = substr($el, strpos($el, "/", 2) + 1);
            //alias - data object alias
            //path - path for extraction
            // var_dump($this->dataSource);
            return $this->dataSource->retrieveData($alias, $path);
        }
        if (strpos($el, ":") !== FALSE) {
            // echo "Parsing tag";
            $namespace = substr($el, 0, strpos($el, ":"));
            $tag = substr($el, strpos($el, ":") + 1);
            // var_dump($namespace, $tag);
            if ($namespace != "r" && $namespace != "env") {
                return $this->parameters[$el]; //get local attribute

            } else if ($namespace == "r") {
                //todo: content
            } else {
                if ($namespace == "env") {
                    //get environment variable
                    return $this->dataSource->getEnv($tag);
                    //	    return $_SESSION["var_".$tag];

                }
            }
        }
    }
    //resolve expression to object
    function resolveObject($el) {
        // echo "Resolving ".$el."\n";
        $el = trim($el);
        if (strpos($el, ":") === FALSE && strpos($el, "//") === FALSE) {
            return $el; //direct string

        }
        $el = trim($el);
        if (strpos($el, "//") == 0) {
            //define binded data
            //  var_dump($el);
            if (strpos($el, "/", 2) !== FALSE) {
                $alias = substr($el, 2, strpos($el, "/", 2) - 2);
            } else {
                $alias = substr($el, 2);
            }
            return $this->dataSource->getObjectId($alias);
        }
        if (strpos($el, ":") !== FALSE) {
            $namespace = substr($el, 0, strpos($el, ":"));
            $tag = substr($el, strpos($el, ":") + 1);
            if ($namespace != "r" && $namespace != "env") {
                return $this->parameters[$el]; //get local attribute

            } else {
                if ($namespace == "env") {
                    //get environment variable
                    return $this->dataSource->getEnv($tag);
                    //	    return $_SESSION["var_".$tag];

                }
            }
        }
        //process el

    }
    function hasExpression($value) {
        if (strpos($value, "//") !== FALSE) {
            return true; //reference to data source

        }
        if (strpos($value, ":") !== FALSE) {
            $namespace = substr($value, 0, strpos($value, ":"));
            if ($namespace == "env") return true; //reference to environment

        }
        return false;
    }
    function regExpression($val) {
        //  foreach (explode(",",$this->track) as $fragment) {
        $this->injectedParameters[] = $val;
        //    $this->append("params:".$fragment,$val);		//todo: lazy adding
        //  }

    }
    function registerExpression($value) {
        if (strpos($value, "//") !== FALSE) {
            $st = strpos($value, "//") + 2;
            $fn = strpos($value, "/", $st);
            $alias = substr($value, $st, $fn - $st);
            $this->regExpression("//" . $alias);
            return;
        }
        if (strpos($value, ":") !== FALSE) {
            $this->regExpression($value);
        }
    }

    //process opening tag
    function start_element($parser, $element_name, $element_attrs) {
        //  echo "Start: ".$element_name."\n";
        //  echo $this->mode;
        $element_name = strtolower($element_name);
        if (empty($this->firstTag)) {
            //    echo "Reset first tag"
            if ($this->top) {
                //build basic html structure
                $this->content.= "<!DOCTYPE html><html><head>";
                $this->content.= "<link type=\"text/css\" rel=\"stylesheet\" href=\"cache/" . $this->cssFileSelector . ".css" . "\"/><meta charset=\"utf-8\"/></head><body>";
            }
            $this->firstTag = $element_name;
            $this->context = $element_attrs;
            //    echo "Context for first tag ".$this->firstTag."\n";
            //    if ($this->mode=="depends") {
            //      $this->memcache->delete("params:".$element_name);
            //    }
            $names = array();
            $this->contextData = array();
            foreach ($element_attrs as $attr_name => $attr_value) {
                $attr_name = strtolower($attr_name);
                $names[] = $attr_name;
                //      echo "Attributes: ".$attr_name."=".$attr_value."\n";
                if (array_key_exists($attr_name, $this->parameters)) {
                    //	echo "Value overrided to ".$this->parameters[$attr_name]."\n";
                    $this->contextData[$attr_name] = $this->parameters[$attr_name]; //set to overridede value

                } else {
                    $this->contextData[$attr_name] = $attr_value; //set default value

                }
            }
            sort($names);
            if ($this->mode == "depends") {
                foreach ($names as $name) {
                    //        echo "Append attribute to dependencies list: ".$name." to ".$element_name."\n";
                    $this->append("params:" . $element_name, $name);
                }
            }
            return;
        }
        if ($this->mode == "scan") return;
        if ($this->mode == "depends") {
            //	echo "DEP";
            if (strpos($element_name, ":") !== FALSE) {
                $namespace = substr($element_name, 0, strpos($element_name, ":"));
                $tagName = substr($element_name, strpos($element_name, ":") + 1);
                if ($namespace=="r" && $tagName=="content") {
                    $this->injectedParameters[] = "@".$element_attrs["ID"];     //inject content
                }
                if ($namespace != "r") {
                    //	    echo "Registered dependency... "."parent:".strtolower($element_name)." = ".strtolower($this->firstTag)."\n";
                    $this->memcache->set("parent:" . strtolower($element_name), strtolower($this->firstTag));
                    $this->embeddedFragments[] = $element_name;
                }
                foreach ($element_attrs as $attr_name => $attr_value) {
                    $attr_name = strtolower($attr_name);
                    //check for expression
                    if ($this->hasExpression($attr_value)) {
                        //register expression
                        $this->registerExpression($attr_value);
                    }
                }
            }
            return;
        }
        //  echo "Language is ".$language;
        if (!array_key_exists("LANG", $element_attrs) || $element_attrs["LANG"] == $this->language) {
            if (@$this->skipping !== FALSE) return; //skip tags
            //  echo "Simple processing tag ".$element_name."\n";
            if (strpos($element_name, ":") !== FALSE) {
                //namespaced
                $namespace = substr($element_name, 0, strpos($element_name, ":"));
                $tagName = substr($element_name, strpos($element_name, ":") + 1);
                if ($namespace != "r") {
                    //	    echo "Embed ".$tagName;
                    $params = array();
                    foreach ($element_attrs as $attr_name => $attr_value) {
                        $attr_name = strtolower($attr_name);
                        if ($attr_name == "lang") continue;
                        $params[$attr_name] = $this->resolve($attr_value);
                    }
                    //todo: fill params
                    //	    echo "Embedding fragment ".$element_name." with ";
                    //	    var_dump($params);
                    $this->content.= $this->renderFragment($element_name, $params, false); //embedded fragment

                } else {
                    //	    echo "Processing special action ".$tagName."\n";
                    switch ($tagName) {
                        case "value":
                            $this->content.= $this->resolve($element_attrs["OF"]); //replace by value
                            break;
                        case "content":
                            $this->content.= $this->getContent($element_attrs["ID"]);
                            break;
                    }
                }
            } else {
                //adding tag
                if ($this->skipping !== FALSE) return;
                if (empty(@$this->content)) $this->content = "";
                //	echo "\nContent Here: ".$this->content."\n";
                //	var_dump($element_attrs);
                $this->content.= "<" . strtolower($element_name);
                foreach ($element_attrs as $attr_name => $attr_value) {
                    if ($attr_name == "LANG") continue;
                    $this->content.= " " . strtolower($attr_name) . "=\"" . $this->resolve($attr_value) . "\"";
                }
                //echo "For tag: ".$element_name." selector is ".$this->activeSelector;
                if ($this->activeSelector != null) $this->content.= " class=\"c" . $this->activeSelector . "\"";
                $this->activeSelector = null;
                $this->content.= ">";
            }
            //    echo "Start element: ".$element_name."\n";
            //    var_dump($element_attrs);

        } else {
            //    echo "Skipping element: ".$element_name;
            $this->skipping = $element_name;
        }
    }

    function end_element($parser, $element_name) {
        //  echo "Ending element ".$element_name."\n";
        $element_name = strtolower($element_name);
        if ($this->mode == "scan" || $this->mode == "depends") return;
        if ($element_name == $this->firstTag) {
            return;
        }
        if (@$this->skipping !== FALSE) {
            if ($element_name == @$this->skipping) $this->skipping = false;
        } else {
            $namespace = substr($element_name, 0, strpos($element_name, ":"));
            $tagName = substr($element_name, strpos($element_name, ":") + 1);
            if ($namespace != "") return;
            $this->content.= "</" . strtolower($element_name) . ">";
            if ($element_name == $this->firstTag && $this->top) {
                $this->content.= "</html>";
            }
            //    $this->skipping = FALSE;
            //    echo "End element: ".$element_name."\n";

        }
    }

    function character_data($parser, $data) {
        if (empty(trim($data))) return;
        if ($this->mode == "scan" || $this->mode == "depends") return;
        // echo "Character data ".$data;
        //  if (@$this->skipping!==FALSE) echo "Skipping";
        //  echo "\n";
        if (@$this->skipping === FALSE) $this->content.= htmlentities($data);
    }

    //Parse XML document
    function parseXML($xml_filename, $mode, &$content, &$firstTag, &$embeddedFragments, &$injectedParameters, $top = false) {
        $oldTop = @$this->top;
        $this->top = $top;
        $oldFirstTag = @$this->firstTag;
        $oldContent = @$this->content;
        $oldEmbeddedFragments = @$this->embeddedFragments;
        $oldMode = @$this->mode;
        $this->firstTag = "";
        $this->embeddedFragments = array();
        $this->injectedParameters = array();
        $this->content = "";
        $this->mode = $mode;
        $fp = fopen("xml/" . $xml_filename, 'r') or die("Cannot open " . $xml_filename . "!");
        $parser = xml_parser_create();
        xml_set_element_handler($parser, 'Render::start_element', 'Render::end_element');
        xml_set_character_data_handler($parser, 'Render::character_data');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        while ($data = fread($fp, 4096)) {
            //      echo $data;
            xml_parse($parser, $data, feof($fp)) or die(sprintf('XML ERROR: %s at line %d', xml_error_string(xml_get_error_code($parser)), xml_get_current_line_number($parser)));
        }
        xml_parser_free($parser);
        $content = $this->content;
        $embeddedFragments = $this->embeddedFragments;
        $injectedParameters = $this->injectedParameters;
        $firstTag = $this->firstTag;
        $this->mode = $oldMode;
        $this->firstTag = $oldFirstTag;
        $this->content = $oldContent;
        $this->top = $oldTop;
        $this->embeddedFragments = $oldEmbeddedFragments;
    }

    function depends($parent, $fragment) {
        $oldTrack = @$this->track;
        if ($parent == "") {
            $this->track = $fragment;
        } else {
            $this->track = $this->track . "," . $fragment;
        }
        //  echo "Dependency tracking for ".$parent."=>".$fragment."\n";
        $this->memcache->delete("params:" . $fragment); //reset parameters
        $content = "";
        $embs = array();
        $injs = array();
        $tag = "";
        $this->parseXML($this->bindings[$fragment], "depends", $content, $tag, $embs, $injs);
        foreach ($embs as $emb) {
            $this->depends($tag, $emb);
        }
        sort($injs);
        foreach (explode(",", $this->track) as $fragment) {
            foreach (array_unique($injs) as $inj) {
                //      echo "Inject parameter ".$inj." to ".$fragment."\n";
                $this->append("params:" . $fragment, $inj);
            }
        }
        $this->track = $oldTrack;
    }

    function getDir($dir, $ext) {
        $files = scandir($dir);
        $xmls = array();
        foreach ($files as $file) {
            if (substr(strtolower($file), strpos($file, ".") + 1) == $ext) {
                $xmls[] = $file;
            }
        }
        return $xmls;
    }

    function refreshXML() {
        $files = $this->getDir("xml", "xml");
        $modified = false;
        foreach ($files as $file) {
            $mtime = filemtime("xml/" . $file);
            $fileDescription = $this->memcache->get("file:" . $file);
            if (empty($fileDescription)) {
                $this->bind();
                $fileDescription = $this->memcache->get("file:" . $file);
            }
            $timestamp = (int)(substr($fileDescription, 0, strpos($fileDescription, ":")));
            $fragment = substr($fileDescription, strpos($fileDescription, ":") + 1);
            if ($mtime > $timestamp) {
                //      echo "File ".$file." updated. Invalidate it\n";
                $this->invalidateFragment($fragment);
                $this->memcache->set("file:" . $file, $mtime . ":" . $fragment);
                $modified = true;
            }
        }
        if ($modified) {
            $this->bind();
        }
        $this->fillBindings();
    }

    function fillBindings() {
        $bindStr = $this->memcache->get("bindings");
        $bindings = array();
        if (!empty($bindStr)) {
            foreach (explode("%", $bindStr) as $bindEntry) {
                $bindings[substr($bindEntry, 0, strpos($bindEntry, "$")) ] = substr($bindEntry, strpos($bindEntry, "$") + 1);
            }
            $this->bindings = $bindings;
            return true;
        }
        return false;
    }

    function bind() {
        //todo: cache and modification
        $content = "";
        $xmls = $this->getDir("xml", "xml");
        //  var_dump($xmls);
        foreach ($xmls as $xml_filename) {
            $this->firstTag = "";
            $this->mode = "scan";
            //    echo "Processing ".$xml_filename."\n";
            $content = "";
            $embeddedFragments = array();
            $tag = "";
            $this->parameters = array();
            $injectedParameters = array();
            $this->parseXML($xml_filename, "scan", $content, $tag, $embeddedFragments, $injectedParameters);
            $bindings[$tag] = $xml_filename;
            $this->memcache->set("file:" . $xml_filename, filemtime("xml/" . $xml_filename) . ":" . $tag); //store filename timestamp

        }
        //update bindings in cache:
        $bindEntries = array();
        foreach ($bindings as $tag => $filename) {
            $bindEntries[] = $tag . "$" . $filename;
        }
        $this->memcache->set("bindings", implode("%", $bindEntries));
        $this->bindings = $bindings;
        foreach ($bindings as $fragment => $filename) {
            $this->depends("", $fragment);
        }
        return;
        //  return $bindings;

    }

    function rulesToString($id, $selector) {
        $rules = $this->cssRules[$selector];

        $extRules = array();
        foreach ($rules as $innerSelector => $innerRules) {

            $ruleArr = array();
            foreach ($innerRules as $ruleName => $ruleValue) {
                $ruleArr[] = $ruleName . ":" . $ruleValue;
            }

            if (!empty($innerSelector)) {
                $innerSelector = ($innerSelector[0] == ":" ? "" : " ") . $innerSelector;
            }

            $extRules[] = ".c" . $id . $innerSelector . "{" . implode(";", $ruleArr) . "}";
        }
        return implode("\n", $extRules);
    }

    function is_selector_exists($selector) {
        //  echo "Checking selector ".$selector;
        return (array_key_exists($selector, $this->cssRules) !== FALSE);
    }

    function searchSelector($fragment, $variant = null) {
        //iterate for most specific selector
        $cases = array();
        if ($variant != null) {
            //scan to variant specific selectors
            $cases[] = $fragment . "@" . $this->theme . "!" . $this->language . "#" . $variant; //with language
            $cases[] = $fragment . "@" . $this->theme . "#" . $variant; //without language
            $cases[] = $fragment . "!" . $this->language . "#" . $variant; //without theme with language
            $cases[] = $fragment . "#" . $variant; //without theme and language

        } else {
            $cases[] = $fragment . "@" . $this->theme . "!" . $this->language; //with language
            $cases[] = $fragment . "@" . $this->theme; //without language
            $cases[] = $fragment . "!" . $this->language; //without theme with language
            $cases[] = $fragment; //without theme and language

        }
        // var_dump($cases);
        // echo "<br/>";
        foreach ($cases as $case) {
            if ($this->is_selector_exists($case)) return $case;
        }
        //echo $fragment." NOT FOUND!!!";
        return null;
    }

    function renderFragment($fragment, $parameters, $top) {
          // echo "Rendering ".$fragment."\n";
        //  echo "Bind";
        $prevContent = @$this->content;
        $localSelector = $this->searchSelector($fragment, null);
        // var_dump($this->cssRules);
        $this->activeSelector = null;
        if ($localSelector != null) {
            // echo "Found selector: ".$localSelector;
            // echo "<br/>";
            //    echo "Applying rules \n";
            //    var_dump($this->cssRules[$cssSelector]);
            $this->activeSelector = hash('md5', $localSelector);
            $this->sourceSelectors[] = $localSelector;
            $this->css[] = $this->rulesToString($this->activeSelector, $localSelector);
        }
        $fragment = strtolower($fragment);
        $params = $this->memcache->get("params:" . $fragment);
          // echo "For fragment ".$fragment." params is ".$params."\n";
        //build key value
        //need to extract data!
        $dataSet = array();
        $this->skipping = FALSE;
        if (strlen(trim($params)) != 0) {
            foreach (explode(",", $params) as $paramName) {
                if (array_key_exists($paramName, $parameters)) {
                     // var_dump("Added env: ".$paramName);
                    $parameters[$paramName] = $this->resolve($parameters[$paramName]);
                    $dataSet[] = $parameters[$paramName];
                } else {
                     // var_dump("Added obj:".$paramName." to dataset (".$this->resolveObject($paramName).")");
                    $dataSet[] = $this->resolveObject($paramName); //save default value

                }
            }
        }
        $data = implode(",", $dataSet);
        $keyData = $this->language . ($data != "" ? "," . $data : "");
        //  echo "Entry key data: ".$keyData."\n";
        // var_dump($fragment.":".$this->theme.":".$keyData);
        $keyData = hash('md5', $fragment . ":" . $this->theme . ":" . $keyData);
        $data = $this->memcache->get($keyData);
        if ($data !== FALSE) {
            //cache available
                // echo "Extract ".$fragment." from cache: ".$keyData."\n";
            return $data;
        }
        foreach (explode(",", $params) as $paramName) {
            if (substr($paramName, 0, 2) == "//") {
                // var_dump("Key to: ".$paramName);
                if (strpos($paramName, "/", 2) !== FALSE) {
                    $pm = substr($paramName, 0, strpos($paramName, "/", 2));
                } else {
                    $pm = $paramName;
                }
                 // var_dump($pm.":".$this->resolveObject($pm));
                $this->append($pm . ":" . $this->resolveObject($pm), $keyData); //store key to //alias/objectId

            } else if (substr($paramName, 0, 4) == "env:") $this->append($paramName, $keyData);
        }
        if (!array_key_exists($fragment, $this->bindings)) return $fragment . " is not found";
        $filename = $this->bindings[$fragment];
        $contentPart = "";
        $embeddedFragments = array();
        $injectedParameters = array();
        $tag = array();
        $this->parameters = $parameters;
        // echo "@";
        //echo $filename;
        $this->parseXML($filename, "render", $contentPart, $tag, $embeddedFragments, $injectedParameters, $top);
        $this->memcache->set($keyData, $contentPart);
          // echo "Cache for ".$keyData." is updated\n";
        //  echo "Adding to entry ref:".$fragment."\n";
        $this->append("ref:" . $fragment, $keyData);
        $returnContent = $contentPart;
        return $returnContent;
    }

    /* Render top fragment */
    function render($fragment, $locator, $parameters) {
        if (empty(@$this->bindings)) {
            $this->refreshXML(); //fill bindings
        }
        //todo: one scan per minute
        $this->retouchCSS();
        $latestModification = $this->memcache->get("csslastmodification");
        $this->cssFileSelector = hash('md5', $fragment . ":" . $this->theme . ":" . $this->language . ":" . $locator . ":" . $latestModification);
        // echo $this->cssFileSelector;
        //check file presence
        $cssExists = false;
        if (file_exists("cache/".$this->cssFileSelector . ".css") && filesize("cache/".$this->cssFileSelector . ".css") != 0 && $this->memcache->get("cssrendered:" . $this->cssFileSelector) !== FALSE) {
            // echo "CSS Exists!";
            $this->reloadRenderedDefinitions($this->cssFileSelector);
            $cssExists = true;
        } else {
            $this->refreshCSS();
        }
        // echo "CSS File Selector: ".$this->cssFileSelector;
        $this->css = array();
        $this->sourceSelectors = array();

        $return = $this->renderFragment($fragment, $parameters, true);
        if (!$cssExists && count($this->css)>0) {
            // echo "Writing rules to ".$this->cssFileSelector;
            // var_dump($this->css);
            $sf = fopen("cache/".$this->cssFileSelector . ".css", "wt"); //todo: associate selectors with file
            $this->memcache->delete("cssrendered:" . $this->cssFileSelector);
            for ($i = 0;$i < count($this->css);$i++) {
                $cssRule = $this->css[$i];
                $selector = $this->sourceSelectors[$i];
                $this->append("cssselectors:" . $selector, $this->cssFileSelector); //cssselectors contains list of associated files
                $this->append("cssrendered:" . $this->cssFileSelector, $selector); //css class references binding with file
                fwrite($sf, $cssRule . "\n");
            }
            fclose($sf);
        }
        return $return;
    }

    function reloadRenderedDefinitions($filename) {
        $cssSelectors = $this->memcache->get("cssrendered:" . $filename);
        //  var_dump($cssSelectors);
        $newRules = array();
        foreach (explode(",", $cssSelectors) as $selector) {
            //    echo "Reloaded rule for ".$selector;
            $newRules[$selector] = explode(";", $this->memcache->get("cssrules:" . $selector));
        }
        $this->cssRules = $newRules;
    }

    function retouchCSS() {
        $cssFiles = $this->getDir("css", "css"); //get directory
        $cached = explode(",", $this->memcache->get("cssfilesindex"));
        $needToRebuild = false; //cached must be rebuilt
        $latestModification = (int)($this->memcache->get("csslastmodification"));
        foreach ($cssFiles as $cssFile) {
            //echo "Scanning file: ".$cssFile."\n";
            if ($latestModification == 0) {
                $latestModification = filemtime("css/" . $cssFile);
            }
            if (array_search($cssFile, $cached) === FALSE) {
                //file not in cache
                //echo "Need to rebuild\n";
                $needToRebuild = true;
                break;
            } else {
                $mtime = filemtime("css/" . $cssFile);
                if ($mtime > ((int)($this->memcache->get("cssfileupdated:" . $cssFile)))) {
                    //echo("File modified: ".$cssFile."\n");
                    if ($mtime > $latestModification) $latestModification = $mtime;
                    //file is most recent
                    $selectors = $this->memcache->get("cssfile:" . $cssFile);
                    foreach (explode(",", $selectors) as $selector) {
                        //invalidate selector
                        $associations = $this->memcache->get("cssselectors:" . $selector);
                        $assocs = explode(",", $associations);
                        // var_dump($assocs);
                        $assocs = array_unique($assocs);
                        // var_dump($assocs);
                        foreach ($assocs as $association) {
                            if (file_exists($association . ".css")) {
                                // echo "Unlink file: ".$association."\n";
                                unlink($association . ".css");
                            }
                        }
                        $this->memcache->delete("cssselectors:" . $selector);
                        // echo "Invalidate selector: ".$selector."\n";
                        $this->memcache->delete("cssrules:" . $selector); //invalidate selector definition

                    }
                }
            }
        }
        $this->memcache->set("csslastmodification", $latestModification);
        if ($needToRebuild) {
            $this->refreshCSS();
        }
    }

    //reload CSS. If parameter is empty or void - reload all the files
    function refreshCSS($filename = "") {
        if ($filename == "") {
            $cssFiles = $this->getDir("css", "css");
        } else {
            $cssFiles = array();
            $cssFiles[] = $filename;
        }
        $allRules = array();
        // var_dump($cssFiles);die;
        foreach ($cssFiles as $cssFile) {
            $this->memcache->delete("cssfile:" . $cssFile);
            //    echo $cssFile;
            $fp = fopen("css/" . $cssFile, "r");
            $cssContent = "";
            while ($data = fread($fp, 4096)) $cssContent.= $data;
            $cssContent = str_replace("\r", " ", str_replace("\n", " ", $cssContent));
            $cssContent = trim($cssContent);

            $closeBracket = 0;
            while (($openBracket = strpos($cssContent, "{", $closeBracket)) !== FALSE) {
                $selector = trim(substr($cssContent, $closeBracket, $openBracket - $closeBracket));

                $innerCloseBracket = $openBracket+1;
                while (($innerOpenBracket = strpos($cssContent, "{", $innerCloseBracket)) !== FALSE) {
                    // echo "$innerOpenBracket . $innerCloseBracket: " . substr($cssContent, $innerCloseBracket);
                    $innerSelector = trim(substr($cssContent, $innerCloseBracket, $innerOpenBracket - $innerCloseBracket));
                    if ($innerSelector == "@") {
                        // if inner selector in ("", "@") -> root selector rules
                        $innerSelector = "";
                    }

                    $closeBracket = strpos($cssContent, "}", $innerCloseBracket) + 1; //to next root selector. todo: skip quotes
                    if ($innerOpenBracket > $closeBracket) {
                        // found root open/close brackets - break

                        break;
                    }
                    // var_dump("innerLoc: $innerOpenBracket, innerPos: $innerCloseBracket");
                    $innerCloseBracket = strpos($cssContent, "}", $innerOpenBracket) + 1; //to next inner selector. todo: skip quotes

                    //      echo "Selector is ".$selector;
                    // var_dump("innerLoc: $innerOpenBracket, innerPos: $innerCloseBracket");
                    // var_dump(substr($cssContent, $innerCloseBracket));

                    $rules = trim(substr($cssContent, $innerOpenBracket + 1, $innerCloseBracket - $innerOpenBracket - 2));
                    // var_dump($innerSelector, $rules);
                    $cssrules = array();
                    $cssPos = 0;
                    //      echo "Rules is ".$rules."\n";
                    while (($delim = strpos($rules, ":", $cssPos)) !== FALSE) {
                        $ruleAttribute = trim(substr($rules, $cssPos, $delim - $cssPos));
                        $delimStored = $delim;
                        $ln = strlen($rules);
                        $quotes = false;
                        while ($delim < $ln) {
                            if ($rules[$delim] == "\"") $quotes = !$quotes;
                            if ($rules[$delim] == ";" && !$quotes) break;
                            $delim++;
                        }
                        $rule = substr($rules, $delimStored + 1, $delim - ($delimStored + 1));
                        $delim++;
                        while ($delim < $ln) {
                            if ($rules[$delim] != " ") break;
                            $delim++;
                        }
                        //todo: associate rule with source file
                        $cssrules[$ruleAttribute] = trim($rule);
                        //search for end
                        $cssPos = $delim;
                    }
                    $this->append("cssfile:" . $cssFile, $selector); //associate file with selector
                    $allRules[$selector][$innerSelector] = $cssrules;

                }
                $closeBracket = strpos($cssContent, "}", $innerCloseBracket) + 1; //to next root selector. todo: skip quotes

                $this->memcache->set("cssfileupdated:" . $cssFile, filemtime("css/" . $cssFile));
            }
        }
        if ($filename == "") {
            $this->memcache->set("cssfilesindex", implode(",", $cssFiles)); //css files index

        }
        // var_dump($allRules);
        //override default rules
        //store rules to cache
        foreach ($allRules as $selector => $ruleset) {
            $ruleentry = implode(";", $ruleset);
            $this->memcache->set("cssrules:" . $selector, $ruleentry); //store specific css rules (hash???)

        }
        //extract existing rules from cache
        if ($filename != "") {
            $index = $this->memcache->get("cssrulesindex");
            $oldRules = array();
            foreach (explode(",", $index) as $indexName) {
                $cssRulesIndex = explode(",", $indexName);
                foreach ($cssRulesIndex as $selector) {
                    $oldRules[$selector] = explode(";", $this->memcache->get("cssrules:" . $selector));
                }
            }
            foreach ($allRules as $selector => $value) {
                $oldRules[$selector] = $value;
            }
            $this->cssRules = $oldRules;
        } else {
            $this->cssRules = $allRules;
        }
        if ($filename == "") {
            $this->memcache->set("cssrulesindex", implode(",", array_keys($allRules))); //css rules index

        }
    }

    /* Invalidate cache by fragment name (with parent tree) */
    function invalidateFragment($fragment) {
        //  echo "Invaliding fragment ".$fragment."\n";
        $refs = $this->memcache->get("ref:" . $fragment);
        //  echo "Refs: ".$refs;
        foreach (explode(",", $refs) as $ref) {
            $this->memcache->delete($ref);
        }
        $key = "parent:" . $fragment;
        $parent = $this->memcache->get($key);
        if ($parent != "") $this->invalidateFragment($parent);
    }

    /* Invalidate cache by session reference */
    function invalidateSession($sessionVar) {
        $refs = $this->memcache->get("env:" . $sessionVar);
        foreach (explode(",", $refs) as $ref) {
            //    echo "Invalidating ".$ref."\n";
            $this->memcache->delete($ref);
        }
        $this->memcache->delete("env:" . $sessionVar);
    }

    /* Invalidate cache by object alias and identifier */
    function invalidateObject($alias, $identifier) {
        $obj = "//" . $alias . ":" . $identifier;
        // var_dump("Invalidating //".$alias.":".$identifier);
        $refs = $this->memcache->get($obj);
        // var_dump($refs);
        foreach (explode(",", $refs) as $ref) {
            //    echo "Invalidating ".$ref."\n";
            $this->memcache->delete($ref);
        }
        $this->memcache->delete($obj);
    }
}
?>
