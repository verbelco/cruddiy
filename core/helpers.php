<?php

/** Prepares a string for a tooltip (replaces ' with ", replaces newlines with <br> and  surrounds with '') */
function prepare_text_for_tooltip($string){
    if(isset($string)){
        return "'" . str_replace("'", '"', $string) . "'";
    } else {
        return "''";
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

    $comments = empty($comments) ? "null" : prepare_text_for_tooltip($comments);
    $sql_join = empty($sql_join) ? "null" : '"'. $sql_join. '"';
    $sql_select = empty($sql_select) ? "null" : '"'. $sql_select. '"';

    $required = $nullable ? "False" : "True";
    $primary_key = $primary_key ? "False" : "True";
    $type = type_to_str($type);

    if($sql_join != "null" && $sql_select != "null"){
        [$fk_table, $fk_column] = get_foreign_table_and_column($table, $name);
        $fk_primary = is_primary_key($fk_table, $fk_column) ? "True" : "False";
        return "'$name' => new ForeignKeyColumn('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, '$fk_table', '$fk_column', $fk_primary, $required, $primary_key)";
    }
    elseif($type == "text"){
        return "'$name' => new TextColumn('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "enum"){
        $enum_list = get_enum_list($table, $name);
        $enum_dict = "array_combine($enum_list, $enum_list)";
        return "'$name' => new EnumColumn('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, $required, $primary_key, $enum_dict)";
    } elseif($type == "bool"){
        return "'$name' => new BoolColumn('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "int"){
        return "'$name' => new IntColumn('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "float"){
        return "'$name' => new FloatColumn('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "date"){
        return "'$name' => new DateColumn('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, $required, $primary_key)";
    } elseif($type == "datetime"){
        return "'$name' => new DateTimeColumn('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, $required, $primary_key)";
    } else {
        // Default, usually strings
        return "'$name' => new Column('$name',\n '$displayname', $comments, '$table', $sql_join, $sql_select, $required, $primary_key)";
    }
}