<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 10.x support
 *
 * @todo add support for identify columns
 *
 * @see https://blog.2ndquadrant.com/postgresql-10-identity-columns/
 */

/**
 * Class to add support for Postgres10.
 */
class Postgres10 extends Postgres96
{
    public $major_version = 10;

    /**
     * Return all tables in current database (and schema).
     *
     * @return int|\ADORecordSet All tables, sorted alphabetically
     */
    public function getTables()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);

        $sql = "
                SELECT c.relname,
                    pg_catalog.pg_get_userbyid(c.relowner) AS relowner,
                    pg_catalog.obj_description(c.oid, 'pg_class') AS relcomment,
                    reltuples::bigint as reltuples,
                    pt.spcname as tablespace, ";

        /*
         * Either display_sizes is true for tables and schemas,
         * or we must check if said config is an associative array
         */
        if ($this->conf['display_sizes']['tables']) {
            $sql .= ' pg_size_pretty(pg_total_relation_size(c.oid)) as table_size ';
        } else {
            $sql .= "   'N/A' as table_size ";
        }

        $sql .= " FROM pg_catalog.pg_class c
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                LEFT JOIN  pg_catalog.pg_tablespace pt ON  pt.oid=c.reltablespace
                WHERE c.relkind IN ('r','p')
                AND nspname='{$c_schema}'
                ORDER BY c.relname";

        return $this->selectSet($sql);
    }
}
