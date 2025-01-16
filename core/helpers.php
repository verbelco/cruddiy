<?php

/** Prepares a string for a tooltip (replaces ' with ", replaces newlines with <br> and  surrounds with '') */
function prepare_text_for_tooltip($string)
{
    if (isset($string)) {
        return "'" . str_replace("'", '"', $string) . "'";
    } else {
        return "''";
    }
}

function get_fk_preview_queries($table, $join_name, &$sql_concat_select, &$sql_select, &$join_clauses)
{
    // This function goes over the preview columns of a table.
    global $preview_columns;
    foreach ($preview_columns[$table] as $column => $fk) {
        if ($fk) {
            // Reference is a foreign key to another table itself
            [$fk_table, $fk_column] = get_foreign_table_and_column($table, $column);
            if (isset($preview_columns[$fk_table])) {
                $new_join_name = $join_name . $fk_table;
                $join_clauses .= "\n\t\t\tLEFT JOIN `$fk_table` AS `$new_join_name` ON `$new_join_name`.`$fk_column` = `$join_name`.`$column`";
                get_fk_preview_queries($fk_table, $new_join_name, $sql_concat_select, $sql_select, $join_clauses);
            } else {
                $sql_concat_select[] = '`' . $join_name . '`.`' . $column . '`';
                $sql_select[] = '`' . $column . '`';
            }
        } else {
            $sql_concat_select[] = '`' . $join_name . '`.`' . $column . '`';
            $sql_select[] = '`' . $column . '`';
        }
    }
}

function is_primary_key($t, $c)
{
    $cols = $_POST[$t . 'columns'];
    foreach ($cols as $col) {
        if (isset($col['primary']) && $col['columnname'] == $c) {
            return 1;
        }
    }
    return 0;
}

function get_default_value($table, $column)
{
    // Get the default value of a column
    global $link;

    $sql = "SELECT COLUMN_DEFAULT AS `def`
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hydrodb' and TABLE_NAME = '$table' AND COLUMN_NAME = '$column';";
    $result = mysqli_query($link, $sql);
    if (mysqli_num_rows($result) == 1) {
        $def = mysqli_fetch_assoc($result)['def'];
    }

    // Rewrite some SQL to PHP
    if ($def == "current_timestamp()") {
        $def = "new DateTime()";
    }

    return !isset($def) || $def == "" || $def == "NULL" ? "null" : $def;
}

function get_foreign_table_and_column($tablename, $columnname)
{
    global $link;

    $sql_getfk = "SELECT i.TABLE_NAME as 'Table', k.COLUMN_NAME as 'Column',
    k.REFERENCED_TABLE_NAME as 'FK Table', k.REFERENCED_COLUMN_NAME as 'FK Column',
    i.CONSTRAINT_NAME as 'Constraint Name'
    FROM information_schema.TABLE_CONSTRAINTS i
    LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME
    WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND k.TABLE_NAME = '$tablename' AND k.COLUMN_NAME = '$columnname'";
    $result = mysqli_query($link, $sql_getfk);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $fk_table = $row["FK Table"];
            $fk_column = $row["FK Column"];
        }
        return [$fk_table, $fk_column];
    }
}

/** True if $column in $table is a json column */
function isJson($table, $column)
{
    global $link, $db_name;
    $sql = "SELECT *
            FROM information_schema.check_constraints
            WHERE constraint_schema = '$db_name' AND table_name = '$table' AND constraint_name = '$column' AND check_clause = 'json_valid(`$column`)'";
    $result = $link->query($sql);
    return $result->num_rows > 0;
}

function column_type($sql_column_def)
{
    switch ($sql_column_def) {
        case (preg_match("/text/i", $sql_column_def) ? true : false):
            return 1;
        case (preg_match("/enum/i", $sql_column_def) ? true : false):
            return 2;
        case (preg_match("/varchar/i", $sql_column_def) ? true : false):
            return 3;
        case (preg_match("/tinyint\(1\)/i", $sql_column_def) ? true : false):
            return 4;
        case (preg_match("/int/i", $sql_column_def) ? true : false):
            return 5;
        case (preg_match("/decimal/i", $sql_column_def) ? true : false):
            return 6;
        case (preg_match("/float/i", $sql_column_def) ? true : false):
            return 6;
        case (preg_match("/datetime/i", $sql_column_def) ? true : false):
            return 8;
        case (preg_match("/date/i", $sql_column_def) ? true : false):
            return 7;
        default:
            return 0;
    }
}

function type_to_str($type)
{
    return match($type) {
        1 => "text",
        2 => "enum",
        3 => "string",
        4 => "bool",
        5 => "int",
        6 => "float",
        7 => "date",
        8 => "datetime",
        default => 'string',
    };
}

function get_react_type($table, $column)
{
    $type = column_type(get_col_types($table, $column));
    $typeStr = match($type) {
        1 => "string",
        2 => $column,
        3 => "string",
        4 => "boolean",
        5 => "number",
        6 => "number",
        7 => "Date",
        8 => "Date",
        default => 'string'
    };

    $nullable = get_col_nullable($table, $column);
    if($nullable){
        $typeStr .= ' | undefined';
    }
    return $typeStr;
}

function type_to_php($type)
{
    return match($type) {
        1 => "string",
        2 => "string",
        3 => "string",
        4 => "bool",
        5 => "int",
        6 => "float",
        7 => "DateTime",
        8 => "DateTime",
        default => 'string',
    };
}

function type_to_laravel_rules($type)
{
    return match($type) {
        1 => "string",
        2 => "string",
        3 => "string",
        4 => "boolean",
        5 => "integer",
        6 => "numeric",
        7 => "date",
        8 => "datetime",
        default => 'string',
    };
}

function get_enums($tablename, $columnname): array
{
    global $link;
    $sql = "SELECT COLUMN_TYPE as AllPossibleEnumValues
            FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tablename' AND COLUMN_NAME = '$columnname';";
    $result = mysqli_query($link, $sql);
    $row = mysqli_fetch_array($result, MYSQLI_NUM);
    preg_match('/enum\((.*)\)$/', $row[0], $matches);

    return array_map(fn($enum) => str_replace("'", '',$enum), explode(',', $matches[1]));
}

/** Returns a string with a php list with enum values, such as:
 * ['UTC', 'Wintertijd (UTC+1)', 'Zomertijd (UTC+2)', 'Kalendertijd(UTC+1/UTC+2)']
 */
function get_enum_list($tablename, $columnname)
{    
    return "['" . implode("','", get_enums($tablename, $columnname)) . "']";
}

function create_column_object($name, $displayname, $comments, $table, $sql_join, $sql_select, $nullable, $primary_key, $type)
{

    if (empty($displayname)) {
        $displayname = $name;
    }

    $default = get_default_value($table, $name);

    $comments = empty($comments) ? "null" : prepare_text_for_tooltip($comments);
    $sql_join = empty($sql_join) ? "null" : '"' . $sql_join . '"';
    $sql_select = empty($sql_select) ? "null" : '"' . $sql_select . '"';

    $required = $nullable ? "False" : "True";
    $primary_key = $primary_key ? "False" : "True";
    $type = type_to_str($type);

    if ($sql_join != "null" && $sql_select != "null") {
        [$fk_table, $fk_column] = get_foreign_table_and_column($table, $name);
        $fk_primary = is_primary_key($fk_table, $fk_column) ? "True" : "False";
        return "'$name' => new ForeignKeyColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, '$fk_table', '$fk_column', $fk_primary, $required, $primary_key)";
    } else if (isJson($table, $name)) {
        return "'$name' => new JSONColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } else if ($type == "text") {
        return "'$name' => new TextColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } else if ($type == "enum") {
        $enum_list = get_enum_list($table, $name);
        $enum_dict = "array_combine($enum_list, $enum_list)";
        return "'$name' => new EnumColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key, $enum_dict)";
    } else if ($type == "bool") {
        return "'$name' => new BoolColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } else if ($type == "int") {
        return "'$name' => new IntColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } else if ($type == "float") {
        return "'$name' => new FloatColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } else if ($type == "date") {
        return "'$name' => new DateColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    } else if ($type == "datetime") {
        if ($name == "MutatieMoment") {
            return "'$name' => new MutatieMomentColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
        } else {
            return "'$name' => new DateTimeColumn('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
        }
    } else {
        // Default, usually strings
        return "'$name' => new Column('$name',\n '$displayname', $comments, '$table', $default, $sql_join, $sql_select, $required, $primary_key)";
    }
}

function create_db_attribute($name, $type, $comments, $nullable)
{
    $comments = empty($comments) ? "" : "/** " . str_replace("\n", "\n*", $comments) . " */\n";
    $nullable = $nullable ? "?" : "";

    $type = type_to_php($type);

    return $comments . "protected $nullable$type $$name;";
}

function create_constructor_parameter($name, $type, $nullable)
{
    $nullable = $nullable ? "?" : "";

    $type = type_to_php($type);

    return "$nullable$type $$name";
}


function PascalCase($string) {
    $string = preg_replace('/[^a-zA-Z0-9]+/', ' ', $string);
    $string = strtolower($string);
    $string = lcfirst(str_replace(' ', '', ucwords($string)));

    return ucwords($string);
}

function camelCase($string) {
    $string = preg_replace('/[^a-zA-Z0-9]+/', ' ', $string);
    $string = strtolower($string);
    $string = lcfirst(str_replace(' ', '', ucwords($string)));

    return $string;
}

function kebabCase($string) {
    return strtolower(str_replace([' ', '_'], ['-', '-'], $string));
}

/** Open a template and replace basic information */
function getTemplate(string $template, string $modelName, string $variableName, string $routeName): string
{
    $result = file_get_contents("templates/{$template}");

    $result = str_replace("{modelName}", $modelName, $result);
    $result = str_replace("{variableName}", $variableName, $result);
    $result = str_replace("{routeName}", $routeName, $result);
    
    return $result;
}

function get_columns($table): array
{
    global $link; 
    $sql = "SHOW columns FROM $table";
    $result = mysqli_query($link,$sql);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    return array_map(fn(array $row) => $row['Field'], $rows);
}

function get_col_types($table,$column){
    global $link; 
    $sql = "SHOW FIELDS FROM $table where FIELD ="."'".$column."'";
    $result = mysqli_query($link,$sql);
    $row = mysqli_fetch_assoc($result);
    return $row['Type'] ;
}

function get_col_comments($table,$column){
    global $link; 
    $sql = "SHOW FULL FIELDS FROM $table where FIELD ="."'".$column."'";
    $result = mysqli_query($link,$sql);
    $row = mysqli_fetch_assoc($result);
    return $row['Comment'] ;
}

function get_col_nullable($table,$column){
    global $link; 
    $sql = "SHOW FULL FIELDS FROM $table where FIELD ="."'".$column."'";
    $result = mysqli_query($link,$sql);
    $row = mysqli_fetch_assoc($result);
    return ($row['Null'] == "YES") ? true : 0;
}

function getReactFormElement(string $column, string $table, string $tableVariableName): string
{
    $required = get_col_nullable($table, $column) ? "" : "\n      required: true,";
    return "    {
      id: '{$column}',
      label: {$tableVariableName}Labels.{$column},
      name: '{$column}',$required
      tooltip: {$tableVariableName}Tooltips.{$column},
    }";
}

function getReactDetailElement(string $column, string $table, string $tableVariableName): string
{
    return "    {
      label: {$tableVariableName}Labels.{$column},
      value: {$tableVariableName}Data.{$column},
      tooltip: {$tableVariableName}Tooltips.{$column},
    }";
}

function getReactTableColumnElement(string $column, string $table, string $tableVariableName): string
{
    return "    {
      accessorKey: '{$column}',
      header: {$tableVariableName}Labels.{$column},
      Header: () => (
        <Tooltip title={{$tableVariableName}Tooltips.{$column}}>
          <p>{{$tableVariableName}Labels.{$column}}</p>
        </Tooltip>
      ),
    }";
}

function getLaravelRequestValidation(string $column, string $table, string $tableVariableName): string
{
    $required = get_col_nullable($table, $column) ? 'nullable' : 'required';
    $type = type_to_laravel_rules(column_type(get_col_types($table, $column)));
    $rules = [$required, $type];

    return "            '$column' => '" . implode('|', $rules)."'";
}