<?php namespace DBDiff\SQLGen;


class MigrationGenerator {

    public static function generate($diffs, $method) {
        global $foreign_keys_list, $disable_fk_list;

        $sql = "";
        $foreign_keys_list=array();
        $disable_fk_list=array();

        foreach ($diffs as $diff) {
            $reflection = new \ReflectionClass($diff);
            $sqlGenClass = __NAMESPACE__."\\DiffToSQL\\".$reflection->getShortName()."SQL";
            $gen = new $sqlGenClass($diff);
            $sql .= $gen->$method()."\n";
        }
        $foreign_keys_script = implode("\n", $foreign_keys_list);
        $sql .= $foreign_keys_script;
        return $sql;
    }

}
