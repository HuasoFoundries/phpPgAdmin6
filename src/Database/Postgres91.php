<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database;

use PHPPgAdmin\Help\PostgresDoc91;

/**
 * @file
 * PostgreSQL 9.1 support
 */
/**
 * Id: Postgres82.php,v 1.10 2007/12/28 16:21:25 ioguix Exp $.
 */
class Postgres91 extends Postgres92
{
    /**
     * @var float
     */
    public $major_version = 9.1;

    /**
     * @var class-string
     */
    public $help_classname = PostgresDoc91::class;

    // Administration functions

    /**
     * Returns all available process information.
     *
     * @param null|string $database (optional) Find only connections to specified database
     *
     * @return \ADORecordSet|bool|int|string A recordset
     */
    public function getProcesses($database = null)
    {
        if (null === $database) {
            $sql = 'SELECT datname, usename, procpid AS pid, waiting, current_query AS query, query_start
				FROM pg_catalog.pg_stat_activity
				ORDER BY datname, usename, procpid';
        } else {
            $this->clean($database);
            $sql = \sprintf(
                'SELECT datname, usename, procpid AS pid, waiting, current_query AS query, query_start
				FROM pg_catalog.pg_stat_activity
				WHERE datname=\'%s\'
				ORDER BY usename, procpid',
                $database
            );
        }

        return $this->selectSet($sql);
    }

    // Tablespace functions

    /**
     * Retrieves information for all tablespaces.
     *
     * @param bool $all Include all tablespaces (necessary when moving objects back to the default space)
     *
     * @return \ADORecordSet|bool|int|string A recordset
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
     * @param string $spcname
     *
     * @return \ADORecordSet|bool|int|string A recordset
     */
    public function getTablespace($spcname)
    {
        $this->clean($spcname);

        $sql = \sprintf(
            'SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid=\'pg_tablespace\'::regclass) AS spccomment
					FROM pg_catalog.pg_tablespace WHERE spcname=\'%s\'',
            $spcname
        );

        return $this->selectSet($sql);
    }

    // Capabilities

    /**
     * @return false
     */
    public function hasUserSignals()
    {
        return false;
    }
}
