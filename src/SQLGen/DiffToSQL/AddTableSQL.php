<?php namespace DBDiff\SQLGen\DiffToSQL;

use DBDiff\SQLGen\SQLGenInterface;


class AddTableSQL implements SQLGenInterface {

    function __construct($obj) {
        $this->obj = $obj;
    }
    
    public function getUp() {
        global $foreign_keys_list, $disable_fk_list;
        $table = $this->obj->table;
        $connection = $this->obj->connection;
        $r = new \ReflectionObject($connection);
        $propertyConfig = $r->getProperty('config');
        $propertyConfig->setAccessible(true);
        $config = $propertyConfig->getValue($connection);
        $host = $config['host'];

        $database = $connection->getDatabaseName();

        $res = $connection->select("SHOW CREATE TABLE `$table`");
        $res_query = $res[0]['Create Table'].';';
        if ($res_query == ";"){
            $res_query = "";   
        }
        $lines = array_map(function($el) { return trim($el);}, explode("\n", $res_query));
        $lines_columns = array_slice($lines, 1,-1);

        foreach ($lines_columns as $i=>$line){
            preg_match("/`([^`]+)`/", $line, $matches);
            $name = $matches[1];
            $line = trim($line, ',');
            if ($table=="SaGl_CLTX4"){
                if (count($lines_columns)==$i+1){
                    $lines_columns[$i] = str_replace("varchar(20)", "varchar(5)",$line);
                }
                else{
                    $lines_columns[$i] = str_replace("varchar(20)", "varchar(5)",$line).",";
                }
            }
            if (strpos($line, " text") !== false) {
                switch ($host){
                    case "69.20.64.47":
                        $column_length = get_connection() -> getOne("SELECT MAX(LENGTH($name)) FROM $database.$table;");
                        break;
                    case "204.232.170.239":
                        $column_length = get_connection2() -> getOne("SELECT MAX(LENGTH($name)) FROM $database.$table;");
                        break;  
                    case "204.232.170.236":
                        $column_length = get_connection_prod() -> getOne("SELECT MAX(LENGTH($name)) FROM $database.$table;");
                        break; 
                    case "173.236.134.74":
                        $connexion_source = mysqli_connect(
                        $config['host'],
                        $config['username'],
                        $config['password']);
                        $query = mysqli_query($connexion_source, "SELECT MAX(LENGTH($name)) FROM $database.$table;");
                        $column_length = mysqli_fetch_assoc($query);
                        $column_length = $column_length["MAX(LENGTH(".$name."))"];
                        break;                     
                }
                
                if ($host !== "173.236.134.74" & $column_length<5000 & $column_length !== null & $column_length !== 0){
                    if (count($lines_columns)==$i+1){
                        $lines_columns[$i] = str_replace(" text", " varchar(".$column_length.")",$line);
                    }
                    elseif($table !=="lms_tokens_19471"){
                        $lines_columns[$i] = str_replace(" text", " varchar(".$column_length.")", $line).",";
                    }

                }
            }
            if (strpos($line, "FOREIGN KEY") !== false) {
                $lines_columns[$i]="";
                if (count($lines_columns)==$i+1){
                    while ($lines_columns[$i] ==""){
                        $lines_columns[$i-1] = substr($lines_columns[$i-1],0,-1);
                        $i-=1;
                    }
                }
                array_push($foreign_keys_list,"ALTER TABLE ".$table." ADD ".$line.";");

            }
        }
        $lines_columns = array_filter($lines_columns);
        if($res_query !== ""){
            return $lines[0].implode("\n\t", $lines_columns).");";
        } 
    }

    public function getDown() {
        $table = $this->obj->table;
        //return "DROP TABLE `$table`;";
    }
}
