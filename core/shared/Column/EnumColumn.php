<?php

class EnumColumn extends Column
{
    /** Array with possible values (<key> => <readable value>) */
    protected array $enums;

    function __construct(string $name, string $displayname, ?string $comment, string $table, $default, ?string $sql_join, ?string $sql_select, bool $required, bool $primary_key, array $enums)
    {
        parent::__construct($name, $displayname, $comment, $table, $default, $sql_join, $sql_select, $required, $primary_key);
        $this->enums = $enums;
    }

    function format($value, $ref = null)
    {
        if (isset($value)) {
            return $this->enums[$value];
        }
    }

    /** Update the enums with better readable values
     * @param array $new_enums Associative array with (<enum key> => <synonyms>)
     * Synonyms can be a string or a list with synonyms. (we then take the first one.)
     * We update the readable values in $this->enums with the first available synonym
     * 
     * This function should be called in class extension, with the defined enums.
     */
    function update_enum(array $new_enums)
    {
        foreach ($new_enums as $key => $value) {
            if (is_array($value)) {
                $value = $value[0];
            }
            if (isset($this->enums[$key])) {
                $this->enums[$key] = $value;
            }
        }
    }


    function html_input_field($value = null, $required = true, $name = null, $id = null, $placeholder = null): string
    {
        $name = empty($name) ? $this->get_name() : $name;
        $id = empty($id) ? $this->get_name() : $id;

        $enum = $this->enums;

        // Maak een zoekbare enum als er meer dan N opties zijn
        if (count($enum) > 25) {
            $class = "";
        } else {
            $class = ' class="form-control"';
        }

        $html = '<select name="' . $name . '" id="' . $id . '"' . $class . '>';

        if (!$this->get_required()) {
            $enum = array("null" => "Null") + $enum;
        }

        if (!$required) {
            $enum = array("" => "") + $enum;
        }

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

    function html_index_advanced_filter_equal($val)
    {
        return $this->html_input_field($val, false, $this->get_name() . '[=]', 'advanced-filter-' . $this->get_name());
    }
}