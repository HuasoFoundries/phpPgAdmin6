<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 9.6 support
 */
class Postgres96 extends Postgres
{
    public $major_version = 9.6;

    // Administration functions

    /**
     * Returns all available process information.
     *
     * @param $database (optional) Find only connections to specified database
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getProcesses($database = null)
    {
        if ($database === null) {
            $sql = "SELECT datid, datname, pid, usename, application_name, client_addr, state, wait_event_type, wait_event, state_change as query_start,
					CASE
                        WHEN state='active' THEN query
                        ELSE state
                    END AS query
					FROM pg_catalog.pg_stat_activity
					ORDER BY datname, usename, pid";
        } else {
            $this->clean($database);
            $sql = "SELECT datid, datname, pid, usename, application_name, client_addr, state, wait_event_type, wait_event, state_change as query_start,
					CASE
                        WHEN state='active' THEN query
                        ELSE state
                    END AS query
					FROM pg_catalog.pg_stat_activity
					WHERE datname='{$database}'
					ORDER BY usename, pid";
        }

        return $this->selectSet($sql);
    }

    public function hasUserSignals()
    {
        return true;
    }
}
