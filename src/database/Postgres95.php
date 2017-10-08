<?php

namespace PHPPgAdmin\Database;

/**
 * PostgreSQL 9.5 support
 *
 */

class Postgres95 extends Postgres94
{
    public $typIndexes    = ['BTREE', 'BRIN', 'RTREE', 'GIST', 'GIN', 'HASH'];
    public $major_version = 9.5;
}
