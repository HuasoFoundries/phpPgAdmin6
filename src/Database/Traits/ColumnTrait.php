<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for column manipulation.
 */
trait ColumnTrait
{
    /**
     * Add a new column to a table.
     *
     * @param string $table   The table to add to
     * @param string $column  The name of the new column
     * @param string $type    The type of the column
     * @param bool   $array   True if array type, false otherwise
     * @param int    $length  The optional size of the column (ie. 30 for varchar(30))
     * @param bool   $notnull True if NOT NULL, false otherwise
     * @param mixed  $default The default for the column.  '' for none.
     * @param string $comment comment for the column
     *
     * @return (int|string)[] first element is 0 on success, second element is sql sentence
     *
     * @psalm-return array{0: int, 1: string}
     */
    public function addColumn($table, $column, $type, $array, $length, $notnull, $default, $comment)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);
        $this->clean($type);
        $this->clean($length);

        if ('' === $length) {
            $sql = \sprintf(
                'ALTER TABLE "%s"."%s" ADD COLUMN "%s" %s',
                $f_schema,
                $table,
                $column,
                $type
            );
        } else {
            switch ($type) {
                // Have to account for weird placing of length for with/without
                // time zone types
                case 'timestamp with time zone':
                case 'timestamp without time zone':
                    $qual = \mb_substr($type, 9);
                    $sql = \sprintf(
                        'ALTER TABLE "%s"."%s" ADD COLUMN "%s" timestamp(%s)%s',
                        $f_schema,
                        $table,
                        $column,
                        $length,
                        $qual
                    );

                    break;
                case 'time with time zone':
                case 'time without time zone':
                    $qual = \mb_substr($type, 4);
                    $sql = \sprintf(
                        'ALTER TABLE "%s"."%s" ADD COLUMN "%s" time(%s)%s',
                        $f_schema,
                        $table,
                        $column,
                        $length,
                        $qual
                    );

                    break;

                default:
                    $sql = \sprintf(
                        'ALTER TABLE "%s"."%s" ADD COLUMN "%s" %s(%s)',
                        $f_schema,
                        $table,
                        $column,
                        $type,
                        $length
                    );
            }
        }

        // Add array qualifier, if requested
        if ($array) {
            $sql .= '[]';
        }

        // If we have advanced column adding, add the extra qualifiers
        if ($this->hasCreateFieldWithConstraints()) {
            // NOT NULL clause
            if ($notnull) {
                $sql .= ' NOT NULL';
            }

            // DEFAULT clause
            if ('' !== $default) {
                $sql .= ' DEFAULT ' . $default;
            }
        }

        $status = $this->beginTransaction();

        if (0 !== $status) {
            return [-1, $sql];
        }

        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return [-1, $sql];
        }

        $status = $this->setComment('COLUMN', $column, $table, $comment);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return [-1, $sql];
        }

        $status = $this->endTransaction();

        return [$status, $sql];
    }

    /**
     * Alters a column in a table.
     *
     * @param string $table      The table in which the column resides
     * @param string $column     The column to alter
     * @param string $name       The new name for the column
     * @param bool   $notnull    (boolean) True if not null, false otherwise
     * @param bool   $oldnotnull (boolean) True if column is already not null, false otherwise
     * @param mixed  $default    The new default for the column
     * @param mixed  $olddefault The old default for the column
     * @param string $type       The new type for the column
     * @param int    $length     The optional size of the column (ie. 30 for varchar(30))
     * @param bool   $array      True if array type, false otherwise
     * @param string $oldtype    The old type for the column
     * @param string $comment    Comment for the column
     *
     * @return (int|string)[] 0 success
     *
     * @psalm-return array{0: int, 1: string}
     */
    public function alterColumn(
        $table,
        $column,
        $name,
        $notnull,
        $oldnotnull,
        $default,
        $olddefault,
        $type,
        $length,
        $array,
        $oldtype,
        $comment
    ) {
        // Begin transaction
        $status = $this->beginTransaction();
        $sql = '';
        $sqlrename = '';

        if (0 !== $status) {
            $this->rollbackTransaction();

            return [-6, $sql];
        }

        // Rename the column, if it has been changed
        if ($column !== $name) {
            [$status, $sqlrename] = $this->renameColumn($table, $column, $name);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return [-4, $sql];
            }
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $toAlter = [];
        // Create the command for changing nullability
        if ($notnull !== $oldnotnull) {
            $toAlter[] = \sprintf(
                'ALTER COLUMN "%s" ',
                $name
            ) . ($notnull ? 'SET' : 'DROP') . ' NOT NULL';
        }

        // Add default, if it has changed
        if ($default !== $olddefault) {
            if ('' === $default) {
                $toAlter[] = \sprintf(
                    'ALTER COLUMN "%s" DROP DEFAULT',
                    $name
                );
            } else {
                $toAlter[] = \sprintf(
                    'ALTER COLUMN "%s" SET DEFAULT %s',
                    $name,
                    $default
                );
            }
        }

        // Add type, if it has changed
        if ('' === $length) {
            $ftype = $type;
        } else {
            switch ($type) {
                // Have to account for weird placing of length for with/without
                // time zone types
                case 'timestamp with time zone':
                case 'timestamp without time zone':
                    $qual = \mb_substr($type, 9);
                    $ftype = \sprintf(
                        'timestamp(%s)%s',
                        $length,
                        $qual
                    );

                    break;
                case 'time with time zone':
                case 'time without time zone':
                    $qual = \mb_substr($type, 4);
                    $ftype = \sprintf(
                        'time(%s)%s',
                        $length,
                        $qual
                    );

                    break;

                default:
                    $ftype = \sprintf(
                        '%s(%s)',
                        $type,
                        $length
                    );
            }
        }

        // Add array qualifier, if requested
        if ($array) {
            $ftype .= '[]';
        }

        if ($ftype !== $oldtype) {
            $toAlter[] = \sprintf(
                'ALTER COLUMN "%s" TYPE %s',
                $name,
                $ftype
            );
        }

        // Attempt to process the batch alteration, if anything has been changed
        if (!empty($toAlter)) {
            // Initialise an empty SQL string
            $sql = \sprintf(
                'ALTER TABLE "%s"."%s" ',
                $f_schema,
                $table
            )
            . \implode(',', $toAlter);

            $status = $this->execute($sql);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return [-1, $sql];
            }
        }

        // Update the comment on the column
        $status = $this->setComment('COLUMN', $name, $table, $comment);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return [-5, $sql];
        }

        return [$this->endTransaction(), $sqlrename . '<br>' . $sql];
    }

    /**
     * Renames a column in a table.
     *
     * @param string $table   The table containing the column to be renamed
     * @param string $column  The column to be renamed
     * @param string $newName The new name for the column
     *
     * @return (int|string)[] [0 if operation was successful, sql of sentence]
     *
     * @psalm-return array{0: int|string, 1: string}
     */
    public function renameColumn($table, $column, $newName)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);
        $this->fieldClean($newName);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" RENAME COLUMN "%s" TO "%s"',
            $f_schema,
            $table,
            $column,
            $newName
        );

        $status = $this->execute($sql);

        return [$status, $sql];
    }

    /**
     * Sets default value of a column.
     *
     * @param string $table   The table from which to drop
     * @param string $column  The column name to set
     * @param mixed  $default The new default value
     *
     * @return int|string
     */
    public function setColumnDefault($table, $column, $default)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" ALTER COLUMN "%s" SET DEFAULT %s',
            $f_schema,
            $table,
            $column,
            $default
        );

        return $this->execute($sql);
    }

    /**
     * Sets whether or not a column can contain NULLs.
     *
     * @param string $table  The table that contains the column
     * @param string $column The column to alter
     * @param bool   $state  True to set null, false to set not null
     *
     * @return int|string
     */
    public function setColumnNull($table, $column, $state)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" ALTER COLUMN "%s" ',
            $f_schema,
            $table,
            $column
        ) . ($state ? 'DROP' : 'SET') . ' NOT NULL';

        return $this->execute($sql);
    }

    /**
     * Drops a column from a table.
     *
     * @param string $table   The table from which to drop a column
     * @param string $column  The column to be dropped
     * @param bool   $cascade True to cascade drop, false to restrict
     *
     * @return (int|string)[] [int status, string sql sentence]
     *
     * @psalm-return array{0: int|string, 1: string}
     */
    public function dropColumn($table, $column, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" DROP COLUMN "%s"',
            $f_schema,
            $table,
            $column
        );

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        $status = $this->execute($sql);

        return [$status, $sql];
    }

    /**
     * Drops default value of a column.
     *
     * @param string $table  The table from which to drop
     * @param string $column The column name to drop default
     *
     * @return int|string
     */
    public function dropColumnDefault($table, $column)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" ALTER COLUMN "%s" DROP DEFAULT',
            $f_schema,
            $table,
            $column
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

    abstract public function phpBool($parameter);

    abstract public function hasCreateTableLikeWithConstraints();

    abstract public function hasCreateTableLikeWithIndexes();

    abstract public function hasTablespaces();

    abstract public function delete($table, $conditions, $schema = '');

    abstract public function fieldArrayClean(&$arr);

    abstract public function hasCreateFieldWithConstraints();

    abstract public function getAttributeNames($table, $atts);
}
