<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
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
