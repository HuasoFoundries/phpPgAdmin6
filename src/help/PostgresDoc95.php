<?php

namespace PHPPgAdmin\Help;

/**
 * Help links for PostgreSQL 9.5 documentation
 *
 */
class PostgresDoc95 extends PostgresDoc94
{

    public function __construct($conf, $major_version)
    {
        parent::__construct($conf, $major_version);

        $this->help_page['pg.matview'] = 'sql-creatematerializedview.html';

    }

}
