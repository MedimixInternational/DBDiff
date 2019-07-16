<?php namespace DBDiff\SQLGen\DiffToSQL;


use DBDiff\SQLGen\SQLGenInterface;

require_once(dirname(__FILE__) . "/../../../../../../../../PROD2/onlinetools/mmx_shared_library/functions/functions.php");
class AddTableSQL implements SQLGenInterface {

    function __construct($obj) {
        $this->obj = $obj;
    }
    
    public function getUp() {
        $table = $this->obj->table;
        $connection = $this->obj->connection;
        $database = $connection->getDatabaseName();
        
        $res = $connection->select("SHOW CREATE TABLE `$table`");
        $res_query = $res[0]['Create Table'].';';
        $lines = array_map(function($el) { return trim($el);}, explode("\n", $res_query));
        $lines = array_slice($lines, 1, -1);
        foreach ($lines as $line){
            preg_match("/`([^`]+)`/", $line, $matches);
            $name = $matches[1];
            $line = trim($line, ',');
            if (strpos($line, "text") !== false) {
                $column_length = get_connection2() -> getOne("SELECT MAX(LENGTH($name)) FROM $database.$table;");
                $line = str_replace(" text "," varchar(".$column_length.") ",$line);
            }
        }
        return '"'. implode(",\n", $lines). '"';
    }

    public function getDown() {
        $table = $this->obj->table;
        return "DROP TABLE `$table`;";
    }
}
