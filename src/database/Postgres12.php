<?php

/**
 * PHPPgAdmin 6.0.1
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 12.x support
 */
/**
 * Class to add support for Postgres12.
 */
class Postgres12 extends Postgres11
{
    public $major_version = 12;

    /**
     * Checks to see whether or not a table has a unique id column.
     *
     * @deprecated this field has been removed of pg_class as of PG 12
     * @see https://www.postgresql.org/docs/12/catalog-pg-class.html
     *
     * @param string $table The table name
     *
     * @return bool false
     */
    public function hasObjectID($table)
    {
        return false;
    }
}
