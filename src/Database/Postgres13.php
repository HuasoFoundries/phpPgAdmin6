<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database;

use PHPPgAdmin\Help\PostgresDoc12;

/**
 * @file
 * PostgreSQL 13.x support
 */
/**
 * Class to add support for Postgres13.
 * (Which doesn't exist yet, but it's better than rejecting connections).
 */
class Postgres13 extends Postgres12
{
    /**
     * @var float
     */
    public $major_version = 13;

    /**
     * @var class-string
     */
    public $help_classname = PostgresDoc12::class;
}
