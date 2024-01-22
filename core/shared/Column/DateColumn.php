<?php

class DateColumn extends Column
{
    function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key);
    }

    function format($value, $ref = null)
    {
        if (isset($value)) {
            $date = date('d-m-Y', strtotime($value));
            return htmlspecialchars($date);
        }
    }

    function html_input_field($value = null, $required = false, $name = null, $id = null, $placeholder = null): string
    {
        $value = empty($value) ? "" : 'value="' . $value . '"';
        $name = empty($name) ? $this->get_name() : $name;
        $id = empty($id) ? $this->get_name() : $id;
        $placeholder = empty($placeholder) ? "" : ' data-toggle="tooltip" data-placement="bottom" title="' . $placeholder . '"';
        $required = $required ? ' required' : '';
        return '<input type="date" name="' . $name . '" id="' . $id . '" ' . $placeholder . ' class="form-control" ' . $value . $required . ' >';
    }

    function html_create_row($value): string
    {
        if (empty($value) && is_a($this->default, 'DateTime')) {
            $value = $this->default->format("Y-m-d");
        }
        return $this->html_update_row($value);
    }

    function html_index_advanced_filter($filter): string
    {
        return '<div class="form-group row my-1">
                    <label class="col-sm-2 col-form-label">' . $this->html_columnname_with_tooltip(false) . '</label>
                    <div class="col-sm-3">
                        ' . $this->html_index_advanced_filter_equal($filter[$this->get_name()]["="]) . '
                    </div>
                    <div class="col-sm-3">
                        ' . $this->html_index_advanced_filter_smaller($filter[$this->get_name()]["<"]) . '
                    </div>
                    <div class="col-sm-3">
                        ' . $this->html_index_advanced_filter_larger($filter[$this->get_name()][">"]) . '
                    </div>
                </div>';
    }

    function html_index_advanced_filter_equal($val)
    {
        return $this->html_input_field($val, false, $this->get_name() . '[=]', 'advanced-filter-' . $this->get_name(), "On this exact date");
    }

    function html_index_advanced_filter_larger($val)
    {
        return $this->html_input_field($val, false, $this->get_name() . '[>]', 'advanced-filter-' . $this->get_name(), "After this date");
    }

    function html_index_advanced_filter_smaller($val)
    {
        return $this->html_input_field($val, false, $this->get_name() . '[<]', 'advanced-filter-' . $this->get_name(), "Before this date");
    }
}