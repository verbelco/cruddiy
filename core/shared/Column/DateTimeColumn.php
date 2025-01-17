<?php

class DateTimeColumn extends DateColumn
{
    public function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key);
    }

    public function format($value, $ref = null)
    {
        if (isset($value)) {
            $date = date('d-m-Y H:i:s', strtotime($value));

            return htmlspecialchars($date);
        }
    }

    public function html_input_field($value = null, $required = false, $name = null, $id = null, $placeholder = null): string
    {
        $value = empty($value) ? '' : 'value="'.date("Y-m-d\TH:i:s", strtotime($value)).'"';
        $name = empty($name) ? $this->get_name() : $name;
        $id = empty($id) ? $this->get_name() : $id;
        $placeholder = empty($placeholder) ? '' : ' data-toggle="tooltip" data-placement="bottom" title="'.$placeholder.'"';
        $required = $required ? ' required' : '';

        return '<input type="datetime-local" name="'.$name.'" id="'.$id.'" '.$placeholder.' max="9999-12-31 00:00" class="form-control" '.$value.$required.' >';
    }

    public function html_create_row($value): string
    {
        if (empty($value) && is_a($this->default, 'DateTime')) {
            $value = $this->default->format('Y-m-d H:i:s');
        }

        return $this->html_update_row($value);
    }

    public function html_index_advanced_filter_equal($val)
    {
        return parent::html_input_field($val, false, $this->get_name().'[=]', 'advanced-filter-'.$this->get_name(), 'On this exact date');
    }

    public function html_index_advanced_filter_larger($val)
    {
        return parent::html_input_field($val, false, $this->get_name().'[>]', 'advanced-filter-'.$this->get_name(), 'After this date');
    }

    public function html_index_advanced_filter_smaller($val)
    {
        return parent::html_input_field($val, false, $this->get_name().'[<]', 'advanced-filter-'.$this->get_name(), 'Before this date');
    }

    public function where_operand_equal($val, $link)
    {
        if ($val == 'null') {
            return $this->get_sql_value().' IS NULL';
        } else {
            return 'DATE('.$this->get_sql_value().") = '".mysqli_real_escape_string($link, $val)."'";
        }
    }

    public function where_operand_larger($val, $link)
    {
        return 'DATE('.$this->get_sql_value().") > '".mysqli_real_escape_string($link, $val)."'";
    }

    public function where_operand_smaller($val, $link)
    {
        return 'DATE('.$this->get_sql_value().") < '".mysqli_real_escape_string($link, $val)."'";
    }
}
