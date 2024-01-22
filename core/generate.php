<?php
$total_postvars = count($_POST, COUNT_RECURSIVE);
$max_postvars = ini_get("max_input_vars"); 
if ($total_postvars >= $max_postvars) {
    echo "Uh oh, it looks like you're trying to use more variables than your PHP settings (<a href='https://www.php.net/manual/en/info.configuration.php#ini.max-input-vars'>max_input_variables</a>) allow! <br>";
    echo "Go back and choose less tables and/or columns or change your php.ini setting. <br>";      
    echo "Read <a href='https://betterstudio.com/blog/increase-max-input-vars-limit/'>here</a> how you can increase this limit.<br>";
    echo "Cruddiy will now exit because only part of what you wanted would otherwise be generated. 🙇";
    exit();
}

require "app/config.php";
require "templates.php";
require_once "helpers.php";
require "save_config.php";

$tablename = '';
$tabledisplay = '';
$tablecomment = '';
$columnname = '' ;
$columndisplay = '';
$columnwithpopup = '';
$columnvisible = '';
$index_table_headers = '';
$sort = '';
$excluded_keys = array('singlebutton', 'keep_startpage', 'append_links');
$generate_start_checked_links = array();
$startpage_filename = "app/navbar.php";
$forced_deletion = false;
$buttons_delimiter = '<!-- TABLE_BUTTONS -->';
$preview_columns = array();

// $CSS_REFS = '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">';
// $CSS_REFS = '<link rel="stylesheet" href="../css/style.css" type="text/css"/>
// <link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css"/>';

// $JS_REFS = '<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
// <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
// <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
// <script src="https://kit.fontawesome.com/6b773fe9e4.js" crossorigin="anonymous"></script>';
// $JS_REFS = '<script src="../js/jquery-3.5.1.min.js"></script>
// <script src="../js/popper.min.js"></script>
// <script src="../js/bootstrap.min.js"></script>
// <script src="https://kit.fontawesome.com/6b773fe9e4.js" crossorigin="anonymous"></script>';


// New bootstrap version
// $CSS_REFS = '<link rel="stylesheet" href="../css/style.css" type="text/css"/>
// <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
// <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css" integrity="sha512-pTaEn+6gF1IeWv3W1+7X7eM60TFu/agjgoHmYhAfLEU8Phuf6JKiiE8YmsNC0aCgQv4192s4Vai8YZ6VNM6vyQ==" crossorigin="anonymous" referrerpolicy="no-referrer"/>';

$CSS_REFS = '<link rel="stylesheet" href="../css/bootstrap-5.min.css" type="text/css"/>
<link rel="stylesheet" href="../css/style.css" type="text/css"/>
<link rel="stylesheet" href="../css/selectize.css" type="text/css"/>';

// $JS_REFS = '<script src="../js/jquery-3.5.1.min.js"></script>
// <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
// <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js" integrity="sha384-Rx+T1VzGupg4BHQYs2gCW9It+akI2MM/mndMCy36UVfodzcJcF0GGLxZIzObiEfa" crossorigin="anonymous"></script>
// <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js" integrity="sha512-IOebNkvA/HZjMM7MxL0NYeLYEalloZ8ckak+NDtOViP7oiYzG5vn6WVXyrJDiJPhl4yRdmNAG49iuLmhkUdVsQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
$JS_REFS = '<script src="../js/jquery-3.7.0.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/selectize.min.js"></script>
<script src="../js/emojis.js"></script>
<script src="../js/custom.js"></script>';

function column_type($columnname){
    switch ($columnname) {
        case (preg_match("/text/i", $columnname) ? true : false) :
            return 1;
        break;
        case (preg_match("/enum/i", $columnname) ? true : false) :
            return 2;
        break;
        case (preg_match("/varchar/i", $columnname) ? true : false) :
            return 3;
        break;
        case (preg_match("/tinyint\(1\)/i", $columnname) ? true : false) :
            return 4;
        break;
        case (preg_match("/int/i", $columnname) ? true : false) :
            return 5;
        break;
        case (preg_match("/decimal/i", $columnname) ? true : false) :
            return 6;
        break;
        case (preg_match("/float/i", $columnname) ? true : false) :
            return 6;
        break;
        case (preg_match("/datetime/i", $columnname) ? true : false) :
            return 8;
        break;
        case (preg_match("/date/i", $columnname) ? true : false) :
            return 7;
        break;
        default:
            return 0;
        break;
    }
}

function is_primary_key($t, $c){
    $cols = $_POST[$t . 'columns'];
    foreach($cols as $col) {
        if (isset($col['primary']) && $col['columnname'] == $c){
            return 1;
        }
    }
    return 0;
}

function generate_error(){
    global $errorfile;
    global $CSS_REFS;
    global $JS_REFS;

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $errorfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    if (!file_put_contents("app/error.php", $prestep2, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating Error file<br>";
}

function generate_startpage(){
    global $startfile;
    global $CSS_REFS;
    global $JS_REFS;

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $startfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    if (!file_put_contents("app/index.php", $prestep2, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating main index file.<br>";

}

function generate_navbar($tablename, $start_page, $keep_startpage, $append_links, $td){
    global $navbarfile;
    global $generate_start_checked_links;
    global $startpage_filename;

    echo "<h3>Table: $tablename</h3>";

    // make sure that a previous startpage was created before trying to keep it alive
    if (!$keep_startpage || ($keep_startpage && !file_exists($startpage_filename))) {
        if (!file_exists($startpage_filename)) {
            // called on the first run of the POST loop
            echo "Generating fresh Startpage file<br>";
            $step0 = str_replace("{TABLE_BUTTONS}", $start_page, $navbarfile);
            if (!file_put_contents($startpage_filename, $step0, LOCK_EX)) {
                die("Unable to open fresh startpage file!");
            }
        } else {
            // called on subsequent runs of the POST loop
            echo "Populating Startpage file<br>";
            $navbarfile = file_get_contents($startpage_filename);
            if (!$navbarfile) {
                die("Unable to open existing startpage file!");
            }
            append_links_to_navbar($navbarfile, $start_page, $startpage_filename, $generate_start_checked_links,$td);
        }
    } else {
        if ($append_links) {
            // load existing template
            echo "Retrieving existing Startpage file<br>";
            $navbarfile = file_get_contents($startpage_filename);
            if (!$navbarfile) {
                die("Unable to open existing startpage file!");
            }
            append_links_to_navbar($navbarfile, $start_page, $startpage_filename, $generate_start_checked_links,$td);
        }
    }
}

function append_links_to_navbar($navbarfile, $start_page, $startpage_filename, $generate_start_checked_links, $td) {
    global $buttons_delimiter;
    global $appname;

    // extract existing links from app/index.php
    echo "Looking for new link to append to Startpage file<br>";
    $navbarfile_appended = $navbarfile;
    $link_matcher_pattern = '/href=["\']?([^"\'>]+)["\']?/im';
    preg_match_all($link_matcher_pattern, $navbarfile, $navbarfile_links);
    if (count($navbarfile_links)) {
        foreach($navbarfile_links[1] as $navbarfile_link) {
            // echo '- Found existing link '.$navbarfile_link.'<br>';
        }
    }

    // do not append links to app/index.php if they already exist
    preg_match_all($link_matcher_pattern, $start_page, $start_page_links);
    if (count($start_page_links)) {
        foreach($start_page_links[1] as $start_page_link) {
            if (!in_array($start_page_link, $generate_start_checked_links)) {
                if (in_array($start_page_link, $navbarfile_links[1])) {
                    echo '- Not appending '.$start_page_link.' as it already exists<br>';
                } else {
                    echo '- Appending '.$start_page_link.'<br>';
                    array_push($navbarfile_links[1], $start_page_link);
                    $button_string = "\t".'<li><a class="dropdown-item" href="'.$start_page_link.'">'.$td.'</a></i>'."\n\t".$buttons_delimiter;
                    $step0 = str_replace($buttons_delimiter, $button_string, $navbarfile);
                    $step1 = str_replace("{APP_NAME}", $appname, $step0 );
                    if (!file_put_contents($startpage_filename, $step1, LOCK_EX)) {
                        die("Unable to open file!");
                    }
                }
                array_push($generate_start_checked_links, $start_page_link);
            }
        }
    }
}

function generate_index($tablename,$tabledisplay, $tablecomment,$column_id, $columns_selected) {
    global $indexfile;
    global $appname;
    global $CSS_REFS;
    global $JS_REFS;

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $indexfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $columns_selected = implode("', '", $columns_selected);
    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step1 = str_replace("{TABLE_DISPLAY}", $tabledisplay, $step0);
    $step2 = str_replace("{TABLE_COMMENT}", $tablecomment, $step1);
    $step5 = str_replace("{COLUMN_ID}", $column_id, $step2 );
    $step7 = str_replace("{COLUMNS}", $columns_selected, $step5 );
    $step9 = str_replace("{APP_NAME}", $appname, $step7 );   
    if (!file_put_contents("app/$tablename/index.php", $step9, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Index file<br>";
}

function generate_read($tablename, $column_id, $foreign_key_references){
    global $readfile;
    global $CSS_REFS;
    global $JS_REFS;

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $readfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step1 = str_replace("{TABLE_ID}", $column_id, $step0);
    $step3 = str_replace("{FOREIGN_KEY_REFS}", $foreign_key_references, $step1 );
    if (!file_put_contents("app/$tablename/read.php", $step3, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Read file<br>";
}

function generate_crud_class($tablename, $column_id, $column_classes){
    global $crud_class_file;

    $step0 = str_replace("{COLUMNS_CLASSES}", $column_classes, $crud_class_file);
    if (!file_put_contents("app/$tablename/class.php", $step0, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename class file<br><br>";
}


function generate_delete($tablename, $column_id, $foreign_key_references){
    global $deletefile;
    global $CSS_REFS;
    global $JS_REFS;

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $deletefile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step1 = str_replace("{TABLE_ID}", $column_id, $step0);
    $step2 = str_replace("{FOREIGN_KEY_REFS}", $foreign_key_references, $step1 );
    if (!file_put_contents("app/$tablename/delete.php", $step2, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Delete file<br>";
}

function generate_create($tablename, $create_sqlcolumns, $column_id, $create_numberofparams) {
    global $createfile;
    global $CSS_REFS;
    global $JS_REFS;

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $createfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step3 = str_replace("{CREATE_COLUMN_NAMES}", $create_sqlcolumns, $step0);
    $step4 = str_replace("{CREATE_QUESTIONMARK_PARAMS}", $create_numberofparams, $step3);
    $step9 = str_replace("{COLUMN_ID}", $column_id, $step4);
    if (!file_put_contents("app/$tablename/create.php", $step9, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Create file<br>";
}

function generate_update($tablename, $column_id){
    global $updatefile;
    global $CSS_REFS;
    global $JS_REFS;

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $updatefile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step3 = str_replace("{COLUMN_ID}", $column_id, $step0);
    if (!file_put_contents("app/$tablename/update.php", $step3, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Update file<br>";
}

function count_index_colums($table) {
    global $excluded_keys;
    $i = 0;
    foreach ( $_POST as $key => $value) {
        if (in_array($key, $excluded_keys)) {
            //echo "nope";
        }
        else if ($key == $table) {
            foreach ( $_POST[$key] as $columns )
            {
                if (isset($columns['columnvisible'])){
                    $column_visible = $columns['columnvisible'];
                    if ($column_visible == 1) {
                        $i++;
                    }
                }
            }
        }
    }
    return $i;
}

function get_fk_preview_queries($table, $join_name, &$sql_concat_select, &$sql_select, &$join_clauses){
    // This function goes over the preview columns of a table.
    global $preview_columns;
    foreach($preview_columns[$table] as $column => $fk)
    {
        if($fk)
        {
            // Reference is a foreign key to another table itself
            [$fk_table, $fk_column] = get_foreign_table_and_column($table, $column);
            if(isset($preview_columns[$fk_table]))
            {
                $new_join_name = $join_name . $fk_table;
                $join_clauses .= "\n\t\t\tLEFT JOIN `$fk_table` AS `$new_join_name` ON `$new_join_name`.`$fk_column` = `$join_name`.`$column`";
                get_fk_preview_queries($fk_table, $new_join_name, $sql_concat_select, $sql_select, $join_clauses);
            } else {
                $sql_concat_select[] = '`'. $join_name .'`.`'. $column .'`';
                $sql_select[] = '`'. $column .'`';
            }            
        } else {
            $sql_concat_select[] = '`'. $join_name .'`.`'. $column .'`';
            $sql_select[] = '`'. $column .'`';
        }
    }
}

function generate($postdata) {
    // echo "<pre>";
    // print_r($postdata);
    // echo "</pre>";
    // Go trough the POST array
    // Every table is a key
    global $excluded_keys, $preview_columns;
    
    // Array with structure $preview_columns[TABLE_NAME] where each instance contains an array of tuples.
    // These tuples have a columnname and a boolean that signals if they are a foreign key reference.
    // This is used to select which columns should be included in previews, such as select foreign keys and foreign key preview.
    foreach ($postdata as $key => $value){
        if (!in_array($key, $excluded_keys)) {
            foreach ($_POST[$key] as $columns ) {
                if (isset($columns['columninpreview'])){
                    $preview_columns[$columns['tablename']][$columns['columnname']] = !empty($columns['fk']);
                }
            }
        }
    }

    foreach ($postdata as $key => $value) {
        $tables = array();
        $tablename = '';
        $tabledisplay = '';
        $tablecomment = '';
        $columnname = '' ;
        $columndisplay = '';
        $columnwithpopup = '';
        $columnvisible = '';
        $columns_available = array();
        $columns_selected = array();
        $index_table_rows = '';
        $read_records = '';

        $create_sql_columnnames = array();
        $create_numberofparams = '';
        $create_sqlcolumns = array();

        $update_column_rows = '';

        $column_classes = array();

        global $sort;
        global $link;
        global $forced_deletion;

        if (!in_array($key, $excluded_keys)) {
            $i = 0;
            $j = 0;
            $max = count_index_colums($key)+1;
            $total_columns = count($_POST[$key]);
            $total_params = count($_POST[$key]);
            $tablename = $_POST[$key][array_keys($_POST[$key])[0]]['tablename'];

            // Find foreign key references to this table
            $foreign_key_references = "";
            $foreign_key_delete_references = [];
            $sql_get_fk_ref = "SELECT i.TABLE_NAME as 'Table', k.COLUMN_NAME as 'Column',
                                k.REFERENCED_TABLE_NAME as 'FK Table', k.REFERENCED_COLUMN_NAME as 'FK Column',
                                i.CONSTRAINT_NAME as 'Constraint Name'
                                FROM information_schema.TABLE_CONSTRAINTS i
                                LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                                WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND k.REFERENCED_TABLE_NAME = '$tablename'";
            $result = mysqli_query($link, $sql_get_fk_ref);
            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    $table = $row["Table"];
                    $fk_table = $row["FK Table"];
                    if(isset($preview_columns[$table]))
                    {
                        $fk_column = $row["FK Column"];
                        $column = $row["Column"];                        
                        $foreign_key_references .= '
                        $subsql = "SELECT COUNT(*) AS count FROM `'. $table .'` WHERE `'. $column .'` = ". $row["'.$fk_column.'"] . ";";
                        $number_of_refs = mysqli_fetch_assoc(mysqli_query($link, $subsql))["count"];
                        if ($number_of_refs > 0)
                        {
                            $html .= \'<p><a href="../'. $table . '/index.php?'. $column . '[]=\'. $row["'.$fk_column.'"]' . '.\'" class="btn btn-info">View \' . $number_of_refs . \' ' . $table . ' with '. $column . ' = \'. $row["'.$fk_column.'"] .\'</a></p></p>\';         
                        }';

                        // Only primary keys can be used when checking for deletion (because we don't have access to the other columns)
                        if(isset($_POST[$key][$fk_column]['primary'])){
                            $foreign_key_delete_references[] = "\n\"SELECT COUNT(*) AS `count`, '$table' AS `table`, '$fk_table' AS `fk_table`, '$column' AS `column`, '$fk_column' AS `fk_column`  FROM `$table` WHERE `$column` = ?;\"";
                        }
                    }
                }
            }
            $foreign_key_references = $foreign_key_references != "" ? '$html = "";' . $foreign_key_references . 'if ($html != "") {echo "<h3>References to this ' . $tablename . ':</h3>" . $html;}' : "";

            //Specific INDEX page variables            
            foreach ( $_POST[$key] as $columns ) {
                if (isset($columns['primary'])){
                    $column_id = $columns['columnname'];
                }

                // These variables contain the generated names, labels, input field and values for column.
                // They are used at the end of this loop to create the {RECORDS_READ_FORM} and the {CREATE_HTML}
                // $columndisplay contains the name of the column
                $column_value = "";
                $column_input = "";

                $type = column_type($columns['columntype']);

                //INDEXFILE VARIABLES
                //Get the columns visible in the index file
                if (isset($columns['columnvisible'])){
                    $column_visible = $columns['columnvisible'];
                    if ($columns['columnvisible'] == 1 &&  $i < $max) {

                        $columnname = $columns['columnname'];
                        $columns_selected[] = $columnname;

                        if (!empty($columns['columndisplay'])){
                            $columndisplay = $columns['columndisplay'];
                        } else {
                            $columndisplay = $columns['columnname'];
                        }

                        if (!empty($columns['columncomment'])){
                            $columndisplay = "<span data-toggle='tooltip' data-placement='top' data-bs-html='true' title=". prepare_text_for_tooltip($columns['columncomment']) .">" . $columndisplay . '</span>';
                        }
                                                
                        // Display date in locale format
                        if(!empty($columns['fk'])){
                            //Get the Foreign Key
                            $tablename = $columns['tablename'];
                            $columnname = $columns['columnname'];
                            
                            [$fk_table, $fk_column] = get_foreign_table_and_column($tablename, $columnname);
                                
                            if (isset($preview_columns[$fk_table]))
                            {
                                $join_column_name = $columnname . $fk_table . $fk_column;
                            }
                        }
                        $i++;
                    }
                }
            }

            //DETAIL CREATE UPDATE DELETE and INDEX FILTER pages variables
            // Also create the classes for this table
            foreach ( $_POST[$key] as $columns ) {

                $join_columns = '';
                $join_clauses = '';

                if ($j < $total_columns) {
                    $columns_available [] = $columns['columnname'];
                    $type = column_type($columns['columntype']);

                    if (isset($columns['columndisplay'])){
                        $columndisplay = $columns['columndisplay'];
                    }
                    if (empty($columns['columndisplay'])){
                        $columndisplay = $columns['columnname'];
                    }
                    
                    if (!$columns['columnnullable'])
                    {
                        $columndisplay .= "*";
                    } 
                    
                    if (!empty($columns['columncomment'])){
                        $columnwithpopup = "<span data-toggle='tooltip' data-placement='top' data-bs-html='true' title=". prepare_text_for_tooltip($columns['columncomment']) .">" . $columndisplay . '</span>';
                    } else {
                        $columnwithpopup = $columndisplay;
                    }
                    
                    if (!empty($columns['auto'])){
                        //Dont create html input field for auto-increment columns
                        $j++;
                        $total_params--;
                    }

                    //Get all tablenames in an array
                    $tablename = $columns['tablename'];
                    if (!in_array($tablename, $tables))
                    {
                        $tables[$tablename] = $tabledisplay;
                    }

                    $tablename = $columns['tablename'];
                    if (!empty($columns['tabledisplay'])) {
                        $tabledisplay = $columns['tabledisplay'];
                    } else {
                        $tabledisplay = $columns['tablename'];
                    }

                    if (!empty($columns['tablecomment'])) {
                        $tablecomment = '<div class="clearfix">
                            <p class="float-start fst-italic fw-light text-secondary">'. $columns["tablecomment"] .'</p>
                        </div>';
                    }

                    if(empty($columns['auto'])) {

                        $columnname = $columns['columnname'];
                        $columnname_var = preg_replace('/[^a-zA-Z0-9]+/', '_', $columnname);
                        
                        $create_record = "\$$columnname_var";
                        $create_sqlcolumns [] = "`$columnname`";
                        $create_sql_params [] = "\$$columnname_var";
                        
                        $update_column_rows .= "$$columnname_var = htmlspecialchars(\$row[\"$columnname\"] ?? \"\");\n\t\t\t\t\t";


                        //Foreign Key
                        //Check if there are foreign keys to take into consideration
                        if(!empty($columns['fk'])){
                            //Get the Foreign Key
                            [$fk_table, $fk_column] = get_foreign_table_and_column($tablename, $columnname);

                            if(isset($preview_columns[$fk_table]))
                            {
                                //Be careful code below is particular regarding single and double quotes.
                            
                                $html = '<select class="form-control" id="'. $columnname .'" name="'. $columnname .'">';
                                if ($columns['columnnullable'])
                                {
                                    $html .= '<option value="null">Null</option>';
                                }
                                
                                // Go over the preview columns and add them to the JOIN recursively.
                                $join_name = $columnname . $fk_table;
                                $join_column_name = $columnname . $fk_table . $fk_column;
                                $sql_concat_select = array();
                                $sql_select = array();
                                
                                // We need may need multiple JOIN, but in any case we need to join our refered foreign key.
                                $join_clauses .= "\tLEFT JOIN `$fk_table` AS `$join_name` ON `$join_name`.`$fk_column` = `$tablename`.`$columnname`";
                                
                                $local_join_clauses = "";

                                get_fk_preview_queries($fk_table, $join_name, $sql_concat_select, $sql_select, $local_join_clauses);  
                                $join_clauses .= $local_join_clauses;                             
                                
                                // implode all gathered values to make the joins and selects.
                                $join_columns .= "CONCAT_WS(' | ',". implode(', ', $sql_concat_select) .')';
                                $fk_columns_select = implode(', ', $sql_concat_select);

                                $is_primary_ref = is_primary_key($fk_table, $fk_column);
                                $column_value = '<?php echo get_fk_url($row["'.$columnname.'"], "'.$fk_table.'", "'.$fk_column.'", $row["'.$join_column_name.'"], '. $is_primary_ref .', false); ?>';

                                $html .= ' <?php
                                            $subsql = "SELECT DISTINCT `'. $join_name .'`.`'. $fk_column .'`, '. $fk_columns_select .' FROM `'. $fk_table . '` AS `'. $join_name .'` '. $local_join_clauses .'
                                                    ORDER BY '. $fk_columns_select .'";
                                            $result = mysqli_query($link, $subsql);
                                            while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                                $duprow = $row;
                                                unset($duprow["' . $fk_column . '"]);
                                                $value = implode(" | ", $duprow);
                                                if ($row["' . $fk_column . '"] == ' . $create_record . '){
                                                echo \'<option value="\' . $row["'. $fk_column. '"] . \'"selected="selected">\' . $value . \'</option>\';
                                                } else {
                                                    echo \'<option value="\' . $row["'. $fk_column. '"] . \'">\' . $value . \'</option>\';
                                            }
                                            }
                                        ?>
                                        </select>';
                                $column_input = $html;
                                unset($html);                                
                            } else {
                                // Foreign key reference found, but one of the tables is not selected
                                $column_value = '<?php echo htmlspecialchars($row["'.$columnname.'"] ?? ""); ?>';
                                $column_input = '<input type="text" name="'. $columnname .'" id="'. $columnname .'" class="form-control" value="<?php echo '. $create_record. '; ?>">';
                            }
                // No Foreign Keys, just regular columns from here on
                } else {                        
                        // Display date in locale format
                        if ($type == 1) // Text
                        {
                            $column_value = '<?php echo nl2br(htmlspecialchars($row["'.$columnname.'"] ?? "")); ?>';
                        }
                        else if ($type == 4) // TinyInt / Bool
                        {
                            $column_value = '<?php echo convert_bool($row["'.$columnname.'"]); ?>';
                        }
                        else if ($type == 7) // Date
                        {
                            $column_value = '<?php echo convert_date($row["'.$columnname.'"]); ?>';
                        }
                        else if ($type == 8) // Datetime
                        {
                            $column_value = '<?php echo convert_datetime($row["'.$columnname.'"]); ?>';
                        }
                        else
                        {
                            $column_value = '<?php echo htmlspecialchars($row["'.$columnname.'"] ?? ""); ?>';
                        }

                        //$type = column_type($columns['columntype']);

                        switch($type) {
                            //TEXT
                            case 1:
                                $column_input = '<textarea name="'. $columnname .'" id="'. $columnname .'" class="form-control"><?php echo '. $create_record. '; ?></textarea>';
                            break;

                            //ENUM types
                            case 2:
                            //Make sure on the update form that the previously selected type is also selected from the list
                            
                                $html = '<select name="'.$columnname.'" class="form-control" id="'.$columnname .'">';
                                if ($columns['columnnullable'])
                                {
                                    $html .= '<option value="null">Null</option>';
                                }

                                $sql_enum = "SELECT COLUMN_TYPE as AllPossibleEnumValues
                                FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tablename' AND COLUMN_NAME = '$columnname';";
                                $result = mysqli_query($link, $sql_enum);
                                $row = mysqli_fetch_array($result, MYSQLI_NUM);
                                preg_match('/enum\((.*)\)$/', $row[0], $matches);
                                $html .= "<?php \n\t\t\t\t\t\t\t \$enum_$columnname = array(" . $matches[1] . ");";
                                $html .= "
                                    foreach (\$enum_$columnname as " . ' $val){
                                        if ($val == $'.$columnname.'){
                                        echo \'<option value="\' . $val . \'" selected="selected">\' . $val . \'</option>\';
                                        } else
                                        echo \'<option value="\' . $val . \'">\' . $val . \'</option>\';
                                                }
                                ?></select>';

                                $column_input = $html;
                                unset($html);
                            break;
                            //VARCHAR
                            case 3:
                                preg_match('#\((.*?)\)#', $columns['columntype'], $match);
                                $maxlength = $match[1];
                                $column_input = '<input type="text" name="'. $columnname .'" id="'. $columnname .'" maxlength="'.$maxlength.'"class="form-control" value="<?php echo '. $create_record. '; ?>">';
                            break;

                            //TINYINT (bool)
                            case 4:
                                $regex = "/'(.*?)'/";
                                preg_match_all( $regex , $columns['columntype'] , $enum_array );
                                $html = '<select name="'.$columnname.'" id="'. $columnname .'" class="form-control">';
                                    if ($columns['columnnullable'])
                                    {
                                        $html .= '<option value="null">Null</option>';
                                    }
                                $html   .= '    <option value="0" <?php echo !' . $create_record . ' ? "selected": ""; ?> >False</option>';
                                $html   .= '    <option value="1" <?php echo ' . $create_record . ' ? "selected": ""; ?> >True</option>';
                                $html   .= '</select>';
                                    $column_input = $html;
                                unset($html);
                            break;
                            //INT
                            case 5:
                                $column_input = '<input type="number" name="'. $columnname .'" id="'. $columnname .'" class="form-control" value="<?php echo '. $create_record. '; ?>">';
                            break;

                            //DECIMAL
                            case 6:
                                $column_input = '<input type="number" name="'. $columnname .'" id="'. $columnname .'" class="form-control" value="<?php echo '. $create_record. '; ?>" step="any">';
                            break;
                            //DATE
                            case 7:
                                $column_input = '<input type="date" name="'. $columnname .'" id="'. $columnname .'" class="form-control" value="<?php echo '. $create_record. '; ?>">';
                            break;
                            //DATETIME
                            case 8:
                                $column_input = '<input type="datetime-local" name="'. $columnname .'" id="'. $columnname .'" class="form-control" max="9999-12-31 00:00" value="<?php echo empty('. $create_record. ') ? "" : date("Y-m-d\TH:i:s", strtotime('. $create_record. ')); ?>">';
                            break;

                            default:
                                $column_input = '<input type="text" name="'. $columnname .'" id="'. $columnname .'" class="form-control" value="<?php echo '. $create_record. '; ?>">';
                            break;
                        }
                    }                                     
                    $j++;
                    }

                    $column_classes[] = create_column_object($columns['columnname'], $columns['columndisplay'], $columns['columncomment'], $columns['tablename'], $join_clauses, $join_columns, $columns['columnnullable'], empty($columns['auto']), $type);
                }

                if ($j == $total_columns) {
                    $create_numberofparams = array_fill(0, $total_params, '?');
                    $create_numberofparams = implode(", ", $create_numberofparams);
                    $create_sqlcolumns = implode(", ", $create_sqlcolumns);

                    $foreign_key_delete_references = implode(",", $foreign_key_delete_references);
                    $column_classes = implode(",\n", $column_classes);

                    //Generate everything
                    $start_page = "";

                    foreach($tables as $key => $value) {
                        //echo "$key is at $value";
                        //$start_page .= '<a href="../'. $key . '/index.php" class="btn btn-primary" role="button">'. $value. '</a> ';
                        //$button_string = "\t".'<a class="dropdown-item" href="'.$start_page_link.'">'.$td.'</a>'."\n\t".$buttons_delimiter;
                        $start_page .= '<a href="../'. $key . '/index.php" class="dropdown-item">'. $value. '</a> ';
                        $start_page .= "\n\t";
                    }

                    // force existing files deletion
                    // if (!$forced_deletion && (!isset($_POST['keep_startpage']) || (isset($_POST['keep_startpage']) && $_POST['keep_startpage'] != 'true'))) {
                    //     $forced_deletion = true;
                    //     echo '<h3>Deleting existing files</h3>';
                    //     $keep = array('config.php', 'helpers.php');
                    //     foreach( glob("app/*") as $file ) {
                    //         if( !in_array(basename($file), $keep) ){
                    //             if (unlink($file)) {
                    //                 echo $file.'<br>';
                    //             }
                    //         }
                    //     }
                    //     echo '<br>';
                    // }

                    generate_navbar($value, $start_page, isset($_POST['keep_startpage']) && $_POST['keep_startpage'] == 'true' ? true : false, isset($_POST['append_links']) && $_POST['append_links'] == 'true' ? true : false, $tabledisplay);
                    generate_error();
                    generate_startpage();

                    if (!file_exists("app/$tablename/")) {
                        mkdir("app/$tablename/", 0777, true);
                    }

                    generate_index($tablename,$tabledisplay,$tablecomment,$column_id, $columns_selected);
                    generate_create($tablename, $create_sqlcolumns, $column_id, $create_numberofparams);
                    generate_read($tablename,$column_id,$foreign_key_references);
                    generate_update($tablename, $column_id);
                    generate_delete($tablename,$column_id, $foreign_key_delete_references);
                    generate_crud_class($tablename,$column_id, $column_classes);
                }
            }

        }

    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <title>Generated pages</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

</head>
<body class="bg-light">
<section class="py-5">
    <div class="container bg-white py-5 shadow">
        <div class="row">
            <div class="col-md-12 mx-auto px-5">
                <?php 
                if(isset($response)){
                    echo "<p class='alert alert-primary'>$response</p>";
                }

                generate($_POST);
                ?>
                <hr>
                <br>Your app has been created! It is completely self contained in the /app folder. You can move this folder anywhere on your server.<br><br>
                <a href="app/index.php" target="_blank" rel="noopener noreferrer">Go to your app</a> (this will open your app in a new tab).<br><br>
                You can close this tab or leave it open and use the back button to make changes and regenerate the app. Every run will overwrite the previous app unless you checked the "Keep previously generated startpage" box.<br><br>
                <hr>
                If you need further instructions please visit <a href="http://cruddiy.com">cruddiy.com</a>

            </div>
        </div>
    </div>
</section>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>
</html>
