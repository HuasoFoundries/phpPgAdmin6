<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 9.5 support
 */
class Postgres95 extends Postgres96
{
    public $typIndexes = ['BTREE', 'BRIN', 'RTREE', 'GIST', 'GIN', 'HASH'];

    public $major_version = 9.5;
}
