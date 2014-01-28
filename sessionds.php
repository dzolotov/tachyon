<?php
class DataSource {
    function __construct() {
        $this->env = array();
        $template = $_SESSION["activeTemplate"];
        if (!empty($template)) {
            $data = file("presets/" . $template);
            foreach ($data as $rowid => $row) {
                if ($rowid == 0) continue;
                $item = substr($row, 0, strpos($row, "="));
                $data = substr($row, strpos($row, "=") + 1);
                $this->setEnv($item, $data);
            }
        }
    }
    function getEnv($item) {
        return $this->env[$item];
    }
    function setEnv($key, $item) {
        $this->env[$key] = $item;
    }
    function retrieveData($alias, $path) {
        // echo "!!!!";
        $object = $_SESSION["bind_" . $alias];
        $content = file("data/" . $alias . "_" . $object);
        // var_dump($content);
        foreach ($content as $rowid => $row) {
            if (strpos($row, "{") !== FALSE) {
                $content[$rowid] = substr($row, strpos($row, "{"));
            }
        }
        $jsonData = implode("\n", $content);
        //    var_dump($jsonData);
        $decoded = json_decode($jsonData, true);
        // var_dump($decoded);
        return $decoded[$path];
        //    return $alias.":".$path;
        
    }
    function getObjectId($alias) {
        $object = $_SESSION["bind_" . $alias];
        // var_dump("ObjId: ".$object);
        return hash('md5', $object);
    }
}
?>
