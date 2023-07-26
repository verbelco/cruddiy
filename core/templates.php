<?php

$indexfile = <<<'EOT'
<?php
    // Include config file
    require_once "../config.php";
    require_once "../helpers.php";

    //Get current URL and parameters for correct pagination
    $script   = $_SERVER['SCRIPT_NAME'];
    $parameters   = $_GET ? $_SERVER['QUERY_STRING'] : "" ;
    $currenturl = $domain. $script . '?' . $parameters;

    //Pagination
    if (isset($_GET['pageno'])) {
        $pageno = $_GET['pageno'];
    } else {
        $pageno = 1;
    }

    //$no_of_records_per_page is set on the index page. Default is 10.
    $offset = ($pageno-1) * $no_of_records_per_page;

    $total_pages_sql = "SELECT COUNT(*) FROM `{TABLE_NAME}`";
    $result = mysqli_query($link,$total_pages_sql);
    $total_rows = mysqli_fetch_array($result)[0];
    $total_pages = ceil($total_rows / $no_of_records_per_page);

    //Column sorting on column name
    $columns = array('{COLUMNS}');
    [$orderclause, $ordering_on, $order_param_array, $default_ordering] = get_orderby_clause($_GET['order'], $columns, "{COLUMN_ID}", "{TABLE_NAME}");
    [$get_param_ordering, $temp] = get_order_parameters($order_param_array);

    //Generate WHERE statements for param
    $where_columns = array_intersect_key($_GET, array_flip($columns));
    $filter = create_sql_filter_array($where_columns);

    if(isset($_GET["target"])){
        if($_GET["target"] == "Search"){
            // Write the filter to the session
            $_SESSION["filter"]["{TABLE_NAME}"] = $filter;
        } else if($_GET["target"] == "empty"){
            // Remove the filter from the session
            $_SESSION["filter"]["{TABLE_NAME}"] = array();
        }
    } else if(count($filter) == 0) {
        // Use the filter from the session if no other filter is used
        $filter = $_SESSION["filter"]["{TABLE_NAME}"];
    }

    [$get_param_where, $where_statement] = create_sql_where($filter, "{TABLE_NAME}", $link);

    if (!empty($_GET['search'])) {
        $search = mysqli_real_escape_string($link, $_GET['search']);
        $get_param_search = "?search=$search";
        $where_statement .= " AND CONCAT_WS ({INDEX_CONCAT_SEARCH_FIELDS}) LIKE '%$search%'";
    } else {
        $get_param_search = "?";
        $search = "";
    }

    // Prepare SQL queries
    $sql = "SELECT `{TABLE_NAME}`.* {JOIN_COLUMNS}
            FROM `{TABLE_NAME}` {JOIN_CLAUSES}
            $where_statement
            GROUP BY `{TABLE_NAME}`.`{COLUMN_ID}`
            ORDER BY $orderclause
            LIMIT $offset, $no_of_records_per_page;";
    $count_pages = "SELECT COUNT(DISTINCT `{TABLE_NAME}`.`{COLUMN_ID}`) AS count FROM `{TABLE_NAME}` {JOIN_CLAUSES}
            $where_statement";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{APP_NAME}</title>
    {CSS_REFS}
    <script src="https://kit.fontawesome.com/6b773fe9e4.js" crossorigin="anonymous"></script>
    <style type="text/css">
        .page-header h2{
            margin-top: 0;
        }
        table tr td:last-child a{
            margin-right: 5px;
        }
        body {
            font-size: 14px;
        }
    </style>
</head>
<?php require_once('../navbar.php'); ?>
<body>
    <section class="pt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-header clearfix">
                        <h2 class="float-left">{TABLE_DISPLAY} Details 
                            <span id='showfilter' data-toggle='tooltip' data-placement='top' title='Show advanced search options'>▾</span>
                            <span id='hidefilter' style='display:none;' data-toggle='tooltip' data-placement='top' title='Hide advanced search options'>▴</span>
                        </h2>
                        <a href="../{TABLE_NAME}/create.php" class="btn btn-success float-right">Add New Record</a>
                        <a href="../{TABLE_NAME}/index.php?target=empty<?php echo $get_param_ordering; ?>" class="btn btn-dark float-right mr-2">Reset Filters</a>
                        <a href="../{TABLE_NAME}/index.php<?php echo $get_param_search . $get_param_where; ?>" class="btn btn-primary float-right mr-2">Reset Ordering</a>
                        <a href="../{TABLE_NAME}/index.php?target=empty" class="btn btn-info float-right mr-2">Reset View</a>
                        <a href="javascript:history.back()" class="btn btn-secondary float-right mr-2">Back</a>
                    </div>
                    {TABLE_COMMENT}
                    <div class="form-row">
                        <form action="../{TABLE_NAME}/index.php" method="get">
                        <div class="col">
                          <input type="text" class="form-control" placeholder="Search this table" name="search" value="<?php echo $search; ?>">
                        </div>  
                        </form>
                    </div>
                    <div class="form-row mt-2" id="advancedfilter"  style="display:none;">
                        <form action="../{TABLE_NAME}/index.php" id="advancedfilterform" method="get">
                        <p class="h3">Advanced Filters
                            <input type="submit" class="btn btn-primary btn-lg" name="target" value="Search">
                        </p>
                        {INDEX_FILTER} 
                        </form>
                    </div>
                    <?php
                    try{
                        $result = mysqli_query($link, $sql);
                        if(mysqli_num_rows($result) > 0){
                            $number_of_results = mysqli_fetch_assoc(mysqli_query($link, $count_pages))['count'];
                            $total_pages = ceil($number_of_results / $no_of_records_per_page);
                            echo "Sorting on $ordering_on <br>";
                            echo " " . $number_of_results . " results - Page " . $pageno . " of " . $total_pages;

                            echo "<table class='table table-bordered table-striped'>";
                                echo "<thead class='thead-light'>";
                                    echo "<tr>";
                                        {INDEX_TABLE_HEADERS}
                                        echo "<th>Action</th>";
                                    echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                while($row = mysqli_fetch_array($result)){
                                    echo "<tr>";
                                    {INDEX_TABLE_ROWS}
                                        echo "<td>";
                                            echo "<a href='../{TABLE_NAME}/read.php?{COLUMN_ID}=". $row['{COLUMN_NAME}'] ."' title='View Record' data-toggle='tooltip'><i class='far fa-eye'></i></a>";
                                            echo "<a href='../{TABLE_NAME}/update.php?{COLUMN_ID}=". $row['{COLUMN_NAME}'] ."' title='Update Record' data-toggle='tooltip'><i class='far fa-edit'></i></a>";
                                            echo "<a href='../{TABLE_NAME}/delete.php?{COLUMN_ID}=". $row['{COLUMN_NAME}'] ."' title='Delete Record' data-toggle='tooltip'><i class='far fa-trash-alt'></i></a>";
                                        echo "</td>";
                                    echo "</tr>";
                                }
                                echo "</tbody>";
                            echo "</table>";
?>
                                <ul class="pagination fixed-bottom" align-right>
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
                        echo "<div class='alert alert-danger' role='alert'>DATABASE ERROR: " . $e->getMessage() . "</div>";
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
    </section>
{JS_REFS}
    <script type="text/javascript">
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        $("#hidefilter").click(function(){
            $("#advancedfilter").hide();
            $("#showfilter").show();
            $("#hidefilter").hide();
        });
          
        $("#showfilter").click(function(){
            $("#advancedfilter").show();
            $("#hidefilter").show();
            $("#showfilter").hide();
        });
        $("#advancedfilter").hide();
        $("#hidefilter").hide();        
        $('#advancedfilterform').submit(function () {
            $(this)
                .find('input[name], select[name]')
                .filter(function () {
                    return !this.value;
                })
                .prop('name', '');
        });
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
    require_once "../helpers.php";

    // Prepare a select statement
    $sql = "SELECT `{TABLE_NAME}`.* {JOIN_COLUMNS}
            FROM `{TABLE_NAME}` {JOIN_CLAUSES}
            WHERE `{TABLE_NAME}`.`{TABLE_ID}` = ?
            GROUP BY `{TABLE_NAME}`.`{TABLE_ID}`;";

    $stmt = mysqli_prepare($link, $sql);
    // Set parameters
    $param_id = trim($_GET["{TABLE_ID}"]);

    // Bind variables to the prepared statement as parameters
    if (is_int($param_id)) $__vartype = "i";
    elseif (is_string($param_id)) $__vartype = "s";
    elseif (is_numeric($param_id)) $__vartype = "d";
    else $__vartype = "b"; // blob
    mysqli_stmt_bind_param($stmt, $__vartype, $param_id);

    // Attempt to execute the prepared statement
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

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
    <title>View Record</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once('../navbar.php'); ?>
<body>
    <section class="pt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="page-header">
                        <h1>View Record</h1>
                    </div>

                     {RECORDS_READ_FORM}
                    <p>
                        <a href="../{TABLE_NAME}/update.php?{TABLE_ID}=<?php echo $_GET["{TABLE_ID}"];?>" class="btn btn-secondary">Edit</a>
                        <a href="../{TABLE_NAME}/delete.php?{TABLE_ID}=<?php echo $_GET["{TABLE_ID}"];?>" class="btn btn-warning">Delete</a>
                        <a href="javascript:history.back()" class="btn btn-primary">Back</a>
                    </p> 
                    <?php
                    {FOREIGN_KEY_REFS}

                    if (file_exists(stream_resolve_include_path("extension.php"))){
                        include("extension.php");
                    }

                    // Close connection
                    mysqli_close($link);
                    ?>
                </div>
            </div>
        </div>
    </section>
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
require_once "../helpers.php";

// Process delete operation after confirmation
if(isset($_POST["{TABLE_ID}"]) && !empty($_POST["{TABLE_ID}"])){

    // Prepare a delete statement
    $sql = "DELETE FROM `{TABLE_NAME}` WHERE `{TABLE_ID}` = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        // Set parameters
        $param_id = trim($_POST["{TABLE_ID}"]);

        // Bind variables to the prepared statement as parameters
		if (is_int($param_id)) $__vartype = "i";
		elseif (is_string($param_id)) $__vartype = "s";
		elseif (is_numeric($param_id)) $__vartype = "d";
		else $__vartype = "b"; // blob
        mysqli_stmt_bind_param($stmt, $__vartype, $param_id);

        try {
            mysqli_stmt_execute($stmt);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $error = "<p class='font-weight-bold'>Er zijn nog verwijzingen naar dit record, zie de view pagina voor meer informatie:</p>";
            $error .= $e->getMessage();
        }
    
        if (!isset($error)){
            // Records deleted successfully. Redirect to landing page
            header("location: ../{TABLE_NAME}/index.php");
        }
    }

    // Close statement
    mysqli_stmt_close($stmt);

    // Close connection
    mysqli_close($link);
} else{
    // Check existence of id parameter
	$_GET["{TABLE_ID}"] = trim($_GET["{TABLE_ID}"]);
    if(empty($_GET["{TABLE_ID}"])){
        // URL doesn't contain id parameter. Redirect to error page
        header("location: ../error.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Record</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once('../navbar.php'); ?>
<body>
    <section class="pt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 mx-auto">
                    <div class="page-header">
                        <h1>Delete Record</h1>
                    </div>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . "?{TABLE_ID}=" . $_GET["{TABLE_ID}"]; ?>" method="post">
                    <?php print_error_if_exists($error); ?>
                        <div class="alert alert-danger fade-in">
                            <input type="hidden" name="{TABLE_ID}" value="<?php echo trim($_GET["{TABLE_ID}"]); ?>"/>
                            <p>Are you sure you want to delete this record?</p><br>
                            <p>
                                <input type="submit" value="Yes" class="btn btn-danger">
                                <a href="javascript:history.back()" class="btn btn-secondary">No</a>
                            </p>
                        </div>
                    </form>
                    <p>
                        <a href="../{TABLE_NAME}/read.php?{TABLE_ID}=<?php echo $_GET["{TABLE_ID}"];?>" class="btn btn-info">View</a>
                        <a href="../{TABLE_NAME}/update.php?{TABLE_ID}=<?php echo $_GET["{TABLE_ID}"];?>" class="btn btn-secondary">Edit</a>
                        <a href="javascript:history.back()" class="btn btn-primary">Back</a>
                    </p>
                </div>
            </div>
        </div>
        <?php
            if (file_exists(stream_resolve_include_path("extension.php"))){
                include("extension.php");
            }
        ?>
    </section>
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
require_once "../helpers.php";

{CREATE_DEFAULT_VARIABLES}
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    {CREATE_POST_VARIABLES}

    $stmt = $link->prepare("INSERT INTO `{TABLE_NAME}` ({CREATE_COLUMN_NAMES}) VALUES ({CREATE_QUESTIONMARK_PARAMS})");

    try {
        $stmt->execute([ {CREATE_SQL_PARAMS} ]);
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Record</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once('../navbar.php'); ?>
<body>
    <section class="pt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 mx-auto">
                    <div class="page-header">
                        <h2>Create Record</h2>
                    </div>
                    <?php print_error_if_exists($error); print_message_if_exists($message); ?>
                    <p>Please fill this form and submit to add a record to the database.</p>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                        {CREATE_HTML}

                        <input type="submit" class="btn btn-primary" value="Create">
                        <input type="submit" class="btn btn-info" name='another' value="Create another">
                        <a href="../{TABLE_NAME}/index.php" class="btn btn-secondary">Cancel</a>
                    </form>
                    <p> * field can not be left empty </p>
                </div>
            </div>
        </div>
        <?php
            if (file_exists(stream_resolve_include_path("extension.php"))){
                include("extension.php");
            }
        ?>
    </section>
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
require_once "../helpers.php";

// Processing form data when form is submitted
if(isset($_POST["{COLUMN_ID}"]) && !empty($_POST["{COLUMN_ID}"])){
    // Get hidden input value
    ${COLUMN_ID} = $_POST["{COLUMN_ID}"];

    {CREATE_POST_VARIABLES}

    // Prepare an update statement

    $stmt = $link->prepare("UPDATE `{TABLE_NAME}` SET {UPDATE_SQL_PARAMS} WHERE {UPDATE_SQL_ID}");

    try {
        $stmt->execute([ {UPDATE_SQL_COLUMNS}  ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = $e->getMessage();
    }

    if (!isset($error)){
        header("location: ../{TABLE_NAME}/read.php?{COLUMN_ID}=${COLUMN_ID}");
    }
} 
// Check existence of id parameter before processing further
$_GET["{COLUMN_ID}"] = trim($_GET["{COLUMN_ID}"]);
if(isset($_GET["{COLUMN_ID}"]) && !empty($_GET["{COLUMN_ID}"])){
    // Get URL parameter
    ${COLUMN_ID} =  trim($_GET["{COLUMN_ID}"]);

    // Prepare a select statement
    $sql = "SELECT * FROM `{TABLE_NAME}` WHERE `{COLUMN_ID}` = ?";
    $stmt = mysqli_prepare($link, $sql);
    // Set parameters
    $param_id = ${COLUMN_ID};

    // Bind variables to the prepared statement as parameters
    if (is_int($param_id)) $__vartype = "i";
    elseif (is_string($param_id)) $__vartype = "s";
    elseif (is_numeric($param_id)) $__vartype = "d";
    else $__vartype = "b"; // blob
    mysqli_stmt_bind_param($stmt, $__vartype, $param_id);

    // Attempt to execute the prepared statement
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1){
        /* Fetch result row as an associative array. Since the result set
        contains only one row, we don't need to use while loop */
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

        // Retrieve individual field value

        {UPDATE_COLUMN_ROWS}

    } else{
        // URL doesn't contain valid id. Redirect to error page
        header("location: ../error.php");
        exit();
    }
   
    // Close statement
    mysqli_stmt_close($stmt);

}  else{
    // URL doesn't contain id parameter. Redirect to error page
    header("location: ../error.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Record</title>
    {CSS_REFS}
    {JS_REFS}
</head>
<?php require_once('../navbar.php'); ?>
<body>
    <section class="pt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 mx-auto">
                    <div class="page-header">
                        <h2>Update Record</h2>
                    </div>
                    <?php print_error_if_exists($error); ?>
                    <p>Please edit the input values and submit to update the record.</p>
                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">

                        {CREATE_HTML}

                        <input type="hidden" name="{COLUMN_ID}" value="<?php echo ${COLUMN_ID}; ?>"/>
                        <p>
                            <input type="submit" class="btn btn-primary" value="Submit">
                            <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
                        </p>
                        <p>
                            <a href="../{TABLE_NAME}/read.php?{COLUMN_ID}=<?php echo $_GET["{COLUMN_ID}"];?>" class="btn btn-info">View</a>
                            <a href="../{TABLE_NAME}/delete.php?{COLUMN_ID}=<?php echo $_GET["{COLUMN_ID}"];?>" class="btn btn-warning">Delete</a>
                            <a href="javascript:history.back()" class="btn btn-primary">Back</a>
                        </p>
                        <p> * field can not be left empty </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
            if (file_exists(stream_resolve_include_path("extension.php"))){
                include("extension.php");
            }
        ?>
    </section>
</body>
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
<body>
    <section class="pt-5">
        <div class="container-fluid">
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
    </section>
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
  <a class="navbar-brand nav-link disabled" href="#">{APP_NAME}</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Select Page
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
        {TABLE_BUTTONS}                                                                                                                                                                                                     
        <!-- TABLE_BUTTONS -->
        </div>
      </li>
    </ul>
  </div>
</nav>
EOT;


