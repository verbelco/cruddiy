<?php

require_once "../app/config.php";
require_once "../helpers.php";

function getLabelsFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('labels.template.ts', $modelName, $variableName, $routeName);

    $labels = implode("\n", array_map(fn($column) => "  {$column}: '{$column},'",get_columns($table)));
    $result = str_replace('{Labels}', $labels, $result);

    return $result;
}

function getTooltipsFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('tooltips.template.ts', $modelName, $variableName, $routeName);

    $tooltips = implode("\n", array_map(fn($column) => "  {$column}: '" . get_col_comments($table, $column) ."'",get_columns($table)));
    $result = str_replace('{Tooltips}', $tooltips, $result);

    return $result;
}

function getTypesFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('types.template.ts', $modelName, $variableName, $routeName);

    $types = implode("\n", array_map(fn($column) => "  {$column}: " . get_react_type($table, $column) .";",get_columns($table)));
    $result = str_replace('{Types}', $types, $result);

    return $result;
}

function getUseFiltersFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('useFilters.template.ts', $modelName, $variableName, $routeName);

    $columns = implode("\n", array_map(fn($column) => "    {$column}: true,",get_columns($table)));
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

    $defaultValues = implode("\n", array_map(fn($column) => "      {$column}: {$variableName}.$column,", $columns));
    $result = str_replace('{defaultValues}', $defaultValues, $result);

    $fields = implode(",\n", array_map(fn($column) => getReactFormElement($column, $table, $variableName), $columns));
    $result = str_replace('{fields}', $fields, $result);

    return $result;
}

function getShowFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('show.template.tsx', $modelName, $variableName, $routeName);

    $columns = get_columns($table);
    $details = implode(",\n", array_map(fn($column) => getReactDetailElement($column, $table, $variableName), $columns));
    $result = str_replace('{details}', $details, $result);

    return $result;
}

function getViewFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('view.template.tsx', $modelName, $variableName, $routeName);

    $columns = get_columns($table);
    $tableColumns = implode(",\n", array_map(fn($column) => getReactTableColumnElement($column, $table, $variableName), $columns));
    $result = str_replace('{tableColumns}', $tableColumns, $result);

    return $result;
}

function getRequestFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('request.template.php', $modelName, $variableName, $routeName);

    $columns = implode(",\n", array_map(fn($column) => getLaravelRequestValidation($column, $table, $variableName), get_columns($table)));
    $result = str_replace('{columns}', $columns, $result);

    return $result;
}
function getResourceFile(string $table, string $modelName, string $variableName, string $routeName): string
{
    $result = getTemplate('resource.template.php', $modelName, $variableName, $routeName);

    $columns = implode(",\n", array_map(fn($column) => "            '{$column}' => z\$this->{$column}", get_columns($table)));
    $result = str_replace('{columns}', $columns, $result);

    return $result;
}


?>
<!doctype html>
<html lang="en">

<head>
    <title>Export table for Laravel / React</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
</head>

<body class="bg-light">
    <section class="py-5">
        <div class="container bg-white shadow py-5">
            <div class="row">
                <div class="col-md-12 mx-auto p-5">
                    <?php
                    $table = $_GET['table'];
                    $modelName = $_GET['modelName'] ?? str_replace('_', '', $table);
                    $variableName = $_GET['variableName'] ?? camelCase($table);
                    $routeName = $_GET['routeName'] ?? kebabCase($table);
                    ?>

                    <form method="GET">
                        Table: <input name="table" value="<?php echo $table;?>"> <br>
                        ModelName: <input name="modelName" value="<?php echo $modelName;?>"> <br>
                        VariableName: <input name="variableName" value="<?php echo $variableName;?>"> <br>
                        RouteName: <input name="routeName" value="<?php echo $routeName;?>"> <br>
                        <input type='submit'>
                    </form>


                    <div class="row">
                        <h3>Types</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getTypesFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>Labels</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getLabelsFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>Tooltips</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getTooltipsFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>useFilters</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getUseFiltersFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>useQuery</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getUseQueryFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>Form</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getFormFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>Show</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getShowFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>View</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getViewFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>Routes</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getRoutes($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>Request</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getRequestFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                    <div class="row">
                        <h3>Resource</h3>
                    </div>
                    <div class="row bg-light p-3">
                        <pre><?php echo htmlspecialchars(getResourceFile($table, $modelName, $variableName, $routeName)); ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>

</html>