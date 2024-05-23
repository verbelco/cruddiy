<?php
// Include config file
require_once "../config.php";
require_once "../shared/helpers.php";
require_once "../shared/Column/Column.php";
require_once "class.php";

// Import custom columns if they exist
if (file_exists(stream_resolve_include_path("class_extension.php"))) {
    require "class_extension.php";
} else {
    $read_only_columns_list = array();
}

include "pre_extension.php";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $row = array();
    foreach ($original_column_list as $name => $column) {
        if ($column->get_name() != "{COLUMN_ID}") {
            $row[$name] = $column->get_sql_create_value($_POST[$name]);
        }
    }

    $inserts = implode(", ", array_map(function ($c) {
        return "`" . $c . "`";
    }, array_keys($row)));
    $question_marks = implode(", ", array_fill(0, count($row), '?'));

    $stmt = $link->prepare("INSERT INTO `{TABLE_NAME}` ($inserts) VALUES ($question_marks)");

    try {
        $stmt->execute(array_values($row));
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = $e->getMessage();
    }

    if (!isset($error)) {
        $new_id = mysqli_insert_id($link);
        if (!isset($_POST['another'])) {
            header("location: ../{TABLE_NAME}/read.php?{COLUMN_ID}=$new_id");
        } else {
            $message = "Record with <a href='../{TABLE_NAME}/read.php?{COLUMN_ID}=$new_id'>{COLUMN_ID}=$new_id</a> added to {TABLE_NAME}";
        }
    }
} else if (isset($_GET['duplicate'])) {
    $duplicate_id = trim($_GET['duplicate']);

    $stmt = $link->prepare("SELECT * FROM `{TABLE_NAME}` WHERE `{COLUMN_ID}` = ?");
    $stmt->execute([$duplicate_id]);
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create {TABLE_NAME}</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once "../shared/navbar.php"; ?>

<body class="bg-light">
    <div class="container-lg bg-white py-5 shadow">
        <div class="row">
            <div class="col-md-7 mx-auto">
                <div class="page-header">
                    <h2>Create {TABLE_NAME}</h2>
                </div>
                <?php print_error_if_exists($error);
                print_message_if_exists($message); ?>
                <p>Please fill this form and submit to add a record to the database.</p>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div>
                        <?php
                        foreach ($original_column_list as $name => $column) {
                            if ($column->get_name() != "{COLUMN_ID}") {
                                echo $column->html_create_row($row[$name]);
                            }
                        }
                        ?>
                    </div>
                    <div class="mt-3 mb-3">
                        <input type="submit" class="btn btn-primary" value="Create">
                        <input type="submit" class="btn btn-info" name='another' value="Create another">
                        <a href="../{TABLE_NAME}/index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
                <p> * field can not be left empty </p>
                <?php
                include "post_extension.php";
                ?>
            </div>
        </div>
    </div>
</body>

</html>