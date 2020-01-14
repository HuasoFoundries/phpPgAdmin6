<?php

/**
 * PHPPgAdmin v6.0.0-RC4
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for tablespaces manipulation.
 */
trait TablespaceTrait
{
    /**
     * Retrieves information for all tablespaces.
     *
     * @param bool $all Include all tablespaces (necessary when moving objects back to the default space)
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTablespaces($all = false)
    {
        $conf = $this->conf;

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, pg_catalog.pg_tablespace_location(oid) as spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
                    FROM pg_catalog.pg_tablespace";

        if (!$conf['show_system'] && !$all) {
            $sql .= ' WHERE spcname NOT LIKE $$pg\_%$$';
        }

        $sql .= ' ORDER BY spcname';

        return $this->selectSet($sql);
    }

    // Misc functions

    /**
     * Retrieves a tablespace's information.
     *
     * @param string $spcname
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTablespace($spcname)
    {
        $this->clean($spcname);

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, pg_catalog.pg_tablespace_location(oid) as spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
                    FROM pg_catalog.pg_tablespace WHERE spcname='{$spcname}'";

        return $this->selectSet($sql);
    }

    /**
     * Creates a tablespace.
     *
     * @param string $spcname  The name of the tablespace to create
     * @param string $spcowner The owner of the tablespace. '' for current
     * @param string $spcloc   The directory in which to create the tablespace
     * @param string $comment
     *
     * @return int 0 success
     */
    public function createTablespace($spcname, $spcowner, $spcloc, $comment = '')
    {
        $this->fieldClean($spcname);
        $this->clean($spcloc);

        $sql = "CREATE TABLESPACE \"{$spcname}\"";

        if ($spcowner != '') {
            $this->fieldClean($spcowner);
            $sql .= " OWNER \"{$spcowner}\"";
        }

        $sql .= " LOCATION '{$spcloc}'";

        $status = $this->execute($sql);
        if ($status != 0) {
            return -1;
        }

        if ($comment != '' && $this->hasSharedComments()) {
            $status = $this->setComment('TABLESPACE', $spcname, '', $comment);
            if ($status != 0) {
                return -2;
            }
        }

        return 0;
    }

    /**
     * Alters a tablespace.
     *
     * @param string $spcname The name of the tablespace
     * @param string $name    The new name for the tablespace
     * @param string $owner   The new owner for the tablespace
     * @param string $comment
     *
     * @return bool|int 0 success
     */
    public function alterTablespace($spcname, $name, $owner, $comment = '')
    {
        $this->fieldClean($spcname);
        $this->fieldClean($name);
        $this->fieldClean($owner);

        // Begin transaction
        $status = $this->beginTransaction();
        if ($status != 0) {
            return -1;
        }

        // Owner
        $sql    = "ALTER TABLESPACE \"{$spcname}\" OWNER TO \"{$owner}\"";
        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -2;
        }

        // Rename (only if name has changed)
        if ($name != $spcname) {
            $sql    = "ALTER TABLESPACE \"{$spcname}\" RENAME TO \"{$name}\"";
            $status = $this->execute($sql);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -3;
            }

            $spcname = $name;
        }

        // Set comment if it has changed
        if (trim($comment) != '' && $this->hasSharedComments()) {
            $status = $this->setComment('TABLESPACE', $spcname, '', $comment);
            if ($status != 0) {
                return -4;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Drops a tablespace.
     *
     * @param string $spcname The name of the domain to drop
     *
     * @return int 0 if operation was successful
     */
    public function dropTablespace($spcname)
    {
        $this->fieldClean($spcname);

        $sql = "DROP TABLESPACE \"{$spcname}\"";

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

    abstract public function phpBool($parameter);

    abstract public function hasCreateTableLikeWithConstraints();

    abstract public function hasCreateTableLikeWithIndexes();

    abstract public function hasTablespaces();

    abstract public function delete($table, $conditions, $schema = '');

    abstract public function fieldArrayClean(&$arr);

    abstract public function hasCreateFieldWithConstraints();

    abstract public function hasSharedComments();

    abstract public function getAttributeNames($table, $atts);
}
