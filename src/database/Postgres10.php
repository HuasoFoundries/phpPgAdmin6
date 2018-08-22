<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 10.x support
 *
 * @todo add support for identify columns
 *
 * @see https://blog.2ndquadrant.com/postgresql-10-identity-columns/
 */

/**
 * Class to add support for Postgres10.
 */
class Postgres10 extends Postgres96
{
    public $major_version = 10;
}
