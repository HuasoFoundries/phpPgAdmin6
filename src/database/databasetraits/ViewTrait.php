<?php

/**
 * PHPPgAdmin 6.1.0
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for views manipulation.
 */
trait ViewTrait
{
    /**
     * Returns a list of all views in the database.
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function getViews()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = "
			SELECT c.relname, pg_catalog.pg_get_userbyid(c.relowner) AS relowner,
				pg_catalog.obj_description(c.oid, 'pg_class') AS relcomment
			FROM pg_catalog.pg_class c
				LEFT JOIN pg_catalog.pg_namespace n ON (n.oid = c.relnamespace)
			WHERE (n.nspname='{$c_schema}') AND (c.relkind = 'v'::\"char\")
			ORDER BY relname";

        return $this->selectSet($sql);
    }

    /**
     * Returns a list of all materialized views in the database.
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function getMaterializedViews()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = "
			SELECT c.relname, pg_catalog.pg_get_userbyid(c.relowner) AS relowner,
				pg_catalog.obj_description(c.oid, 'pg_class') AS relcomment
			FROM pg_catalog.pg_class c
				LEFT JOIN pg_catalog.pg_namespace n ON (n.oid = c.relnamespace)
			WHERE (n.nspname='{$c_schema}') AND (c.relkind = 'm'::\"char\")
			ORDER BY relname";

        return $this->selectSet($sql);
    }

    /**
     * Updates a view.
     *
     * @param string $viewname     The name fo the view to update
     * @param string $definition   The new definition for the view
     * @param string $comment
     * @param bool   $materialized tells if it's a materialized view or not
     *
     * @return bool|int 0 success
     */
    public function setView($viewname, $definition, $comment, $materialized = false)
    {
        return $this->createView($viewname, $definition, true, $comment, $materialized);
    }

    /**
     * Creates a new view.
     *
     * @param string $viewname     The name of the view to create
     * @param string $definition   The definition for the new view
     * @param bool   $replace      True to replace the view, false otherwise
     * @param string $comment
     * @param bool   $materialized tells if it's a materialized view
     *
     * @return bool|int 0 success
     */
    public function createView($viewname, $definition, $replace, $comment, $materialized = false)
    {
        $status = $this->beginTransaction();

        if (0 !== $status) {
            return -1;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($viewname);

        // Note: $definition not cleaned

        $sql = 'CREATE ';

        $sql .= $replace ? ' OR REPLACE ' : ' ';

        $obj_type = $materialized ? ' MATERIALIZED VIEW ' : ' VIEW ';

        $sql .= $obj_type . " \"{$f_schema}\".\"{$viewname}\" AS {$definition}";

        $status = $this->execute($sql);

        if ($status) {
            $this->rollbackTransaction();

            return -1;
        }

        if ('' !== $comment) {
            $status = $this->setComment($obj_type, $viewname, '', $comment);

            if ($status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Alter view properties.
     *
     * @param string $view    The name of the view
     * @param string $name    The new name for the view
     * @param string $owner   The new owner for the view
     * @param string $schema  The new schema for the view
     * @param string $comment The comment on the view
     *
     * @return bool|int 0 success
     */
    public function alterView($view, $name, $owner, $schema, $comment)
    {
        $data = $this->getView($view);

        if (1 !== $data->recordCount()) {
            return -2;
        }

        $status = $this->beginTransaction();

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        $status = $this->_alterView($data, $name, $owner, $schema, $comment);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return $status;
        }

        return $this->endTransaction();
    }

    /**
     * Returns all details for a particular view or materialized view.
     *
     * @param string $view The name of the view or materialized to retrieve
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function getView($view)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($view);

        $sql = "
			SELECT c.relname, n.nspname, pg_catalog.pg_get_userbyid(c.relowner) AS relowner,
				pg_catalog.pg_get_viewdef(c.oid, true) AS vwdefinition,
				pg_catalog.obj_description(c.oid, 'pg_class') AS relcomment,
                c.relkind
			FROM pg_catalog.pg_class c
				LEFT JOIN pg_catalog.pg_namespace n ON (n.oid = c.relnamespace)
			WHERE (c.relname = '{$view}') AND n.nspname='{$c_schema}'";

        return $this->selectSet($sql);
    }

    /**
     * Alter a view's owner.
     *
     * @param \PHPPgAdmin\ADORecordSet $vwrs  The view recordSet returned by getView()
     * @param null|string              $owner
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     *
     * @internal param  $name new view's owner
     */
    public function alterViewOwner($vwrs, $owner = null)
    {
        $type = ('m' === $vwrs->fields['relkind']) ? 'MATERIALIZED VIEW' : 'VIEW';
        /* $vwrs and $owner are cleaned in _alterView */
        if ((!empty($owner)) && ($vwrs->fields['relowner'] !== $owner)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            // If owner has been changed, then do the alteration.  We are
            // careful to avoid this generally as changing owner is a
            // superuser only function.
            $sql = "ALTER {$type} \"{$f_schema}\".\"{$vwrs->fields['relname']}\" OWNER TO \"{$owner}\"";

            return $this->execute($sql);
        }

        return 0;
    }

    /**
     * Rename a view.
     *
     * @param \PHPPgAdmin\ADORecordSet $vwrs The view recordSet returned by getView()
     * @param string                   $name The new view's name
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function alterViewName($vwrs, $name)
    {
        $type = ('m' === $vwrs->fields['relkind']) ? 'MATERIALIZED VIEW' : 'VIEW';
        // Rename (only if name has changed)
        /* $vwrs and $name are cleaned in _alterView */
        if (!empty($name) && ($name !== $vwrs->fields['relname'])) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $sql = "ALTER {$type} \"{$f_schema}\".\"{$vwrs->fields['relname']}\" RENAME TO \"{$name}\"";
            $status = $this->execute($sql);

            if (0 === $status) {
                $vwrs->fields['relname'] = $name;
            } else {
                return $status;
            }
        }

        return 0;
    }

    /**
     * Alter a view's schema.
     *
     * @param \PHPPgAdmin\ADORecordSet $vwrs   The view recordSet returned by getView()
     * @param string                   $schema
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     *
     * @internal param The $name new view's schema
     */
    public function alterViewSchema($vwrs, $schema)
    {
        $type = ('m' === $vwrs->fields['relkind']) ? 'MATERIALIZED VIEW' : 'VIEW';

        /* $vwrs and $schema are cleaned in _alterView */
        if (!empty($schema) && ($vwrs->fields['nspname'] !== $schema)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            // If tablespace has been changed, then do the alteration.  We
            // don't want to do this unnecessarily.
            $sql = "ALTER {$type} \"{$f_schema}\".\"{$vwrs->fields['relname']}\" SET SCHEMA \"{$schema}\"";

            return $this->execute($sql);
        }

        return 0;
    }

    /**
     * Drops a view.
     *
     * @param string $viewname The name of the view to drop
     * @param string $cascade  True to cascade drop, false to restrict
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function dropView($viewname, $cascade)
    {
        $vwrs = $this->getView($viewname);
        $type = ('m' === $vwrs->fields['relkind']) ? 'MATERIALIZED VIEW' : 'VIEW';

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($viewname);

        $sql = "DROP {$type} \"{$f_schema}\".\"{$viewname}\"";

        if ($cascade) {
            $sql .= ' CASCADE';
        }

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

    abstract public function fieldArrayClean(&$arr);

    /**
     * Protected method which alter a view
     * SHOULDN'T BE CALLED OUTSIDE OF A TRANSACTION.
     *
     * @param \PHPPgAdmin\ADORecordSet $vwrs    The view recordSet returned by getView()
     * @param string                   $name    The new name for the view
     * @param string                   $owner   The new owner for the view
     * @param string                   $schema  Schema name
     * @param string                   $comment The comment on the view
     *
     * @return int 0 success
     */
    protected function _alterView($vwrs, $name, $owner, $schema, $comment)
    {
        $this->fieldArrayClean($vwrs->fields);

        $type = ('m' === $vwrs->fields['relkind']) ? 'MATERIALIZED VIEW' : 'VIEW';
        // Comment

        if (0 !== $this->setComment($type, $vwrs->fields['relname'], '', $comment)) {
            return -4;
        }

        // Owner
        $this->fieldClean($owner);
        $status = $this->alterViewOwner($vwrs, $owner);

        if (0 !== $status) {
            return -5;
        }

        // Rename
        $this->fieldClean($name);
        $status = $this->alterViewName($vwrs, $name);

        if (0 !== $status) {
            return -3;
        }

        // Schema
        $this->fieldClean($schema);
        $status = $this->alterViewSchema($vwrs, $schema);

        if (0 !== $status) {
            return -6;
        }

        return 0;
    }
}
