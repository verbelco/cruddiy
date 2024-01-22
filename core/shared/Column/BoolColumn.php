<?php

class BoolColumn extends EnumColumn
{
    function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key, array(1 => "True", 0 => "False"));
    }

    function format($value, $ref = null)
    {
        if (isset($value)) {
            return $value ? "True" : "False";
        }
    }

    function html_index_advanced_filter($filter): string
    {
        return '<div class="form-group row my-1">
                    <label class="col-sm-2 col-form-label">' . $this->html_columnname_with_tooltip(false) . '</label>
                    <div class="col-sm-3">
                        ' . $this->html_index_advanced_filter_equal($filter[$this->get_name()]["="]) . '
                    </div>
                </div>';
    }
}