<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Database;

use PHPPgAdmin\Help\PostgresDoc12;

/**
 * @file
 * PostgreSQL 12.x support
 */
/**
 * Class to add support for Postgres12.
 */
class Postgres12 extends Postgres11
{
    /**
     * @var float
     */
    public $major_version = 12;

    /**
     * @var class-string
     */
    public $help_classname = PostgresDoc12::class;

    /**
     * Checks to see whether or not a table has a unique id column.
     *
     * @deprecated this field has been removed of pg_class as of PG 12
     * @see https://www.postgresql.org/docs/12/catalog-pg-class.html
     *
     * @param string $table The table name
     *
     * @return bool
     */
    public function hasObjectID($table)
    {
        return false;
    }
}
