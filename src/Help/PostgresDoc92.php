<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Help;

/**
 * Help links for PostgreSQL 9.2 documentation.
 *
 * Release: PostgresDoc84.php,v 1.3 2008/11/18 21:35:48 ioguix Exp $
 *
 * @SuppressWarnings(PHPMD)
 */
class PostgresDoc92 extends PostgresDoc91
{
    public function __construct($conf, $major_version)
    {
        parent::__construct($conf, $major_version);

        $this->help_topics['pg.rule.view'] = 'rules-views.html';
    }
}
