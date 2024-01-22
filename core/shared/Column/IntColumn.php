<?php

class IntColumn extends Column
{
    function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key);
    }

    function html_input_field($value = null, $required = false, $name = null, $id = null, $placeholder = null): string
    {
        $value = empty($value) && $value != 0 ? "" : 'value="' . $value . '"';
        $name = empty($name) ? $this->get_name() : $name;
        $id = empty($id) ? $this->get_name() : $id;
        $placeholder = empty($placeholder) ? "" : ' placeholder="' . $placeholder . '"';
        $required = $required ? ' required' : '';
        return '<input type="number" name="' . $name . '" id="' . $id . '" class="form-control" ' . $placeholder . $value . $required . '>';
    }

    function html_index_advanced_filter($filter): string
    {
        return '<div class="form-group row my-1">
                    <label class="col-sm-2 col-form-label">' . $this->html_columnname_with_tooltip(false) . '</label>
                    <div class="col-sm-3">
                        ' . $this->html_index_advanced_filter_equal($filter[$this->get_name()]["="]) . '
                    </div>
                    <div class="col-sm-3">
                        ' . $this->html_index_advanced_filter_larger($filter[$this->get_name()][">"]) . '
                    </div>
                    <div class="col-sm-3">
                        ' . $this->html_index_advanced_filter_smaller($filter[$this->get_name()]["<"]) . '
                    </div>
                </div>';
    }

    function html_index_advanced_filter_equal($val)
    {
        return $this->html_input_field($val, false, $this->get_name() . '[=]', 'advanced-filter-' . $this->get_name(), "Equal to");
    }

    function html_index_advanced_filter_larger($val)
    {
        return $this->html_input_field($val, false, $this->get_name() . '[>]', 'advanced-filter-' . $this->get_name(), "Larger than");
    }

    function html_index_advanced_filter_smaller($val)
    {
        return $this->html_input_field($val, false, $this->get_name() . '[<]', 'advanced-filter-' . $this->get_name(), "Smaller than");
    }
}