<?php

    namespace PHPPgAdmin\Help;

/**
     * Help links for PostgreSQL 8.3 documentation
     *
     * $Id: PostgresDoc83.php,v 1.3 2008/03/17 21:35:48 ioguix Exp $
     */
    class PostgresDoc83 extends PostgresDoc82
    {
        public function __construct($conf, $major_version)
        {
            parent::__construct($conf, $major_version);

            $this->help_page['pg.fts'] = 'textsearch.html';

            $this->help_page['pg.ftscfg']         = 'textsearch-intro.html#TEXTSEARCH-INTRO-CONFIGURATIONS';
            $this->help_page['pg.ftscfg.example'] = 'textsearch-configuration.html';
            $this->help_page['pg.ftscfg.drop']    = 'sql-droptsconfig.html';
            $this->help_page['pg.ftscfg.create']  = 'sql-createtsconfig.html';
            $this->help_page['pg.ftscfg.alter']   = 'sql-altertsconfig.html';

            $this->help_page['pg.ftsdict']        = 'textsearch-dictionaries.html';
            $this->help_page['pg.ftsdict.drop']   = 'sql-droptsdictionary.html';
            $this->help_page['pg.ftsdict.create'] = ['sql-createtsdictionary.html', 'sql-createtstemplate.html'];
            $this->help_page['pg.ftsdict.alter']  = 'sql-altertsdictionary.html';

            $this->help_page['pg.ftsparser'] = 'textsearch-parsers.html';
        }
    }
