<?php

/** Top level class for rendering columns */
class Column
{
    /** Name of the column (friendly name) */
    private string $name;
    /** Extra information on this column */
    private ?string $comment;
    /** Allowed types are string, bool, int, float and enum
     * Foreign keys are a special type of enum
     */
    private string $type;
    /** Name of the column in SQL */
    private string $sql;
    /** True if this column may not be false */
    private bool $required;
    /** Join that is required for $sql */
    private ?string $sql_join;

    function __construct(string $name, ?string $comment, string $sqlname, bool $required, string $type)
    {
        $this->name = $name;
        $this->comment = $comment;
        $this->sqlname = $sqlname;
        $this->required = $required;
        $this->type = $type;
    }

    function get_name(){
        return $this->name;
    }

    /** Converts a value to a nice readable format */
    function convert(?string $value){
        return $value;
    }

    function html_index_table_element(array $row){
        return "<td>" . $this->convert($row[$this->name]) . "</td>";
    }

    function html_columnname_with_tooltip(){
        if(isset($this->comment)){
            return "<span data-toggle='tooltip' data-placement='top' data-bs-html='true' title=". prepare_text_for_tooltip($this->comment).">". $this->name ."</span>";
        } else {
            return $this->name;
        }
    }

    function html_index_table_header($get_param_search, $get_param_where, $get_param_order, $arrow){
        return "<th><a href='$get_param_search$get_param_where$get_param_order'> ". $this->html_columnname_with_tooltip() ." $arrow</a></th>";
    }
}