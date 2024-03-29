<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for aggregates manipulation.
 */
trait AggregateTrait
{
    /**
     * Creates a new aggregate in the database.
     *
     * @param string $name     The name of the aggregate
     * @param string $basetype The input data type of the aggregate
     * @param string $sfunc    The name of the state transition function for the aggregate
     * @param string $stype    The data type for the aggregate's state value
     * @param string $ffunc    The name of the final function for the aggregate
     * @param string $initcond The initial setting for the state value
     * @param string $sortop   The sort operator for the aggregate
     * @param string $comment  Aggregate comment
     *
     * @return int
     *
     * @psalm-return -1|0|1
     */
    public function createAggregate($name, $basetype, $sfunc, $stype, $ffunc, $initcond, $sortop, $comment)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);
        $this->fieldClean($basetype);
        $this->fieldClean($sfunc);
        $this->fieldClean($stype);
        $this->fieldClean($ffunc);
        $this->fieldClean($initcond);
        $this->fieldClean($sortop);

        $this->beginTransaction();

        $sql = \sprintf(
            'CREATE AGGREGATE "%s"."%s" (BASETYPE = "%s", SFUNC = "%s", STYPE = "%s"',
            $f_schema,
            $name,
            $basetype,
            $sfunc,
            $stype
        );

        if ('' !== \trim($ffunc)) {
            $sql .= \sprintf(
                ', FINALFUNC = "%s"',
                $ffunc
            );
        }

        if ('' !== \trim($initcond)) {
            $sql .= \sprintf(
                ', INITCOND = "%s"',
                $initcond
            );
        }

        if ('' !== \trim($sortop)) {
            $sql .= \sprintf(
                ', SORTOP = "%s"',
                $sortop
            );
        }

        $sql .= ')';

        $status = $this->execute($sql);

        if ($status) {
            $this->rollbackTransaction();

            return -1;
        }

        if ('' !== \trim($comment)) {
            $status = $this->setComment('AGGREGATE', $name, '', $comment, $basetype);

            if ($status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Removes an aggregate function from the database.
     *
     * @param string $aggrname The name of the aggregate
     * @param string $aggrtype The input data type of the aggregate
     * @param bool   $cascade  True to cascade drop, false to restrict
     *
     * @return int|string
     */
    public function dropAggregate($aggrname, $aggrtype, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($aggrname);
        $this->fieldClean($aggrtype);

        $sql = \sprintf(
            'DROP AGGREGATE "%s"."%s" ("%s")',
            $f_schema,
            $aggrname,
            $aggrtype
        );

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Gets all information for an aggregate.
     *
     * @param string $name     The name of the aggregate
     * @param string $basetype The input data type of the aggregate
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getAggregate($name, $basetype)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->fieldClean($name);
        $this->fieldClean($basetype);

        $sql = \sprintf(
            '
            SELECT p.proname, CASE p.proargtypes[0]
                WHEN \'pg_catalog."any"\'::pg_catalog.regtype THEN NULL
                ELSE pg_catalog.format_type(p.proargtypes[0], NULL) END AS proargtypes,
                a.aggtransfn, format_type(a.aggtranstype, NULL) AS aggstype, a.aggfinalfn,
                a.agginitval, a.aggsortop, u.usename, pg_catalog.obj_description(p.oid, \'pg_proc\') AS aggrcomment
            FROM pg_catalog.pg_proc p, pg_catalog.pg_namespace n, pg_catalog.pg_user u, pg_catalog.pg_aggregate a
            WHERE n.oid = p.pronamespace AND p.proowner=u.usesysid AND p.oid=a.aggfnoid
                AND p.proisagg AND n.nspname=\'%s\'
                AND p.proname=\'',
            $c_schema
        ) . $name . "'
                AND CASE p.proargtypes[0]
                    WHEN 'pg_catalog.\"any\"'::pg_catalog.regtype THEN ''
                    ELSE pg_catalog.format_type(p.proargtypes[0], NULL)
                END ='" . $basetype . "'";

        return $this->selectSet($sql);
    }

    /**
     * Gets all aggregates.
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getAggregates()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = \sprintf(
            'SELECT p.proname, CASE p.proargtypes[0] WHEN \'pg_catalog."any"\'::pg_catalog.regtype THEN NULL ELSE
               pg_catalog.format_type(p.proargtypes[0], NULL) END AS proargtypes, a.aggtransfn, u.usename,
               pg_catalog.obj_description(p.oid, \'pg_proc\') AS aggrcomment
               FROM pg_catalog.pg_proc p, pg_catalog.pg_namespace n, pg_catalog.pg_user u, pg_catalog.pg_aggregate a
               WHERE n.oid = p.pronamespace AND p.proowner=u.usesysid AND p.oid=a.aggfnoid
               AND p.proisagg AND n.nspname=\'%s\' ORDER BY 1, 2',
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Alters an aggregate.
     *
     * @param string $aggrname       The actual name of the aggregate
     * @param string $aggrtype       The actual input data type of the aggregate
     * @param string $aggrowner      The actual owner of the aggregate
     * @param string $aggrschema     The actual schema the aggregate belongs to
     * @param string $aggrcomment    The actual comment for the aggregate
     * @param string $newaggrname    The new name of the aggregate
     * @param string $newaggrowner   The new owner of the aggregate
     * @param string $newaggrschema  The new schema where the aggregate will belong to
     * @param string $newaggrcomment The new comment for the aggregate
     *
     * @return int
     *
     * @psalm-return -4|-3|-2|-1|0|1
     */
    public function alterAggregate(
        $aggrname,
        $aggrtype,
        $aggrowner,
        $aggrschema,
        $aggrcomment,
        $newaggrname,
        $newaggrowner,
        $newaggrschema,
        $newaggrcomment
    ) {
        // Clean fields
        $this->fieldClean($aggrname);
        $this->fieldClean($aggrtype);
        $this->fieldClean($aggrowner);
        $this->fieldClean($aggrschema);
        $this->fieldClean($newaggrname);
        $this->fieldClean($newaggrowner);
        $this->fieldClean($newaggrschema);

        $this->beginTransaction();

        // Change the owner, if it has changed
        if ($aggrowner !== $newaggrowner) {
            $status = $this->changeAggregateOwner($aggrname, $aggrtype, $newaggrowner);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        // Set the comment, if it has changed
        if ($aggrcomment !== $newaggrcomment) {
            $status = $this->setComment('AGGREGATE', $aggrname, '', $newaggrcomment, $aggrtype);

            if ($status) {
                $this->rollbackTransaction();

                return -2;
            }
        }

        // Change the schema, if it has changed
        if ($aggrschema !== $newaggrschema) {
            $status = $this->changeAggregateSchema($aggrname, $aggrtype, $newaggrschema);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -3;
            }
        }

        // Rename the aggregate, if it has changed
        if ($aggrname !== $newaggrname) {
            $status = $this->renameAggregate($newaggrschema, $aggrname, $aggrtype, $newaggrname);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -4;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Changes the owner of an aggregate function.
     *
     * @param string $aggrname     The name of the aggregate
     * @param string $aggrtype     The input data type of the aggregate
     * @param string $newaggrowner The new owner of the aggregate
     *
     * @return int|string
     */
    public function changeAggregateOwner($aggrname, $aggrtype, $newaggrowner)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($aggrname);
        $this->fieldClean($newaggrowner);
        $sql = \sprintf(
            'ALTER AGGREGATE "%s"."%s" ("%s") OWNER TO "%s"',
            $f_schema,
            $aggrname,
            $aggrtype,
            $newaggrowner
        );

        return $this->execute($sql);
    }

    /**
     * Changes the schema of an aggregate function.
     *
     * @param string $aggrname      The name of the aggregate
     * @param string $aggrtype      The input data type of the aggregate
     * @param string $newaggrschema The new schema for the aggregate
     *
     * @return int|string
     */
    public function changeAggregateSchema($aggrname, $aggrtype, $newaggrschema)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($aggrname);
        $this->fieldClean($newaggrschema);
        $sql = \sprintf(
            'ALTER AGGREGATE "%s"."%s" ("%s") SET SCHEMA  "%s"',
            $f_schema,
            $aggrname,
            $aggrtype,
            $newaggrschema
        );

        return $this->execute($sql);
    }

    /**
     * Renames an aggregate function.
     *
     * @param string $aggrschema  The schema of the aggregate
     * @param string $aggrname    The actual name of the aggregate
     * @param string $aggrtype    The actual input data type of the aggregate
     * @param string $newaggrname The new name of the aggregate
     *
     * @return int|string
     */
    public function renameAggregate($aggrschema, $aggrname, $aggrtype, $newaggrname)
    {
        /* this function is called from alterAggregate where params are cleaned */
        $sql = \sprintf(
            'ALTER AGGREGATE "%s"',
            $aggrschema
        ) . '.' . \sprintf(
            '"%s" ("%s") RENAME TO "%s"',
            $aggrname,
            $aggrtype,
            $newaggrname
        );

        return $this->execute($sql);
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
