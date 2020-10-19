<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Help;

/**
 * Help links for PostgreSQL 9.3 documentation.
 *
 * Release: PostgresDoc84.php,v 1.3 2008/11/18 21:35:48 ioguix Exp $
 *
 * @SuppressWarnings(PHPMD)
 */
class PostgresDoc93 extends PostgresDoc92
{
    public function __construct($conf, $major_version)
    {
        parent::__construct($conf, $major_version);

        $this->help_topics['pg.matview'] = 'sql-creatematerializedview.html';

        $this->help_topics['pg.matview.create'] = 'sql-creatematerializedview.html';
        $this->help_topics['pg.matview.drop'] = 'sql-dropmaterializedview.html';
        $this->help_topics['pg.matview.alter'] = 'sql-altermaterializedview.html';
        $this->help_topics['pg.matview.refresh'] = 'sql-refreshmaterializedview.html';

        $this->help_topics['pg.rule.matview'] = 'rules-materializedviews.html';
    }
}
