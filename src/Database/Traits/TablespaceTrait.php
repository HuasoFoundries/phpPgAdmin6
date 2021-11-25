<?php

/**
 * PHPPgAdmin6
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
     * @return \ADORecordSet|bool|int|string
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
     * @return \ADORecordSet|bool|int|string
     */
    public function getTablespace($spcname)
    {
        $this->clean($spcname);

        $sql = \sprintf('SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, pg_catalog.pg_tablespace_location(oid) as spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid=\'pg_tablespace\'::regclass) AS spccomment
                    FROM pg_catalog.pg_tablespace WHERE spcname=\'%s\'', $spcname);

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
     * @return int
     *
     * @psalm-return -2|-1|0
     */
    public function createTablespace($spcname, $spcowner, $spcloc, $comment = '')
    {
        $this->fieldClean($spcname);
        $this->clean($spcloc);

        $sql = \sprintf('CREATE TABLESPACE "%s"', $spcname);

        if ('' !== $spcowner) {
            $this->fieldClean($spcowner);
            $sql .= \sprintf(' OWNER "%s"', $spcowner);
        }

        $sql .= \sprintf(' LOCATION \'%s\'', $spcloc);

        $status = $this->execute($sql);

        if (0 !== $status) {
            return -1;
        }

        if ('' !== $comment && $this->hasSharedComments()) {
            $status = $this->setComment('TABLESPACE', $spcname, '', $comment);

            if (0 !== $status) {
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
     * @return int
     *
     * @psalm-return -4|-3|-2|-1|0|1
     */
    public function alterTablespace($spcname, $name, $owner, $comment = '')
    {
        $this->fieldClean($spcname);
        $this->fieldClean($name);
        $this->fieldClean($owner);

        // Begin transaction
        $status = $this->beginTransaction();

        if (0 !== $status) {
            return -1;
        }

        // Owner
        $sql = \sprintf('ALTER TABLESPACE "%s" OWNER TO "%s"', $spcname, $owner);
        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -2;
        }

        // Rename (only if name has changed)
        if ($name !== $spcname) {
            $sql = \sprintf('ALTER TABLESPACE "%s" RENAME TO "%s"', $spcname, $name);
            $status = $this->execute($sql);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -3;
            }

            $spcname = $name;
        }

        // Set comment if it has changed
        if ('' !== \trim($comment) && $this->hasSharedComments()) {
            $status = $this->setComment('TABLESPACE', $spcname, '', $comment);

            if (0 !== $status) {
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
     * @return int|string
     */
    public function dropTablespace($spcname)
    {
        $this->fieldClean($spcname);

        $sql = \sprintf('DROP TABLESPACE "%s"', $spcname);

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
