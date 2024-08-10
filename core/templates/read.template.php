<?php
// Check existence of id parameter before processing further
$_GET["{TABLE_ID}"] = trim($_GET["{TABLE_ID}"]);
if (!empty($_GET["{TABLE_ID}"])) {
    // Include config file
    require_once "../config.php";
    require_once "../shared/helpers.php";
    require_once "class.php";

    $original_column_list = $CRUD['{TABLE_NAME}']->get_original_columns();
    $read_only_columns_list = $CRUD['{TABLE_NAME}']->get_read_only_columns();

    $column_list = $original_column_list + $read_only_columns_list;

    include "pre_extension.php";

    // Prepare a select statement
    $sql_select = implode(", ", array_map(function ($c) {
        return $c->get_sql_select_alias();
    }, $column_list));
    $sql_join = implode("", array_map(function ($c) {
        return $c->get_sql_join();
    }, $column_list));

    $sql = "SELECT $sql_select
            FROM `{TABLE_NAME}`
            $sql_join
            WHERE `{TABLE_NAME}`.`{TABLE_ID}` = ?
            GROUP BY `{TABLE_NAME}`.`{TABLE_ID}`;";

    $stmt = $link->prepare($sql);
    $param_id = trim($_GET["{TABLE_ID}"]);
    $stmt->execute([$param_id]);
    $result = $stmt->get_result();

    if (mysqli_num_rows($result) == 1) {
        /* Fetch result row as an associative array. Since the result set
        contains only one row, we don't need to use while loop */
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    } else {
        // URL doesn't contain valid id parameter. Redirect to error page
        header("location: ../error.php");
        exit();
    }

    // Close statement
    mysqli_stmt_close($stmt);

} else {
    // URL doesn't contain id parameter. Redirect to error page
    header("location: ../error.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View {TABLE_NAME}</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once "../shared/navbar.php"; ?>

<body class="bg-light">
    <div class="container-lg bg-white py-5 shadow">
        <div class="row">
            <div class="col-md-7 mx-auto">
                <div class="page-header">
                    <h1>View {TABLE_NAME}</h1>
                </div>
                <?php print_error_if_exists($error);
                print_message_if_exists($message); ?>
                <div>
                    <?php
                    if (count($read_only_columns_list) > 0) {
                        echo "<h5>Original columns</h5>";
                    }
                    foreach ($original_column_list as $name => $c) {
                        echo $c->html_read_row($row);
                    }
                    if (count($read_only_columns_list) > 0) {
                        echo "<h5>Read-only columns</h5>";
                        foreach ($read_only_columns_list as $name => $c) {
                            echo $c->html_read_row($row);
                        }
                    }
                    ?>
                </div>
                <div class="mt-3 mb-5">
                    <a href="../{TABLE_NAME}/update.php?{TABLE_ID}=<?php echo $_GET["{TABLE_ID}"]; ?>"
                        class="btn btn-secondary">Edit</a>
                    <a href="../{TABLE_NAME}/create.php?duplicate=<?php echo $_GET["{TABLE_ID}"]; ?>"
                        class="btn btn-info">Duplicate</a>
                    <a href="../{TABLE_NAME}/delete.php?{TABLE_ID}=<?php echo $_GET["{TABLE_ID}"]; ?>"
                        class="btn btn-warning">Delete</a>
                    <a href="../{TABLE_NAME}/index.php" class="btn btn-primary">Back to index</a>
                </div>
                <?php
                // Look for references to this record
                echo $CRUD['{TABLE_NAME}']->html_read_references($param_id);
                
                include "post_extension.php";
                ?>
            </div>
        </div>
    </div>
</body>

</html>