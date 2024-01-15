<?php

/** Prepares a string for a tooltip (replaces ' with ", replaces newlines with <br> and  surrounds with '') */
function prepare_text_for_tooltip($string){
    if(isset($string)){
        return "'" . nl2br(str_replace("'", '"', $string)) . "'";
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


function create_column_object($name, $displayname, $comments, $table, $sql_join, $nullable, $primary_key, $type){

    if(empty($displayname)){
        $displayname = $name;
    }

    $comments = empty($comments) ? "null" : prepare_text_for_tooltip($comments);
    $sql_join = empty($sql_join) ? "null" : $sql_join;

    $required = $nullable ? "False" : "True";
    $primary_key = $primary_key ? "False" : "True";
    $type = type_to_str($type);

    if($type == "text"){
        return "new TextColumn('$name', '$displayname', $comments, '$table', $sql_join, $required, $primary_key)";
    } elseif($type == "enum"){
        return "new EnumColumn('$name', '$displayname', $comments, '$table', $sql_join, $required, $primary_key)";
    } elseif($type == "bool"){
        return "new BoolColumn('$name', '$displayname', $comments, '$table', $sql_join, $required, $primary_key)";
    } elseif($type == "int"){
        return "new IntColumn('$name', '$displayname', $comments, '$table', $sql_join, $required, $primary_key)";
    } elseif($type == "float"){
        return "new FloatColumn('$name', '$displayname', $comments, '$table', $sql_join, $required, $primary_key)";
    } elseif($type == "date"){
        return "new DateColumn('$name', '$displayname', $comments, '$table', $sql_join, $required, $primary_key)";
    } elseif($type == "DateTime"){
        return "new DateTimeColumn('$name', '$displayname', $comments, '$table', $sql_join, $required, $primary_key)";
    } else {
        // Default, usually strings
        return "new Column('$name', '$displayname', $comments, '$table', $sql_join, $required, $primary_key)";
    }
}