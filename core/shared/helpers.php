<?php
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

function get_fk_url($value, $fk_table, $fk_column, $representation, bool $pk = false, bool $index = false)
// Gets a URL to the foreign key parents read page
{
    if (isset($value)) {
        $value = htmlspecialchars($value);
        if ($pk) {
            return '<a href="../' . $fk_table . '/read.php?' . $fk_column . '=' . $value . '">' . $representation . '</a>';
        } else {
            return '<a href="../' . $fk_table . '/index.php?' . $fk_column . '[]=' . $value . '">' . $representation . '</a>';
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
    if ($orderclause == "") {
        $orderclause = "`$table_name`.`$column_id` " . $sortBy['asc'];
        $ordering_on = $column_id . ' ' . $sortBy['asc'];
        $get_param_array[$column_id] = 'asc';
        $default_ordering = true;
    }
    return [$orderclause, $ordering_on, $get_param_array, $default_ordering];
}

function get_order_parameters($get_array, $column = null)
{
    $arrow = "";
    $result = "";
    if (isset($get_array[$column])) {
        $sort = $get_array[$column] == 'asc' ? 'dsc' : 'asc';
        $arrow = $get_array[$column] == 'asc' ? '⇡' : '⇣';
        unset($get_array[$column]); // Move the newly selected column to the back
        $get_array[$column] = $sort;
    } elseif (isset($column)) {
        $get_array[$column] = 'asc';
    }

    // Turn the array into a get string
    foreach ($get_array as $col => $sort) {
        $result .= "&order[]=$col$sort";
    }
    return [$result, $arrow];
}

function create_sql_filter_array($where_columns)
{
    $filter = array();
    // Loop over all columns
    foreach ($where_columns as $column => $f_array) {
        // Loop over all restrictions per column
        foreach ($f_array as $operand => $val) {
            if ($operand == 0) {
                $operand = '=';
            }
            if (in_array($operand, ['=', '>', '<', '%'])) {
                $filter[$column][$operand] = $val;
            }
        }
    }
    return $filter;
}

function create_sql_where($filter, $table_name, $link)
{
    $get_param_where = "";
    $where_statement = " WHERE 1=1 ";
    // Loop over all columns
    foreach ($filter as $column => $f_array) {
        // Loop over all restrictions per column
        foreach ($f_array as $operand => $val) {
            if ($operand == '%') {
                $where_statement .= " AND `$table_name`.`$column` LIKE '%" . mysqli_real_escape_string($link, $val) . "%' ";
            } elseif ($operand == '=' && $val == 'null') {
                $where_statement .= " AND `$table_name`.`$column` IS NULL";
            } else {
                $where_statement .= " AND `$table_name`.`$column` $operand '" . mysqli_real_escape_string($link, $val) . "' ";
            }
            $get_param_where .= "&$column" . '[' . $operand . "]=$val";
        }
    }
    return [$get_param_where, $where_statement];
}

?>