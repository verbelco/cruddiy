<?php

function getLabelsFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('labels.template.ts', $modelName, $variableName, $routeName);

    $labels = implode("\n", array_map(fn ($column) => "  {$column}: '{$column},'", get_columns($table)));
    $result = str_replace('{Labels}', $labels, $result);

    return $result;
}

function getTooltipsFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('tooltips.template.ts', $modelName, $variableName, $routeName);

    $tooltips = implode("\n", array_map(fn ($column) => "  {$column}: '".get_col_comments($table, $column)."'", get_columns($table)));
    $result = str_replace('{Tooltips}', $tooltips, $result);

    return $result;
}

function get_enum_type($table, $column): string
{
    $enum_values = get_enums($table, $column);

    return "export enum $column {".implode('', array_map(fn ($val) => "\n  ".PascalCase($val).": '".$val."'", $enum_values))."\n}";
}

function getTypesFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('types.template.ts', $modelName, $variableName, $routeName);

    $columns = get_columns($table);
    $types = implode("\n", array_map(fn ($column) => "  {$column}: ".get_react_type($table, $column).';', $columns));
    $result = str_replace('{Types}', $types, $result);

    $enumCols = array_filter($columns, fn ($c) => column_type(get_col_types($table, $c)) == 2);
    $enums = implode("\n\n", array_map(fn ($c) => get_enum_type($table, $c), $enumCols));
    $result = str_replace('{Enums}', $enums, $result);

    return $result;
}

function getUseFiltersFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('useFilters.template.ts', $modelName, $variableName, $routeName);

    $columns = implode("\n", array_map(fn ($column) => "    {$column}: true,", get_columns($table)));
    $result = str_replace('{Columns}', $columns, $result);

    return $result;
}

function getUseQueryFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('useQuery.template.ts', $modelName, $variableName, $routeName);

    return $result;
}

function getRoutes(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('routes.template.tsx', $modelName, $variableName, $routeName);

    return $result;
}

function getFormFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('form.template.tsx', $modelName, $variableName, $routeName);

    $columns = get_columns($table);
    unset($columns[0]);

    $defaultValues = implode("\n", array_map(fn ($column) => "      {$column}: {$variableName}.$column,", $columns));
    $result = str_replace('{defaultValues}', $defaultValues, $result);

    $fields = implode(",\n", array_map(fn ($column) => getReactFormElement($column, $table, $variableName), $columns));
    $result = str_replace('{fields}', $fields, $result);

    return $result;
}

function getShowFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('show.template.tsx', $modelName, $variableName, $routeName);

    $columns = get_columns($table);
    $details = implode(",\n", array_map(fn ($column) => getReactDetailElement($column, $table, $variableName), $columns));
    $result = str_replace('{details}', $details, $result);

    return $result;
}

function getViewFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('view.template.tsx', $modelName, $variableName, $routeName);

    $columns = get_columns($table);
    $tableColumns = implode(",\n", array_map(fn ($column) => getReactTableColumnElement($column, $table, $variableName), $columns));
    $result = str_replace('{tableColumns}', $tableColumns, $result);

    return $result;
}

function getRequestFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('request.template.php', $modelName, $variableName, $routeName);

    $columns = get_columns($table);
    unset($columns[0]);
    $columns = implode(",\n", array_map(fn ($column) => getLaravelRequestValidation($column, $table, $variableName), $columns));
    $result = str_replace('{columns}', $columns, $result);

    return $result;
}
function getResourceFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('resource.template.php', $modelName, $variableName, $routeName);

    $columns = implode(",\n", array_map(fn ($column) => "            '{$column}' => \$this->{$column}", get_columns($table)));
    $result = str_replace('{columns}', $columns, $result);

    return $result;
}
