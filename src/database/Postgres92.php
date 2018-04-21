<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 9.2 support
 */
class Postgres92 extends Postgres93
{
    public $major_version = 9.2;

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
            $sql = "SELECT datname, usename, pid, waiting, state_change as query_start,
                  case when state='idle in transaction' then '<IDLE> in transaction' when state = 'idle' then '<IDLE>' else query end as query
				FROM pg_catalog.pg_stat_activity
				ORDER BY datname, usename, pid";
        } else {
            $this->clean($database);
            $sql = "SELECT datname, usename, pid, waiting, state_change as query_start,
                  case when state='idle in transaction' then '<IDLE> in transaction' when state = 'idle' then '<IDLE>' else query end as query
				FROM pg_catalog.pg_stat_activity
				WHERE datname='{$database}'
				ORDER BY usename, pid";
        }

        return $this->selectSet($sql);
    }

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

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, pg_catalog.pg_tablespace_location(oid) as spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
					FROM pg_catalog.pg_tablespace";

        if (!$conf['show_system'] && !$all) {
            $sql .= ' WHERE spcname NOT LIKE $$pg\_%$$';
        }

        $sql .= ' ORDER BY spcname';

        return $this->selectSet($sql);
    }

    // Misc functions

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

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, pg_catalog.pg_tablespace_location(oid) as spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
					FROM pg_catalog.pg_tablespace WHERE spcname='{$spcname}'";

        return $this->selectSet($sql);
    }
}
