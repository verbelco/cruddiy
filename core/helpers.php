<?php
// retrieves and enhances postdata table keys and values on CREATE and UPDATE events
function parse_columns($table_name, $postdata)
{
    global $link;
    $vars = array();

    // prepare a default return value
    $default = null;

    // get all columns, including the ones not sent by the CRUD form
    $sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '" . $table_name . "'";
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {

        $debug = 0;
        if ($debug) {
            echo "<pre>";
            // print_r($postdata);
            echo $row['COLUMN_NAME'] . "\t";
            echo $row['DATA_TYPE'] . "\t";
            echo $row['IS_NULLABLE'] . "\t";
            echo $row['COLUMN_DEFAULT'] . "\t";
            echo $row['EXTRA'] . "\t";
            echo $default . "\n";
            echo "</pre>";
        }

        switch ($row['DATA_TYPE']) {

            // fix "Incorrect decimal value: '' error in STRICT_MODE or STRICT_TRANS_TABLE
            // @see https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html
            case 'decimal':
                $default = 0;
                break;

            // fix "Incorrect datetime value: '0' " on non-null datetime columns
            // with 'CURRENT_TIMESTAMP' default not being set automatically
            // and refusing to take NULL value
            case 'datetime':
                if ($row['COLUMN_DEFAULT'] != 'CURRENT_TIMESTAMP' && $row['IS_NULLABLE'] == 'YES') {
                    $default = null;
                } else {
                    $default = date('Y-m-d H:i:s');
                }
                if ($postdata[$row['COLUMN_NAME']] == 'CURRENT_TIMESTAMP') {
                    $_POST[$row['COLUMN_NAME']] = date('Y-m-d H:i:s');
                }
                break;
        }

        // check that fieldname was set before sending values to pdo
        $vars[$row['COLUMN_NAME']] = isset($_POST[$row['COLUMN_NAME']]) && $_POST[$row['COLUMN_NAME']] ? trim($_POST[$row['COLUMN_NAME']]) : $default;
    }
    return $vars;
}



// get extra attributes for  table keys on CREATE and UPDATE events
function get_columns_attributes($table_name, $column)
{
    global $link;
    $sql = "SELECT COLUMN_DEFAULT, COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '" . $table_name . "'
            AND column_name = '" . $column . "'";
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $debug = 0;
        if ($debug) {
            echo "<pre>";
            print_r($row);
            echo "</pre>";
        }
        return $row;
    }
}

function print_error_if_exists($error)
{
    if (isset($error)) {
        echo "<div class='alert alert-danger' role='alert'>$error</div>";
    }
}

function print_message_if_exists($message)
{
    if (isset($message)) {
        echo "<div class='alert alert-success' role='alert'>$message</div>";
    }
}

function convert_date($date_str)
{
    if (isset($date_str)) {
        $date = date('d-m-Y', strtotime($date_str));
        return htmlspecialchars($date);
    }
}

function convert_datetime($date_str)
{
    if (isset($date_str)) {
        $date = date('d-m-Y H:i:s', strtotime($date_str));
        return htmlspecialchars($date);
    }
}

function convert_bool($var)
{
    if (isset($var)) {
        return $var ? "True" : "False";
    }
}

function get_fk_url($value, $fk_table, $fk_column, $representation, bool $pk=false, bool $index=false)
// Gets a URL to the foreign key parents read page
{
    if (isset($value)) {
        $value = htmlspecialchars($value);
        if($pk)
        {
            return '<a href="../' . $fk_table . '/read.php?' . $fk_column . '=' . $value . '">' . $representation . '</a>';
        }
        else
        {
            return '<a href="../' . $fk_table . '/index.php?' . $fk_column . '=' . $value . '">' . $representation . '</a>';
        }
        
    }
}

function get_orderby_clause($given_order_array, $columns, $column_id, $table_name)
{
    $sortBy = array('asc' => 'ASC', 'dsc' => 'DESC');
    $orderclause = "";
    $ordering_on = "";
    $get_param_array = array();
    $default_ordering = false;
    if (isset($given_order_array)) {
        foreach ($given_order_array as $i => $str) {
            $column = substr($str, 0, -3);
            if (in_array($column, $columns)) {
                $s = substr($str, -3);
                if (isset($sortBy[$s])) {
                    $sort = $sortBy[$s];
                    $orderclause .= $orderclause == "" ? "`$table_name`.`$column` $sort" : ", `$table_name`.`$column` $sort";
                    $ordering_on .= $ordering_on == "" ? "$column $sort" : ", $column $sort";
                    $get_param_array[$column] = $s;
                }
            }
        }
    }
    
    // Default to ordering on the primary key
    if($orderclause == "")
    {
        $orderclause = "`$table_name`.`$column_id` " . $sortBy['asc'];
        $ordering_on = $column_id . ' ' . $sortBy['asc'];
        $get_param_array[$column_id] = 'asc';
        $default_ordering = true;
    }
     return [$orderclause, $ordering_on, $get_param_array, $default_ordering];
}

function get_order_parameters($get_array, $column)
{
    $arrow = "";
    $result = "";
    if (isset($get_array[$column])) {
        $sort = $get_array[$column] == 'asc' ? 'dsc' : 'asc';
        $arrow = $get_array[$column] == 'asc' ? '⇡' : '⇣';
        unset($get_array[$column]); // Move the newly selected column to the back
        $get_array[$column] = $sort;
    } else {
        $get_array[$column] = 'asc';
    }

    // Turn the array into a get string
    foreach ($get_array as $col => $sort){
        $result .= "&order[]=$col$sort";
    }
    return [$result, $arrow];
}

?>