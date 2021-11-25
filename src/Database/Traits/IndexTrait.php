<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for indexes and constraints manipulation.
 */
trait IndexTrait
{
    /**
     * Test if a table has been clustered on an index.
     *
     * @param string $table The table to test
     *
     * @return bool true if the table has been already clustered
     */
    public function alreadyClustered($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            'SELECT i.indisclustered
			FROM pg_catalog.pg_class c, pg_catalog.pg_index i
			WHERE c.relname = \'%s\'
				AND c.oid = i.indrelid AND i.indisclustered
				AND c.relnamespace = (SELECT oid FROM pg_catalog.pg_namespace
					WHERE nspname=\'%s\')
				',
            $table,
            $c_schema
        );

        $v = $this->selectSet($sql);

        return 0 !== $v->RecordCount();
    }

    /**
     * Creates an index.
     *
     * @param string       $name         The index name (can be blank)
     * @param string       $table        The table on which to add the index
     * @param array|string $columns      An array of columns that form the index  or a string expression for a functional index
     * @param string       $type         The index type
     * @param bool         $unique       True if unique, false otherwise
     * @param string       $where        Index predicate ('' for none)
     * @param string       $tablespace   The tablespaces ('' means none/default)
     * @param bool         $concurrently true to create index concurrently
     *
     * @return (int|string)[]
     *
     * @psalm-return array{0: int|string, 1: string}
     */
    public function createIndex($name, $table, $columns, $type, $unique, $where, $tablespace, $concurrently)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);
        $this->fieldClean($table);

        $sql = 'CREATE ';

        $sql .= $unique ? ' UNIQUE ' : '';

        $sql .= ' INDEX ';

        $sql .= $concurrently ? ' CONCURRENTLY ' : '';

        $sql .= $name ? \sprintf(
            '  "%s" ',
            $name
        ) : '';

        $sql .= \sprintf(
            ' ON "%s"."%s" USING %s ',
            $f_schema,
            $table,
            $type
        );

        if (\is_array($columns)) {
            $this->arrayClean($columns);
            $sql .= '("' . \implode('","', $columns) . '")';
        } else {
            $sql .= '(' . $columns . ')';
        }

        // Tablespace
        if ($this->hasTablespaces() && '' !== $tablespace) {
            $this->fieldClean($tablespace);
            $sql .= \sprintf(
                ' TABLESPACE "%s"',
                $tablespace
            );
        }

        // Predicate
        if ('' !== \trim($where)) {
            $sql .= \sprintf(
                ' WHERE (%s)',
                $where
            );
        }

        $status = $this->execute($sql);

        return [$status, $sql];
    }

    /**
     * Removes an index from the database.
     *
     * @param string $index   The index to drop
     * @param bool   $cascade True to cascade drop, false to restrict
     *
     * @return (int|string)[] 0 if operation was successful
     *
     * @psalm-return array{0: int|string, 1: string}
     */
    public function dropIndex($index, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($index);

        $sql = \sprintf(
            'DROP INDEX "%s"."%s"',
            $f_schema,
            $index
        );

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        $status = $this->execute($sql);

        return [$status, $sql];
    }

    /**
     * Rebuild indexes.
     *
     * @param string $type  'DATABASE' or 'TABLE' or 'INDEX'
     * @param string $name  The name of the specific database, table, or index to be reindexed
     * @param bool   $force If true, recreates indexes forcedly in PostgreSQL 7.0-7.1, forces rebuild of system indexes in
     *                      7.2-7.3, ignored in >=7.4
     *
     * @return int|string
     */
    public function reindex($type, $name, $force = false)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);

        switch ($type) {
            case 'DATABASE':
                $sql = \sprintf(
                    'REINDEX %s "%s"',
                    $type,
                    $name
                );

                if ($force) {
                    $sql .= ' FORCE';
                }

                break;
            case 'TABLE':
            case 'INDEX':
                $sql = \sprintf(
                    'REINDEX %s "%s"."%s"',
                    $type,
                    $f_schema,
                    $name
                );

                if ($force) {
                    $sql .= ' FORCE';
                }

                break;

            default:
                return -1;
        }

        return $this->execute($sql);
    }

    /**
     * Clusters an index.
     *
     * @param string $table The table the index is on
     * @param string $index The name of the index
     *
     * @return (int|string)[]
     *
     * @psalm-return array{0: int|string, 1: string}
     */
    public function clusterIndex($table = '', $index = '')
    {
        $sql = 'CLUSTER';

        // We don't bother with a transaction here, as there's no point rolling
        // back an expensive cluster if a cheap analyze fails for whatever reason

        if (!empty($table)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $this->fieldClean($table);
            $sql .= \sprintf(
                ' "%s"."%s"',
                $f_schema,
                $table
            );

            if (!empty($index)) {
                $this->fieldClean($index);
                $sql .= \sprintf(
                    ' USING "%s"',
                    $index
                );
            }
        }

        $status = $this->execute($sql);

        return [$status, $sql];
    }

    /**
     * Returns a list of all constraints on a table,
     * including constraint name, definition, related col and referenced namespace,
     * table and col if needed.
     *
     * @param string $table the table where we are looking for fk
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getConstraintsWithFields($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        // get the max number of col used in a constraint for the table
        $sql = \sprintf(
            'SELECT DISTINCT
			max(SUBSTRING(array_dims(c.conkey) FROM  $patern$^\[.*:(.*)\]$$patern$)) as nb
		FROM pg_catalog.pg_constraint AS c
			JOIN pg_catalog.pg_class AS r ON (c.conrelid=r.oid)
			JOIN pg_catalog.pg_namespace AS ns ON (r.relnamespace=ns.oid)
		WHERE
			r.relname = \'%s\' AND ns.nspname=\'%s\'',
            $table,
            $c_schema
        );

        $rs = $this->selectSet($sql);

        $max_col = $rs->EOF ? 0 : $rs->fields['nb'];

        $sql = '
			SELECT
				c.oid AS conid, c.contype, c.conname, pg_catalog.pg_get_constraintdef(c.oid, true) AS consrc,
				ns1.nspname as p_schema, r1.relname as p_table, ns2.nspname as f_schema,
				r2.relname as f_table, f1.attname as p_field, f1.attnum AS p_attnum, f2.attname as f_field,
				f2.attnum AS f_attnum, pg_catalog.obj_description(c.oid, \'pg_constraint\') AS constcomment,
				c.conrelid, c.confrelid
			FROM
				pg_catalog.pg_constraint AS c
				JOIN pg_catalog.pg_class AS r1 ON (c.conrelid=r1.oid)
				JOIN pg_catalog.pg_attribute AS f1 ON (f1.attrelid=r1.oid AND (f1.attnum=c.conkey[1]';

        for ($i = 2; $i <= $rs->fields['nb']; ++$i) {
            $sql .= \sprintf(
                ' OR f1.attnum=c.conkey[%s]',
                $i
            );
        }
        $sql .= '))
				JOIN pg_catalog.pg_namespace AS ns1 ON r1.relnamespace=ns1.oid
				LEFT JOIN (
					pg_catalog.pg_class AS r2 JOIN pg_catalog.pg_namespace AS ns2 ON (r2.relnamespace=ns2.oid)
				) ON (c.confrelid=r2.oid)
				LEFT JOIN pg_catalog.pg_attribute AS f2 ON
					(f2.attrelid=r2.oid AND ((c.confkey[1]=f2.attnum AND c.conkey[1]=f1.attnum)';

        for ($i = 2; $i <= $rs->fields['nb']; ++$i) {
            $sql .= \sprintf(
                ' OR (c.confkey[%s]=f2.attnum AND c.conkey[%s]=f1.attnum)',
                $i,
                $i
            );
        }

        $sql .= \sprintf("))
			WHERE
				r1.relname = '%s' AND ns1.nspname='%s'
			ORDER BY 1", $table, $c_schema);

        return $this->selectSet($sql);
    }

    /**
     * Adds a primary key constraint to a table.
     *
     * @param string $table      The table to which to add the primery key
     * @param array  $fields     (array) An array of fields over which to add the primary key
     * @param string $name       (optional) The name to give the key, otherwise default name is assigned
     * @param string $tablespace (optional) The tablespace for the schema, '' indicates default
     *
     * @return int|string
     */
    public function addPrimaryKey($table, $fields, $name = '', $tablespace = '')
    {
        if (!\is_array($fields) || 0 === \count($fields)) {
            return -1;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldArrayClean($fields);
        $this->fieldClean($name);
        $this->fieldClean($tablespace);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" ADD ',
            $f_schema,
            $table
        );

        if ('' !== $name) {
            $sql .= \sprintf(
                'CONSTRAINT "%s" ',
                $name
            );
        }

        $sql .= 'PRIMARY KEY ("' . \implode('","', $fields) . '")';

        if ('' !== $tablespace && $this->hasTablespaces()) {
            $sql .= \sprintf(
                ' USING INDEX TABLESPACE "%s"',
                $tablespace
            );
        }

        return $this->execute($sql);
    }

    /**
     * Adds a unique constraint to a table.
     *
     * @param string      $table      The table to which to add the unique key
     * @param array|mixed $fields     (array) An array of fields over which to add the unique key
     * @param string      $name       (optional) The name to give the key, otherwise default name is assigned
     * @param string      $tablespace (optional) The tablespace for the schema, '' indicates default
     *
     * @return int|string
     */
    public function addUniqueKey($table, $fields, $name = '', $tablespace = '')
    {
        if (!\is_array($fields) || 0 === \count($fields)) {
            return -1;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldArrayClean($fields);
        $this->fieldClean($name);
        $this->fieldClean($tablespace);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" ADD ',
            $f_schema,
            $table
        );

        if ('' !== $name) {
            $sql .= \sprintf(
                'CONSTRAINT "%s" ',
                $name
            );
        }

        $sql .= 'UNIQUE ("' . \implode('","', $fields) . '")';

        if ('' !== $tablespace && $this->hasTablespaces()) {
            $sql .= \sprintf(
                ' USING INDEX TABLESPACE "%s"',
                $tablespace
            );
        }

        return $this->execute($sql);
    }

    // Function functions

    /**
     * Adds a check constraint to a table.
     *
     * @param string $table      The table to which to add the check
     * @param string $definition The definition of the check
     * @param string $name       (optional) The name to give the check, otherwise default name is assigned
     *
     * @return int|string
     */
    public function addCheckConstraint($table, $definition, $name = '')
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($name);
        // @@ How the heck do you clean a definition???

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" ADD ',
            $f_schema,
            $table
        );

        if ('' !== $name) {
            $sql .= \sprintf(
                'CONSTRAINT "%s" ',
                $name
            );
        }

        $sql .= \sprintf(
            'CHECK (%s)',
            $definition
        );

        return $this->execute($sql);
    }

    /**
     * Drops a check constraint from a table.
     *
     * @param string $table The table from which to drop the check
     * @param string $name  The name of the check to be dropped
     *
     * @return int
     *
     * @psalm-return -4|-3|-2|0|1
     */
    public function dropCheckConstraint($table, $name)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $c_table = $table;
        $this->fieldClean($table);
        $this->clean($c_table);
        $this->clean($name);

        // Begin transaction
        $status = $this->beginTransaction();

        if (0 !== $status) {
            return -2;
        }

        // Properly lock the table
        $sql = \sprintf(
            'LOCK TABLE "%s"."%s" IN ACCESS EXCLUSIVE MODE',
            $f_schema,
            $table
        );
        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -3;
        }

        // Delete the check constraint
        $sql = \sprintf(
            'DELETE FROM pg_relcheck WHERE rcrelid=(SELECT oid FROM pg_catalog.pg_class WHERE relname=\'%s\'
			AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE
			nspname = \'%s\')) AND rcname=\'%s\'',
            $c_table,
            $c_schema,
            $name
        );
        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -4;
        }

        // Update the pg_class catalog to reflect the new number of checks
        $sql = \sprintf(
            'UPDATE pg_class SET relchecks=(SELECT COUNT(*) FROM pg_relcheck WHERE
					rcrelid=(SELECT oid FROM pg_catalog.pg_class WHERE relname=\'%s\'
						AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE
						nspname = \'%s\')))
					WHERE relname=\'%s\'',
            $c_table,
            $c_schema,
            $c_table
        );
        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -4;
        }

        // Otherwise, close the transaction
        return $this->endTransaction();
    }

    /**
     * Adds a foreign key constraint to a table.
     *
     * @param string $table      The table on which to add an FK
     * @param string $targschema The schema that houses the target table to which to add the foreign key
     * @param string $targtable  The table to which to add the foreign key
     * @param array  $sfields    (array) An array of source fields over which to add the foreign key
     * @param array  $tfields    (array) An array of target fields over which to add the foreign key
     * @param string $upd_action The action for updates (eg. RESTRICT)
     * @param string $del_action The action for deletes (eg. RESTRICT)
     * @param string $match      The match type (eg. MATCH FULL)
     * @param string $deferrable The deferrability (eg. NOT DEFERRABLE)
     * @param string $initially  The initially parameter for the FK (eg. INITIALLY IMMEDIATE)
     * @param string $name       [optional] The name to give the key, otherwise default name is assigned
     *
     * @return int|string
     *
     * @internal param string $targtable table that contains the target columns
     * @internal param string $intially initial deferrability (eg. INITIALLY IMMEDIATE)
     */
    public function addForeignKey(
        $table,
        $targschema,
        $targtable,
        $sfields,
        $tfields,
        $upd_action,
        $del_action,
        $match,
        $deferrable,
        $initially,
        $name = ''
    ) {
        if (!\is_array($sfields) || 0 === \count($sfields)
            || !\is_array($tfields) || 0 === \count($tfields)
        ) {
            return -1;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($targschema);
        $this->fieldClean($targtable);
        $this->fieldArrayClean($sfields);
        $this->fieldArrayClean($tfields);
        $this->fieldClean($name);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" ADD ',
            $f_schema,
            $table
        );

        if ('' !== $name) {
            $sql .= \sprintf(
                'CONSTRAINT "%s" ',
                $name
            );
        }

        $sql .= 'FOREIGN KEY ("' . \implode('","', $sfields) . '") ';
        // Target table needs to be fully qualified
        $sql .= \sprintf(
            'REFERENCES "%s"."%s"("',
            $targschema,
            $targtable
        ) . \implode('","', $tfields) . '") ';

        if ($match !== $this->fkmatches[0]) {
            $sql .= \sprintf(
                ' %s',
                $match
            );
        }

        if ($upd_action !== $this->fkactions[0]) {
            $sql .= \sprintf(
                ' ON UPDATE %s',
                $upd_action
            );
        }

        if ($del_action !== $this->fkactions[0]) {
            $sql .= \sprintf(
                ' ON DELETE %s',
                $del_action
            );
        }

        if ($deferrable !== $this->fkdeferrable[0]) {
            $sql .= \sprintf(
                ' %s',
                $deferrable
            );
        }

        if ($initially !== $this->fkinitial[0]) {
            $sql .= \sprintf(
                ' %s',
                $initially
            );
        }

        return $this->execute($sql);
    }

    /**
     * Removes a constraint from a relation.
     *
     * @param string $constraint The constraint to drop
     * @param string $relation   The relation from which to drop
     * @param string $type       The type of constraint (c, f, u or p)
     * @param bool   $cascade    True to cascade drop, false to restrict
     *
     * @return int|string
     */
    public function dropConstraint($constraint, $relation, $type, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($constraint);
        $this->fieldClean($relation);

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" DROP CONSTRAINT "%s"',
            $f_schema,
            $relation,
            $constraint
        );

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * A function for getting all columns linked by foreign keys given a group of tables.
     *
     * @param array $tables multi dimensional assoc array that holds schema and table name
     *
     * @return \ADORecordSet|bool|int|string recordset of linked tables and columns or -1 if $tables isn't an array
     */
    public function getLinkingKeys($tables)
    {
        if (!\is_array($tables)) {
            return -1;
        }

        $this->clean($tables[0]['tablename']);
        $this->clean($tables[0]['schemaname']);
        $tables_list = \sprintf(
            '\'%s\'',
            $tables[0]['tablename']
        );
        $schema_list = \sprintf(
            '\'%s\'',
            $tables[0]['schemaname']
        );
        $schema_tables_list = \sprintf(
            '\'%s.%s\'',
            $tables[0]['schemaname'],
            $tables[0]['tablename']
        );
        $tablescount = \count($tables);

        for ($i = 1; $i < $tablescount; ++$i) {
            $this->clean($tables[$i]['tablename']);
            $this->clean($tables[$i]['schemaname']);
            $tables_list .= \sprintf(
                ', \'%s\'',
                $tables[$i]['tablename']
            );
            $schema_list .= \sprintf(
                ', \'%s\'',
                $tables[$i]['schemaname']
            );
            $schema_tables_list .= \sprintf(
                ', \'%s.%s\'',
                $tables[$i]['schemaname'],
                $tables[$i]['tablename']
            );
        }

        $maxDimension = 1;

        $sql = \sprintf(
            '
			SELECT DISTINCT
				array_dims(pc.conkey) AS arr_dim,
				pgc1.relname AS p_table
			FROM
				pg_catalog.pg_constraint AS pc,
				pg_catalog.pg_class AS pgc1
			WHERE
				pc.contype = \'f\'
				AND (pc.conrelid = pgc1.relfilenode OR pc.confrelid = pgc1.relfilenode)
				AND pgc1.relname IN (%s)
			',
            $tables_list
        );

        //parse our output to find the highest dimension of foreign keys since pc.conkey is stored in an array
        $rs = $this->selectSet($sql);

        while (!$rs->EOF) {
            $arrData = \explode(':', $rs->fields['arr_dim']);
            $strdimension = \trim(\mb_substr($arrData[1], 0, \mb_strlen($arrData[1]) - 1));
            $tmpDimension = (int) $strdimension;
            $maxDimension = $tmpDimension > $maxDimension ? $tmpDimension : $maxDimension;
            $rs->MoveNext();
        }

        //we know the highest index for foreign keys that conkey goes up to, expand for us in an IN query
        $cons_str = '( (pfield.attnum = conkey[1] AND cfield.attnum = confkey[1]) ';

        for ($i = 2; $i <= $maxDimension; ++$i) {
            $cons_str .= \sprintf(
                'OR (pfield.attnum = conkey[%s] AND cfield.attnum = confkey[%s]) ',
                $i,
                $i
            );
        }
        $cons_str .= ') ';

        $sql = \sprintf(
            '
			SELECT
				pgc1.relname AS p_table,
				pgc2.relname AS f_table,
				pfield.attname AS p_field,
				cfield.attname AS f_field,
				pgns1.nspname AS p_schema,
				pgns2.nspname AS f_schema
			FROM
				pg_catalog.pg_constraint AS pc,
				pg_catalog.pg_class AS pgc1,
				pg_catalog.pg_class AS pgc2,
				pg_catalog.pg_attribute AS pfield,
				pg_catalog.pg_attribute AS cfield,
				(SELECT oid AS ns_id, nspname FROM pg_catalog.pg_namespace WHERE nspname IN (%s) ) AS pgns1,
 				(SELECT oid AS ns_id, nspname FROM pg_catalog.pg_namespace WHERE nspname IN (%s) ) AS pgns2
			WHERE
				pc.contype = \'f\'
				AND pgc1.relnamespace = pgns1.ns_id
 				AND pgc2.relnamespace = pgns2.ns_id
				AND pc.conrelid = pgc1.relfilenode
				AND pc.confrelid = pgc2.relfilenode
				AND pfield.attrelid = pc.conrelid
				AND cfield.attrelid = pc.confrelid
				AND %s
				AND pgns1.nspname || \'.\' || pgc1.relname IN (%s)
				AND pgns2.nspname || \'.\' || pgc2.relname IN (%s)
		',
            $schema_list,
            $schema_list,
            $cons_str,
            $schema_tables_list,
            $schema_tables_list
        );

        return $this->selectSet($sql);
    }

    /**
     * Finds the foreign keys that refer to the specified table.
     *
     * @param string $table The table to find referrers for
     *
     * @return \ADORecordSet|bool|int|string A recordset or -1 in case of error
     */
    public function getReferrers($table)
    {
        $this->clean($table);

        $status = $this->beginTransaction();

        if (0 !== $status) {
            return -1;
        }

        $c_schema = $this->_schema;
        $this->clean($c_schema);

        $sql = \sprintf(
            '
			SELECT
				pn.nspname,
				pl.relname,
				pc.conname,
				pg_catalog.pg_get_constraintdef(pc.oid) AS consrc
			FROM
				pg_catalog.pg_constraint pc,
				pg_catalog.pg_namespace pn,
				pg_catalog.pg_class pl
			WHERE
				pc.connamespace = pn.oid
				AND pc.conrelid = pl.oid
				AND pc.contype = \'f\'
				AND confrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=\'%s\'
					AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace
					WHERE nspname=\'%s\'))
			ORDER BY 1,2,3
		',
            $table,
            $c_schema
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

    abstract public function hasTablespaces();

    abstract public function arrayClean(&$arr);

    abstract public function fieldArrayClean(&$arr);
}
