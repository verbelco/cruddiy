<?php

namespace Database;

require_once "shared/database/classes/Tabel.php";

use DateTime, Exception, ErrorException, Logger;

/** Class om {TABLE} op te halen uit de database */
class {TABLE} extends Tabel
{
    const KOLOMMEN = [/**{COLUMNS}*/];
    const PREVIEW_KOLOMMEN = [/**{PREVIEW_COLUMNS}*/];
    const PRIMARY_KEY = '{COLUMN_ID}';
    const TABEL = '{TABLE}';

    /**{ATTRIBUTES}*/

    /** Initialiseer een {TABLE} met een associative array met de vereiste kolommen */
    public function __construct(array ${TABLE})
    {
        ${TABLE} = array_intersect_key(${TABLE}, array_flip($this::KOLOMMEN));
        parent::__construct(${TABLE});
    }

    /** Kijk of dit record aanwezig is in de database */
    public function vindRecord(?Logger $logger = null, bool $log_verbose = false): ?{TABLE}
    {
        if (isset($this->{COLUMN_ID})) {
            try {
                return new {TABLE}Via{COLUMN_ID}($this->{COLUMN_ID});
            } catch (Exception $e) {
                if ($log_verbose) {
                    $logger->log($e->getMessage());
                }
            }
        }

        return null;
    }
}

class {TABLE}Via{COLUMN_ID} extends {TABLE}
{

    /**
     * Initialiseer een {TABLE} vanuit het {COLUMN_ID}
     * @throws ErrorException als er geen {TABLE} gevonden wordt.
     */
    public function __construct(int ${COLUMN_ID})
    {
        global $db;
        static $stmt;

        if (!isset($stmt)) {
            $stmt = $db->prepare("SELECT * FROM {TABLE} WHERE {COLUMN_ID} = ?");
        }

        $stmt->execute([${COLUMN_ID}]);
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            parent::__construct($result->fetch_assoc());
        } else {
            throw new ErrorException("Kan geen {TABLE} vinden met {COLUMN_ID} ${COLUMN_ID}");
        }
    }
}