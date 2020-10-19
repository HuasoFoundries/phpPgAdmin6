<?php

/**
 * PHPPgAdmin 6.1.0
 */

namespace PHPPgAdmin\Database;

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
    public $help_classname = \PHPPgAdmin\Help\PostgresDoc95::class;

    /**
     * @var float
     */
    public $major_version = 9.5;
}
