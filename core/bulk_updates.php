<?php

/** Perform bulk updates on records with $ids in table $table. $values contains the columns with the new values
 * @param string $table The name of the table that we perform updates on
 * @param string $table_id Column of $table that is the primary key
 * @param array $values Associative array with columns => new values
 * @param array $ids List with all the ids that we need to change.
 * We assume that all these values are correct and clean. We also assume that $values and $ids have at least one element
 */
function bulk_update_crud(string $table, string $table_id, array $values, array $ids)
{
    global $link;

    $sql = "UPDATE `$table` SET `" . implode("` = ?, `", array_keys($values)) . "` = ? WHERE `$table_id` = ?";
    try {
        $stmt = $link->prepare($sql);
    } catch (mysqli_sql_exception $e) {
        return "Error preparing bulk update statement: " . $e->getMessage();
    }

    $num_successful = 0;
    $html = "";

    foreach ($ids as $id) {
        $stmt_param = array_values($values);
        // Translate "null" to null
        $stmt_param = array_map(function ($v) {
            return strtolower($v) == 'null' ? null : $v; }, $stmt_param);
        $stmt_param[] = $id;
        try {
            $stmt->execute($stmt_param);
            $num_successful += 1;
        } catch (mysqli_sql_exception $e) {
            $html .= "Error bulk updating ($table.$table_id: $id): " . $e->getMessage() . "<br>";
        }
    }

    return "Succesfully updated $num_successful records.<br> " . $html;
}

/** Perform bulk deletion on records with $ids in table $table. 
 * @param string $table The name of the table that we perform updates on
 * @param string $table_id Column of $table that is the primary key
 * @param array $ids List with all the ids that we need to delete.
 * We assume that all these values are correct and clean and that $ids has at least one element
 */
function bulk_delete_crud(string $table, string $table_id, array $ids)
{
    global $link;

    $sql = "DELETE FROM `$table` WHERE `$table_id` = ?";
    try {
        $stmt = $link->prepare($sql);
    } catch (mysqli_sql_exception $e) {
        return "Error preparing bulk delete statement: " . $e->getMessage();
    }

    $num_deleted = 0;
    $html = "";

    foreach ($ids as $id) {
        try {
            $stmt->execute([$id]);
            $num_deleted += 1;
        } catch (mysqli_sql_exception $e) {
            $html .= "Error bulk deleting ($table.$table_id: $id): " . $e->getMessage() . "<br>";
        }
    }

    return "Succesfully deleted $num_deleted records.<br> " . $html;
}

?>