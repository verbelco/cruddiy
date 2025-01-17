<?php

class TextColumn extends Column
{
    public function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key);
    }

    public function format($value, $ref = null)
    {
        if (isset($value)) {
            return nl2br($value);
        }
    }

    public function html_input_field($value = null, $required = false, $name = null, $id = null, $placeholder = null): string
    {
        $value = empty($value) ? '' : $value;
        $name = empty($name) ? $this->get_name() : $name;
        $id = empty($id) ? $this->get_name() : $id;
        $placeholder = empty($placeholder) ? '' : ' placeholder="'.$placeholder.'"';
        $required = $required ? ' required' : '';

        return '<textarea name="'.$name.'" id="'.$id.'" class="form-control"'.$required.$placeholder.'>'.$value.'</textarea>';
    }
}
