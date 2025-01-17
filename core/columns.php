<?php

include 'app/config.php';
include 'helpers.php';
$config_folder = 'temp/';
if (! is_dir($config_folder)) {
    mkdir($config_folder, 0777, true);
}

?>
<!doctype html>
<html lang="en">
<head>
    <title>Select Columns</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

</head>
<body class="bg-light">
<section class="py-5">
    <div class="container bg-white shadow py-5">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="text-center">
                    <h4 class="h1 border-bottom pb-2">All Available Columns</h4>
                </div>
                <div class="row">
                    <div class="col-3 text-right">
                        <label for="config_select">Choose a saved config</label>
                    </div>
                    <div class="col-5 custom-control">
                        <select class="custom-select" id="config_select" name="config_id">
                            <option selected></option>
                            <?php
                            $configs = scandir($config_folder);
unset($configs[0]);
unset($configs[1]);

foreach (array_reverse($configs) as $k => $fname) {
    $name = pathinfo($fname, PATHINFO_FILENAME);
    echo "<option value='$name'>$name</option>";
}
?>
                        </select>
                    </div>
                </div>
                <div class="mx-atuo text-right ml-4">
                    <input type="checkbox" id="checkall-1" checked>
                    <label for="checkall-1">Check/uncheck all</label>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="checkbox" id="checkall-2" checked>
                    <label for="checkall-2">Check/uncheck all</label>
                </div>
                
                <form class="form-horizontal" action="generate.php" id="cruddiy_data" method="post">
                    <fieldset>                  
                        <?php
                        function get_primary_keys($table)
                        {
                            global $link;
                            $sql = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";
                            $result = mysqli_query($link, $sql);
                            $primary_keys = [];
                            while ($row = mysqli_fetch_assoc($result)) {
                                $primary_keys[] = $row['Column_name'];
                            }

                            return $primary_keys;
                        }

function get_autoincrement_cols($table)
{
    global $link;
    $sql = "DESCRIBE $table";
    $result = mysqli_query($link, $sql);
    $auto_keys = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['Extra'] == 'auto_increment') {
            $auto_keys[] = $row['Field'];
        }
    }

    return $auto_keys;
}

function get_foreign_keys($table)
{
    global $link;
    global $db_name;
    $fks[] = '';
    $sql = "SELECT k.COLUMN_NAME as 'Foreign Key'
                                    FROM information_schema.TABLE_CONSTRAINTS i
                                    LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                                    WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND i.TABLE_NAME = '$table'";
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $fks[] = $row['Foreign Key'];
    }

    return $fks;
}

$checked_tables_counter = 0;
if (isset($_POST['table'])) {
    foreach ($_POST['table'] as $table) {
        if (isset($table['tablecheckbox']) && $table['tablecheckbox'] == 1) {
            $tablename = $table['tablename'];
            $tablecomment = $table['tablecomment'];
            $tabledisplay = $table['tabledisplay'];
            echo "<div class='text-center mb-4'><span data-toggle='tooltip' data-placement='top' title='$tablecomment'><b>Table: ".$tabledisplay.' ('.$tablename.')</b></span></div>';
            $columns = get_columns($tablename);
            $primary_keys = get_primary_keys($tablename);
            $auto_keys = get_autoincrement_cols($tablename);
            $foreign_keys = get_foreign_keys($tablename);

            foreach ($columns as $column) {
                $column_type = get_col_types($tablename, $column);
                $column_comment = get_col_comments($tablename, $column);
                $column_nullable = get_col_nullable($tablename, $column);
                $columnname = $column;

                if (in_array("$column", $primary_keys)) {
                    $primary = '🔑';
                    echo '<input type="hidden" name="'.$tablename.'columns['.$columnname.'][primary]" value="'.$primary.'"/>';
                } else {
                    $primary = '';
                }

                if (in_array("$column", $auto_keys)) {
                    $auto = '🔒';
                    echo '<input type="hidden" name="'.$tablename.'columns['.$columnname.'][auto]" value="'.$auto.'"/>';
                } else {
                    $auto = '';
                }

                if (in_array("$column", $foreign_keys)) {
                    $fk = '🛅';
                    echo '<input type="hidden" name="'.$tablename.'columns['.$columnname.'][fk]" value="'.$fk.'"/>';
                } else {
                    $fk = '';
                }

                if ($column_nullable) {
                    $nb = '🫙';
                } else {
                    $nb = '';
                }

                echo "<span data-toggle='tooltip' data-placement='top' data-bs-html=\"true\" title=".prepare_text_for_tooltip($column_comment).'>';
                echo '<div class="row align-items-center mb-2">
                                    <div class="col-2 text-right">
                                        <label class="col-form-label" for="'.$tablename.'">'.$primary.$auto.$fk.$nb.$column.' </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="hidden" name="'.$tablename.'columns['.$columnname.'][tablename]" value="'.$tablename.'"/>
                                        <input type="hidden" name="'.$tablename.'columns['.$columnname.'][tabledisplay]" value="'.$tabledisplay.'"/>
                                        <input type="hidden" name="'.$tablename.'columns['.$columnname.'][tablecomment]" value="'.$tablecomment.'"/>
                                        <input type="hidden" name="'.$tablename.'columns['.$columnname.'][columnname]" value="'.$columnname.'"/>
                                        <input type="hidden" name="'.$tablename.'columns['.$columnname.'][columntype]" value="'.$column_type.'"/>
                                        <input type="hidden" name="'.$tablename.'columns['.$columnname.'][columncomment]" value="'.$column_comment.'"/>
                                        <input type="hidden" name="'.$tablename.'columns['.$columnname.'][columnnullable]" value="'.$column_nullable.'"/>
                                        <input id="textinput_'.$tablename.'-'.$columnname.'"name="'.$tablename.'columns['.$columnname.'][columndisplay]" type="text" placeholder="Display field name in frontend" class="form-control rounded-0">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="checkbox"  name="'.$tablename.'columns['.$columnname.'][columnvisible]" id="checkboxes-'.$checked_tables_counter.'-'.$columnname.'" value="1" checked>
                                <label for="checkboxes-'.$checked_tables_counter.'-'.$columnname.'">Visible in overview?</label></div>
                                    <div class="col-md-2">
                                        <input type="checkbox"  name="'.$tablename.'columns['.$columnname.'][columninpreview]" id="checkboxes-'.$checked_tables_counter.'-'.$columnname.'-2" value="1" checked>
                                <label for="checkboxes-'.$checked_tables_counter.'-'.$columnname.'-2">Visible in preview?</label></div>
                     </div></span>';
            }
            $checked_tables_counter++;
        }
    }
}
?>

                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <p class="form-check">
                                    <small id="passwordHelpBlock" class="form-text text-muted">
                                        Cruddiy will create a fresh startpage in the app/ sub-folder, with link<?php echo $checked_tables_counter > 1 ? 's' : '' ?> to manage the table<?php echo $checked_tables_counter > 1 ? 's' : '' ?> above.<br>
                                        If you have used Cruddiy on other tables before, your start page will be replaced by the fresh one, and previous links will be lost.
                                    </small>
                                    <input class="form-check-input" type="checkbox" value="true" id="keep_startpage" name="keep_startpage">
                                    <label class="form-check-label" for="keep_startpage">
                                        Keep previously generated startpage and CRUD pages if they exist
                                    </label>
                                    <br>
                                    <input class="form-check-input" type="checkbox" value="true" id="append_links" name="append_links">
                                    <label class="form-check-label" for="append_links">
                                        Append new link<?php echo $checked_tables_counter > 1 ? 's' : '' ?> to previously generated startpage if necessary
                                    </label>
                                </p>
                            </div>
                            <div class="col-md-8 mx-auto">
                                <button type="submit" id="singlebutton" name="singlebutton" class="btn btn-success btn-block rounded-0 shadow-sm">Generate Pages</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</section>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<script>
$(document).ready(function () {
    $('#checkall-1').click(function(e) {
        var chb = $('.form-horizontal').find('input[name$="[columnvisible]"]');
        chb.prop('checked', !chb.prop('checked'));
    });
});
$(document).ready(function () {
    $('#checkall-2').click(function(e) {
        var chb = $('.form-horizontal').find('input[name$="[columninpreview]"]');
        chb.prop('checked', !chb.prop('checked'));
    });
});
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});

<?php echo "const config_folder = '$config_folder';";
?>

    // Switch configs bij generic peilstanden
    function fill_config() {
        config = $("#config_select").val();
        $.ajax({
            cache: false,
            url: config_folder + config + ".json",
            dataType: "json",
            success: function (data) {
                var inputs = $('#cruddiy_data input,select');

                inputs.prop('checked', false);
                $.each(data, function (key, value) {
                    inputs.filter(function () {
                        return key == this.name;
                    }).val(value).prop('checked', true);
                });

            }
        });
    }
    $("#config_select").change(fill_config);

</script>
</body>
</html>
