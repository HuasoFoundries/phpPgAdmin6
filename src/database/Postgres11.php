<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * PostgreSQL 11.x support
 */
/**
 * Class to add support for Postgres11.
 */
class Postgres11 extends Postgres10
{
    public $major_version = 11;

    /**
     * Returns a list of all functions in the database.
     * In PG 11 proagg was replaced with prokind.
     *
     * @see https://www.postgresql.org/docs/11/catalog-pg-proc.html
     *
     * @param bool  $all  If true, will find all available functions, if false just those in search path
     * @param mixed $type If truthy, will return functions of type trigger
     *
     * @return int|\PHPPgAdmin\ADORecordSet All functions
     */
    public function getFunctions($all = false, $type = null)
    {
        if ($all) {
            $where = 'pg_catalog.pg_function_is_visible(p.oid)';
            $distinct = 'DISTINCT ON (p.proname)';

            if ($type) {
                $where .= " AND p.prorettype = (select oid from pg_catalog.pg_type p where p.typname = 'trigger') ";
            }
        } else {
            $c_schema = $this->_schema;
            $this->clean($c_schema);
            $where = "n.nspname = '{$c_schema}'";
            $distinct = '';
        }

        $sql = "
            SELECT
                {$distinct}
                p.oid AS prooid,
                p.proname,
                p.proretset,
                pg_catalog.format_type(p.prorettype, NULL) AS proresult,
                pg_catalog.oidvectortypes(p.proargtypes) AS proarguments,
                pl.lanname AS prolanguage,
                pg_catalog.obj_description(p.oid, 'pg_proc') AS procomment,
                p.proname || ' (' || pg_catalog.oidvectortypes(p.proargtypes) || ')' AS proproto,
                CASE WHEN p.proretset THEN 'setof ' ELSE '' END || pg_catalog.format_type(p.prorettype, NULL) AS proreturns,
                coalesce(u.usename::text,p.proowner::text) AS proowner

            FROM pg_catalog.pg_proc p
                INNER JOIN pg_catalog.pg_namespace n ON n.oid = p.pronamespace
                INNER JOIN pg_catalog.pg_language pl ON pl.oid = p.prolang
                LEFT JOIN pg_catalog.pg_user u ON u.usesysid = p.proowner
            WHERE p.prokind !='a'
                AND {$where}
            ORDER BY p.proname, proresult
            ";

        return $this->selectSet($sql);
    }
}
