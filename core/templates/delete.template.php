<?php
// Include config file
require_once '../config.php';
require_once '../shared/helpers.php';
require_once 'class.php';

$original_column_list = $CRUD['{TABLE_NAME}']->get_original_columns();
$read_only_columns_list = $CRUD['{TABLE_NAME}']->get_read_only_columns();

include 'pre_extension.php';

// Process delete operation after confirmation
if (! empty($_POST['{TABLE_ID}'])) {

    // Prepare a delete statement
    $sql = 'DELETE FROM `{TABLE_NAME}` WHERE `{TABLE_ID}` = ?';

    $stmt = $link->prepare($sql);
    $param_id = trim($_POST['{TABLE_ID}']);

    try {
        $stmt->execute([$param_id]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = "<p class='fw-bold'>Er zijn nog verwijzingen naar dit record, zie de view pagina voor meer informatie:</p>";
        $error .= $e->getMessage();
    }

    if (! isset($error)) {
        // Records deleted successfully. Redirect to landing page
        header('location: ../{TABLE_NAME}/index.php');
    }
} else {
    // Check existence of id parameter
    $param_id = trim($_GET['{TABLE_ID}']);
    if (empty($param_id)) {
        // URL doesn't contain id parameter. Redirect to error page
        header('location: ../error.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delete {TABLE_NAME}</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once '../shared/navbar.php'; ?>

<body class="bg-light">
    <div class="container-lg bg-white py-5 shadow">
        <div class="row">
            <div class="col-md-7 mx-auto">
                <div class="page-header">
                    <h1>Delete {TABLE_NAME}</h1>
                </div>
                <?php echo $CRUD['{TABLE_NAME}']->html_delete_references($param_id); ?>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']).'?{TABLE_ID}='.$param_id; ?>"
                    method="post">
                    <?php print_error_if_exists($error); ?>
                    <div class="alert alert-danger fade-in">
                        <input type="hidden" name="{TABLE_ID}" value="<?php echo trim($param_id); ?>" />
                        <p>Are you sure you want to delete this record?</p><br>
                        <p>
                            <input type="submit" value="Yes" class="btn btn-danger">
                            <a href="../{TABLE_NAME}/read.php?{TABLE_ID}=<?php echo $param_id; ?>"
                                class="btn btn-secondary">No</a>
                        </p>
                    </div>
                </form>
                <div class="mt-3 mb-5">
                    <a href="../{TABLE_NAME}/read.php?{TABLE_ID}=<?php echo $param_id; ?>" class="btn btn-info">View</a>
                    <a href="../{TABLE_NAME}/update.php?{TABLE_ID}=<?php echo $param_id; ?>"
                        class="btn btn-secondary">Edit</a>
                    <a href="../{TABLE_NAME}/index.php" class="btn btn-primary">Back to index</a>
                </div>
                <?php
                include 'post_extension.php';
?>
            </div>
        </div>
    </div>
</body>

</html>