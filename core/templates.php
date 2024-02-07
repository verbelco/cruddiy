<?php

$indexfile = <<<'EOT'
<?php
// Include config file
require_once "../config.php";
require_once "../shared/helpers.php";
require_once "../shared/bulk_updates.php";
require_once "../shared/Column/Column.php";
require_once "class.php";

// Import custom columns if they exist
if (file_exists(stream_resolve_include_path("class_extension.php"))) {
    require "class_extension.php";
} else {
    $read_only_columns_list = array();
}

//Get current URL and parameters for correct pagination
$script   = $_SERVER['SCRIPT_NAME'];
$parameters   = $_GET ? $_SERVER['QUERY_STRING'] : "" ;
$currenturl = $domain. $script . '?' . $parameters;

$column_list = $original_column_list + $read_only_columns_list;
$columns = array_keys($column_list);

// Handle bulk updates
if (isset($_POST['target']) && in_array($_POST['target'], ['Update', 'Update_all', 'Delete', 'Delete_all'])) {
    $ids = str_contains($_POST['target'], 'all') ? explode(';', $_POST['all_ids']) : $_POST['bulk-update'];
    $values = array_intersect_key($_POST, array_flip(array_keys($original_column_list)));

    if (is_array($ids) && count($ids) > 0) {
        if (str_contains($_POST['target'], 'Update')) {
            if(count($values) > 0){
                $result_html = bulk_update_crud("{TABLE_NAME}", "{COLUMN_ID}", $values, $ids);
            } else {
                $result_html = "Bulk updates was started, but no columns to update were selected.";
            }
        } else if (str_contains($_POST['target'], 'Delete')) {
            $result_html = bulk_delete_crud("{TABLE_NAME}", "{COLUMN_ID}", $ids);
        }
    } else {
        $result_html = "Bulk updates was started, but no records were selected.";
    }
}

// Handle page resets
if (isset($_GET["target"])) {
    if ($_GET["target"] == "empty") {
        $_SESSION["CRUD"]["{TABLE_NAME}"]["selected_columns"] = null;
        $_SESSION["CRUD"]["{TABLE_NAME}"]["order"] = null;
        $_SESSION["CRUD"]["{TABLE_NAME}"]["filter"] = null;
        $_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"] = null;
        $_SESSION["CRUD"]["{TABLE_NAME}"]["pageno"] = null;
    } elseif ($_GET["target"] == "resetfilter") {
        $_SESSION["CRUD"]["{TABLE_NAME}"]["filter"] = null;
        $_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"] = null;
    } elseif ($_GET["target"] == "resetorder") {
        $_SESSION["CRUD"]["{TABLE_NAME}"]["order"] = null;
    }
}

// Get the selected columns
if (isset($_POST['flexible-columns'])) {
    $selected_columns = array_intersect($_POST['flexible-columns'], $columns);
    $_SESSION["CRUD"]["{TABLE_NAME}"]["selected_columns"] = $selected_columns;
} else if (isset($_SESSION["CRUD"]["{TABLE_NAME}"]["selected_columns"])) {
    $selected_columns = $_SESSION["CRUD"]["{TABLE_NAME}"]["selected_columns"];
} else {
    $selected_columns = ['{COLUMNS}'];
}

$selected_columns_list = array_filter($column_list, function ($c) use ($selected_columns) {
    return in_array($c->get_name(), $selected_columns);
});   

// Column sorting on column name
if (isset($_GET['order'])) {
    $order_param_array = get_orderby_array($_GET['order'], $column_list);
    $_SESSION["CRUD"]["{TABLE_NAME}"]["order"] = $order_param_array;
    $default_ordering = false;
} else if (!empty($_SESSION["CRUD"]["{TABLE_NAME}"]["order"])) {
    $order_param_array = $_SESSION["CRUD"]["{TABLE_NAME}"]["order"];
    $default_ordering = false;
}

if (empty($order_param_array)) {
    $default_ordering = true;
    $order_param_array = array('{COLUMN_ID}' => 'asc');
}

$orderclause = get_orderby_clause($order_param_array, $column_list);
$ordering_on = get_ordering_on($order_param_array, $column_list);

[$get_param_ordering, $temp] = get_order_parameters($order_param_array);

// Create a filter
$where_columns = array_intersect_key($_GET, array_flip($columns));
$filter = create_sql_filter_array($where_columns);

if(isset($_GET["target"]) && $_GET["target"] == "Search"){
    $_SESSION["CRUD"]["{TABLE_NAME}"]["filter"] = $filter;
} else if(count($filter) == 0 && !empty($_SESSION["CRUD"]["{TABLE_NAME}"]["filter"])) {
    // Use the filter from the session if no other filter is used
    $filter = $_SESSION["CRUD"]["{TABLE_NAME}"]["filter"];
}
[$get_param_where, $where_clause] = create_sql_where($column_list, $filter, $link);

// Handle quick search
if(!empty($_GET['search'])){
    $search = mysqli_real_escape_string($link, $_GET['search']);
    $_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"] = $search;
} else if(isset($_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"])){
    $search = $_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"];
}

$columns_in_search = [];
if (isset($search)) {
    $columns_in_search = array_filter($column_list, function ($c) use ($selected_columns) {
        return !in_array(get_class($c), ['IntColumn', 'FloatColumn', 'DateColumn', 'DateTimeColumn', 'BoolColumn']) && ($c->get_table() == "{TABLE_NAME}" || in_array($c->get_name(), $selected_columns));
    });

    $sql_values = implode(", ", array_map(function ($c) {
        return $c->get_sql_value();
    }, $columns_in_search));

    $get_param_search = "?search=$search";
    $where_clause .= " AND CONCAT_WS ('|', $sql_values) LIKE '%$search%'";
} else {
    $get_param_search = "?";
    $search = "";
}

// Pagination
if (isset($_GET['pageno']) && is_numeric($_GET['pageno'])) {
    $pageno = (int) $_GET['pageno'];
    $_SESSION["CRUD"]["{TABLE_NAME}"]["pageno"] = $pageno;
} elseif (!empty($_SESSION["CRUD"]["{TABLE_NAME}"]["pageno"])) {
    $pageno = $_SESSION["CRUD"]["{TABLE_NAME}"]["pageno"];
} else {
    $pageno = 1;
}
$offset = ($pageno - 1) * $no_of_records_per_page;

// Prepare the query
$sql_select = implode(", ", array_map(function ($c) {
    return $c->get_sql_select();
}, $selected_columns_list + [$original_column_list["{COLUMN_ID}"]]));

// Only load the joins from columns that are required. (When they are used for searching, ordering or being selected)
$columns_join_list = array_filter($column_list, function ($c) use ($selected_columns, $filter, $order_param_array, $columns_in_search) {
    return in_array($c->get_name(), $selected_columns) || isset($columns_in_search[$c->get_name()]) || isset($filter[$c->get_name()]) || isset($order_param_array[$c->get_name()]);
});

$sql_join = implode("", array_map(function ($c) {
    return $c->get_sql_join();
}, $columns_join_list));

// Run SQL queries
$count_pages = "SELECT COUNT(DISTINCT `{TABLE_NAME}`.`{COLUMN_ID}`) AS count, GROUP_CONCAT(DISTINCT `{TABLE_NAME}`.`{COLUMN_ID}` SEPARATOR ';') AS all_ids FROM `{TABLE_NAME}` 
        $sql_join $where_clause";

try{
    $count_result = mysqli_fetch_assoc(mysqli_query($link, $count_pages));
    $number_of_results = $count_result['count'];
    $all_ids = $count_result['all_ids'];
    if ($number_of_results < $offset) {
        $offset = 0;
        $pageno = 1;
    }
} catch (mysqli_sql_exception $e) {
    echo "<div class='alert alert-danger' role='alert'>DATABASE ERROR IN COUNT QUERY: " . $e->getMessage() . "</div>";
}

$sql = "SELECT $sql_select
        FROM `{TABLE_NAME}`
        $sql_join $where_clause  
        GROUP BY `{TABLE_NAME}`.`{COLUMN_ID}`
        $orderclause
        LIMIT $offset, $no_of_records_per_page;";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{APP_NAME}</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once "../shared/navbar.php"; ?>
<body>
    <div class="container-xxl py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header clearfix">
                <h2 class="float-start">{TABLE_DISPLAY} Details</h2>
                    <a href="../{TABLE_NAME}/create.php" class="btn btn-success float-end">Add New Record</a>
                    <a href="../{TABLE_NAME}/index.php?target=resetfilter" class="btn btn-dark float-end me-2">Reset Filters</a>
                    <a href="../{TABLE_NAME}/index.php?target=resetorder" class="btn btn-primary float-end me-2">Reset Ordering</a>
                    <a href="../{TABLE_NAME}/index.php?target=empty" class="btn btn-info float-end me-2">Reset View</a>
                </div>
                {TABLE_COMMENT}
                <div class="form-row">
                    <form action="../{TABLE_NAME}/index.php" method="get">
                    <div class="form-floating col-sm-3">
                        <input type="text" id="quicksearch" class="form-control" placeholder="Search this table" name="search" value="<?php echo $search; ?>">
                        <label for="quicksearch">Search this table</label>
                    </div>  
                    </form>
                </div>
                <?php
                    if(!empty($result_html)){
                        echo "<div class='alert alert-primary my-3'>$result_html</div>";
                    }
                ?>
                <div class="my-2">                
                    <ul class="nav nav-tabs nav-fill subnav">
                        <li class="nav-item">
                            <a class="nav-link" id="Advanced_filters_T" href="#">Advanced filters</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="Bulk_updates_T" href="#">Bulk updates</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="Flexible_columns_T" href="#">Flexible Columns</a>
                        </li>
                    </ul>
                    <div class="form-row border p-3 border-top-0 rounded-0 rounded-bottom subpage" id="Advanced_filters">
                        <form action="../{TABLE_NAME}/index.php" id="advancedfilterform" method="get">
                            <div class="h3 text-center">    
                                Advanced Filters
                                <button type="submit" class="btn btn-success btn-lg" name="target" value="Search">Search</button>
                            </div>
                            <div>
                                <?php
                                foreach ($column_list as $c) {
                                    echo $c->html_index_advanced_filter($filter);
                                }
                                ?>
                            </div>
                        </form>
                    </div>

                    <div class="form-row border p-3 border-top-0 rounded-0 rounded-bottom subpage" id="Bulk_updates">
                        <form action="../{TABLE_NAME}/index.php" id="bulkupdatesform" method="post">
                            <h3 class="text-center">Bulk Updates</h3>
                            <div>
                                <?php
                                foreach ($original_column_list as $c) {
                                    echo $c->html_index_bulk_update();
                                }
                                ?>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success btn-lg" name="target" value="Update" id="bulkupdate-update-button">Update</button>
                                <button type="submit" class="btn btn-outline-success btn-lg" name="target" value="Update_all">Update all <?php echo $number_of_results;?> records</button>

                                <button type="submit" class="btn btn-warning btn-lg ms-4" name="target" value="Delete" id="bulkupdate-delete-button">Delete</button>
                                <button type="submit" class="btn btn-outline-warning btn-lg" name="target" value="Delete_all">Delete all <?php echo $number_of_results;?> records</button>
                            </div>
                        </form>
                    </div>

                    <div class="form-row border p-3 border-top-0 rounded-0 rounded-bottom subpage"
                        id="Flexible_columns">
                        <form action="../{TABLE_NAME}/index.php" id="flexiblecolumnsform" method="post">
                            <h3 class="text-center">Flexible Columns</h3>
                            <div>
                                <p>
                                    Select the columns that you want to display on this page
                                </p>
                                <?php
                                if(count($read_only_columns_list) > 0){
                                    echo "<h5>Original columns</h5>";
                                }
                                foreach ($original_column_list as $name => $c) {
                                    echo $c->html_index_flexible_columns(in_array($name, $selected_columns));
                                }
                                if(count($read_only_columns_list) > 0){
                                    echo "<h5>Read-only columns</h5>";
                                    foreach ($read_only_columns_list as $name => $c) {
                                        echo $c->html_index_flexible_columns(in_array($name, $selected_columns));
                                    }
                                }
                                ?>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success btn-lg" name="target"
                                    value="select-columns">Update view</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                try{
                    $result = mysqli_query($link, $sql);
                    if(mysqli_num_rows($result) > 0){
                        $total_pages = ceil($number_of_results / $no_of_records_per_page);
                        echo "Sorting on $ordering_on <br>";
                        echo " " . $number_of_results . " results - Page " . $pageno . " of " . $total_pages;

                        echo "<table class='table table-bordered table-striped'>";
                            echo "<thead class='table-primary sticky-top'>";
                                echo "<tr>";
                                    echo '<th class="text-center" title="Select all" data-toggle="tooltip" style="display:none;">
                                        Bulk updates <input type="checkbox" id="select_all_checkboxes">
                                        <input type="hidden" form="bulkupdatesform" name="all_ids" value="'. $all_ids .'">
                                    </th>';
                                    foreach($selected_columns_list as $c){
                                        if ($default_ordering && $c->get_name() != "{COLUMN_ID}") {
                                            unset($order_param_array["{COLUMN_ID}"]);
                                        }
                                        [$get_param_order, $arrow] = get_order_parameters($order_param_array, $c->get_name());
                                        echo $c->html_index_table_header($get_param_search, $get_param_where, $get_param_order, $arrow);
                                    }
                                    echo "<th>Action</th>";
                                echo "</tr>";
                            echo "</thead>";
                            echo "<tbody>";
                            while($row = mysqli_fetch_assoc($result)){
                                echo "<tr>";
                                echo '<td class="text-center" style="display:none;">
                                        <input type="checkbox" form="bulkupdatesform" name="bulk-update[]" value="'. $row['{COLUMN_ID}'] .'">
                                    </td>';
                                    foreach ($selected_columns_list as $c) {
                                        echo $c->html_index_table_element($row);
                                    }
                                    echo "<td class='text-nowrap'>";
                                        echo "<a href='../{TABLE_NAME}/read.php?{COLUMN_ID}=". $row['{COLUMN_ID}'] ."' title='View Record' data-toggle='tooltip' class='me-1'><i class='far fa-eye'></i></a>";
                                        echo "<a href='../{TABLE_NAME}/update.php?{COLUMN_ID}=". $row['{COLUMN_ID}'] ."' title='Update Record' data-toggle='tooltip'class='me-1'><i class='far fa-edit'></i></a>";
                                        echo "<a href='../{TABLE_NAME}/create.php?duplicate=". $row['{COLUMN_ID}'] ."' title='Create a duplicate of this record' data-toggle='tooltip' class='me-1'><i class='fa fa-copy'></i></a>";
                                        echo "<a href='../{TABLE_NAME}/delete.php?{COLUMN_ID}=". $row['{COLUMN_ID}'] ."' title='Delete Record' data-toggle='tooltip'><i class='far fa-trash-alt'></i></a>";
                                    echo "</td>";
                                echo "</tr>";
                            }
                            echo "</tbody>";
                        echo "</table>";
?>
                            <ul id="pagination" class="pagination fixed-bottom" align-right>
                            <?php
                                $new_url = preg_replace('/&?pageno=[^&]*/', '', $currenturl);
                                ?>
                                <li class="page-item"><a class="page-link" href="<?php echo $new_url .'&pageno=1' ?>">First</a></li>
                                <li class="page-item <?php if($pageno <= 1){ echo 'disabled'; } ?>">
                                    <a class="page-link" href="<?php if($pageno <= 1){ echo '#'; } else { echo $new_url ."&pageno=".($pageno - 1); } ?>">Prev</a>
                                </li>
                                <li class="page-item <?php if($pageno >= $total_pages){ echo 'disabled'; } ?>">
                                    <a class="page-link" href="<?php if($pageno >= $total_pages){ echo '#'; } else { echo $new_url . "&pageno=".($pageno + 1); } ?>">Next</a>
                                </li>
                                <li class="page-item <?php if($pageno >= $total_pages){ echo 'disabled'; } ?>">
                                    <a class="page-item"><a class="page-link" href="<?php echo $new_url .'&pageno=' . $total_pages; ?>">Last</a>
                                </li>
                            </ul>
<?php
                        // Free result set
                        mysqli_free_result($result);
                    } else{
                        echo "<p class='lead'><em>No records were found.</em></p>";
                    }
                } catch (mysqli_sql_exception $e) {
                    echo "<div class='alert alert-danger' role='alert'>DATABASE ERROR IN MAIN QUERY: " . $e->getMessage() . "</div>";
                }

                if (file_exists(stream_resolve_include_path("extension.php"))){
                    include("extension.php");
                }

                // Close connection
                mysqli_close($link);
                ?>
            </div>
        </div>
    </div>
    <script type="text/javascript">        
        $(".subnav .nav-link").click(function () {
            $(".subpage").hide();
            id = $(this).attr('id').slice(0, -2);
            
            if(!$(this).hasClass("active")){
                $("#" + id).css('display', 'block');
                $(".nav-link").removeClass("active");
                $(this).addClass("active");
            } else {
                $(this).removeClass("active");
            }

            if(id == 'Bulk_updates') {
                $('td:nth-child(1),th:nth-child(1)').show();
            }
        });

        $("tbody input[type=checkbox]").change(count_checked_boxes);
    </script>
</body>
</html>
EOT;


$readfile = <<<'EOT'
<?php
// Check existence of id parameter before processing further
$_GET["{TABLE_ID}"] = trim($_GET["{TABLE_ID}"]);
if(isset($_GET["{TABLE_ID}"]) && !empty($_GET["{TABLE_ID}"])){
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

    $column_list = $original_column_list + $read_only_columns_list;

    // Prepare a select statement
    $sql_select = implode(", ", array_map(function ($c) { return $c->get_sql_select(); }, $column_list));
    $sql_join = implode("", array_map(function ($c) { return $c->get_sql_join(); }, $column_list));

    $sql = "SELECT $sql_select
            FROM `{TABLE_NAME}`
            $sql_join
            WHERE `{TABLE_NAME}`.`{TABLE_ID}` = ?
            GROUP BY `{TABLE_NAME}`.`{TABLE_ID}`;";

    $stmt = $link->prepare($sql);
    $param_id = trim($_GET["{TABLE_ID}"]);
    $stmt->execute([$param_id]);
    $result = $stmt->get_result();

    if(mysqli_num_rows($result) == 1){
        /* Fetch result row as an associative array. Since the result set
        contains only one row, we don't need to use while loop */
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    } else{
        // URL doesn't contain valid id parameter. Redirect to error page
        header("location: ../error.php");
        exit();
    }

    // Close statement
    mysqli_stmt_close($stmt);

} else{
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
                <div>
                    <?php
                    if(count($read_only_columns_list) > 0){
                        echo "<h5>Original columns</h5>";
                    }
                    foreach ($original_column_list as $name => $c) {
                        echo $c->html_read_row($row);
                    }
                    if(count($read_only_columns_list) > 0){
                        echo "<h5>Read-only columns</h5>";
                        foreach ($read_only_columns_list as $name => $c) {
                            echo $c->html_read_row($row);
                        }
                    } 
                    ?>
                </div>
                <div class="mt-3 mb-5">
                    <a href="../{TABLE_NAME}/update.php?{TABLE_ID}=<?php echo $_GET["{TABLE_ID}"];?>" class="btn btn-secondary">Edit</a>
                    <a href="../{TABLE_NAME}/create.php?duplicate=<?php echo $_GET["{TABLE_ID}"]; ?>" class="btn btn-info">Duplicate</a>
                    <a href="../{TABLE_NAME}/delete.php?{TABLE_ID}=<?php echo $_GET["{TABLE_ID}"];?>" class="btn btn-warning">Delete</a>
                    <a href="../{TABLE_NAME}/index.php" class="btn btn-primary">Back to index</a>                    
                </div> 
                <?php
                // Look for references to this record
                $references = [];
                $subsqls = [{FOREIGN_KEY_REFS}];
                foreach($subsqls as $subsql){
                    $stmt = $link->prepare($subsql);
                    $stmt->execute([$param_id]);
                    $subrow = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if($subrow['count'] > 0){
                        $references[] = $subrow;
                    }
                }
                echo html_read_references($references);

                if (file_exists(stream_resolve_include_path("extension.php"))){
                    include("extension.php");
                }

                // Close connection
                mysqli_close($link);
                ?>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
    </body>
</html>
EOT;


$deletefile = <<<'EOT'
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

// Process delete operation after confirmation
if(isset($_POST["{TABLE_ID}"]) && !empty($_POST["{TABLE_ID}"])){

    // Prepare a delete statement
    $sql = "DELETE FROM `{TABLE_NAME}` WHERE `{TABLE_ID}` = ?";

    $stmt = $link->prepare($sql);
    $param_id = trim($_POST["{TABLE_ID}"]);
    
    try {
        $stmt->execute([$param_id]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = "<p class='fw-bold'>Er zijn nog verwijzingen naar dit record, zie de view pagina voor meer informatie:</p>";
        $error .= $e->getMessage();
    }
    
    if (!isset($error)){
        // Records deleted successfully. Redirect to landing page
        header("location: ../{TABLE_NAME}/index.php");
    }

    // Close statement
    mysqli_stmt_close($stmt);

    // Close connection
    mysqli_close($link);
} else{
    // Check existence of id parameter
	$param_id = trim($_GET["{TABLE_ID}"]);
    if(empty($param_id)){
        // URL doesn't contain id parameter. Redirect to error page
        header("location: ../error.php");
        exit();
    }

    // Look for references to this record
    $references = [];
    $subsqls = [{FOREIGN_KEY_REFS}];
    foreach($subsqls as $subsql){
        $stmt = $link->prepare($subsql);
        $stmt->execute([$param_id]);
        $subrow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if($subrow['count'] > 0){
            $references[] = $subrow;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete {TABLE_NAME}</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once "../shared/navbar.php"; ?>
<body class="bg-light">
    <div class="container-lg bg-white py-5 shadow">
        <div class="row">
            <div class="col-md-7 mx-auto">
                <div class="page-header">
                    <h1>Delete {TABLE_NAME}</h1>
                </div>
                <?php echo html_delete_references($references); ?>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . "?{TABLE_ID}=" . $param_id; ?>" method="post">
                <?php print_error_if_exists($error); ?>
                    <div class="alert alert-danger fade-in">
                        <input type="hidden" name="{TABLE_ID}" value="<?php echo trim($param_id); ?>"/>
                        <p>Are you sure you want to delete this record?</p><br>
                        <p>
                            <input type="submit" value="Yes" class="btn btn-danger">
                            <a href="../{TABLE_NAME}/read.php?{TABLE_ID}=<?php echo $param_id;?>" class="btn btn-secondary">No</a>
                        </p>
                    </div>
                </form>
                <div class="mt-3 mb-5">
                    <a href="../{TABLE_NAME}/read.php?{TABLE_ID}=<?php echo $param_id;?>" class="btn btn-info">View</a>
                    <a href="../{TABLE_NAME}/update.php?{TABLE_ID}=<?php echo $param_id;?>" class="btn btn-secondary">Edit</a>
                    <a href="../{TABLE_NAME}/index.php" class="btn btn-primary">Back to index</a>
                </div>
                <?php
                if (file_exists(stream_resolve_include_path("extension.php"))){
                    include("extension.php");
                }
                ?>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>

EOT;

$createfile = <<<'EOT'
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

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $row = array();
    foreach ($original_column_list as $name => $column) {
        if ($column->get_name() != "{COLUMN_ID}") {
            $row[$name] = $original_column_list[$name]->get_sql_create_value($_POST[$name]);
        }
    }

    $stmt = $link->prepare("INSERT INTO `{TABLE_NAME}` ({CREATE_COLUMN_NAMES}) VALUES ({CREATE_QUESTIONMARK_PARAMS})");

    try {
        $stmt->execute(array_values($row));
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = $e->getMessage();
    }

    if (!isset($error)){
        $new_id = mysqli_insert_id($link);
        if(!isset($_POST['another'])){
            header("location: ../{TABLE_NAME}/read.php?{COLUMN_ID}=$new_id");
        } else {
            $message = "Record with <a href='../{TABLE_NAME}/read.php?{COLUMN_ID}=$new_id'>{COLUMN_ID}=$new_id</a> added to {TABLE_NAME}";
        }
    }
} else if (isset($_GET['duplicate'])){
    $duplicate_{COLUMN_ID} = trim($_GET['duplicate']);

    $stmt = $link->prepare("SELECT {CREATE_COLUMN_NAMES} FROM `{TABLE_NAME}` WHERE `{COLUMN_ID}` = ?");
    $stmt->execute([ $duplicate_{COLUMN_ID} ]);
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
                <?php print_error_if_exists($error); print_message_if_exists($message); ?>
                <p>Please fill this form and submit to add a record to the database.</p>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div>
                    <?php
                        foreach ($original_column_list as $name => $column) {
                            if ($column->get_name() != "{COLUMN_ID}") {
                                echo $original_column_list[$name]->html_create_row($row[$name]);
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
                if (file_exists(stream_resolve_include_path("extension.php"))) {
                    include("extension.php");
                }
                ?>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>
EOT;


$updatefile = <<<'EOT'
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

// Processing form data when form is submitted
if(isset($_POST["{COLUMN_ID}"]) && !empty($_POST["{COLUMN_ID}"])){
    $row = array();
    $update_stmts = [];
    foreach ($original_column_list as $name => $column) {
        if ($column->get_name() != "{COLUMN_ID}") {
            if(get_class($column) != "MutatieMomentColumn"){
                $row[$name] = $original_column_list[$name]->get_sql_update_value($_POST[$name]);
            }
            $update_stmts[] = $original_column_list[$name]->get_sql_update_stmt();
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
if (isset($_GET["{COLUMN_ID}"]) && !empty($_GET["{COLUMN_ID}"])) {
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
                            echo $original_column_list[$name]->html_update_row($row[$name]);
                        }
                    }
                    ?>
                    <input type="hidden" name="{COLUMN_ID}" value="<?php echo $_GET["{COLUMN_ID}"]; ?>"/>
                    <p>
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
                    </p>
                    <p> * field can not be left empty </p>
                    <div class="mt-5 mb-5">
                        <a href="../{TABLE_NAME}/read.php?{COLUMN_ID}=<?php echo $_GET["{COLUMN_ID}"];?>" class="btn btn-primary">View</a>
                        <a href="../{TABLE_NAME}/create.php?duplicate=<?php echo $_GET["{COLUMN_ID}"]; ?>" class="btn btn-info">Duplicate</a>
                        <a href="../{TABLE_NAME}/delete.php?{COLUMN_ID}=<?php echo $_GET["{COLUMN_ID}"];?>" class="btn btn-warning">Delete</a>
                        <a href="../{TABLE_NAME}/index.php" class="btn btn-primary">Back to index</a>
                    </div>
                </form>
                <?php
                if (file_exists(stream_resolve_include_path("extension.php"))) {
                    include("extension.php");
                }
                ?>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>

EOT;

$errorfile = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<body class="bg-light">
    <div class="container-lg bg-white py-5 shadow">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <h1>Invalid Request</h1>
                </div>
                <div class="alert alert-danger fade-in">
                    <p>Sorry, you've made an invalid request. Please <a href="index.php" class="alert-link">go back</a> and try again.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
EOT;

$startfile = <<<'EOT'
<html lang="en">                                                                                                                                                                                                   
<head>                                                                                                                                                                                                             
    <meta charset="UTF-8">                                                                                                                                                                                         
    <title>{APP_NAME}</title>
    {CSS_REFS}
    {JS_REFS}

    <style type="text/css">                                                                                                                                                                                        
        .page-header h2{                                                                                                                                                                                           
            margin-top: 0;                                                                                                                                                                                         
        }                                                                                                                                                                                                          
        table tr td:last-child a{                                                                                                                                                                                  
            margin-right: 5px;                                                                                                                                                                                     
        }                                                                                                                                                                                                          
    </style>                                                                                                                                                                                                       
</head>                                                                                                                                                                                                            
<?php require_once('navbar.php'); ?>
</html>  
EOT;

$navbarfile = <<<'EOT'
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <span class="navbar-brand" href="#">
      {APP_NAME}
    </span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Select page
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            {TABLE_BUTTONS}                                                                                                                                                                                           
            <!-- TABLE_BUTTONS -->
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
EOT;

$crud_class_file = <<<'EOT'
<?php

$original_column_list = array({COLUMNS_CLASSES});

EOT;