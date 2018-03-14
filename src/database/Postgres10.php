<?php

namespace PHPPgAdmin\Database;

/**
 * PostgreSQL 10.x support
 * @todo add support for identify columns
 * @see https://blog.2ndquadrant.com/postgresql-10-identity-columns/
 */

class Postgres10 extends Postgres96
{
    public $major_version = 10;
}
