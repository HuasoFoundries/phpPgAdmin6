<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 9.5 support
 */
class Postgres95 extends Postgres94
{
    public $typIndexes = ['BTREE', 'BRIN', 'RTREE', 'GIST', 'GIN', 'HASH'];
    public $major_version = 9.5;
}
