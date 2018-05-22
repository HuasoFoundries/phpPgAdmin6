<?php

/**
 * PHPPgAdmin v6.0.0-beta.45
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 9.6 support
 */
class Postgres96 extends Postgres
{
    public $typIndexes    = ['BTREE', 'BRIN', 'RTREE', 'GIST', 'GIN', 'HASH', 'SP-GIST'];
    public $major_version = 9.6;

    // Administration functions

    /**
     * Returns all available process information.
     *
     * @param string|null $database (optional) Find only connections to specified database
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

    /**
     * Creates a new user. As of PG 9.6, CREATEUSER privilege has been deprecated.
     *
     * @see {@link https://www.postgresql.org/docs/9.6/static/sql-createrole.html}
     *
     * @param string $username   The username of the user to create
     * @param string $password   A password for the user
     * @param bool   $createdb   boolean Whether or not the user can create databases
     * @param bool   $createrole boolean Whether or not the user can create other users
     * @param string $expiry     string Format 'YYYY-MM-DD HH:MM:SS'.  '' means never expire
     * @param array  $groups     The groups to create the user in
     *
     * @return int 0 if operation was successful
     *
     * @internal param $group (array) The groups to create the user in
     */
    public function createUser($username, $password, $createdb, $createrole, $expiry, $groups)
    {
        $enc = $this->_encryptPassword($username, $password);
        $this->fieldClean($username);
        $this->clean($enc);
        $this->clean($expiry);
        $this->fieldArrayClean($groups);

        $sql = "CREATE USER \"{$username}\"";
        if ($password != '') {
            $sql .= " WITH ENCRYPTED PASSWORD '{$enc}'";
        }

        $sql .= $createdb ? ' CREATEDB' : ' NOCREATEDB';
        $sql .= $createrole ? ' CREATEROLE' : ' NOCREATEROLE';
        if (is_array($groups) && sizeof($groups) > 0) {
            $sql .= ' IN GROUP "' . join('", "', $groups) . '"';
        }

        if ($expiry != '') {
            $sql .= " VALID UNTIL '{$expiry}'";
        } else {
            $sql .= " VALID UNTIL 'infinity'";
        }

        return $this->execute($sql);
    }
}
