<?php

class BoolColumn extends EnumColumn
{
    function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key, array(1 => "True", 0 => "False"));
    }

    function html_index_advanced_filter($filter): string
    {
        $html = '<div class="form-group row row-cols-5 my-1">
                    <label class="col-sm-2 col-form-label">' . $this->html_columnname_with_tooltip(false) . '</label>
                    <div class="col">
                        ' . $this->html_index_advanced_filter_equal($filter[$this->get_name()]["="]) . '
                    </div>';

        if (!$this->get_required()) {
            $html .= '<div class="col"></div>';
            $html .= '<div class="col"></div>';
            $html .= '<div class="col">
                        ' . $this->html_index_advanced_filter_null($filter[$this->get_name()]["null"]) . '
                    </div>';
        }

        $html .= '</div>';
        return $html;
    }
}