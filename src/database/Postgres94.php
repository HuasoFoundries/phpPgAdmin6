<?php

/**
 * PHPPgAdmin 6.1.0
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 9.4 support
 */
class Postgres94 extends Postgres95
{
    public $typIndexes = ['BTREE', 'RTREE', 'GIST', 'GIN', 'HASH', 'SP-GIST'];

    /**
     * @var class-string
     */
    public $help_classname = \PHPPgAdmin\Help\PostgresDoc94::class;

    /**
     * @var float
     */
    public $major_version = 9.4;
}
