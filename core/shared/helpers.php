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
            return '<a href="../' . $fk_table . '/index.php?' . $fk_column . urlencode("[=]") . '=' . $value . '">' . $representation . '</a>';
        }

    }
}

function get_orderby_clause($given_order_array, $column_list, $column_id, $table_name)
{
    $sortBy = array('asc' => 'ASC', 'dsc' => 'DESC');
    $orderclause = "";
    $ordering_on = "";
    $get_param_array = array();
    $default_ordering = false;
    if (isset($given_order_array)) {
        foreach ($given_order_array as $i => $str) {
            $column = substr($str, 0, -3);
            if (isset($column_list[$column])) {
                $s = substr($str, -3);
                if (isset($sortBy[$s])) {
                    $select = $column_list[$column]->get_sql_value();
                    $sort = $sortBy[$s];
                    $orderclause .= $orderclause == "" ? "$select $sort" : ", $select $sort";
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

/** Create the WHERE statement and WHERE clause */
function create_sql_where($column_list, $filter, $link)
{
    $where_statements = [];
    $get_param_where = "";
    foreach ($filter as $c => $fs) {
        if (isset($column_list[$c])) {
            [$g, $w] = $column_list[$c]->create_sql_where($fs, $link);
            $get_param_where .= $g;
            $where_statements = array_merge($where_statements, $w);
        }
    }
    $where_clause = count($where_statements) > 0 ? "WHERE true AND " . implode(" AND ", $where_statements) : "WHERE true";
    return [$get_param_where, $where_clause];
}

// Helpers voor extensies:

/** Return de HTML om een geldigheidsuitbreiding te starten 
 * @param string $id de kolomnaam van de primaire key (Meestal id).
 * @param string $Start De datum waarop het huidige record begint.
 */
function get_geldigheids_extensie_form(string $id, ?string $Start)
{
    return "
    <div class='page-header'>
        <h5>Nieuw geldigheidsrecord:</h5>
    </div>
    <p> Vul de einddatum in van dit record en klik dan op \"Nieuw geldigheidsrecord\".
        Het systeem eindigt dan het huidige record met de gegeven datum en vult deze gegevens alvast in.
    </p>
    <form action='create.php' method='GET'>
        <div class='form-group row my-2'>
            <div class='col'>
                <input type='datetime-local' name='einddatum' class='form-control' min='$Start' max='9999-12-31 00:00' required>
            </div>
            <div class='col'>
                <input type='hidden' name='$id' value='" . $_GET[$id] . "'>
                <input type='hidden' name='duplicate' value='" . $_GET[$id] . "'>
                <input type='submit' value='Nieuw geldigheidsrecord' class='btn btn-outline-dark'>
            </div>
        </div>
    </form>";
}

/**
 * Functie om de einddatum in te vullen en een kopie van dit record te openen (dit gebeurd op de duplicate pagina)
 * Hier zetten we de einddatum in de database vullen we deze in het start veld in.
 * @param string $id_kolom Kolomnaam van de primaire key
 * @param string $tablename Naam van de tabel zoals hij in de database staat
 * @param string $start Kolomnaam van de start kolom (meestal start of Start)
 * @param string $eind Kolomnaam van de eind kolom (meestal eind of Eind)
 * @return string $html met javascript code om de informatie op het scherm te zetten
 */
function process_geldigheids_extensie(string $id_kolom, string $tablename, string $start, string $eind)
{
    global $db;
    $id = (int) $_GET[$id_kolom];
    $einddatum = $_GET["einddatum"];

    $stmt = $db->prepare("UPDATE `$tablename` SET `$eind` = ? WHERE `$id_kolom` = ?");
    try {
        $stmt->bind_param("si", $einddatum, $id);
        $stmt->execute();
        $bericht = "Bij $tablename record met $id_kolom $id is einddatum $einddatum ingevuld.";
        $state = "success";
    } catch (Exception $e) {
        $bericht = "Einddatum kon niet worden ingevuld bij $tablename record $id. [" . $e->getMessage() . "]";
        $state = "danger";
    }
    $html = "<script>";

    // Presenteer het bericht en de state
    $alert = "<div class='alert alert-$state'>$bericht</div>";

    $html .= "$('.page-header').append(\"$alert\");";

    // Vul de einddatum in bij start en zet eind op null
    $html .= "$('#$start').val('$einddatum');";
    $html .= "$('#$eind').val('');";
    $html .= "</script>";

    return $html;
}

/** Print de references naar dit record
 * @param ?array $reference lijst met associative arrays met count, table, fk_table, column, fk_column en value.
 * table en column verwijzen naar de brontabel (waar vanuit verwezen wordt).
 * fk_table en fk_column verwijzen naar de tabel/kolom waarnaar verwezen wordt.
 * count bevat het aantal references naar deze kolom.
 */
function html_delete_references(?array $references)
{
    if (isset($references)) {
        $html = "";

        foreach ($references as $r) {
            $html .= '<p> There are <a href="../' . $r['table'] . '/index.php?' . $r['column'] . urlencode('[=]') . "=" . $r['value'] . '"> ' . $r['count'] . ' ' . $r['table'] . ' with ' . $r['column'] . ' = ' . $r['value'] . '</a>. Be careful when deleting!</p>';
        }

        if ($html != "") {
            return '<div class="alert alert-warning">' . $html . '</div>';
        } else {
            return "";
        }
    }
}