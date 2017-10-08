<?php

    namespace PHPPgAdmin\Help;

/**
     * Help links for PostgreSQL 8.1 documentation
     *
     * $Id: PostgresDoc81.php,v 1.3 2006/12/28 04:26:55 xzilla Exp $
     */
    class PostgresDoc81 extends PostgresDoc80
    {
        public function __construct($conf, $major_version)
        {
            parent::__construct($conf, $major_version);

            $this->help_page['pg.role']        = 'user-manag.html';
            $this->help_page['pg.role.create'] = ['sql-createrole.html', 'user-manag.html#DATABASE-ROLES'];
            $this->help_page['pg.role.alter']  = ['sql-alterrole.html', 'role-attributes.html'];
            $this->help_page['pg.role.drop']   = ['sql-droprole.html', 'user-manag.html#DATABASE-ROLES'];
        }
    }
