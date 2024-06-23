<?php

namespace CRUD;

use DateTime;

require_once "shared/crud/Table/Table.php";

class {TABLE} extends Table
{
    function __construct()
    {
        parent::__construct(
            "{TABLE}",
            "{TABLE_DISPLAY}",
            "{TABLE_COMMENT}",
            "{COLUMN_ID}",
            [ /**{FOREIGN_KEY_REFS}*/],
            array( /**{COLUMNS_CLASSES}*/)
        );
    }
}

$CRUD['{TABLE}'] = new {TABLE}();

include "class_extension.php";