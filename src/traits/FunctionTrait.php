<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Traits;

/**
 * Common trait for full text search manipulation.
 */
trait FunctionTrait
{

    /**
     * Returns an array containing a function's properties.
     *
     * @param array $f The array of data for the function
     *
     * @return array An array containing the properties
     */
    public function getFunctionProperties($f)
    {
        $temp = [];

        // Volatility
        if ($f['provolatile'] == 'v') {
            $temp[] = 'VOLATILE';
        } elseif ($f['provolatile'] == 'i') {
            $temp[] = 'IMMUTABLE';
        } elseif ($f['provolatile'] == 's') {
            $temp[] = 'STABLE';
        } else {
            return -1;
        }

        // Null handling
        $f['proisstrict'] = $this->phpBool($f['proisstrict']);
        if ($f['proisstrict']) {
            $temp[] = 'RETURNS NULL ON NULL INPUT';
        } else {
            $temp[] = 'CALLED ON NULL INPUT';
        }

        // Security
        $f['prosecdef'] = $this->phpBool($f['prosecdef']);
        if ($f['prosecdef']) {
            $temp[] = 'SECURITY DEFINER';
        } else {
            $temp[] = 'SECURITY INVOKER';
        }

        return $temp;
    }

    /**
     * Updates (replaces) a function.
     *
     * @param string $funcname     The name of the function to create
     * @param string $newname      The new name for the function
     * @param string  $args        imploded array of argument types
     * @param string $returns      The return type
     * @param string $definition   The definition for the new function
     * @param string $language     The language the function is written for
     * @param array  $flags        An array of optional flags
     * @param bool   $setof        True if returns a set, false otherwise
     * @param string $funcown
     * @param string $newown
     * @param string $funcschema
     * @param string $newschema
     * @param float  $cost
     * @param int    $rows
     * @param string $comment      The comment on the function
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
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Replace the existing function
        $status = $this->createFunction($funcname, $args, $returns, $definition, $language, $flags, $setof, $cost, $rows, $comment, true);
        if ($status != 0) {
            $this->rollbackTransaction();

            return $status;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);

        // Rename the function, if necessary
        $this->fieldClean($newname);
        /* $funcname is escaped in createFunction */
        if ($funcname != $newname) {
            $sql    = "ALTER FUNCTION \"{$f_schema}\".\"{$funcname}\"({$args}) RENAME TO \"{$newname}\"";
            $status = $this->execute($sql);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -5;
            }

            $funcname = $newname;
        }

        // Alter the owner, if necessary
        if ($this->hasFunctionAlterOwner()) {
            $this->fieldClean($newown);
            if ($funcown != $newown) {
                $sql    = "ALTER FUNCTION \"{$f_schema}\".\"{$funcname}\"({$args}) OWNER TO \"{$newown}\"";
                $status = $this->execute($sql);
                if ($status != 0) {
                    $this->rollbackTransaction();

                    return -6;
                }
            }
        }

        // Alter the schema, if necessary
        if ($this->hasFunctionAlterSchema()) {
            $this->fieldClean($newschema);
            /* $funcschema is escaped in createFunction */
            if ($funcschema != $newschema) {
                $sql    = "ALTER FUNCTION \"{$f_schema}\".\"{$funcname}\"({$args}) SET SCHEMA \"{$newschema}\"";
                $status = $this->execute($sql);
                if ($status != 0) {
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
     * @return bool|int 0 success
     */
    public function createFunction($funcname, $args, $returns, $definition, $language, $flags, $setof, $cost, $rows, $comment, $replace = false)
    {
        // Begin a transaction
        $status = $this->beginTransaction();
        if ($status != 0) {
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

        $sql .= " FUNCTION \"{$f_schema}\".\"{$funcname}\" (";

        if ($args != '') {
            $sql .= $args;
        }

        // For some reason, the returns field cannot have quotes...
        $sql .= ') RETURNS ';
        if ($setof) {
            $sql .= 'SETOF ';
        }

        $sql .= "{$returns} AS ";

        if (is_array($definition)) {
            $this->arrayClean($definition);
            $sql .= "'" . $definition[0] . "'";
            if ($definition[1]) {
                $sql .= ",'" . $definition[1] . "'";
            }
        } else {
            $this->clean($definition);
            $sql .= "'" . $definition . "'";
        }

        $sql .= " LANGUAGE \"{$language}\"";

        // Add costs
        if (!empty($cost)) {
            $sql .= " COST {$cost}";
        }

        if ($rows != 0) {
            $sql .= " ROWS {$rows}";
        }

        // Add flags
        foreach ($flags as $v) {
            // Skip default flags
            if ($v == '') {
                continue;
            }

            $sql .= "\n{$v}";
        }

        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -3;
        }

        /* set the comment */
        $status = $this->setComment('FUNCTION', "\"{$funcname}\"({$args})", null, $comment);
        if ($status != 0) {
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
     * @return int 0 if operation was successful
     */
    public function dropFunction($function_oid, $cascade)
    {
        // Function comes in with $object as function OID
        $fn       = $this->getFunction($function_oid);
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($fn->fields['proname']);

        $sql = "DROP FUNCTION \"{$f_schema}\".\"{$fn->fields['proname']}\"({$fn->fields['proarguments']})";
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
     * @return \PHPPgAdmin\ADORecordSet Function info
     *
     * @internal param string The $func name of the function to retrieve
     */
    public function getFunction($function_oid)
    {
        $this->clean($function_oid);

        $sql = "
            SELECT
                pc.oid AS prooid, proname,
                pg_catalog.pg_get_userbyid(proowner) AS proowner,
                nspname as proschema, lanname as prolanguage, procost, prorows,
                pg_catalog.format_type(prorettype, NULL) as proresult, prosrc,
                probin, proretset, proisstrict, provolatile, prosecdef,
                pg_catalog.oidvectortypes(pc.proargtypes) AS proarguments,
                proargnames AS proargnames,
                pg_catalog.obj_description(pc.oid, 'pg_proc') AS procomment,
                proconfig,
                (select array_agg( (select typname from pg_type pt
                    where pt.oid = p.oid) ) from unnest(proallargtypes) p)
                AS proallarguments,
                proargmodes
            FROM
                pg_catalog.pg_proc pc, pg_catalog.pg_language pl,
                pg_catalog.pg_namespace pn
            WHERE
                pc.oid = '{$function_oid}'::oid AND pc.prolang = pl.oid
                AND pc.pronamespace = pn.oid
            ";

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
}
