<?php

// declare(strict_types=1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for tables manipulation.
 */
trait SchemaTrait
{
    // Schema functons

    /**
     * Return all schemas in the current database.
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function getSchemas()
    {
        $conf = $this->conf;

        if (!$conf['show_system']) {
            $where = "WHERE nspname NOT LIKE 'pg@_%' ESCAPE '@' AND nspname != 'information_schema'";
        } else {
            $where = "WHERE nspname !~ '^pg_t(emp_[0-9]+|oast)$'";
        }

        $sql = "
            SELECT pn.nspname,
                   pu.rolname AS nspowner,
                   pg_catalog.obj_description(pn.oid, 'pg_namespace') AS nspcomment, ";

        /*
         * Either display_sizes is true for tables and schemas,
         * or we must check if said config is an associative array
         */
        if ($this->conf['display_sizes']['tables']) {
            $sql .= ' pg_size_pretty(SUM(pg_total_relation_size(pg_class.oid))) as schema_size ';
        } else {
            $sql .= " 'N/A' as schema_size ";
        }

        $sql .= " FROM pg_catalog.pg_namespace pn
            LEFT JOIN pg_catalog.pg_class  ON relnamespace = pn.oid
            LEFT JOIN pg_catalog.pg_roles pu ON (pn.nspowner = pu.oid)
            {$where}
            GROUP BY pn.nspname, pu.rolname, pg_catalog.obj_description(pn.oid, 'pg_namespace')
            ORDER BY nspname";

        return $this->selectSet($sql);
    }

    /**
     * Sets the current working schema.  Will also set Class variable.
     *
     * @param string $schema The the name of the schema to work in
     *
     * @return int 0 if operation was successful
     */
    public function setSchema($schema)
    {
        // Get the current schema search path, including 'pg_catalog'.
        $search_path = $this->getSearchPath();
        // Prepend $schema to search path
        \array_unshift($search_path, $schema);
        $status = $this->setSearchPath($search_path);

        if (0 === $status) {
            $this->_schema = $schema;

            return 0;
        }

        return $status;
    }

    /**
     * Return the current schema search path.
     *
     * @return array array of schema names
     */
    public function getSearchPath()
    {
        $sql = 'SELECT current_schemas(false) AS search_path';

        $fetchMode = $this->conn->fetchMode;
        $this->conn->setFetchMode(\ADODB_FETCH_ASSOC);
        $search_path = $this->selectField($sql, 'search_path');
        $this->conn->setFetchMode($fetchMode);

        return $this->phpArray($search_path);
    }

    /**
     * Sets the current schema search path.
     *
     * @param mixed $paths An array of schemas in required search order
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function setSearchPath($paths)
    {
        if (!\is_array($paths)) {
            return -1;
        }

        if (0 === \count($paths)) {
            return -2;
        }

        if (1 === \count($paths) && '' === $paths[0]) {
            // Need to handle empty paths in some cases
            $paths[0] = 'pg_catalog';
        }

        // Loop over all the paths to check that none are empty
        $temp = [];

        foreach ($paths as $schema) {
            if ('' !== $schema) {
                $temp[] = $schema;
            }
        }
        $this->fieldArrayClean($temp);

        $sql = 'SET SEARCH_PATH TO "' . \implode('","', $temp) . '"';

        return $this->execute($sql);
    }

    /**
     * Creates a new schema.
     *
     * @param string $schemaname    The name of the schema to create
     * @param string $authorization (optional) The username to create the schema for
     * @param string $comment       (optional) If omitted, defaults to nothing
     *
     * @return bool|int 0 success
     */
    public function createSchema($schemaname, $authorization = '', $comment = '')
    {
        $this->fieldClean($schemaname);
        $this->fieldClean($authorization);

        $sql = "CREATE SCHEMA \"{$schemaname}\"";

        if ('' !== $authorization) {
            $sql .= " AUTHORIZATION \"{$authorization}\"";
        }

        if ('' !== $comment) {
            $status = $this->beginTransaction();

            if (0 !== $status) {
                return -1;
            }
        }

        // Create the new schema
        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        // Set the comment
        if ('' !== $comment) {
            $status = $this->setComment('SCHEMA', $schemaname, '', $comment);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -1;
            }

            return $this->endTransaction();
        }

        return 0;
    }

    /**
     * Updates a schema.
     *
     * @param string $schemaname The name of the schema to drop
     * @param string $comment    The new comment for this schema
     * @param string $name       new name for this schema
     * @param string $owner      The new owner for this schema
     *
     * @return bool|int 0 success
     */
    public function updateSchema($schemaname, $comment, $name, $owner)
    {
        $this->fieldClean($schemaname);
        $this->fieldClean($name);
        $this->fieldClean($owner);

        $status = $this->beginTransaction();

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        $status = $this->setComment('SCHEMA', $schemaname, '', $comment);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        $schema_rs = $this->getSchemaByName($schemaname);
        /* Only if the owner change */
        if ($schema_rs->fields['ownername'] !== $owner) {
            $sql    = "ALTER SCHEMA \"{$schemaname}\" OWNER TO \"{$owner}\"";
            $status = $this->execute($sql);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        // Only if the name has changed
        if ($name !== $schemaname) {
            $sql    = "ALTER SCHEMA \"{$schemaname}\" RENAME TO \"{$name}\"";
            $status = $this->execute($sql);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Return all information relating to a schema.
     *
     * @param string $schema The name of the schema
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function getSchemaByName($schema)
    {
        $this->clean($schema);
        $sql = "
            SELECT nspname, nspowner, r.rolname AS ownername, nspacl,
                pg_catalog.obj_description(pn.oid, 'pg_namespace') as nspcomment
            FROM pg_catalog.pg_namespace pn
                LEFT JOIN pg_roles as r ON pn.nspowner = r.oid
            WHERE nspname='{$schema}'";

        return $this->selectSet($sql);
    }

    // Table functions

    /**
     * Drops a schema.
     *
     * @param string $schemaname The name of the schema to drop
     * @param bool   $cascade    True to cascade drop, false to restrict
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function dropSchema($schemaname, $cascade)
    {
        $this->fieldClean($schemaname);

        $sql = "DROP SCHEMA \"{$schemaname}\"";

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

    abstract public function phpBool($parameter);

    abstract public function hasCreateTableLikeWithConstraints();

    abstract public function hasCreateTableLikeWithIndexes();

    abstract public function hasTablespaces();

    abstract public function delete($table, $conditions, $schema = '');

    abstract public function fieldArrayClean(&$arr);

    abstract public function hasCreateFieldWithConstraints();

    abstract public function getAttributeNames($table, $atts);

    abstract public function selectField($sql, $field);

    abstract public function phpArray($dbarr);
}
