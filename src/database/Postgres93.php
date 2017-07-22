<?php

    namespace PHPPgAdmin\Database;

    /**
     * PostgreSQL 9.3 support
     *
     */

    class Postgres93 extends Postgres
    {

        public $major_version = 9.3;

        // Help functions

        public function getHelpPages()
        {
            include_once BASE_PATH . '/src/help/PostgresDoc93.php';

            return $this->help_page;
        }

    }
