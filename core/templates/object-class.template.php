<?php

require_once "WaterWebObject.php";

class {TABLE} extends WaterWebObject
{
const KOLOMMEN = [/**{COLUMNS}*/];

    /**{ATTRIBUTES}*/

    public function __construct(/**{CONSTRUCT_PARAMETERS}*/)
    {
        /**{CONSTRUCT_STATEMENTS}*/
    }

    public function __toString() : string {
        return "{TABLE} " . $this->id . ": ";
    }
}

class {TABLE}ViaArray extends {TABLE}
{

    function __construct(array $row)
    {
        parent::__construct(
            /**{ARRAY_CONSTRUCT_ROW}*/
        );
    }
}