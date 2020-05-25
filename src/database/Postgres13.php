<?php

/**
 * PHPPgAdmin 6.0.1
 */

namespace PHPPgAdmin\Database;

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
    public $major_version = 13;
}
