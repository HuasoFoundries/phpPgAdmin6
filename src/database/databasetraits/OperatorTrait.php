<?php

/**
 * PHPPgAdmin v6.0.0-RC8
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for operators manipulation.
 */
trait OperatorTrait
{
    /**
     * Returns a list of all operators in the database.
     *
     * @return \PHPPgAdmin\ADORecordSet All operators
     */
    public function getOperators()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        // We stick with the subselects here, as you cannot ORDER BY a regtype
        $sql = "
            SELECT
                po.oid, po.oprname,
                (SELECT pg_catalog.format_type(oid, NULL) FROM pg_catalog.pg_type pt WHERE pt.oid=po.oprleft) AS oprleftname,
                (SELECT pg_catalog.format_type(oid, NULL) FROM pg_catalog.pg_type pt WHERE pt.oid=po.oprright) AS oprrightname,
                po.oprresult::pg_catalog.regtype AS resultname,
                pg_catalog.obj_description(po.oid, 'pg_operator') AS oprcomment
            FROM
                pg_catalog.pg_operator po
            WHERE
                po.oprnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='{$c_schema}')
            ORDER BY
                po.oprname, oprleftname, oprrightname
        ";

        return $this->selectSet($sql);
    }

    /**
     * Drops an operator.
     *
     * @param mixed $operator_oid The OID of the operator to drop
     * @param bool  $cascade      True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropOperator($operator_oid, $cascade)
    {
        // Function comes in with $object as operator OID
        $opr      = $this->getOperator($operator_oid);
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($opr->fields['oprname']);

        $sql = "DROP OPERATOR \"{$f_schema}\".{$opr->fields['oprname']} (";
        // Quoting or formatting here???
        if ($opr->fields['oprleftname'] !== null) {
            $sql .= $opr->fields['oprleftname'].', ';
        } else {
            $sql .= 'NONE, ';
        }

        if ($opr->fields['oprrightname'] !== null) {
            $sql .= $opr->fields['oprrightname'].')';
        } else {
            $sql .= 'NONE)';
        }

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Returns all details for a particular operator.
     *
     * @param mixed $operator_oid The oid of the operator
     *
     * @return \PHPPgAdmin\ADORecordSet Function info
     */
    public function getOperator($operator_oid)
    {
        $this->clean($operator_oid);

        $sql = "
            SELECT
                po.oid, po.oprname,
                oprleft::pg_catalog.regtype AS oprleftname,
                oprright::pg_catalog.regtype AS oprrightname,
                oprresult::pg_catalog.regtype AS resultname,
                po.oprcanhash,
                oprcanmerge,
                oprcom::pg_catalog.regoperator AS oprcom,
                oprnegate::pg_catalog.regoperator AS oprnegate,
                po.oprcode::pg_catalog.regproc AS oprcode,
                po.oprrest::pg_catalog.regproc AS oprrest,
                po.oprjoin::pg_catalog.regproc AS oprjoin
            FROM
                pg_catalog.pg_operator po
            WHERE
                po.oid='{$operator_oid}'
        ";

        return $this->selectSet($sql);
    }

    /**
     *  Gets all opclasses.
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getOpClasses()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = "
            SELECT
                pa.amname, po.opcname,
                po.opcintype::pg_catalog.regtype AS opcintype,
                po.opcdefault,
                pg_catalog.obj_description(po.oid, 'pg_opclass') AS opccomment
            FROM
                pg_catalog.pg_opclass po, pg_catalog.pg_am pa, pg_catalog.pg_namespace pn
            WHERE
                po.opcmethod=pa.oid
                AND po.opcnamespace=pn.oid
                AND pn.nspname='{$c_schema}'
            ORDER BY 1,2
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

    abstract public function phpBool($parameter);

    abstract public function hasCreateTableLikeWithConstraints();

    abstract public function hasCreateTableLikeWithIndexes();

    abstract public function hasTablespaces();

    abstract public function delete($table, $conditions, $schema = '');

    abstract public function fieldArrayClean(&$arr);

    abstract public function hasCreateFieldWithConstraints();

    abstract public function getAttributeNames($table, $atts);
}
