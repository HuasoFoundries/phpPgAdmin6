<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Database;

use PHPPgAdmin\Help\PostgresDoc95;

/**
 * @file
 * PostgreSQL 9.5 support
 */
class Postgres95 extends Postgres96
{
    public $typIndexes = ['BTREE', 'BRIN', 'RTREE', 'GIST', 'GIN', 'HASH'];

    /**
     * @var class-string
     */
    public $help_classname = PostgresDoc95::class;

    /**
     * @var float
     */
    public $major_version = 9.5;
}
