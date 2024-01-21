<?php

/** Prepares a string for a tooltip (replaces ' with ", replaces newlines with <br> and  surrounds with '') */
function prepare_text_for_tooltip($string){
    if(isset($string)){
        return "'" . str_replace("'", '"', $string) . "'";
    } else {
        return "''";
    }
}

function get_default_value($table, $column){
    // Get the default value of a column
    global $link;

    $sql = "SELECT COLUMN_DEFAULT AS `def`
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hydrodb' and TABLE_NAME = '$table' AND COLUMN_NAME = '$column';";
    $result = mysqli_query($link, $sql);
    if(mysqli_num_rows($result) == 1)
    {
        $def = mysqli_fetch_assoc($result)['def']; 
    }

    // Rewrite some SQL to PHP
    if($def == "current_timestamp()"){
        $def = "new DateTime()";
    }



    return empty($def) || $def == "NULL" ? "null" : $def;
}

function get_foreign_table_and_column($tablename, $columnname){
    global $link;

    $sql_getfk = "SELECT i.TABLE_NAME as 'Table', k.COLUMN_NAME as 'Column',
    k.REFERENCED_TABLE_NAME as 'FK Table', k.REFERENCED_COLUMN_NAME as 'FK Column',
    i.CONSTRAINT_NAME as 'Constraint Name'
    FROM information_schema.TABLE_CONSTRAINTS i
    LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME
    WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND k.TABLE_NAME = '$tablename' AND k.COLUMN_NAME = '$columnname'";
    $result = mysqli_query($link, $sql_getfk);
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $fk_table = $row["FK Table"];
            $fk_column = $row["FK Column"];
        }
        return [$fk_table, $fk_column];
    }
}

function type_to_str($type){
    $type_to_str = array(
        1 => "text",
        2 => "enum",
        3 => "string",
        4 => "bool",
        5 => "int",
        6 => "float",
        7 => "date",
        8 => "datetime"
    );
    if(isset($type_to_str[$type])){
        return $type_to_str[$type];
    } else {
        return "string";
    }
}

/** Returns a string with a php list with enum values, such as:
 * ['UTC', 'Wintertijd (UTC+1)', 'Zomertijd (UTC+2)', 'Kalendertijd(UTC+1/UTC+2)']
 */
function get_enum_list($tablename, $columnname){
    global $link;

    $sql = "SELECT COLUMN_TYPE as AllPossibleEnumValues
            FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tablename' AND COLUMN_NAME = '$columnname';";
    $result = mysqli_query($link, $sql);
    $row = mysqli_fetch_array($result, MYSQLI_NUM);
    preg_match('/enum\((.*)\)$/', $row[0], $matches);
    return "[" . $matches[1] . "]";
}

function create_column_object($name, $displayname, $comments, $table, $sql_join, $sql_select, $nullable, $primary_key, $type){

    if(empty($displayname)){
        $displayname = $name;
    }

    $default = get_default_value($table, $name);

    $comments = empty($comments) ? "null" : prepare_text_for_tooltip($comments);
    $sql_join = empty($sql_join) ? "null" : '"'. $sql_join. '"';
    $sql_select = empty($sql_select) ? "null" : '"'. $sql_select. '"';

    $required = $nullable ? "False" : "True";
    $primary_key = $primary_key ? "False" : "True";
    $type = type_to_str($type);

    if($sql_join != "null" && $sql_select != "null"){
        [$fk_table, $fk_column] = get_foreign_table_and_column($table, $name);
        $fk_primary = is_primary_key($fk_table, $fk_column) ? "True" : "False";
        return "'$name' => new ForeignKeyColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, '$fk_table', '$fk_column', $fk_primary, $required, $primary_key)";
    }
    elseif($type == "text"){
        return "'$name' => new TextColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "enum"){
        $enum_list = get_enum_list($table, $name);
        $enum_dict = "array_combine($enum_list, $enum_list)";
        return "'$name' => new EnumColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key, $enum_dict)";
    } elseif($type == "bool"){
        return "'$name' => new BoolColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "int"){
        return "'$name' => new IntColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "float"){
        return "'$name' => new FloatColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "date"){
        return "'$name' => new DateColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "datetime"){
        return "'$name' => new DateTimeColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } else {
        // Default, usually strings
        return "'$name' => new Column('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    }
}