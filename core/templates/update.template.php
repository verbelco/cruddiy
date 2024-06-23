<?php
// Include config file
require_once "../config.php";
require_once "../shared/helpers.php";
require_once "class.php";

$original_column_list = $CRUD['{TABLE_NAME}']->get_original_columns();
$read_only_columns_list = $CRUD['{TABLE_NAME}']->get_read_only_columns();

include "pre_extension.php";

// Processing form data when form is submitted
if (!empty($_POST["{COLUMN_ID}"])) {
    $row = array();
    $update_stmts = [];
    foreach ($original_column_list as $name => $column) {
        if ($column->get_name() != "{COLUMN_ID}") {
            if (get_class($column) != "MutatieMomentColumn") {
                $row[$name] = $column->get_sql_update_value($_POST[$name]);
            }
            $update_stmts[] = $column->get_sql_update_stmt();
        }
    }

    $param_id = $_POST["{COLUMN_ID}"];
    $row["{COLUMN_ID}"] = $param_id;

    try {
        $stmt = $link->prepare("UPDATE `{TABLE_NAME}` SET " . implode(", ", $update_stmts) . " WHERE `{COLUMN_ID}`=?");
        $stmt->execute(array_values($row));
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = $e->getMessage();
    }

    if (!isset($error)) {
        header("location: ../{TABLE_NAME}/read.php?{COLUMN_ID}=$param_id");
    }
}

// Retrieve the values for this record
$_GET["{COLUMN_ID}"] = trim($_GET["{COLUMN_ID}"]);
if (!empty($_GET["{COLUMN_ID}"])) {
    if (!isset($error)) {
        // Get URL parameter
        $param_id = trim($_GET["{COLUMN_ID}"]);

        // Prepare a select statement
        $sql = "SELECT * FROM `{TABLE_NAME}` WHERE `{COLUMN_ID}` = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_execute($stmt, [$param_id]);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        } else {
            // URL doesn't contain valid id. Redirect to error page
            header("location: ../error.php");
            exit();
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
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
    <title>Update {TABLE_NAME}</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once "../shared/navbar.php"; ?>

<body class="bg-light">
    <div class="container-lg bg-white py-5 shadow">
        <div class="row">
            <div class="col-md-7 mx-auto">
                <div class="page-header">
                    <h2>Update {TABLE_NAME}</h2>
                </div>
                <?php print_error_if_exists($error); ?>
                <p>Please edit the input values and submit to update the record.</p>
                <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
                    <?php
                    foreach ($original_column_list as $name => $column) {
                        if ($column->get_name() != "{COLUMN_ID}") {
                            echo $column->html_update_row($row[$name]);
                        }
                    }
                    ?>
                    <input type="hidden" name="{COLUMN_ID}" value="<?php echo $_GET["{COLUMN_ID}"]; ?>" />
                    <p>
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
                    </p>
                    <p> * field can not be left empty </p>
                    <div class="mt-5 mb-5">
                        <a href="../{TABLE_NAME}/read.php?{COLUMN_ID}=<?php echo $_GET["{COLUMN_ID}"]; ?>"
                            class="btn btn-primary">View</a>
                        <a href="../{TABLE_NAME}/create.php?duplicate=<?php echo $_GET["{COLUMN_ID}"]; ?>"
                            class="btn btn-info">Duplicate</a>
                        <a href="../{TABLE_NAME}/delete.php?{COLUMN_ID}=<?php echo $_GET["{COLUMN_ID}"]; ?>"
                            class="btn btn-warning">Delete</a>
                        <a href="../{TABLE_NAME}/index.php" class="btn btn-primary">Back to index</a>
                    </div>
                </form>
                <?php
                include "post_extension.php";
                ?>
            </div>
        </div>
    </div>
</body>

</html>