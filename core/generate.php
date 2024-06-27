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

require_once "app/config.php";
require_once "helpers.php";
require "save_config.php";

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

$CSS_REFS = '<link rel="stylesheet" href="../css/bootstrap-5.min.css" type="text/css" />
<link rel="stylesheet" href="../css/style.css" type="text/css" />
<link rel="stylesheet" href="../css/selectize.css" type="text/css" />';

// $JS_REFS = '<script src="../js/jquery-3.5.1.min.js"></script>
// <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
// <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js" integrity="sha384-Rx+T1VzGupg4BHQYs2gCW9It+akI2MM/mndMCy36UVfodzcJcF0GGLxZIzObiEfa" crossorigin="anonymous"></script>
// <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js" integrity="sha512-IOebNkvA/HZjMM7MxL0NYeLYEalloZ8ckak+NDtOViP7oiYzG5vn6WVXyrJDiJPhl4yRdmNAG49iuLmhkUdVsQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
$JS_REFS = '<script src="../js/jquery-3.7.0.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/selectize.min.js"></script>
<script src="../js/selectize-plugin.js"></script>
<script src="../js/sortable.min.js"></script>
<script src="../js/jquery-sortable.js"></script>
<script src="../js/emojis.js"></script>
<script src="../js/custom.js"></script>';

function generate_error()
{
    global $CSS_REFS, $JS_REFS;

    $errorfile = file_get_contents("templates/error.template.php");

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $errorfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    if (!file_put_contents("app/error.php", $prestep2, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating Error file<br>";
}

function generate_startpage()
{
    global $CSS_REFS, $JS_REFS;

    $startfile = file_get_contents("templates/start.template.php");

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $startfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    if (!file_put_contents("app/index.php", $prestep2, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating main index file.<br>";

}

function generate_navbar($tablename, $start_page, $keep_startpage, $append_links, $td)
{
    global $generate_start_checked_links, $startpage_filename;

    $navbarfile = file_get_contents("templates/navbar.template.php");

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
            append_links_to_navbar($navbarfile, $start_page, $startpage_filename, $generate_start_checked_links, $td);
        }
    } else {
        if ($append_links) {
            // load existing template
            echo "Retrieving existing Startpage file<br>";
            $navbarfile = file_get_contents($startpage_filename);
            if (!$navbarfile) {
                die("Unable to open existing startpage file!");
            }
            append_links_to_navbar($navbarfile, $start_page, $startpage_filename, $generate_start_checked_links, $td);
        }
    }
}

function append_links_to_navbar($navbarfile, $start_page, $startpage_filename, $generate_start_checked_links, $td)
{
    global $buttons_delimiter, $appname;

    // extract existing links from app/index.php
    echo "Looking for new link to append to Startpage file<br>";
    $navbarfile_appended = $navbarfile;
    $link_matcher_pattern = '/href=["\']?([^"\'>]+)["\']?/im';
    preg_match_all($link_matcher_pattern, $navbarfile, $navbarfile_links);
    if (count($navbarfile_links)) {
        foreach ($navbarfile_links[1] as $navbarfile_link) {
            // echo '- Found existing link '.$navbarfile_link.'<br>';
        }
    }

    // do not append links to app/index.php if they already exist
    preg_match_all($link_matcher_pattern, $start_page, $start_page_links);
    if (count($start_page_links)) {
        foreach ($start_page_links[1] as $start_page_link) {
            if (!in_array($start_page_link, $generate_start_checked_links)) {
                if (in_array($start_page_link, $navbarfile_links[1])) {
                    echo '- Not appending ' . $start_page_link . ' as it already exists<br>';
                } else {
                    echo '- Appending ' . $start_page_link . '<br>';
                    array_push($navbarfile_links[1], $start_page_link);
                    $button_string = "\t" . '<li><a class="dropdown-item" href="' . $start_page_link . '">' . $td . '</a></i>' . "\n\t" . $buttons_delimiter;
                    $step0 = str_replace($buttons_delimiter, $button_string, $navbarfile);
                    $step1 = str_replace("{APP_NAME}", $appname, $step0);
                    if (!file_put_contents($startpage_filename, $step1, LOCK_EX)) {
                        die("Unable to open file!");
                    }
                }
                array_push($generate_start_checked_links, $start_page_link);
            }
        }
    }
}

function generate_index($tablename, $tabledisplay, $column_id, $columns_selected)
{
    global $appname, $CSS_REFS, $JS_REFS;

    $indexfile = file_get_contents("templates/index.template.php");

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $indexfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $columns_selected = implode("', '", $columns_selected);
    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step1 = str_replace("{TABLE_DISPLAY}", $tabledisplay, $step0);
    $step5 = str_replace("{COLUMN_ID}", $column_id, $step1);
    $step7 = str_replace("{COLUMNS}", $columns_selected, $step5);
    $step9 = str_replace("{APP_NAME}", $appname, $step7);
    if (!file_put_contents("app/$tablename/index.php", $step9, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Index file<br>";
}

function generate_read($tablename, $column_id, $foreign_key_references)
{
    global $CSS_REFS, $JS_REFS;

    $readfile = file_get_contents("templates/read.template.php");

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $readfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step1 = str_replace("{TABLE_ID}", $column_id, $step0);
    $step3 = str_replace("/**{FOREIGN_KEY_REFS}*/", $foreign_key_references, $step1);
    if (!file_put_contents("app/$tablename/read.php", $step3, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Read file<br>";
}

function generate_crud_class($tablename, $tabledisplay, $tablecomment, $column_id, $foreign_key_references, $column_classes)
{
    $crud_class_file = file_get_contents("templates/crud_class.template.php");

    $step0 = str_replace("{TABLE}", $tablename, $crud_class_file);
    $step1 = str_replace("{TABLE_DISPLAY}", $tabledisplay, $step0);
    $step2 = str_replace('"{TABLE_COMMENT}"', $tablecomment, $step1);
    $step3 = str_replace("{COLUMN_ID}", $column_id, $step2);
    $step4 = str_replace("/**{FOREIGN_KEY_REFS}*/", $foreign_key_references, $step3);
    $step5 = str_replace("/**{COLUMNS_CLASSES}*/", $column_classes, $step4);
    if (!file_put_contents("app/$tablename/class.php", $step5, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename class file<br><br>";
}

function generate_database_link($tablename, $column_id, $columns_list, $preview_columns_list, $attributes_list)
{
    $database_class_file = file_get_contents("templates/database-link.template.php");

    $columns = "'" . implode("', '", $columns_list) . "'";
    $preview_columns = "'" . implode("', '", $preview_columns_list) . "'";
    $attributes = implode("\n", $attributes_list);

    $step0 = str_replace("{TABLE}", $tablename, $database_class_file);
    $step1 = str_replace("{COLUMN_ID}", $column_id, $step0);
    $step2 = str_replace("/**{COLUMNS}*/", $columns, $step1);
    $step3 = str_replace("/**{PREVIEW_COLUMNS}*/", $preview_columns, $step2);
    $step4 = str_replace("/**{ATTRIBUTES}*/", $attributes, $step3);

    $dir = "app/database_link";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (!file_put_contents("$dir/$tablename.php", $step4, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename database link file<br>";
}

function generate_object($tablename, $columns_list, $attributes_list,  $constructor_parameters)
{
    $database_class_file = file_get_contents("templates/object-class.template.php");

    $columns = "'" . implode("', '", $columns_list) . "'";
    $attributes = implode("\n", $attributes_list);
    $constructor_parameters = implode(",", $constructor_parameters);
    $construct_statements = implode("\n", array_map(fn($c) => "\$this->$c = \$$c;", $columns_list));
    $array_construct = implode(",\n", array_map(fn($c) => "\$row['$c']", $columns_list));

    $step0 = str_replace("{TABLE}", $tablename, $database_class_file);
    $step1 = str_replace("/**{COLUMNS}*/", $columns, $step0);
    $step2 = str_replace("/**{ATTRIBUTES}*/", $attributes, $step1);
    $step3 = str_replace("/**{CONSTRUCT_PARAMETERS}*/", $constructor_parameters, $step2);
    $step4 = str_replace("/**{CONSTRUCT_STATEMENTS}*/", $construct_statements, $step3);
    $step5 = str_replace("/**{ARRAY_CONSTRUCT_ROW}*/", $array_construct, $step4);

    $dir = "app/object_class";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (!file_put_contents("$dir/$tablename.php", $step5, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename object class file<br>";
}

function generate_delete($tablename, $column_id, $foreign_key_references)
{
    global $CSS_REFS, $JS_REFS;

    $deletefile = file_get_contents("templates/delete.template.php");

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $deletefile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step1 = str_replace("{TABLE_ID}", $column_id, $step0);
    $step2 = str_replace("/**{FOREIGN_KEY_REFS}*/", $foreign_key_references, $step1);
    if (!file_put_contents("app/$tablename/delete.php", $step2, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Delete file<br>";
}

function generate_create($tablename, $column_id)
{
    global $CSS_REFS, $JS_REFS;

    $createfile = file_get_contents("templates/create.template.php");

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $createfile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step9 = str_replace("{COLUMN_ID}", $column_id, $step0);
    if (!file_put_contents("app/$tablename/create.php", $step9, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Create file<br>";
}

function generate_update($tablename, $column_id)
{
    global $CSS_REFS, $JS_REFS;

    $updatefile = file_get_contents("templates/update.template.php");

    $prestep1 = str_replace("{CSS_REFS}", $CSS_REFS, $updatefile);
    $prestep2 = str_replace("{JS_REFS}", $JS_REFS, $prestep1);

    $step0 = str_replace("{TABLE_NAME}", $tablename, $prestep2);
    $step3 = str_replace("{COLUMN_ID}", $column_id, $step0);
    if (!file_put_contents("app/$tablename/update.php", $step3, LOCK_EX)) {
        die("Unable to open file!");
    }
    echo "Generating $tablename Update file<br>";
}

function generate($postdata)
{
    global $excluded_keys, $preview_columns;

    // Array with structure $preview_columns[TABLE_NAME] where each instance contains an array of tuples.
    // These tuples have a columnname and a boolean that signals if they are a foreign key reference.
    // This is used to select which columns should be included in previews, such as select foreign keys and foreign key preview.
    foreach ($postdata as $key => $table_data) {
        if (!in_array($key, $excluded_keys)) {
            foreach ($table_data as $column) {
                if (isset($column['columninpreview'])) {
                    $preview_columns[$column['tablename']][$column['columnname']] = !empty($column['fk']);
                }
            }
        }
    }

    foreach ($postdata as $key => $table_data) {
        // Loop over a single table
        global $link, $forced_deletion;

        $columns_selected = [];
        /** List with all columns of this table */
        $column_list = [];
        /** List with the selected columns of this table */
        $column_classes = [];

        $constructor_parameters = [];
        $db_attributes = [];

        if (!in_array($key, $excluded_keys)) {

            // Specific INDEX page variables            
            foreach ($table_data as $c) {
                // Find the primary key of this table
                if (isset($c['primary'])) {
                    $column_id = $c['columnname'];
                }

                // Get the columns visible in the index file
                if (isset($c['columnvisible'])) {
                    if ($c['columnvisible'] == 1) {
                        $columns_selected[] = $c['columnname'];
                    }
                }

                $column_list[] = $c['columnname'];
            }

            $first_column = $table_data[array_keys($table_data)[0]];
            $tablename = $first_column['tablename'];

            // Gather data specific to this table
            if (!empty($first_column['tabledisplay'])) {
                $tabledisplay = $first_column['tabledisplay'];
            } else {
                $tabledisplay = $tablename;
            }

            if (!empty($first_column['tablecomment'])) {
                $tablecomment = "'". $first_column['tablecomment'] ."'";
            } else {
                $tablecomment = 'null';
            }

            // Find foreign key references to this table
            $foreign_key_references = [];
            $sql_get_fk_ref = " SELECT DISTINCT i.CONSTRAINT_NAME as 'Constraint Name', i.TABLE_NAME as 'Table', k.COLUMN_NAME as 'Column',
                                k.REFERENCED_TABLE_NAME as 'FK Table', k.REFERENCED_COLUMN_NAME as 'FK Column'
                                FROM information_schema.TABLE_CONSTRAINTS i
                                LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                                WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND k.REFERENCED_TABLE_NAME = '$tablename'";
            $result = mysqli_query($link, $sql_get_fk_ref);
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $table = $row["Table"];
                    $fk_table = $row["FK Table"];
                    if (isset($preview_columns[$table])) {
                        $fk_column = $row["FK Column"];
                        $column = $row["Column"];
                        if (isset($table_data[$fk_column]['primary'])) {
                            $foreign_key_references[] = "\n\"new ExternalReference('$table', '$fk_table', '$column', '$fk_column', '$fk_column')\"";
                        } elseif (isset($column_id)) {
                            $foreign_key_references[] = "\n\"new ExternalReference('$table', '$fk_table', '$column', '$fk_column', '$column_id')\"";
                        }
                    }
                }
            }

            // Create objects for each column
            foreach ($table_data as $c) {
                $join_columns = '';
                $join_clauses = '';
                $type = column_type($c['columntype']);

                if (empty($c['auto'])) {
                    // Foreign Key
                    // Create FK JOINS.
                    if (!empty($c['fk'])) {
                        //Get the Foreign Key
                        [$fk_table, $fk_column] = get_foreign_table_and_column($tablename, $c['columnname']);

                        if (isset($preview_columns[$fk_table])) {
                            // Go over the preview columns and add them to the JOIN recursively.
                            $join_name = $c['columnname'] . $fk_table;
                            $sql_concat_select = array();
                            $sql_select = array();

                            // We need may need multiple JOIN, but in any case we need to join our referred foreign key.
                            $join_clauses = "\tLEFT JOIN `$fk_table` AS `$join_name` ON `$join_name`.`$fk_column` = `$tablename`.`" . $c['columnname'] . "`";

                            $local_join_clauses = "";

                            get_fk_preview_queries($fk_table, $join_name, $sql_concat_select, $sql_select, $local_join_clauses);
                            $join_clauses .= $local_join_clauses;

                            // implode all gathered values to make the joins and selects.
                            $join_columns .= "CONCAT_WS(' | '," . implode(', ', $sql_concat_select) . ')';
                        }
                    }
                }
                $column_classes[] = create_column_object($c['columnname'], $c['columndisplay'], $c['columncomment'], $c['tablename'], $join_clauses, $join_columns, $c['columnnullable'], empty($c['auto']), $type);
                $db_attributes[] = create_db_attribute($c['columnname'], $type, $c['columncomment'], $c['columnnullable']);
                $constructor_parameters[] = create_constructor_parameter($c['columnname'], $type, $c['columnnullable']);    
            }

            $foreign_key_references = implode(",", $foreign_key_references);
            $column_classes = implode(",\n", $column_classes);

            //Generate everything (without navbar)
            // $start_page = "";

            // foreach($tables as $k => $v) {
            //echo "$k is at $v";
            //$start_page .= '<a href="../'. $k . '/index.php" class="btn btn-primary" role="button">'. $v. '</a> ';
            //$button_string = "\t".'<a class="dropdown-item" href="'.$start_page_link.'">'.$td.'</a>'."\n\t".$buttons_delimiter;
            // $start_page .= '<a href="../'. $k . '/index.php" class="dropdown-item">'. $v. '</a> ';
            // $start_page .= "\n\t";
            // }

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

            // generate_navbar($value, $start_page, isset($_POST['keep_startpage']) && $_POST['keep_startpage'] == 'true' ? true : false, isset($_POST['append_links']) && $_POST['append_links'] == 'true' ? true : false, $tabledisplay);
            generate_error();
            generate_startpage();

            if (!file_exists("app/$tablename/")) {
                mkdir("app/$tablename/", 0777, true);
            }

            generate_index($tablename, $tabledisplay, $column_id, $columns_selected);
            generate_create($tablename, $column_id);
            generate_read($tablename, $column_id, $foreign_key_references);
            generate_update($tablename, $column_id);
            generate_delete($tablename, $column_id, $foreign_key_references);
            generate_database_link($tablename, $column_id, $column_list, $columns_selected, $db_attributes);
            generate_object($tablename, $column_list, $db_attributes, $constructor_parameters);
            generate_crud_class($tablename, $tabledisplay, $tablecomment, $column_id, $foreign_key_references, $column_classes);
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <title>Generated pages</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"
        integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

</head>

<body class="bg-light">
    <section class="py-5">
        <div class="container bg-white py-5 shadow">
            <div class="row">
                <div class="col-md-12 mx-auto px-5">
                    <?php
                    if (isset($response)) {
                        echo "<p class='alert alert-primary'>$response</p>";
                    }

                    generate($_POST);
                    ?>
                    <hr>
                    <br>Your app has been created! It is completely self contained in the /app folder. You can move this
                    folder anywhere on your server.<br><br>
                    <a href="app/index.php" target="_blank" rel="noopener noreferrer">Go to your app</a> (this will open
                    your app in a new tab).<br><br>
                    You can close this tab or leave it open and use the back button to make changes and regenerate the
                    app. Every run will overwrite the previous app unless you checked the "Keep previously generated
                    startpage" box.<br><br>
                    <hr>
                    If you need further instructions please visit <a href="http://cruddiy.com">cruddiy.com</a>

                </div>
            </div>
        </div>
    </section>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"
        integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI"
        crossorigin="anonymous"></script>
</body>

</html>