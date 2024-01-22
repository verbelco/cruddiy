<?php

class FloatColumn extends IntColumn
{
    function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key);
    }

    function format($value, $ref = null)
    {
        if (isset($value)) {
            return str_replace('.', ',', $value);
        }
    }

    function html_input_field($value = null, $required = false, $name = null, $id = null, $placeholder = null): string
    {
        $value = empty($value) && $value != 0 ? "" : 'value="' . $value . '"';
        $name = empty($name) ? $this->get_name() : $name;
        $id = empty($id) ? $this->get_name() : $id;
        $placeholder = empty($placeholder) ? "" : ' placeholder="' . $placeholder . '"';
        $required = $required ? ' required' : '';
        return '<input type="number" name="' . $name . '" id="' . $id . '" class="form-control" step="any" ' . $placeholder . $value . $required . '>';
    }
}