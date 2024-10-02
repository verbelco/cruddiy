<?php
// Include config file
require_once "../config.php";
require_once "../shared/helpers.php";
require_once "../shared/bulk_updates.php";
require_once "class.php";

//Get current URL and parameters for correct pagination
$script = $_SERVER['SCRIPT_NAME'];
$parameters = $_GET ? $_SERVER['QUERY_STRING'] : "";
$currenturl = $domain . $script . '?' . $parameters;

$original_column_list = $CRUD['{TABLE_NAME}']->get_original_columns();
$read_only_columns_list = $CRUD['{TABLE_NAME}']->get_read_only_columns();

$column_list = $original_column_list + $read_only_columns_list;
$columns = array_keys($column_list);

$DEFAULT_COLUMNS = ['{COLUMNS}'];
$DEFAULT_ORDERING = array('{COLUMN_ID}' => 'asc');
$DEFAULT_FILTER = array();

include "pre_extension.php";

// Handle bulk updates
if (isset($_POST['target']) && in_array($_POST['target'], ['Update', 'Update_all', 'Delete', 'Delete_all'])) {
    $ids = str_contains($_POST['target'], 'all') ? explode(';', $_POST['all_ids']) : $_POST['bulk-update'];
    $values = array_intersect_key($_POST, array_flip(array_keys($original_column_list)));

    if (is_array($ids) && count($ids) > 0) {
        if (str_contains($_POST['target'], 'Update')) {
            if (count($values) > 0) {
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
        $_SESSION["CRUD"]["{TABLE_NAME}"]["filter"] = array();
        $_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"] = null;
    } elseif ($_GET["target"] == "resetorder") {
        $_SESSION["CRUD"]["{TABLE_NAME}"]["order"] = null;
    }
}

// Get the selected columns
if (isset($_POST['flexible-columns'])) {
    $selected_columns = array_intersect($_POST['flexible-columns'], $columns);
    $_SESSION["CRUD"]["{TABLE_NAME}"]["selected_columns"] = $selected_columns;
} else {
    $selected_columns = $_SESSION["CRUD"]["{TABLE_NAME}"]["selected_columns"] ?? $DEFAULT_COLUMNS;
}

// Get the selected columns in the right order
$selected_columns_list = array();
foreach ($selected_columns as $c) {
    if (isset($column_list[$c])) {
        $selected_columns_list[$c] = $column_list[$c];
    }
}

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
    $order_param_array = $DEFAULT_ORDERING;
}

$orderclause = get_orderby_clause($order_param_array, $column_list);
$ordering_on = get_ordering_on($order_param_array, $column_list);

[$get_param_ordering, $temp] = get_order_parameters($order_param_array);

// Create a filter
$where_columns = array_intersect_key($_GET, array_flip($columns));
$filter = create_sql_filter_array($where_columns);

if (isset($_GET["target"]) && $_GET["target"] == "Search") {
    $_SESSION["CRUD"]["{TABLE_NAME}"]["filter"] = $filter;
} else if (count($filter) == 0) {
    // Use the filter from the session if no other filter is used
    $filter = $_SESSION["CRUD"]["{TABLE_NAME}"]["filter"] ?? $DEFAULT_FILTER;
} else {
    // Skip quick search when a direct reference is being called
    $_SESSION["CRUD"]["{TABLE_NAME}"]["filter"] = $filter;
    $_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"] = null;
}
[$get_param_where, $where_clause] = create_sql_where($column_list, $filter, $link);

// Handle quick search
if (isset($_GET['search'])) {
    if (empty($_GET['search'])) {
        $search = null;
    } else {
        $search = mysqli_real_escape_string($link, $_GET['search']);
    }
    $_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"] = $search;
} else if (isset($_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"])) {
    $search = $_SESSION["CRUD"]["{TABLE_NAME}"]["quick-search"];
}

$columns_in_search = [];
if (isset($search)) {
    $columns_in_search = array_filter($column_list, function ($c) use ($selected_columns) {
        return
            !(
                $c instanceof CRUD\IntColumn
                || $c instanceof CRUD\DateColumn
                || $c instanceof CRUD\BoolColumn
                || $c instanceof CRUD\FlexibleForeignKeyColumn
            )
            && ($c->get_table() == "{TABLE_NAME}" || in_array($c->get_name(), $selected_columns));
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
    return $c->get_sql_select_alias();
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

if (isset($skip_count) && $skip_count && empty($filter)) {
    $number_of_results = $number_of_records ?? 1000;
    $all_ids = "";
} else {
    try {
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Overview {TABLE_DISPLAY}</title>
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
                    <a href="../{TABLE_NAME}/index.php?target=resetfilter" class="btn btn-dark float-end me-2">Reset
                        Filters</a>
                    <a href="../{TABLE_NAME}/index.php?target=resetorder" class="btn btn-primary float-end me-2">Reset
                        Ordering</a>
                    <a href="../{TABLE_NAME}/index.php?target=empty" class="btn btn-info float-end me-2">Reset View</a>
                </div>
                <?php echo $CRUD['{TABLE_NAME}']->html_comment(); ?>
                <div class="form-row">
                    <form action="../{TABLE_NAME}/index.php" method="get">
                        <div class="form-floating col-sm-3">
                            <input type="text" id="quicksearch" class="form-control" placeholder="Search this table" name="search" value="<?php echo $search; ?>">
                            <label for="quicksearch">Search this table</label>
                        </div>
                    </form>
                </div>
                <?php
                if (!empty($result_html)) {
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
                                <button type="submit" class="btn btn-outline-success btn-lg" name="target" value="Update_all">Update all
                                    <?php echo $number_of_results; ?> records
                                </button>

                                <button type="submit" class="btn btn-warning btn-lg ms-4" name="target" value="Delete" id="bulkupdate-delete-button">Delete</button>
                                <button type="submit" class="btn btn-outline-warning btn-lg" name="target" value="Delete_all">Delete all
                                    <?php echo $number_of_results; ?> records
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="form-row border p-3 border-top-0 rounded-0 rounded-bottom subpage" id="Flexible_columns">
                        <h3 class="text-center">Flexible Columns</h3>
                        <p>
                            Select the columns that you want to display on this page
                        </p>
                        <div class="row">
                            <div class='col'>
                                <form action="../{TABLE_NAME}/index.php" id="flexiblecolumnsform" method="post">
                                    <h5>Selected columns (in order)</h5>
                                    <ol class='list-group flexible-columns my-2'>
                                        <?php
                                        foreach ($selected_columns_list as $name => $c) {
                                            echo $c->html_index_flexible_columns();
                                        }
                                        ?>
                                    </ol>
                                </form>
                            </div>
                            <div class='col'>
                                <h5>Original columns</h5>
                                <ol class='list-group flexible-columns my-2'>
                                    <?php
                                    $not_used_original_columns_list = array_diff_key($original_column_list, $selected_columns_list);
                                    foreach ($not_used_original_columns_list as $name => $c) {
                                        echo $c->html_index_flexible_columns();
                                    }
                                    ?>
                                </ol>
                            </div>
                            <?php
                            $not_used_read_only_columns_list = array_diff_key($read_only_columns_list, $selected_columns_list);
                            if (count($not_used_read_only_columns_list) > 0) {
                                echo "<div class='col'>";
                                echo "<h5>Read-only columns</h5>";
                                echo "<ol class='list-group flexible-columns my-2'>";
                                foreach ($not_used_read_only_columns_list as $name => $c) {
                                    echo $c->html_index_flexible_columns();
                                }
                                echo "</ol>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg" name="target" value="select-columns" form="flexiblecolumnsform">Update
                                view</button>
                        </div>
                    </div>
                </div>
                <?php
                try {
                    $result = mysqli_query($link, $sql);
                    $rows = $result->fetch_all(MYSQLI_ASSOC);
                    if (count($rows) > 0) {
                        $total_pages = ceil($number_of_results / $no_of_records_per_page);
                        echo "Sorting on $ordering_on <br>";
                        echo " " . $number_of_results . " results - Page " . $pageno . " of " . $total_pages;

                        echo "<table class='table table-bordered table-striped'>";
                        echo "<thead class='table-primary sticky-top'>";
                        echo "<tr>";
                        echo '<th class="text-center" title="Select all" data-toggle="tooltip" style="display:none;">
                                        Bulk updates <input type="checkbox" id="select_all_checkboxes">
                                        <input type="hidden" form="bulkupdatesform" name="all_ids" value="' . $all_ids . '">
                                    </th>';
                        foreach ($selected_columns_list as $c) {
                            if ($default_ordering && !isset($DEFAULT_ORDERING[$c->get_name()])) {
                                foreach (array_keys($DEFAULT_ORDERING) as $column_name) {
                                    unset($order_param_array[$column_name]);
                                }
                            }
                            [$get_param_order, $arrow] = get_order_parameters($order_param_array, $c->get_name());
                            echo $c->html_index_table_header($get_param_search, $get_param_where, $get_param_order, $arrow);
                        }
                        echo "<th>Action</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        foreach ($rows as $row) {
                            echo "<tr>";
                            echo '<td class="text-center" style="display:none;">
                                        <input type="checkbox" form="bulkupdatesform" name="bulk-update[]" value="' . $row['{COLUMN_ID}'] . '">
                                    </td>';
                            foreach ($selected_columns_list as $c) {
                                echo $c->html_index_table_element($row);
                            }
                            echo "<td class='text-nowrap'>";
                            echo "<a href='../{TABLE_NAME}/read.php?{COLUMN_ID}=" . $row['{COLUMN_ID}'] . "' title='View Record' data-toggle='tooltip' class='me-1'><i class='far fa-eye'></i></a>";
                            echo "<a href='../{TABLE_NAME}/update.php?{COLUMN_ID}=" . $row['{COLUMN_ID}'] . "' title='Update Record' data-toggle='tooltip'class='me-1'><i class='far fa-edit'></i></a>";
                            echo "<a href='../{TABLE_NAME}/create.php?duplicate=" . $row['{COLUMN_ID}'] . "' title='Create a duplicate of this record' data-toggle='tooltip' class='me-1'><i class='fa fa-copy'></i></a>";
                            echo "<a href='../{TABLE_NAME}/delete.php?{COLUMN_ID}=" . $row['{COLUMN_ID}'] . "' title='Delete Record' data-toggle='tooltip'><i class='far fa-trash-alt'></i></a>";
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
                            <li class="page-item"><a class="page-link" href="<?php echo $new_url . '&pageno=1' ?>">First</a>
                            </li>
                            <li class="page-item <?php if ($pageno <= 1) {
                                                        echo 'disabled';
                                                    } ?>">
                                <a class="page-link" href="<?php if ($pageno <= 1) {
                                                                echo '#';
                                                            } else {
                                                                echo $new_url . "&pageno=" . ($pageno - 1);
                                                            } ?>">Prev</a>
                            </li>
                            <li class="page-item <?php if ($pageno >= $total_pages) {
                                                        echo 'disabled';
                                                    } ?>">
                                <a class="page-link" href="<?php if ($pageno >= $total_pages) {
                                                                echo '#';
                                                            } else {
                                                                echo $new_url . "&pageno=" . ($pageno + 1);
                                                            } ?>">Next</a>
                            </li>
                            <li class="page-item <?php if ($pageno >= $total_pages) {
                                                        echo 'disabled';
                                                    } ?>">
                                <a class="page-item"><a class="page-link" href="<?php echo $new_url . '&pageno=' . $total_pages; ?>">Last</a>
                            </li>
                        </ul>
                <?php
                        // Free result set
                        mysqli_free_result($result);
                    } else {
                        echo "<p class='lead'><em>No records were found.</em></p>";
                    }
                } catch (mysqli_sql_exception $e) {
                    echo "<div class='alert alert-danger' role='alert'>DATABASE ERROR IN MAIN QUERY: " . $e->getMessage() . "</div>";
                }

                include "post_extension.php";
                ?>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(".subnav .nav-link").click(function() {
            $(".subpage").hide();
            const id = $(this).attr('id').slice(0, -2);

            if (!$(this).hasClass("active")) {
                $("#" + id).css('display', 'block');
                $(".nav-link").removeClass("active");
                $(this).addClass("active");
            } else {
                $(this).removeClass("active");
            }

            if (id == 'Bulk_updates') {
                $('td:nth-child(1),th:nth-child(1)').show();
            }
        });

        $("tbody input[type=checkbox]").change(count_checked_boxes);

        $("ol.flexible-columns").sortable({
            group: 'flexible-columns',
        });
    </script>
</body>

</html>