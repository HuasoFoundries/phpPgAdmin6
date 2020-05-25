<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 9.4 support
 */
class Postgres94 extends Postgres95
{
    public $typIndexes = ['BTREE', 'RTREE', 'GIST', 'GIN', 'HASH', 'SP-GIST'];

    public $major_version = 9.4;
}
