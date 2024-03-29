<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database;

use PHPPgAdmin\Help\PostgresDoc93;

/**
 * @file
 * PostgreSQL 9.3 support
 */
class Postgres93 extends Postgres94
{
    /**
     * @var float
     */
    public $major_version = 9.3;

    /**
     * @var class-string
     */
    public $help_classname = PostgresDoc93::class;

    /**
     * Returns a list of all functions in the database.
     *
     * @param bool  $all  If true, will find all available functions, if false just those in search path
     * @param mixed $type If not null, will find all trigger functions
     *
     * @return \ADORecordSet|bool|int|string All functions
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
            $where = \sprintf(
                'n.nspname = \'%s\'',
                $c_schema
            );
            $distinct = '';
        }

        $sql = \sprintf(
            '
			SELECT
				%s
				p.oid AS prooid,
				p.proname,
				p.proretset,
				pg_catalog.format_type(p.prorettype, NULL) AS proresult,
				pg_catalog.oidvectortypes(p.proargtypes) AS proarguments,
				pl.lanname AS prolanguage,
				pg_catalog.obj_description(p.oid, \'pg_proc\') AS procomment,
				p.proname || \' (\' || pg_catalog.oidvectortypes(p.proargtypes) || \')\' AS proproto,
				CASE WHEN p.proretset THEN \'setof \' ELSE \'\' END || pg_catalog.format_type(p.prorettype, NULL) AS proreturns,
				coalesce(u.rolname::text,p.proowner::text) AS proowner

			FROM pg_catalog.pg_proc p
				INNER JOIN pg_catalog.pg_namespace n ON n.oid = p.pronamespace
				INNER JOIN pg_catalog.pg_language pl ON pl.oid = p.prolang
				LEFT JOIN pg_catalog.pg_roles u ON u.oid = p.proowner
			WHERE NOT p.proisagg
				AND %s
			ORDER BY p.proname, proresult
			',
            $distinct,
            $where
        );

        return $this->selectSet($sql);
    }
}
