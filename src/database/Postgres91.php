<?php

/**
 * PHPPgAdmin v6.0.0-beta.40
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 9.1 support
 */

/*
 * Id: Postgres82.php,v 1.10 2007/12/28 16:21:25 ioguix Exp $
 * @package PHPPgAdmin
 */
class Postgres91 extends Postgres92
{
    public $major_version = 9.1;

    // Administration functions

    /**
     * Returns all available process information.
     *
     * @param $database (optional) Find only connections to specified database
     *
     * @return \ADORecordSet A recordset
     */
    public function getProcesses($database = null)
    {
        if ($database === null) {
            $sql = 'SELECT datname, usename, procpid AS pid, waiting, current_query AS query, query_start
				FROM pg_catalog.pg_stat_activity
				ORDER BY datname, usename, procpid';
        } else {
            $this->clean($database);
            $sql = "SELECT datname, usename, procpid AS pid, waiting, current_query AS query, query_start
				FROM pg_catalog.pg_stat_activity
				WHERE datname='{$database}'
				ORDER BY usename, procpid";
        }

        return $this->selectSet($sql);
    }

    // Tablespace functions

    /**
     * Retrieves information for all tablespaces.
     *
     * @param bool $all Include all tablespaces (necessary when moving objects back to the default space)
     *
     * @return \ADORecordSet A recordset
     */
    public function getTablespaces($all = false)
    {
        $conf = $this->conf;

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
					FROM pg_catalog.pg_tablespace";

        if (!$conf['show_system'] && !$all) {
            $sql .= ' WHERE spcname NOT LIKE $$pg\_%$$';
        }

        $sql .= ' ORDER BY spcname';

        return $this->selectSet($sql);
    }

    /**
     * Retrieves a tablespace's information.
     *
     * @param $spcname
     *
     * @return \ADORecordSet A recordset
     */
    public function getTablespace($spcname)
    {
        $this->clean($spcname);

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
					FROM pg_catalog.pg_tablespace WHERE spcname='{$spcname}'";

        return $this->selectSet($sql);
    }

    // Capabilities
    public function hasUserSignals()
    {
        return false;
    }
}
