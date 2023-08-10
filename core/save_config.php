<?php
// With this file you can save you cruddiy config.
$config_folder = "temp/";
if (!is_dir($config_folder)) {
    mkdir($config_folder, 0777, true);
}

$response = "";
$state = 0;

$config = $_POST;

$fields_to_save = ['columndisplay', 'columnvisible', 'columninpreview',];

$config_name = "config-cruddiy-" . date("d-m-Y_H:i:s");

unset($config['singlebutton']);

/** Returns true if this column should be saved for the config */
function to_save($fullname){
    global $fields_to_save;
    foreach($fields_to_save as $n => $name){
        if(str_contains($fullname, $name)){
            return true;
        }
    }
    return false;
}

/**
 * The array must be one dimensional for the Javascript parser
 * So in this function we transform array['typekolom']['kolom'] = val to array['typekolom[kolom]']
 * We also remove fields that we don't want.
 */
function verklein_json($data, string $name = "")
{
    if (!is_array($data)) {
        if(to_save($name) && !empty($data)){
            return array($name => $data);
        } else {
            return array();
        }
        
    } else {
        $result = array();
        foreach ($data as $key => $val) {
            $key = $name == "" ? $key : $name . '[' . $key . ']';
            array_push($result, verklein_json($val, $key));
        }
        return array_merge(...$result);
    }
}

function export_json($data)
{
    return json_encode(verklein_json($data), JSON_PRETTY_PRINT);
}

$filename = $config_folder . $config_name . ".json";

if (!(file_put_contents($filename, export_json($config)) === False)) {
    $response = "config $config_name for this app saved in cruddiy/temp/";
    $state = 1;
} else {
    $response = "Onbekende fout bij het toevoegen van config $config_name.";
    $state = -2;
}



?>