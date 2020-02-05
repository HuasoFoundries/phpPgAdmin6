<?php

/**
 * PHPPgAdmin v6.0.0-RC5
 */

namespace PHPPgAdmin\Help;

/**
 * Help links for PostgreSQL 9.3 documentation.
 *
 * Release: PostgresDoc84.php,v 1.3 2008/11/18 21:35:48 ioguix Exp $
 */
class PostgresDoc93 extends PostgresDoc92
{
    public function __construct($conf, $major_version)
    {
        parent::__construct($conf, $major_version);

        $this->help_page['pg.matview'] = 'sql-creatematerializedview.html';

        $this->help_page['pg.matview.create']  = 'sql-creatematerializedview.html';
        $this->help_page['pg.matview.drop']    = 'sql-dropmaterializedview.html';
        $this->help_page['pg.matview.alter']   = 'sql-altermaterializedview.html';
        $this->help_page['pg.matview.refresh'] = 'sql-refreshmaterializedview.html';

        $this->help_page['pg.rule.matview'] = 'rules-materializedviews.html';
    }
}
