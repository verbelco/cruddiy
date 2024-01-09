<?php

/** Prepares a string for a tooltip (replaces ' with ", replaces newlines with <br> and  surrounds with '') */
function prepare_text_for_tooltip($string){
    if(isset($string)){
        return "'" . nl2br(str_replace("'", '"', $string)) . "'";
    } else {
        return "''";
    }
}