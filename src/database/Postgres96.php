<?php

namespace PHPPgAdmin\Database;

/**
 * PostgreSQL 9.5 support
 *
 */

class Postgres96 extends Postgres95
{

    public $major_version = 9.6;

    // Administration functions

    /**
     * Returns all available process information.
     *
     * @param $database (optional) Find only connections to specified database
     * @return A recordset
     */
    public function getProcesses($database = null)
    {
        if ($database === null) {
            $sql = "SELECT datid, datname, pid, usename, application_name, client_addr, state, wait_event_type, wait_event, state_change as query_start,
					CASE when state='idle in transaction' then ' in transaction' else query end as query
					FROM pg_catalog.pg_stat_activity
					ORDER BY datname, usename, pid";
        } else {
            $this->clean($database);
            $sql = "SELECT datid, datname, pid, usename, application_name, client_addr, state, wait_event_type, wait_event, state_change as query_start,
					CASE when state='idle in transaction' then ' in transaction' else query end as query
					FROM pg_catalog.pg_stat_activity
					WHERE datname='{$database}'
					ORDER BY usename, pid";
        }

        return $this->selectSet($sql);
    }
}
