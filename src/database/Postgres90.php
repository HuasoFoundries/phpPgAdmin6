<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database;

use PHPPgAdmin\Help\PostgresDoc90;

/**
 * @file
 * PostgreSQL 9.0 support
 *
 * Id: Postgres82.php,v 1.10 2007/12/28 16:21:25 ioguix Exp $
 */
class Postgres90 extends Postgres91
{
    /**
     * @var float
     */
    public $major_version = 9.0;

    /**
     * @var class-string
     */
    public $help_classname = PostgresDoc90::class;
}
