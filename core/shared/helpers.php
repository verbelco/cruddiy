<?php

$sortBy = ['asc' => 'ASC', 'dsc' => 'DESC'];

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
        return $var ? 'True' : 'False';
    }
}

function get_fk_url($value, $fk_table, $fk_column, $representation, bool $pk = false, bool $index = false)
// Gets a URL to the foreign key parents read page
{
    if (isset($value)) {
        $value = htmlspecialchars($value);
        if ($pk) {
            return '<a href="../'.$fk_table.'/read.php?'.$fk_column.'='.$value.'">'.$representation.'</a>';
        } else {
            return '<a href="../'.$fk_table.'/index.php?'.$fk_column.urlencode('[=]').'='.$value.'">'.$representation.'</a>';
        }

    }
}

/** Return an array with $columnname => asc/dsc, signaling what to sort on */
function get_orderby_array($given_order_array, $column_list)
{
    global $sortBy;

    // Check if the column and sortBy are known
    $given_order_array = array_filter($given_order_array, function ($c) use ($column_list, $sortBy) {
        return in_array(substr($c, 0, -3), array_keys($column_list)) && isset($sortBy[substr($c, -3)]);
    });

    $result = [];

    foreach ($given_order_array as $str) {
        $column = substr($str, 0, -3);
        $sort = substr($str, -3);
        $result[$column] = $sort;
    }

    return $result;
}

/** Return a string displaying what is being sorted on */
function get_ordering_on($order_param_array, $column_list): string
{
    global $sortBy;

    $ordering = [];
    foreach ($order_param_array as $c => $s) {
        $ordering[] = $column_list[$c]->html_columnname_with_tooltip(false).' '.$sortBy[$s];
    }

    return implode(', ', $ordering);
}

/** Return the SQL code for ordering */
function get_orderby_clause($order_param_array, $column_list): string
{
    global $sortBy;

    $ordering = [];
    foreach ($order_param_array as $c => $s) {
        $ordering[] = $column_list[$c]->get_sql_value().' '.$sortBy[$s];
    }

    if (empty($ordering)) {
        return '';
    } else {
        return 'ORDER BY '.implode(', ', $ordering);
    }
}

/** For column $column, return the get parameters to follow up on the current ordering.
 * Also return $arrow, displaying what this column is sorted on.
 */
function get_order_parameters($get_array, $column = null)
{
    $arrow = '';
    $result = '';
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
    $filter = [];
    // Loop over all columns
    foreach ($where_columns as $column => $f_array) {
        // Loop over all restrictions per column
        foreach ($f_array as $operand => $val) {
            if ($operand == 0) {
                $operand = '=';
            }
            if (in_array($operand, ['=', '>', '<', '%', 'null'])) {
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
    $get_param_where = '';
    foreach ($filter as $c => $fs) {
        if (isset($column_list[$c])) {
            [$g, $w] = $column_list[$c]->create_sql_where($fs, $link);
            $get_param_where .= $g;
            $where_statements = array_merge($where_statements, $w);
        }
    }
    $where_clause = count($where_statements) > 0 ? 'WHERE true AND '.implode(' AND ', $where_statements) : 'WHERE true';

    return [$get_param_where, $where_clause];
}

// Helpers voor extensies:

/** Return de HTML om een geldigheidsuitbreiding te starten
 * @param  string  $id  de kolomnaam van de primaire key (Meestal id).
 * @param  string  $Start  De datum waarop het huidige record begint.
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
                <input type='hidden' name='$id' value='".$_GET[$id]."'>
                <input type='hidden' name='duplicate' value='".$_GET[$id]."'>
                <input type='submit' value='Nieuw geldigheidsrecord' class='btn btn-outline-dark'>
            </div>
        </div>
    </form>";
}

/**
 * Functie om de einddatum in te vullen en een kopie van dit record te openen (dit gebeurd op de duplicate pagina)
 * Hier zetten we de einddatum in de database vullen we deze in het start veld in.
 *
 * @param  string  $id_kolom  Kolomnaam van de primaire key
 * @param  string  $tablename  Naam van de tabel zoals hij in de database staat
 * @param  string  $start  Kolomnaam van de start kolom (meestal start of Start)
 * @param  string  $eind  Kolomnaam van de eind kolom (meestal eind of Eind)
 * @return string $html met javascript code om de informatie op het scherm te zetten
 */
function process_geldigheids_extensie(string $id_kolom, string $tablename, string $start, string $eind)
{
    global $db;
    $id = (int) $_GET[$id_kolom];
    $einddatum = $_GET['einddatum'];

    $stmt = $db->prepare("UPDATE `$tablename` SET `$eind` = ? WHERE `$id_kolom` = ?");
    try {
        $stmt->bind_param('si', $einddatum, $id);
        $stmt->execute();
        $bericht = "Bij $tablename record met $id_kolom $id is einddatum $einddatum ingevuld.";
        $state = 'success';
    } catch (Exception $e) {
        $bericht = "Einddatum kon niet worden ingevuld bij $tablename record $id. [".$e->getMessage().']';
        $state = 'danger';
    }
    $html = '<script>';

    // Presenteer het bericht en de state
    $alert = "<div class='alert alert-$state'>$bericht</div>";

    $html .= "$('.page-header').append(\"$alert\");";

    // Vul de einddatum in bij start en zet eind op null
    $html .= "$('#$start').val('$einddatum');";
    $html .= "$('#$eind').val('');";
    $html .= '</script>';

    return $html;
}

/** Return de link om naar de index pagina te gaan en de filters toe te passen die overeenkomen met deze reference. Zie html_delete_references() voor meer informatie. */
function get_reference_url(array $reference)
{
    return '../'.$reference['table'].'/index.php?'.$reference['column'].urlencode('[=]').'='.$reference['local_value'];
}

/** Print de references naar dit record
 * @param  ?array  $reference  lijst met associative arrays met count, table, fk_table, column, fk_column en local_value.
 *                             table en column verwijzen naar de brontabel (waar vanuit verwezen wordt).
 *                             fk_table en fk_column verwijzen naar de tabel/kolom waarnaar verwezen wordt. (Dit is de tabel waarvan een record verwijderd wordt)
 *                             count bevat het aantal references naar deze kolom.
 *                             local_value is de waarde van de kolom waarnaar verwezen wordt.
 */
function html_delete_references(?array $references)
{
    if (isset($references)) {
        $html = '';

        foreach ($references as $r) {
            $html .= '<p> There are <a href="'.get_reference_url($r).'"> '.$r['count'].' '.$r['table'].' with '.$r['column'].' = '.$r['local_value'].'</a>. Be careful when deleting!</p>';
        }

        if ($html != '') {
            return '<div class="alert alert-warning">'.$html.'</div>';
        } else {
            return '';
        }
    }
}

/** Vergelijkbaar met html_delete_references(), maar toont dan een knop om door te verwijzen. */
function html_read_references(?array $references)
{
    if (isset($references)) {
        $html = '';

        foreach ($references as $r) {
            $html .= '<p><a href="'.get_reference_url($r).'" class="btn btn-info">View '.$r['count'].' '.$r['table'].' with '.$r['column'].' = '.$r['local_value'].'</a></p>';
        }

        if ($html != '') {
            return '<div><h3>References to this '.$r['fk_table'].':</h3>'.$html.'</div>';
        } else {
            return '';
        }
    }
}
