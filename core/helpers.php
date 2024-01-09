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
        1 => "string",
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


function create_column_object($name, $comments, $sqlname, $nullable, $type){

    if(empty($name)){
        $name = $sqlname;
    }

    $comments = empty($comments) ? "null" : prepare_text_for_tooltip($comments);

    $required = $nullable ? "False" : "True";
    $type = type_to_str($type);

    return "new Column('$name',$comments,'$sqlname',$required,'$type')";
}