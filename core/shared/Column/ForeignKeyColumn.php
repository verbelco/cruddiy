<?php

/** Foreign keys are different, in the sense that they actually have two values.
 * One is the local value, typically an integer id.
 * The second one is a string representing the referenced record */
class ForeignKeyColumn extends EnumColumn
{

    /** The name of the column in the results with the foreign key value */
    private string $fk_value;
    /** Table that is referenced */
    private string $fk_table;
    /** Column that is referenced */
    private string $fk_column;
    /** True if the reference is to a single record i.e. primary key */
    private string $fk_unique;
    /** Name of the FK table in AS part of the query*/
    private string $fk_table_alias;


    function __construct(string $name, string $displayname, ?string $comment, string $table, $default, string $sql_join, string $sql_select, string $fk_table, string $fk_column, bool $fk_unique, bool $required, bool $primary_key)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key, array());
        $this->fk_value = $this->get_name() . "-value";
        $this->fk_table = $fk_table;
        $this->fk_table_alias = $this->get_name() . $fk_table;
        $this->fk_column = $fk_column;
        $this->fk_unique = $fk_unique;
    }

    /** $value contains the string that we want to display, $ref contains the actual value that is stored */
    function format($value, $ref = null)
    {
        if (isset($value)) {
            if ($this->fk_unique) {
                return '<a href="../' . $this->fk_table . '/read.php?' . $this->fk_column . '=' . $ref . '">' . $value . '</a>';
            } else {
                return '<a href="../' . $this->fk_table . '/index.php?' . $this->fk_column . urlencode("[=]") . '=' . $ref . '">' . $value . '</a>';
            }
        }
    }

    /** For foreign keys, we need both the value in this column, and the string that it represents */
    function get_sql_select(): string
    {
        return $this->sql_select . " AS `" . $this->fk_value . "`, `" . $this->get_table() . "`.`" . $this->get_name() . "` AS `" . $this->get_name() . "`";
    }

    function html_index_table_element(array $row): string
    {
        return "<td>" . $this->format($row[$this->fk_value], $row[$this->get_name()]) . "</td>";
    }

    function html_read_row($row): string
    {
        return '<div class="form-group row my-1">
                    <div class="col-sm-4 fw-bold">
                    ' . $this->html_columnname_with_tooltip(false) . '
                    </div>
                    <div class="col">
                    ' . $this->format($row[$this->fk_value], $row[$this->get_name()]) . '
                    </div>
                </div>';
    }

    /** Remove the first JOIN from this set of joins and returns the other joins.
     * I.e, return the joins that are required for the referenced table.
     */
    function get_fk_joins()
    {
        $join_string = substr($this->get_sql_join(), 1);
        $position = strpos($join_string, "\n");
        $fk_joins = ($position !== 0) ? substr($join_string, $position + 1) : "";
        return $fk_joins;
    }

    /** We create an select field with all possible foreign key relations */
    function html_input_field($value = null, $required = false, $name = null, $id = null, $placeholder = null): string
    {
        global $link;

        $sql = "SELECT " . $this->sql_select . " AS `" . $this->fk_value . "`, `" . $this->fk_table_alias . "`.`" . $this->fk_column . "` AS `" . $this->get_name() . "`
                FROM `" . $this->fk_table . "` AS `" . $this->fk_table_alias . "` " . $this->get_fk_joins() . " ORDER BY " . $this->sql_select;
        $result = $link->query($sql);
        $this->enums = array();
        while ($row = $result->fetch_assoc()) {
            $this->enums[$row[$this->get_name()]] = $row[$this->fk_value];
        }

        return parent::html_input_field($value, $required, $name, $id, $placeholder);
    }

    function where_operand_equal($val, $link)
    {
        if ($val == "null") {
            return "`" . $this->get_table() . "`.`" . $this->get_name() . "` IS NULL";
        } else {
            return "`" . $this->get_table() . "`.`" . $this->get_name() . "` = '" . mysqli_real_escape_string($link, $val) . "'";
        }
    }

    /** Perform sql subqueries to improve speed */
    function where_operand_like($val, $link)
    {
        $subsql = " SELECT `" . $this->fk_table_alias . "`.`" . $this->fk_column . "`
                    FROM `" . $this->fk_table . "` AS `" . $this->fk_table_alias . "` " . $this->get_fk_joins() . "
                    WHERE " . $this->get_sql_value() . " LIKE '%" . mysqli_real_escape_string($link, $val) . "%'";
        return "`" . $this->get_table() . "`.`" . $this->get_name() . "` IN ($subsql)";
    }
}