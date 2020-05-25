<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for tables manipulation.
 */
trait RowTrait
{
    /**
     * Returns a recordset of all columns in a table.
     *
     * @param string $table The name of a table
     * @param array  $key   The associative array holding the key to retrieve
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function browseRow($table, $key)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        $sql = "SELECT * FROM \"{$f_schema}\".\"{$table}\"";

        if (\is_array($key) && 0 < \count($key)) {
            $sql .= ' WHERE true';

            foreach ($key as $k => $v) {
                $this->fieldClean($k);
                $this->clean($v);
                $sql .= " AND \"{$k}\"='{$v}'";
            }
        }

        return $this->selectSet($sql);
    }

    /**
     * Get the fields for uniquely identifying a row in a table.
     *
     * @param string $table The table for which to retrieve the identifier
     *
     * @return array|array<integer,string>|int An array mapping attribute number to attribute name, empty for no identifiers
     */
    public function getRowIdentifier($table)
    {
        $oldtable = $table;
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $status = $this->beginTransaction();

        if (0 !== $status) {
            return -1;
        }

        // Get the first primary or unique index (sorting primary keys first) that
        // is NOT a partial index.
        $sql = "
            SELECT indrelid, indkey
            FROM pg_catalog.pg_index
            WHERE indisunique AND indrelid=(
                SELECT oid FROM pg_catalog.pg_class
                WHERE relname='{$table}' AND relnamespace=(
                    SELECT oid FROM pg_catalog.pg_namespace
                    WHERE nspname='{$c_schema}'
                )
            ) AND indpred IS NULL AND indexprs IS NULL
            ORDER BY indisprimary DESC LIMIT 1";
        $rs = $this->selectSet($sql);

        // If none, check for an OID column.  Even though OIDs can be duplicated, the edit and delete row
        // functions check that they're only modiying a single row.  Otherwise, return empty array.
        if (0 === $rs->recordCount()) {
            // Check for OID column
            $temp = [];

            if ($this->hasObjectID($table)) {
                $temp = ['oid'];
            }
            $this->endTransaction();

            return $temp;
        } // Otherwise find the names of the keys

        $attnames = $this->getAttributeNames($oldtable, \explode(' ', $rs->fields['indkey']));

        if (!\is_array($attnames)) {
            $this->rollbackTransaction();

            return -1;
        }

        $this->endTransaction();

        return $attnames;
    }

    /**
     * Adds a new row to a table.
     *
     * @param string $table  The table in which to insert
     * @param array  $fields Array of given field in values
     * @param array  $values Array of new values for the row
     * @param array  $nulls  An array mapping column => something if it is to be null
     * @param array  $format An array of the data type (VALUE or EXPRESSION)
     * @param array  $types  An array of field types
     *
     * @return int|\PHPPgAdmin\ADORecordSet
     */
    public function insertRow($table, $fields, $values, $nulls, $format, $types)
    {
        if (!\is_array($fields) || !\is_array($values) || !\is_array($nulls)
            || !\is_array($format) || !\is_array($types)
            || (\count($fields) !== \count($values))
        ) {
            return -1;
        }

        // Build clause
        if (0 < \count($values)) {
            // Escape all field names
            $fields = \array_map(['\PHPPgAdmin\Database\Postgres', 'fieldClean'], $fields);
            $f_schema = $this->_schema;
            $this->fieldClean($table);
            $this->fieldClean($f_schema);

            $sql = '';

            foreach ($values as $i => $value) {
                // Handle NULL values
                if (isset($nulls[$i])) {
                    $sql .= ',NULL';
                } else {
                    $sql .= ',' . $this->formatValue($types[$i], $format[$i], $value);
                }
            }

            $sql = "INSERT INTO \"{$f_schema}\".\"{$table}\" (\"" . \implode('","', $fields) . '")
                VALUES (' . \mb_substr($sql, 1) . ')';

            return $this->execute($sql);
        }

        return -1;
    }

    /**
     * Formats a value or expression for sql purposes.
     *
     * @param string $type   The type of the field
     * @param mixed  $format VALUE or EXPRESSION
     * @param mixed  $value  The actual value entered in the field.  Can be NULL
     *
     * @return mixed The suitably quoted and escaped value
     */
    public function formatValue($type, $format, $value)
    {
        switch ($type) {
            case 'bool':
            case 'boolean':
                if ('t' === $value) {
                    return 'TRUE';
                }

                if ('f' === $value) {
                    return 'FALSE';
                }

                if ('' === $value) {
                    return 'NULL';
                }

                return $value;

                break;

            default:
                // Checking variable fields is difficult as there might be a size
                // attribute...
                if (0 === \mb_strpos($type, 'time')) {
                    // Assume it's one of the time types...
                    if ('' === $value) {
                        return "''";
                    }

                    if (0 === \strcasecmp($value, 'CURRENT_TIMESTAMP')
                        || 0 === \strcasecmp($value, 'CURRENT_TIME')
                        || 0 === \strcasecmp($value, 'CURRENT_DATE')
                        || 0 === \strcasecmp($value, 'LOCALTIME')
                        || 0 === \strcasecmp($value, 'LOCALTIMESTAMP')) {
                        return $value;
                    }

                    if ('EXPRESSION' === $format) {
                        return $value;
                    }
                    $this->clean($value);

                    return "'{$value}'";
                }

                if ('VALUE' === $format) {
                    $this->clean($value);

                    return "'{$value}'";
                }

                return $value;
        }
    }

    // View functions

    /**
     * Updates a row in a table.
     *
     * @param string $table  The table in which to update
     * @param array  $vars   An array mapping new values for the row
     * @param array  $nulls  An array mapping column => something if it is to be null
     * @param array  $format An array of the data type (VALUE or EXPRESSION)
     * @param array  $types  An array of field types
     * @param array  $keyarr An array mapping column => value to update
     *
     * @return bool|int 0 success
     */
    public function editRow($table, $vars, $nulls, $format, $types, $keyarr)
    {
        if (!\is_array($vars) || !\is_array($nulls) || !\is_array($format) || !\is_array($types)) {
            return -1;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $sql = '';
        // Build clause
        if (0 < \count($vars)) {
            foreach ($vars as $key => $value) {
                $this->fieldClean($key);

                // Handle NULL values
                if (isset($nulls[$key])) {
                    $tmp = 'NULL';
                } else {
                    $tmp = $this->formatValue($types[$key], $format[$key], $value);
                }

                if (0 < \mb_strlen($sql)) {
                    $sql .= ", \"{$key}\"={$tmp}";
                } else {
                    $sql = "UPDATE \"{$f_schema}\".\"{$table}\" SET \"{$key}\"={$tmp}";
                }
            }
            $first = true;

            foreach ($keyarr as $k => $v) {
                $this->fieldClean($k);
                $this->clean($v);

                if ($first) {
                    $sql .= " WHERE \"{$k}\"='{$v}'";
                    $first = false;
                } else {
                    $sql .= " AND \"{$k}\"='{$v}'";
                }
            }
        }

        // Begin transaction.  We do this so that we can ensure only one row is
        // edited
        $status = $this->beginTransaction();

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }
        $status = $this->execute($sql);

        if (0 !== $status) {
            // update failed
            $this->rollbackTransaction();

            return -1;
        }

        if (1 !== $this->conn->Affected_Rows()) {
            // more than one row could be updated
            $this->rollbackTransaction();

            return -2;
        }

        // End transaction
        return $this->endTransaction();
    }

    /**
     * Delete a row from a table.
     *
     * @param string $table  The table from which to delete
     * @param array  $key    An array mapping column => value to delete
     * @param string $schema the schema of the table
     *
     * @return bool|int 0 success
     */
    public function deleteRow($table, $key, $schema = '')
    {
        if (!\is_array($key)) {
            return -1;
        }

        // Begin transaction.  We do this so that we can ensure only one row is
        // deleted
        $status = $this->beginTransaction();

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        if ('' === $schema) {
            $schema = $this->_schema;
        }

        $status = $this->delete($table, $key, $schema);

        if (0 !== $status || 1 !== $this->conn->Affected_Rows()) {
            $this->rollbackTransaction();

            return -2;
        }

        // End transaction
        return $this->endTransaction();
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

    abstract public function hasObjectID($table);
}
