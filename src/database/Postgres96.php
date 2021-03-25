<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database;

use PHPPgAdmin\Help\PostgresDoc96;

/**
 * @file
 * PostgreSQL 9.6 support
 */
class Postgres96 extends Postgres
{
    public $typIndexes = ['BTREE', 'BRIN', 'RTREE', 'GIST', 'GIN', 'HASH', 'SP-GIST'];

    /**
     * @var float
     */
    public $major_version = 9.6;

    /**
     * @var class-string
     */
    public $help_classname = PostgresDoc96::class;

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
            $sql = "SELECT datid, datname, pid, usename, application_name, client_addr, state, wait_event_type, wait_event, state_change as query_start,
					CASE
                        WHEN state='active' THEN query
                        ELSE state
                    END AS query
					FROM pg_catalog.pg_stat_activity
					ORDER BY datname, usename, pid";
        } else {
            $this->clean($database);
            $sql = \sprintf(
                'SELECT datid, datname, pid, usename, application_name, client_addr, state, wait_event_type, wait_event, state_change as query_start,
					CASE
                        WHEN state=\'active\' THEN query
                        ELSE state
                    END AS query
					FROM pg_catalog.pg_stat_activity
					WHERE datname=\'%s\'
					ORDER BY usename, pid',
                $database
            );
        }

        return $this->selectSet($sql);
    }

    /**
     * @return true
     */
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
     * @param bool   $createuser boolean Whether or not the user can create other users
     * @param string $expiry     string Format 'YYYY-MM-DD HH:MM:SS'.  '' means never expire
     * @param array  $groups     The groups to create the user in
     *
     * @return int|string 0 if operation was successful
     *
     * @internal param $group (array) The groups to create the user in
     */
    public function createUser($username, $password, $createdb, $createuser, $expiry, $groups)
    {
        $enc = $this->_encryptPassword($username, $password);
        $this->fieldClean($username);
        $this->clean($enc);
        $this->clean($expiry);
        $this->fieldArrayClean($groups);

        $sql = \sprintf(
            'CREATE USER "%s"',
            $username
        );

        if ('' !== $password) {
            $sql .= \sprintf(
                ' WITH ENCRYPTED PASSWORD \'%s\'',
                $enc
            );
        }

        $sql .= $createdb ? ' CREATEDB' : ' NOCREATEDB';
        $sql .= $createuser ? ' CREATEROLE' : ' NOCREATEROLE';

        if (\is_array($groups) && 0 < \count($groups)) {
            $sql .= ' IN GROUP "' . \implode('", "', $groups) . '"';
        }

        if ('' !== $expiry) {
            $sql .= \sprintf(
                ' VALID UNTIL \'%s\'',
                $expiry
            );
        } else {
            $sql .= " VALID UNTIL 'infinity'";
        }

        return $this->execute($sql);
    }
}
