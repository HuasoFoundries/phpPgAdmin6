<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Database\Traits;

use PHPPgAdmin\ADORecordSet;

/**
 * Common trait for full text search manipulation.
 */
trait FunctionTrait
{
    /**
     * Returns a list of all functions in the database.
     *
     * @param bool  $all  If true, will find all available functions, if false just those in search path
     * @param mixed $type If truthy, will return functions of type trigger
     *
     * @return \RecordSet|int|string
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
                coalesce(u.usename::text,p.proowner::text) AS proowner

            FROM pg_catalog.pg_proc p
                INNER JOIN pg_catalog.pg_namespace n ON n.oid = p.pronamespace
                INNER JOIN pg_catalog.pg_language pl ON pl.oid = p.prolang
                LEFT JOIN pg_catalog.pg_user u ON u.usesysid = p.proowner
            WHERE NOT p.proisagg
                AND %s
            ORDER BY p.proname, proresult
            ',
            $distinct,
            $where
        );

        return $this->selectSet($sql);
    }

    /**
     * Returns a list of all functions that can be used in triggers.
     *
     * @return int|ADORecordSet Functions that can be used in a trigger
     */
    public function getTriggerFunctions()
    {
        return $this->getFunctions(true, 'trigger');
    }

    /**
     * Returns an array containing a function's properties.
     *
     * @param array $f The array of data for the function
     *
     * @return int|string[] An array containing the properties, or -1 in case of error
     *
     * @psalm-return int|non-empty-list<string>
     */
    public function getFunctionProperties($f)
    {
        $temp = [];

        // Volatility
        if ('v' === $f['provolatile']) {
            $temp[] = 'VOLATILE';
        } elseif ('i' === $f['provolatile']) {
            $temp[] = 'IMMUTABLE';
        } elseif ('s' === $f['provolatile']) {
            $temp[] = 'STABLE';
        } else {
            return -1;
        }

        // Null handling
        $f['proisstrict'] = $this->phpBool($f['proisstrict']);

        $temp[] = $f['proisstrict'] ? 'RETURNS NULL ON NULL INPUT' : 'CALLED ON NULL INPUT';

        // Security
        $f['prosecdef'] = $this->phpBool($f['prosecdef']);

        $temp[] = $f['prosecdef'] ? 'SECURITY DEFINER' : 'SECURITY INVOKER';

        return $temp;
    }

    /**
     * Updates (replaces) a function.
     *
     * @param string $funcname   The name of the function to create
     * @param string $newname    The new name for the function
     * @param string $args       imploded array of argument types
     * @param string $returns    The return type
     * @param string $definition The definition for the new function
     * @param string $language   The language the function is written for
     * @param array  $flags      An array of optional flags
     * @param bool   $setof      True if returns a set, false otherwise
     * @param string $funcown
     * @param string $newown
     * @param string $funcschema
     * @param string $newschema
     * @param float  $cost
     * @param int    $rows
     * @param string $comment    The comment on the function
     *
     * @return bool|int 0 success
     */
    public function setFunction(
        $funcname,
        $newname,
        $args,
        $returns,
        $definition,
        $language,
        $flags,
        $setof,
        $funcown,
        $newown,
        $funcschema,
        $newschema,
        $cost,
        $rows,
        $comment
    ) {
        // Begin a transaction
        $status = $this->beginTransaction();

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        // Replace the existing function
        $status = $this->createFunction($funcname, $args, $returns, $definition, $language, $flags, $setof, $cost, $rows, $comment, true);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return $status;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);

        // Rename the function, if necessary
        $this->fieldClean($newname);
        /* $funcname is escaped in createFunction */
        if ($funcname !== $newname) {
            $sql = \sprintf(
                'ALTER FUNCTION "%s"."%s"(%s) RENAME TO "%s"',
                $f_schema,
                $funcname,
                $args,
                $newname
            );
            $status = $this->execute($sql);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -5;
            }

            $funcname = $newname;
        }

        // Alter the owner, if necessary
        if ($this->hasFunctionAlterOwner()) {
            $this->fieldClean($newown);

            if ($funcown !== $newown) {
                $sql = \sprintf(
                    'ALTER FUNCTION "%s"."%s"(%s) OWNER TO "%s"',
                    $f_schema,
                    $funcname,
                    $args,
                    $newown
                );
                $status = $this->execute($sql);

                if (0 !== $status) {
                    $this->rollbackTransaction();

                    return -6;
                }
            }
        }

        // Alter the schema, if necessary
        if ($this->hasFunctionAlterSchema()) {
            $this->fieldClean($newschema);
            /* $funcschema is escaped in createFunction */
            if ($funcschema !== $newschema) {
                $sql = \sprintf(
                    'ALTER FUNCTION "%s"."%s"(%s) SET SCHEMA "%s"',
                    $f_schema,
                    $funcname,
                    $args,
                    $newschema
                );
                $status = $this->execute($sql);

                if (0 !== $status) {
                    $this->rollbackTransaction();

                    return -7;
                }
            }
        }

        return $this->endTransaction();
    }

    /**
     * Creates a new function.
     *
     * @param string $funcname   The name of the function to create
     * @param string $args       A comma separated string of types
     * @param string $returns    The return type
     * @param string $definition The definition for the new function
     * @param string $language   The language the function is written for
     * @param array  $flags      An array of optional flags
     * @param bool   $setof      True if it returns a set, false otherwise
     * @param string $cost       cost the planner should use in the function  execution step
     * @param int    $rows       number of rows planner should estimate will be returned
     * @param string $comment    Comment for the function
     * @param bool   $replace    (optional) True if OR REPLACE, false for
     *                           normal
     *
     * @return int 0 success
     */
    public function createFunction($funcname, $args, $returns, $definition, $language, $flags, $setof, $cost, $rows, $comment, $replace = false)
    {
        // Begin a transaction
        $status = $this->beginTransaction();

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        $this->fieldClean($funcname);
        $this->clean($args);
        $this->fieldClean($language);
        $this->arrayClean($flags);
        $this->clean($cost);
        $this->clean($rows);
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);

        $sql = 'CREATE';

        if ($replace) {
            $sql .= ' OR REPLACE';
        }

        $sql .= \sprintf(
            ' FUNCTION "%s"."%s" (',
            $f_schema,
            $funcname
        );

        if ('' !== $args) {
            $sql .= $args;
        }

        // For some reason, the returns field cannot have quotes...
        $sql .= ') RETURNS ';

        if ($setof) {
            $sql .= 'SETOF ';
        }

        $sql .= \sprintf(
            '%s AS ',
            $returns
        );

        if (\is_array($definition)) {
            $this->arrayClean($definition);
            $sql .= "'" . $definition[0] . "'";

            if ($definition[1]) {
                $sql .= ",'" . $definition[1] . "'";
            }
        } else {
            $this->clean($definition);
            $sql .= "'" . $definition . "'";
        }

        $sql .= \sprintf(
            ' LANGUAGE "%s"',
            $language
        );

        // Add costs
        if (!empty($cost)) {
            $sql .= \sprintf(
                ' COST %s',
                $cost
            );
        }

        if (0 !== $rows) {
            $sql .= \sprintf(
                ' ROWS %s',
                $rows
            );
        }

        // Add flags
        foreach ($flags as $v) {
            // Skip default flags
            if ('' === $v) {
                continue;
            }

            $sql .= \PHP_EOL . $v;
        }

        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -3;
        }

        /* set the comment */
        $status = $this->setComment('FUNCTION', \sprintf(
            '"%s"(%s)',
            $funcname,
            $args
        ), null, $comment);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -4;
        }

        return $this->endTransaction();
    }

    /**
     * Drops a function.
     *
     * @param int  $function_oid The OID of the function to drop
     * @param bool $cascade      True to cascade drop, false to restrict
     *
     * @return int|string
     */
    public function dropFunction($function_oid, $cascade)
    {
        // Function comes in with $object as function OID
        $fn = $this->getFunction($function_oid);
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($fn->fields['proname']);

        $sql = \sprintf(
            'DROP FUNCTION "%s"."%s"(%s)',
            $f_schema,
            $fn->fields['proname'],
            $fn->fields['proarguments']
        );

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Returns all details for a particular function.
     *
     * @param int $function_oid
     *
     * @return \RecordSet|int|string
     *
     * @internal param string The $func name of the function to retrieve
     */
    public function getFunction($function_oid)
    {
        $this->clean($function_oid);

        $sql = \sprintf(
            '
            SELECT
                pc.oid AS prooid, proname,
                pg_catalog.pg_get_userbyid(proowner) AS proowner,
                nspname as proschema, lanname as prolanguage, procost, prorows,
                pg_catalog.format_type(prorettype, NULL) as proresult, prosrc,
                probin, proretset, proisstrict, provolatile, prosecdef,
                pg_catalog.oidvectortypes(pc.proargtypes) AS proarguments,
                proargnames AS proargnames,
                pg_catalog.obj_description(pc.oid, \'pg_proc\') AS procomment,
                proconfig,
                (select array_agg( (select typname from pg_type pt
                    where pt.oid = p.oid) ) from unnest(proallargtypes) p)
                AS proallarguments,
                proargmodes
            FROM
                pg_catalog.pg_proc pc, pg_catalog.pg_language pl,
                pg_catalog.pg_namespace pn
            WHERE
                pc.oid = \'%s\'::oid AND pc.prolang = pl.oid
                AND pc.pronamespace = pn.oid
            ',
            $function_oid
        );

        return $this->selectSet($sql);
    }

    /**
     * Returns plain definition for a particular function.
     *
     * @param int $function_oid
     *
     * @return \RecordSet|int|string
     */
    public function getFunctionDef($function_oid)
    {
        $this->clean($function_oid);
        $sql = \sprintf(
            '
            SELECT
                f.proname as relname,
                n.nspname,
                u.usename AS relowner,
                 pg_catalog.obj_description(f.oid, \'pg_proc\') as relcomment,
                 (SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=f.pronamespace) AS tablespace,
                pg_get_functiondef(f.oid),
                pl.lanname AS prolanguage
                FROM pg_catalog.pg_proc f
                JOIN pg_catalog.pg_namespace n ON (f.pronamespace = n.oid)
                JOIN pg_catalog.pg_language pl ON pl.oid = f.prolang
                LEFT JOIN pg_catalog.pg_user u ON u.usesysid=f.proowner
                WHERE f.oid=\'%s\'
        ',
            $function_oid
        );

        return $this->selectSet($sql);
    }

    abstract public function fieldClean(&$str);

    abstract public function beginTransaction();

    abstract public function rollbackTransaction();

    abstract public function endTransaction();

    abstract public function execute($sql);

    abstract public function setComment($obj_type, $obj_name, $table, $comment, $basetype = null);

    abstract public function selectSet($sql);

    abstract public function clean(&$str);

    abstract public function phpBool($parameter);

    abstract public function hasFunctionAlterOwner();

    abstract public function hasFunctionAlterSchema();

    abstract public function arrayClean(&$arr);
}
