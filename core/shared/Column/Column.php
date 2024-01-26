<?php

require_once "TextColumn.php";
require_once "DateColumn.php";
require_once "DateTimeColumn.php";
require_once "EnumColumn.php";
require_once "BoolColumn.php";
require_once "IntColumn.php";
require_once "FloatColumn.php";
require_once "ForeignKeyColumn.php";

/** Top level class for converting sql columns to html */
class Column
{
    /** Name of the column, Must be unique in this table */
    private string $name;
    /** Friendly name of the column */
    private string $displayname;
    /** Default value for this column, usually the same as $name */
    protected $default;
    /** Extra information on this column */
    private ?string $comment;
    /** Table that this column belongs to */
    private string $table;
    /** True if this column may not be null */
    private bool $required;
    /** True if this column is the primary key */
    private bool $primary_key;
    /** Join that is required for $sql */
    private ?string $sql_join;
    /** Custom SQL select statement */
    protected ?string $sql_select;

    function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key)
    {
        $this->name = $name;
        $this->displayname = $displayname;
        $this->comment = $comment;
        $this->default = $default;
        $this->table = $table;
        $this->sql_join = $sql_join;
        $this->required = $required;
        $this->primary_key = $primary_key;
        $this->sql_select = $sql_select;
    }

    function get_name()
    {
        return $this->name;
    }

    function get_table()
    {
        return $this->table;
    }

    function get_required()
    {
        return $this->required;
    }

    /** Converts a value to a nice readable format */
    function format($value, $ref = null)
    {
        return $value;
    }

    function html_index_table_element(array $row): string
    {
        return "<td>" . $this->format($row[$this->get_name()]) . "</td>";
    }

    function html_columnname_with_tooltip($required): string
    {
        if ($required) {
            $displayname = $this->displayname . "*";
        } else {
            $displayname = $this->displayname;
        }

        if (isset($this->comment)) {
            return "<span data-toggle='tooltip' data-placement='top' data-bs-html='true' title='" . nl2br($this->comment) . "'>" . $displayname . "</span>";
        } else {
            return $displayname;
        }
    }

    /** Create the HTML for a table header */
    function html_index_table_header($get_param_search, $get_param_where, $get_param_order, $arrow): string
    {
        return "<th><a href='$get_param_search$get_param_where$get_param_order'> " . $this->html_columnname_with_tooltip(false) . " $arrow</a></th>";
    }

    /** Create an input field for this column 
     * Specify values for $value, $name or $id to overwrite them
     * Defaults for $name and $id are $this->get_name()
     */
    function html_input_field($value = null, $required = false, $name = null, $id = null, $placeholder = null): string
    {
        $value = empty($value) ? "" : 'value="' . $value . '"';
        $name = empty($name) ? $this->get_name() : $name;
        $id = empty($id) ? $this->get_name() : $id;
        $placeholder = empty($placeholder) ? "" : ' placeholder="' . $placeholder . '"';
        $required = $required ? ' required' : '';
        return '<input type="text" name="' . $name . '" id="' . $id . '" class="form-control" ' . $placeholder . $value . $required . ' >';
    }

    /** Create the HTML for the bulk updates */
    function html_index_bulk_update(): string
    {
        if ($this->primary_key) {
            return "";
        }

        $html = '
        <div class="form-group row my-2 text-center">
            <div class="col-md-1">
                    <input type="checkbox" id="bulkupdates-' . $this->get_name() . '-visible" value="1">
                    <label class="col-form-label" for="bulkupdates-' . $this->get_name() . '-visible">Edit</label>
            </div>
            <div class="col-md-2">
                <label class="col-form-label" for="bulkupdates-' . $this->get_name() . '-visible">' . $this->html_columnname_with_tooltip($this->get_required()) . '</label>
            </div>
            <div class="col-md-1">';

        if (!$this->required) {
            $html .= '<input type="checkbox" id="bulkupdates-' . $this->get_name() . '-null" name="' . $this->get_name() . '" value="null">
            <label class="col-form-label" for="bulkupdates-' . $this->get_name() . '-null">null</label>';
        }

        $html .= '</div>
            <div class="col">' . $this->html_input_field() . '</div>
        </div>';

        return $html;
    }

    /** Creates a form element to select whether this column should be visible */
    function html_index_flexible_columns($selected)
    {
        $selected = $selected ? " checked" : "";
        return '<div class="form-check">
                    <input class="form-check-input" type="checkbox" name="flexible-columns[]" id="' . $this->get_name() . '-flexible-column" value="' . $this->get_name() . '"' . $selected . '>
                    <label class="form-check-label fw-bold" for="' . $this->get_name() . '-flexible-column">
                    ' . $this->html_columnname_with_tooltip(false) . '
                    </label>
                </div>';
    }

    /** Creates a row for this column to display 
     * We get the $row with mysql data
     */
    function html_read_row($row): string
    {
        return '<div class="form-group row my-1">
                    <div class="col-sm-4 fw-bold">
                    ' . $this->html_columnname_with_tooltip(false) . '
                    </div>
                    <div class="col">
                    ' . $this->format($row[$this->get_name()]) . '
                    </div>
                </div>';
    }

    function html_create_row($value): string
    {
        if (empty($value)) {
            $value = $this->default;
        }
        return $this->html_update_row($value);
    }

    function html_update_row($value): string
    {
        return '
        <div class="form-group row my-2">
            <label class="col-sm-4 col-form-label" for="naam">' . $this->html_columnname_with_tooltip($this->get_required()) . '</label>
            <div class="col">' .
            $this->html_input_field($value, $this->get_required())
            . '</div>
        </div>';
    }

    /** Return the pre-processed value to give to the query */
    function get_sql_update_value($value)
    {
        if ((empty($value) || $value == "null") && !$this->get_required()) {
            return null;
        } else {
            return $value;
        }
    }

    function get_sql_create_value($value)
    {
        return $this->get_sql_update_value($value);
    }

    /** Return SQL for the update statement */
    function get_sql_update_stmt()
    {
        return "`" . $this->get_name() . "` = ?";
    }

    /** Return the value in SQL syntax */
    function get_sql_value(): string
    {
        if (isset($this->sql_select)) {
            return $this->sql_select;
        } else {
            return "`" . $this->table . "`.`" . $this->get_name() . "`";
        }
    }

    /** Return the SQL Select Statement */
    function get_sql_select(): string
    {
        return $this->get_sql_value() . " AS `" . $this->get_name() . "`";
    }

    /** Return the JOINS required for the select statement */
    function get_sql_join(): string
    {
        if (isset($this->sql_join)) {
            return $this->sql_join . "\n";
        } else {
            return "";
        }
    }

    // Advanced Filter and creating WHERE statements

    function html_index_advanced_filter_null($value)
    {
        $html = '<select name="' . $this->get_name() . '[null]" class="form-control" data-toggle="tooltip" title="Filter on (non-)empty values">';

        $enum = array(
            "" => "",
            "null" => "Null",
            "notnull" => "Not Null"
        );

        foreach ($enum as $key => $text) {
            if (isset($value) && $key == $value) {
                $html .= '<option value="' . $key . '" selected>' . $text . '</option>';
            } else {
                $html .= '<option value="' . $key . '">' . $text . '</option>';
            }
        }

        $html .= '</select>';
        return $html;
    }

    function html_index_advanced_filter_like($val)
    {
        return '<input type="text" name="' . $this->get_name() . '[%]" data-toggle="tooltip"
        data-placement="bottom" title="Like this string" class="form-control"
        placeholder="Like (%)" step="any"
        value="' . $val . '">';
    }

    function html_index_advanced_filter_equal($val)
    {
        return '<input type="text" name="' . $this->get_name() . '[=]" data-toggle="tooltip"
            data-placement="bottom" title="Filter on this exact string"
            class="form-control" placeholder="Equal to" step="any"
            value="' . $val . '">';
    }

    function html_index_advanced_filter_larger($val)
    {
        return '<input type="number" name="' . $this->get_name() . '[>]" class="form-control" placeholder="Larger than" step="any" value="' . $val . '">';
    }

    function html_index_advanced_filter_smaller($val)
    {
        return '<input type="number" name="' . $this->get_name() . '[<]" class="form-control" placeholder="Smaller than" step="any" value="' . $val . '">';
    }

    /** Create the HTML for the advanced filter */
    function html_index_advanced_filter($filter): string
    {
        $html = '<div class="form-group row row-cols-5 my-1">
                    <label class="col-sm-2 col-form-label">' . $this->html_columnname_with_tooltip(false) . '</label>
                    <div class="col">
                        ' . $this->html_index_advanced_filter_equal($filter[$this->get_name()]["="]) . '
                    </div>
                    <div class="col">
                        ' . $this->html_index_advanced_filter_like($filter[$this->get_name()]["%"]) . '
                    </div>';

        if (!$this->get_required()) {
            $html .= '<div class="col"></div>';
            $html .= '<div class="col">
                        ' . $this->html_index_advanced_filter_null($filter[$this->get_name()]["null"]) . '
                    </div>';
        }

        $html .= '</div>';
        return $html;
    }

    function where_operand_like($val, $link)
    {
        return $this->get_sql_value() . " LIKE '%" . mysqli_real_escape_string($link, $val) . "%'";
    }

    function where_operand_equal($val, $link)
    {
        if ($val == "null") {
            return $this->get_sql_value() . " IS NULL";
        } else {
            return $this->get_sql_value() . " = '" . mysqli_real_escape_string($link, $val) . "'";
        }
    }

    function where_operand_null($val, $link)
    {
        if ($val == "null") {
            return $this->get_sql_value() . " IS NULL";
        } elseif ($val == "notnull") {
            return $this->get_sql_value() . " IS NOT NULL";
        }
    }

    function where_operand_larger($val, $link)
    {
        return $this->get_sql_value() . " > '" . mysqli_real_escape_string($link, $val) . "'";
    }

    function where_operand_smaller($val, $link)
    {
        return $this->get_sql_value() . " < '" . mysqli_real_escape_string($link, $val) . "'";
    }

    /** Create the SQL where parameters for this column. These come from the Advanced Filters
     *  @param array $filter associative array with 'operand' => 'value'
     *  Allowed operands are %, >, <, =. Default is =.
     *  We call a different function for each operand.
     *  @return array [$get_param_where, $where_statement]
     *  $get_param_where (string): The parameters properly encoded for URL
     *  $where_statement (list): The WHERE statement
     */
    function create_sql_where(array $filter, $link)
    {
        $where_statement = [];
        $get_param_where = "";

        foreach ($filter as $operand => $val) {

            if ($operand == '%') {
                $where_statement[] = $this->where_operand_like($val, $link);
            } elseif ($operand == '>') {
                $where_statement[] = $this->where_operand_larger($val, $link);
            } elseif ($operand == '<') {
                $where_statement[] = $this->where_operand_smaller($val, $link);
            } elseif ($operand == 'null') {
                $where_statement[] = $this->where_operand_null($val, $link);
            } else {
                $where_statement[] = $this->where_operand_equal($val, $link);
            }
            $get_param_where .= "&" . $this->get_name() . urlencode("[$operand]") . "=$val";
        }
        return [$get_param_where, $where_statement];
    }
}