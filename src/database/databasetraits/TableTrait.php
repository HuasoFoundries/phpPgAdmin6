<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Database\Traits;

use PHPPgAdmin\ADORecordSet;
use PHPPgAdmin\ArrayRecordSet;

/**
 * Common trait for tables manipulation.
 */
trait TableTrait
{
    use ColumnTrait;
    use RowTrait;
    use TriggerTrait;

    /**
     * Return all tables in current database excluding schemas 'pg_catalog', 'information_schema' and 'pg_toast'.
     *
     * @return ADORecordSet|int
     */
    public function getAllTables()
    {
        $sql = "SELECT
                        schemaname AS nspname,
                        tablename AS relname,
                        tableowner AS relowner
                    FROM pg_catalog.pg_tables
                    WHERE schemaname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')
                    ORDER BY schemaname, tablename";

        return $this->selectSet($sql);
    }

    /**
     * Return all tables in current database (and schema).
     *
     * @return ADORecordSet|int
     */
    public function getTables()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);

        $sql = "
                SELECT c.relname,
                    pg_catalog.pg_get_userbyid(c.relowner) AS relowner,
                    pg_catalog.obj_description(c.oid, 'pg_class') AS relcomment,
                    reltuples::bigint as reltuples,
                    pt.spcname as tablespace, ";

        /*
         * Either display_sizes is true for tables and schemas,
         * or we must check if said config is an associative array
         */
        if ($this->conf['display_sizes']['tables']) {
            $sql .= ' pg_size_pretty(pg_total_relation_size(c.oid)) as table_size ';
        } else {
            $sql .= "   'N/A' as table_size ";
        }

        $sql .= \sprintf(
            ' FROM pg_catalog.pg_class c
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                LEFT JOIN  pg_catalog.pg_tablespace pt ON  pt.oid=c.reltablespace
                WHERE c.relkind = \'r\'
                AND nspname=\'%s\'
                ORDER BY c.relname',
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Finds the names and schemas of parent tables (in order).
     *
     * @param string $table The table to find the parents for
     *
     * @return ADORecordSet|int
     */
    public function getTableParents($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            '
            SELECT
                pn.nspname, relname
            FROM
                pg_catalog.pg_class pc, pg_catalog.pg_inherits pi, pg_catalog.pg_namespace pn
            WHERE
                pc.oid=pi.inhparent
                AND pc.relnamespace=pn.oid
                AND pi.inhrelid = (SELECT oid from pg_catalog.pg_class WHERE relname=\'%s\'
                    AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname = \'%s\'))
            ORDER BY
                pi.inhseqno
        ',
            $table,
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Finds the names and schemas of child tables.
     *
     * @param string $table The table to find the children for
     *
     * @return ADORecordSet|int
     */
    public function getTableChildren($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            '
            SELECT
                pn.nspname, relname
            FROM
                pg_catalog.pg_class pc, pg_catalog.pg_inherits pi, pg_catalog.pg_namespace pn
            WHERE
                pc.oid=pi.inhrelid
                AND pc.relnamespace=pn.oid
                AND pi.inhparent = (SELECT oid from pg_catalog.pg_class WHERE relname=\'%s\'
                    AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname = \'%s\'))
        ',
            $table,
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Returns the SQL definition for the table.
     * MUST be run within a transaction.
     *
     * @param string $table       The table to define
     * @param string $cleanprefix set to '-- ' to avoid issuing DROP statement
     */
    public function getTableDefPrefix($table, $cleanprefix = ''): ?string
    {
        // Fetch table
        $t = $this->getTable($table);

        if (!\is_object($t) || 1 !== $t->RecordCount()) {
            $this->rollbackTransaction();

            return null;
        }
        $this->fieldClean($t->fields['relname']);
        $this->fieldClean($t->fields['nspname']);

        // Fetch attributes
        $atts = $this->getTableAttributes($table);

        if (!\is_object($atts)) {
            $this->rollbackTransaction();

            return null;
        }

        // Fetch constraints
        $cons = $this->getConstraints($table);

        if (!\is_object($cons)) {
            $this->rollbackTransaction();

            return null;
        }

        // Output a reconnect command to create the table as the correct user
        $sql = "-- PHPPgAdmin\n" . $this->getChangeUserSQL($t->fields['relowner']) . "\n\n";

        $sql = $this->_dumpCreate($t, $sql, $cleanprefix);

        // Output all table columns
        $col_comments_sql = ''; // Accumulate comments on columns
        $num = $atts->RecordCount() + $cons->RecordCount();
        $i = 1;

        $sql = $this->_dumpSerials($atts, $t, $sql, $col_comments_sql, $i, $num);

        $consOutput = $this->_dumpConstraints($cons, $table, $sql, $i, $num);

        if (null === $consOutput) {
            return null;
        }
        $sql = $consOutput;

        $sql .= ')';

        // Handle WITHOUT OIDS
        if ($this->hasObjectID($table)) {
            $sql .= ' WITH OIDS';
        } else {
            $sql .= ' WITHOUT OIDS';
        }

        $sql .= ";\n";

        $colStorage = $this->_dumpColStats($atts, $t, $sql);

        if (null === $colStorage) {
            return null;
        }
        $sql = $colStorage;

        // Comment
        if (null !== $t->fields['relcomment']) {
            $this->clean($t->fields['relcomment']);
            $sql .= "\n-- Comment\n\n";
            $sql .= \sprintf(
                'COMMENT ON TABLE "%s"."%s" IS \'%s\';
',
                $t->fields['nspname'],
                $t->fields['relname'],
                $t->fields['relcomment']
            );
        }

        // Add comments on columns, if any
        if ('' !== $col_comments_sql) {
            $sql .= $col_comments_sql;
        }

        // Privileges
        $privs = $this->getPrivileges($table, 'table');

        if (!\is_array($privs)) {
            $this->rollbackTransaction();

            return null;
        }

        $privsOutput = $this->_dumpPrivileges($privs, $t, $sql);

        if (null === $privsOutput) {
            return null;
        }
        $sql = $privsOutput;

        // Add a newline to separate data that follows (if any)
        $sql .= "\n";

        return $sql;
    }

    /**
     * Returns table information.
     *
     * @param string $table The name of the table
     *
     * @return ADORecordSet|int
     */
    public function getTable($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = '
            SELECT
              c.relname, n.nspname, ';

        $sql .= ($this->hasRoles() ? ' coalesce(u.usename,r.rolname) ' : ' u.usename') . " AS relowner,
              pg_catalog.obj_description(c.oid, 'pg_class') AS relcomment,
              pt.spcname  AS tablespace
            FROM pg_catalog.pg_class c
                LEFT JOIN pg_catalog.pg_tablespace pt ON pt.oid=c.reltablespace
                 LEFT JOIN pg_catalog.pg_user u ON u.usesysid = c.relowner
                 LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace ";

        $sql .= ($this->hasRoles() ? ' LEFT JOIN pg_catalog.pg_roles r ON c.relowner = r.oid ' : '') .
            \sprintf(
                ' WHERE c.relkind = \'r\'
                  AND n.nspname = \'%s\'
                  AND n.oid = c.relnamespace
                  AND c.relname = \'%s\'',
                $c_schema,
                $table
            );

        return $this->selectSet($sql);
    }

    /**
     * Retrieve the attribute definition of a table.
     *
     * @param string $table The name of the table
     * @param string $field (optional) The name of a field to return
     *
     * @return int|\PHPPgAdmin\ADORecordSet All attributes in order
     */
    public function getTableAttributes($table, $field = '')
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        if ('' === $field) {
            // This query is made much more complex by the addition of the 'attisserial' field.
            // The subquery to get that field checks to see if there is an internally dependent
            // sequence on the field.
            return $this->_getTableAttributesAll($table, $c_schema);
        }
        $this->clean($field);

        return $this->_getTableAttribute($table, $c_schema, $field);
    }

    /**
     * Returns a list of all constraints on a table.
     *
     * @param string $table The table to find rules for
     *
     * @return ADORecordSet|int
     */
    public function getConstraints($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        // This SQL is greatly complicated by the need to retrieve
        // index clustering information for primary and unique constraints
        $sql = \sprintf(
            'SELECT
                pc.conname,
                pg_catalog.pg_get_constraintdef(pc.oid, true) AS consrc,
                pc.contype,
                CASE WHEN pc.contype=\'u\' OR pc.contype=\'p\' THEN (
                    SELECT
                        indisclustered
                    FROM
                        pg_catalog.pg_depend pd,
                        pg_catalog.pg_class pl,
                        pg_catalog.pg_index pi
                    WHERE
                        pd.refclassid=pc.tableoid
                        AND pd.refobjid=pc.oid
                        AND pd.objid=pl.oid
                        AND pl.oid=pi.indexrelid
                ) ELSE
                    NULL
                END AS indisclustered
            FROM
                pg_catalog.pg_constraint pc
            WHERE
                pc.conrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=\'%s\'
                    AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace
                    WHERE nspname=\'%s\'))
            ORDER BY
                1
        ',
            $table,
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Checks to see whether or not a table has a unique id column.
     *
     * @param string $table The table name
     *
     * @return bool true if it has a unique id, false otherwise
     */
    public function hasObjectID($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            'SELECT relhasoids FROM pg_catalog.pg_class WHERE relname=\'%s\'
            AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname=\'%s\')',
            $table,
            $c_schema
        );

        $rs = $this->selectSet($sql);

        if (1 !== $rs->RecordCount()) {
            return false;
        }

        $rs->fields['relhasoids'] = $this->phpBool($rs->fields['relhasoids']);

        return $rs->fields['relhasoids'];
    }

    /**
     * Returns extra table definition information that is most usefully
     * dumped after the table contents for speed and efficiency reasons.
     *
     * @param string $table The table to define
     */
    public function getTableDefSuffix($table): ?string
    {
        $sql = '';

        // Indexes
        $indexes = $this->getIndexes($table);

        if (!\is_object($indexes)) {
            $this->rollbackTransaction();

            return null;
        }

        if (0 < $indexes->RecordCount()) {
            $sql .= "\n-- Indexes\n\n";

            while (!$indexes->EOF) {
                $sql .= $indexes->fields['inddef'] . ";\n";

                $indexes->MoveNext();
            }
        }

        // Triggers
        $triggers = $this->getTriggers($table);

        if (!\is_object($triggers)) {
            $this->rollbackTransaction();

            return null;
        }

        if (0 < $triggers->RecordCount()) {
            $sql .= "\n-- Triggers\n\n";

            while (!$triggers->EOF) {
                $sql .= $triggers->fields['tgdef'];
                $sql .= ";\n";

                $triggers->MoveNext();
            }
        }

        // Rules
        $rules = $this->getRules($table);

        if (!\is_object($rules)) {
            $this->rollbackTransaction();

            return null;
        }

        if (0 < $rules->RecordCount()) {
            $sql .= "\n-- Rules\n\n";

            while (!$rules->EOF) {
                $sql .= $rules->fields['definition'] . "\n";

                $rules->MoveNext();
            }
        }

        return $sql;
    }

    /**
     * Grabs a list of indexes for a table.
     *
     * @param string $table  The name of a table whose indexes to retrieve
     * @param bool   $unique Only get unique/pk indexes
     *
     * @return ADORecordSet|int
     */
    public function getIndexes($table = '', $unique = false)
    {
        $this->clean($table);

        $sql = \sprintf(
            '
            SELECT c2.relname AS indname, i.indisprimary, i.indisunique, i.indisclustered,
                pg_catalog.pg_get_indexdef(i.indexrelid, 0, true) AS inddef
            FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i
            WHERE c.relname = \'%s\' AND pg_catalog.pg_table_is_visible(c.oid)
                AND c.oid = i.indrelid AND i.indexrelid = c2.oid
        ',
            $table
        );

        if ($unique) {
            $sql .= ' AND i.indisunique ';
        }

        $sql .= ' ORDER BY c2.relname';

        return $this->selectSet($sql);
    }

    /**
     * Grabs a list of triggers on a table.
     *
     * @param string $table The name of a table whose triggers to retrieve
     *
     * @return ADORecordSet|int
     */
    public function getTriggers($table = '')
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            'SELECT
                t.tgname, pg_catalog.pg_get_triggerdef(t.oid) AS tgdef,
                CASE WHEN t.tgenabled = \'D\' THEN FALSE ELSE TRUE END AS tgenabled, p.oid AS prooid,
                p.proname || \' (\' || pg_catalog.oidvectortypes(p.proargtypes) || \')\' AS proproto,
                ns.nspname AS pronamespace
            FROM pg_catalog.pg_trigger t, pg_catalog.pg_proc p, pg_catalog.pg_namespace ns
            WHERE t.tgrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=\'%s\'
                AND relnamespace=(SELECT oid FROM pg_catalog.pg_namespace WHERE nspname=\'%s\'))
                AND ( tgconstraint = 0 OR NOT EXISTS
                        (SELECT 1 FROM pg_catalog.pg_depend d    JOIN pg_catalog.pg_constraint c
                            ON (d.refclassid = c.tableoid AND d.refobjid = c.oid)
                        WHERE d.classid = t.tableoid AND d.objid = t.oid AND d.deptype = \'i\' AND c.contype = \'f\'))
                AND p.oid=t.tgfoid
                AND p.pronamespace = ns.oid',
            $table,
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Returns a list of all rules on a table OR view.
     *
     * @param string $table The table to find rules for
     *
     * @return ADORecordSet|int
     */
    public function getRules($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            '
            SELECT *
            FROM pg_catalog.pg_rules
            WHERE
                schemaname=\'%s\' AND tablename=\'%s\'
            ORDER BY rulename
        ',
            $c_schema,
            $table
        );

        return $this->selectSet($sql);
    }

    /**
     * Creates a new table in the database.
     *
     * @param string $name        The name of the table
     * @param int    $fields      The number of fields
     * @param array  $field       An array of field names
     * @param array  $type        An array of field types
     * @param array  $array       An array of '' or '[]' for each type if it's an array or not
     * @param array  $length      An array of field lengths
     * @param array  $notnull     An array of not null
     * @param array  $default     An array of default values
     * @param bool   $withoutoids True if WITHOUT OIDS, false otherwise
     * @param array  $colcomment  An array of comments
     * @param string $tblcomment  the comment for the table
     * @param string $tablespace  The tablespace name ('' means none/default)
     * @param array  $uniquekey   An Array indicating the fields that are unique (those indexes that are set)
     * @param array  $primarykey  An Array indicating the field used for the primarykey (those indexes that are set)
     *
     * @return bool|int 0 success
     */
    public function createTable(
        $name,
        $fields,
        $field,
        $type,
        $array,
        $length,
        $notnull,
        $default,
        $withoutoids,
        $colcomment,
        $tblcomment,
        $tablespace,
        $uniquekey,
        $primarykey
    ) {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);

        $status = $this->beginTransaction();

        if (0 !== $status) {
            return -1;
        }

        $found = false;
        $first = true;
        $comment_sql = ''; //Accumulate comments for the columns
        $sql = \sprintf(
            'CREATE TABLE "%s"."%s" (',
            $f_schema,
            $name
        );

        for ($i = 0; $i < $fields; ++$i) {
            $this->fieldClean($field[$i]);
            $this->clean($type[$i]);
            $this->clean($length[$i]);
            $this->clean($colcomment[$i]);

            // Skip blank columns - for user convenience
            if ('' === $field[$i] || '' === $type[$i]) {
                continue;
            }

            // If not the first column, add a comma
            if (!$first) {
                $sql .= ', ';
            } else {
                $first = false;
            }
            $length[$i] = $length[$i] ?? '';

            switch ($type[$i]) {
                    // Have to account for weird placing of length for with/without
                    // time zone types
                case 'timestamp with time zone':
                case 'timestamp without time zone':
                    $qual = \mb_substr($type[$i], 9);
                    $sql .= \sprintf(
                        '"%s" timestamp',
                        $field[$i]
                    );

                    if ('' !== $length[$i]) {
                        $sql .= \sprintf(
                            '(%s)',
                            $length[$i]
                        );
                    }

                    $sql .= $qual;

                    break;
                case 'time with time zone':
                case 'time without time zone':
                    $qual = \mb_substr($type[$i], 4);
                    $sql .= \sprintf(
                        '"%s" time',
                        $field[$i]
                    );

                    if ('' !== $length[$i]) {
                        $sql .= \sprintf(
                            '(%s)',
                            $length[$i]
                        );
                    }

                    $sql .= $qual;

                    break;

                default:
                     $sql .= \sprintf(
                         '"%s" %s',
                         $field[$i],
                         $type[$i]
                     );

                    if ('' !== $length[$i]) {
                        $sql .= \sprintf(
                            '(%s)',
                            $length[$i]
                        );
                    }
            }
            // Add array qualifier if necessary
            if ('[]' === $array[$i]) {
                $sql .= '[]';
            }

            // Add other qualifiers
            if (!isset($primarykey[$i])) {
                if (isset($uniquekey[$i])) {
                    $sql .= ' UNIQUE';
                }

                if (isset($notnull[$i])) {
                    $sql .= ' NOT NULL';
                }
            }

            if ('' !== $default[$i]) {
                $sql .= \sprintf(
                    ' DEFAULT %s',
                    $default[$i]
                );
            }

            if ('' !== $colcomment[$i]) {
                $comment_sql .= \sprintf(
                    'COMMENT ON COLUMN "%s"."%s" IS \'%s\';
',
                    $name,
                    $field[$i],
                    $colcomment[$i]
                );
            }

            $found = true;
        }

        if (!$found) {
            return -1;
        }

        // PRIMARY KEY
        $primarykeycolumns = [];

        for ($i = 0; $i < $fields; ++$i) {
            if (isset($primarykey[$i])) {
                $primarykeycolumns[] = \sprintf(
                    '"%s"',
                    $field[$i]
                );
            }
        }

        if (0 < \count($primarykeycolumns)) {
            $sql .= ', PRIMARY KEY (' . \implode(', ', $primarykeycolumns) . ')';
        }

        $sql .= ')';

        // WITHOUT OIDS
        if ($withoutoids) {
            $sql .= ' WITHOUT OIDS';
        } else {
            $sql .= ' WITH OIDS';
        }

        // Tablespace
        if ($this->hasTablespaces() && '' !== $tablespace) {
            $this->fieldClean($tablespace);
            $sql .= \sprintf(
                ' TABLESPACE "%s"',
                $tablespace
            );
        }

        $status = $this->execute($sql);

        if ($status) {
            $this->rollbackTransaction();

            return -1;
        }

        if ('' !== $tblcomment) {
            $status = $this->setComment('TABLE', '', $name, $tblcomment, true);

            if ($status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        if ('' !== $comment_sql) {
            $status = $this->execute($comment_sql);

            if ($status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Creates a new table in the database copying attribs and other properties from another table.
     *
     * @param string $name        The name of the table
     * @param array  $like        an array giving the schema ans the name of the table from which attribs are copying
     *                            from: array(
     *                            'table' => table name,
     *                            'schema' => the schema name,
     *                            )
     * @param bool   $defaults    if true, copy the defaults values as well
     * @param bool   $constraints if true, copy the constraints as well (CHECK on table & attr)
     * @param bool   $idx
     * @param string $tablespace  The tablespace name ('' means none/default)
     *
     * @return bool|int
     */
    public function createTableLike($name, $like, $defaults = false, $constraints = false, $idx = false, $tablespace = '')
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);
        $this->fieldClean($like['schema']);
        $this->fieldClean($like['table']);
        $like = \sprintf(
            '"%s"."%s"',
            $like['schema'],
            $like['table']
        );

        $status = $this->beginTransaction();

        if (0 !== $status) {
            return -1;
        }

        $sql = \sprintf(
            'CREATE TABLE "%s"."%s" (LIKE %s',
            $f_schema,
            $name,
            $like
        );

        if ($defaults) {
            $sql .= ' INCLUDING DEFAULTS';
        }

        if ($this->hasCreateTableLikeWithConstraints() && $constraints) {
            $sql .= ' INCLUDING CONSTRAINTS';
        }

        if ($this->hasCreateTableLikeWithIndexes() && $idx) {
            $sql .= ' INCLUDING INDEXES';
        }

        $sql .= ')';

        if ($this->hasTablespaces() && '' !== $tablespace) {
            $this->fieldClean($tablespace);
            $sql .= \sprintf(
                ' TABLESPACE "%s"',
                $tablespace
            );
        }

        $status = $this->execute($sql);

        if ($status) {
            $this->rollbackTransaction();

            return -1;
        }

        return $this->endTransaction();
    }

    /**
     * Alter table properties.
     *
     * @param string $table      The name of the table
     * @param string $name       The new name for the table
     * @param string $owner      The new owner for the table
     * @param string $schema     The new schema for the table
     * @param string $comment    The comment on the table
     * @param string $tablespace The new tablespace for the table ('' means leave as is)
     * @param bool   $with_oids  If set to FALSE, will drop oids column
     *
     * @return bool|int 0 success
     */
    public function alterTable($table, $name, $owner, $schema, $comment, $tablespace, bool $with_oids = true)
    {
        $tblrs = $this->getTable($table);

        if (1 !== $tblrs->RecordCount()) {
            return -2;
        }

        $status = $this->beginTransaction();
        //dump(['beginTransaction' => $status]);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }
        $sql_sentence = \sprintf(
            'ALTER TABLE "%s"."%s" ',
            $this->_schema,
            $tblrs->fields['relname']
        );
        $status = $this->_alterTable($tblrs, $name, $owner, $schema, $comment, $tablespace, !$with_oids, $sql_sentence);
        //dump(['_alterTable' => [$status, $sql_sentence.$this->getLastExecutedSQL()]]);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return $status;
        }

        return $this->endTransaction();
    }

    /**
     * Enables or disables the oid system column to a table a table's owner
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param ADORecordSet $tblrs       The table RecordSet returned by getTable()
     * @param null|string  $owner
     * @param bool         $withoutoids
     *
     * @return array{0:int,1:string} [status:0 if successful, change_sql: changed attribute]
     */
    public function alterTableOids($tblrs, bool $withoutoids = false): array
    {
        $status = 0;
        $change_sql = '';
        // no changes. Return 0
        if ((bool) ($this->hasObjectID($tblrs->fields['relname'])) !== !$withoutoids) {
            /* vars cleaned in _alterTable */
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);

            $alter_sql = \sprintf(
                'ALTER TABLE "%s"."%s" ',
                $f_schema,
                $tblrs->fields['relname']
            );
            $change_sql = '   SET ';

            if ($withoutoids) {
                $change_sql .= ' WITHOUT OIDS';
            } else {
                $change_sql .= ' WITH OIDS';
            }
            $sql = \implode(' ', [$alter_sql, $change_sql]);

            $status = $this->execute($sql);
        }

        return [$status, $change_sql];
    }

    /**
     * Alter a table's owner
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param ADORecordSet $tblrs The table RecordSet returned by getTable()
     * @param null|string  $owner
     *
     * @return array{0:int,1:string} [status:0 if successful, change_sql: changed attribute]
     */
    public function alterTableOwner($tblrs, $owner = null): array
    {
        $status = 0;
        $change_sql = '';
        /* vars cleaned in _alterTable */
        if (!empty($owner) && ($tblrs->fields['relowner'] !== $owner)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            // If owner has been changed, then do the alteration.  We are
            // careful to avoid this generally as changing owner is a
            // superuser only function.
            $alter_sql = \sprintf(
                'ALTER TABLE "%s"."%s" ',
                $f_schema,
                $tblrs->fields['relname']
            );
            $change_sql = \sprintf(
                ' OWNER TO "%s"',
                $owner
            );
            $sql = \implode(' ', [$alter_sql, $change_sql]);

            $status = $this->execute($sql);
        }

        return [$status, $change_sql];
    }

    /**
     * Alter a table's tablespace
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param ADORecordSet $tblrs      The table RecordSet returned by getTable()
     * @param null|string  $tablespace
     *
     * @return array{0:int,1:string} [status:0 if successful, change_sql: changed attribute]
     */
    public function alterTableTablespace($tblrs, $tablespace = null): array
    {
        $status = 0;
        $change_sql = '';
        /* vars cleaned in _alterTable */
        if (!empty($tablespace) && ($tblrs->fields['tablespace'] !== $tablespace)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);

            // If tablespace has been changed, then do the alteration.  We
            // don't want to do this unnecessarily.
            $alter_sql = \sprintf(
                'ALTER TABLE "%s"."%s" ',
                $f_schema,
                $tblrs->fields['relname']
            );
            $change_sql = \sprintf(
                ' SET TABLESPACE "%s"',
                $tablespace
            );
            $sql = \implode(' ', [$alter_sql, $change_sql]);

            $status = $this->execute($sql);
        }

        return [$status, $change_sql];
    }

    /**
     * Alter a table's name
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param ADORecordSet $tblrs The table RecordSet returned by getTable()
     * @param string       $name  The new table's name
     *
     *  @return array{0:int,1:string} [status:0 if successful, change_sql: changed attribute]
     */
    public function alterTableName($tblrs, $name = null): array
    {
        $status = 0;
        $change_sql = '';
        /* vars cleaned in _alterTable */
        // Rename (only if name has changed)
        if (!empty($name) && ($name !== $tblrs->fields['relname'])) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);

            $alter_sql = \sprintf(
                'ALTER TABLE "%s"."%s" ',
                $f_schema,
                $tblrs->fields['relname']
            );
            $change_sql = \sprintf(
                ' RENAME TO "%s"',
                $name
            );
            $sql = \implode(' ', [$alter_sql, $change_sql]);
            $status = $this->execute($sql);

            if (0 === $status) {
                $tblrs->fields['relname'] = $name;
            }
        }

        return [$status, $change_sql];
    }

    // Row functions

    /**
     * Alter a table's schema
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param ADORecordSet $tblrs  The table RecordSet returned by getTable()
     * @param null|string  $schema
     *
     *  @return array{0:int,1:string} [status:0 if successful, change_sql: changed attribute]
     */
    public function alterTableSchema($tblrs, $schema = null): array
    {
        $status = 0;
        $change_sql = '';
        /* vars cleaned in _alterTable */
        if (!empty($schema) && ($tblrs->fields['nspname'] !== $schema)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            // If tablespace has been changed, then do the alteration.  We
            // don't want to do this unnecessarily.
            $alter_sql = \sprintf(
                'ALTER TABLE "%s"."%s" ',
                $f_schema,
                $tblrs->fields['relname']
            );
            $change_sql = \sprintf(
                ' SET SCHEMA "%s"',
                $schema
            );
            $sql = \implode(' ', [$alter_sql, $change_sql]);

            $status = $this->execute($sql);
        }

        return [$status, $change_sql];
    }

    /**
     * Empties a table in the database.
     *
     * @param string $table   The table to be emptied
     * @param bool   $cascade True to cascade truncate, false to restrict
     *
     * @return array<integer,mixed|string> 0 if operation was successful
     */
    public function emptyTable($table, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        $sql = \sprintf(
            'TRUNCATE TABLE "%s"."%s" ',
            $f_schema,
            $table
        );

        if ($cascade) {
            $sql = $sql . ' CASCADE';
        }

        $status = $this->execute($sql);

        return [$status, $sql];
    }

    /**
     * Removes a table from the database.
     *
     * @param string $table   The table to drop
     * @param bool   $cascade True to cascade drop, false to restrict
     *
     * @return ADORecordSet|int
     */
    public function dropTable($table, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        $sql = \sprintf(
            'DROP TABLE "%s"."%s"',
            $f_schema,
            $table
        );

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Sets up the data object for a dump.  eg. Starts the appropriate
     * transaction, sets variables, etc.
     *
     * @return int 0 success
     */
    public function beginDump()
    {
        // Begin serializable transaction (to dump consistent data)
        $status = $this->beginTransaction();

        if (0 !== $status) {
            return -1;
        }

        // Set serializable
        $sql = 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE';
        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        // Set datestyle to ISO
        $sql = 'SET DATESTYLE = ISO';
        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        // Set extra_float_digits to 2
        $sql = 'SET extra_float_digits TO 2';
        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        return 0;
    }

    /**
     * Ends the data object for a dump.
     *
     * @return int 0 success
     */
    public function endDump()
    {
        return $this->endTransaction();
    }

    /**
     * Returns a recordset of all columns in a relation.  Used for data export.
     *
     * @@ Note: Really needs to use a cursor
     *
     * @param string $relation The name of a relation
     * @param bool   $oids     true to dump also the oids
     *
     * @return ADORecordSet|int
     */
    public function dumpRelation($relation, $oids)
    {
        $this->fieldClean($relation);

        // Actually retrieve the rows
        if ($oids) {
            $oid_str = $this->id . ', ';
        } else {
            $oid_str = '';
        }

        return $this->selectSet(\sprintf(
            'SELECT %s* FROM "%s"',
            $oid_str,
            $relation
        ));
    }

    /**
     * Returns all available autovacuum per table information.
     *
     * @param string $table if given, return autovacuum info for the given table or return all informations for all table
     *
     * @return ArrayRecordSet A recordset
     */
    public function getTableAutovacuum($table = '')
    {
        $sql = '';

        if ('' !== $table) {
            $this->clean($table);
            $c_schema = $this->_schema;
            $this->clean($c_schema);

            $sql = \sprintf(
                'SELECT c.oid, nspname, relname, pg_catalog.array_to_string(reloptions, E\',\') AS reloptions
                FROM pg_class c
                    LEFT JOIN pg_namespace n ON n.oid = c.relnamespace
                WHERE c.relkind = \'r\'::"char"
                    AND n.nspname NOT IN (\'pg_catalog\',\'information_schema\')
                    AND c.reloptions IS NOT NULL
                    AND c.relname = \'%s\' AND n.nspname = \'%s\'
                ORDER BY nspname, relname',
                $table,
                $c_schema
            );
        } else {
            $sql = "SELECT c.oid, nspname, relname, pg_catalog.array_to_string(reloptions, E',') AS reloptions
                FROM pg_class c
                    LEFT JOIN pg_namespace n ON n.oid = c.relnamespace
                WHERE c.relkind = 'r'::\"char\"
                    AND n.nspname NOT IN ('pg_catalog','information_schema')
                    AND c.reloptions IS NOT NULL
                ORDER BY nspname, relname";
        }

        /* tmp var to parse the results */
        $_autovacs = $this->selectSet($sql);

        /* result aray to return as RS */
        $autovacs = [];

        while (!$_autovacs->EOF) {
            $_ = [
                'nspname' => $_autovacs->fields['nspname'],
                'relname' => $_autovacs->fields['relname'],
            ];

            foreach (\explode(',', $_autovacs->fields['reloptions']) as $var) {
                [$o, $v] = \explode('=', $var);
                $_[$o] = $v;
            }

            $autovacs[] = $_;

            $_autovacs->MoveNext();
        }

        return new ArrayRecordSet($autovacs);
    }

    /**
     * Returns the SQL for changing the current user.
     *
     * @param string $user The user to change to
     *
     * @return string The SQL
     */
    public function getChangeUserSQL($user)
    {
        $this->clean($user);

        return \sprintf(
            'SET SESSION AUTHORIZATION \'%s\';',
            $user
        );
    }

    /**
     * Returns all available autovacuum per table information.
     *
     * @param string $table          table name
     * @param bool   $vacenabled     true if vacuum is enabled
     * @param int    $vacthreshold   vacuum threshold
     * @param int    $vacscalefactor vacuum scalefactor
     * @param int    $anathresold    analyze threshold
     * @param int    $anascalefactor analyze scale factor
     * @param int    $vaccostdelay   vacuum cost delay
     * @param int    $vaccostlimit   vacuum cost limit
     *
     * @return ADORecordSet|int
     */
    public function saveAutovacuum(
        $table,
        $vacenabled,
        $vacthreshold,
        $vacscalefactor,
        $anathresold,
        $anascalefactor,
        $vaccostdelay,
        $vaccostlimit
    ) {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        $params = [];

        $sql = \sprintf(
            'ALTER TABLE "%s"."%s" SET (',
            $f_schema,
            $table
        );

        if (!empty($vacenabled)) {
            $this->clean($vacenabled);
            $params[] = \sprintf(
                'autovacuum_enabled=\'%s\'',
                $vacenabled
            );
        }

        if (!empty($vacthreshold)) {
            $this->clean($vacthreshold);
            $params[] = \sprintf(
                'autovacuum_vacuum_threshold=\'%s\'',
                $vacthreshold
            );
        }

        if (!empty($vacscalefactor)) {
            $this->clean($vacscalefactor);
            $params[] = \sprintf(
                'autovacuum_vacuum_scale_factor=\'%s\'',
                $vacscalefactor
            );
        }

        if (!empty($anathresold)) {
            $this->clean($anathresold);
            $params[] = \sprintf(
                'autovacuum_analyze_threshold=\'%s\'',
                $anathresold
            );
        }

        if (!empty($anascalefactor)) {
            $this->clean($anascalefactor);
            $params[] = \sprintf(
                'autovacuum_analyze_scale_factor=\'%s\'',
                $anascalefactor
            );
        }

        if (!empty($vaccostdelay)) {
            $this->clean($vaccostdelay);
            $params[] = \sprintf(
                'autovacuum_vacuum_cost_delay=\'%s\'',
                $vaccostdelay
            );
        }

        if (!empty($vaccostlimit)) {
            $this->clean($vaccostlimit);
            $params[] = \sprintf(
                'autovacuum_vacuum_cost_limit=\'%s\'',
                $vaccostlimit
            );
        }

        $sql = $sql . \implode(',', $params) . ');';

        return $this->execute($sql);
    }

    // Type conversion routines

    /**
     * Drops autovacuum config for a table.
     *
     * @param string $table The table
     *
     * @return ADORecordSet|int
     */
    public function dropAutovacuum($table)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        return $this->execute(
            \sprintf(
                '
            ALTER TABLE "%s"."%s" RESET (autovacuum_enabled, autovacuum_vacuum_threshold,
                autovacuum_vacuum_scale_factor, autovacuum_analyze_threshold, autovacuum_analyze_scale_factor,
                autovacuum_vacuum_cost_delay, autovacuum_vacuum_cost_limit
            );',
                $f_schema,
                $table
            )
        );
    }

    abstract public function formatType($typname, $typmod);

    abstract public function hasGrantOption();

    abstract public function hasRoles();

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

    /**
     * Protected method which alter a table
     * SHOULDN'T BE CALLED OUTSIDE OF A TRANSACTION.
     *
     * @param ADORecordSet $tblrs       The table recordSet returned by getTable()
     * @param string       $name        The new name for the table
     * @param string       $owner       The new owner for the table
     * @param string       $schema      The new schema for the table
     * @param string       $comment     The comment on the table
     * @param string       $tablespace  The new tablespace for the table ('' means leave as is)
     * @param bool         $withoutoids If set to TRUE, will drop oids column
     *
     * @return int 0 success
     */
    protected function _alterTable($tblrs, $name, $owner, $schema, $comment, $tablespace, bool $withoutoids = false)
    {
        $this->fieldArrayClean($tblrs->fields);

        // Comment
        $status = $this->setComment('TABLE', '', $tblrs->fields['relname'], $comment);
        //dump(['setComment' => $this->getLastExecutedSQL()]);

        $this->lastExecutedSql = $this->getLastExecutedSQL();

        if (0 !== $status) {
            return -4;
        }

        // Owner
        $this->fieldClean($owner);
        [$TableOwnerStatus,$TableOwnerSQL] = $this->alterTableOwner($tblrs, $owner);
        $this->lastExecutedSql .= $TableOwnerSQL ? \sprintf(
            '%s%s',
            \PHP_EOL,
            $TableOwnerSQL
        ) : '';
        //dump(['alterTableOwner' => [$TableOwnerStatus,$TableOwnerSQL]]);

        if (0 !== $TableOwnerStatus) {
            return -5;
        }

        // Tablespace
        $this->fieldClean($tablespace);
        [$TableTablespaceStatus,$TableTablespaceSQL] = $this->alterTableTablespace($tblrs, $tablespace);
        $this->lastExecutedSql .= $TableTablespaceSQL ? \sprintf(
            '%s%s',
            \PHP_EOL,
            $TableTablespaceSQL
        ) : '';
        //dump(['alterTableTablespace' => [$TableTablespaceStatus,$TableTablespaceSQL]]);

        if (0 !== $TableTablespaceStatus) {
            return -6;
        }

        // Rename
        $this->fieldClean($name);
        [$TableNameStatus,$TableNameSQL] = $this->alterTableName($tblrs, $name);
        $this->lastExecutedSql .= $TableNameSQL ? \sprintf(
            '%s%s',
            \PHP_EOL,
            $TableNameSQL
        ) : '';
        //dump(['alterTableName' => [$TableNameStatus,$TableNameSQL]]);

        if (0 !== $TableNameStatus) {
            return -3;
        }

        // Schema
        $this->fieldClean($schema);
        [$TableSchemaStatus,$TableSchemaSQL] = $this->alterTableSchema($tblrs, $schema);
        $this->lastExecutedSql .= $TableSchemaSQL ? \sprintf(
            '%s%s',
            \PHP_EOL,
            $TableSchemaSQL
        ) : '';
        //dump(['alterTableSchema' => [$TableSchemaStatus,$TableSchemaSQL]]);

        if (0 !== $TableSchemaStatus) {
            return -7;
        }
        [$TableOidsStatus,$TableOidsSQL] = $this->alterTableOids($tblrs, $withoutoids);
        $this->lastExecutedSql .= $TableOidsSQL ? \sprintf(
            '%s%s',
            \PHP_EOL,
            $TableOidsSQL
        ) : '';
        //dump(['alterTableOids' => [$TableOidsStatus,$TableOidsSQL]]);
        if (0 !== $TableOidsStatus) {
            return -7;
        }

        return 0;
    }

    /**
     * Dumps serial-like columns in the table.
     *
     * @param ADORecordSet $atts             table attributes
     * @param ADORecordSet $tblfields        table fields object
     * @param string       $sql              The sql sentence
     *                                       generated so far
     * @param string       $col_comments_sql Column comments,
     *                                       passed by reference
     * @param int          $i                current counter to
     *                                       know if we should
     *                                       append a comma to the
     *                                       sentence
     * @param int          $num              Table attributes
     *                                       count + table
     *                                       constraints count
     *
     * @return string original $sql plus appended strings
     */
    private function _dumpSerials($atts, $tblfields, $sql, &$col_comments_sql, $i, $num)
    {
        while (!$atts->EOF) {
            $this->fieldClean($atts->fields['attname']);
            $sql .= \sprintf(
                '    "%s"',
                $atts->fields['attname']
            );
            // Dump SERIAL and BIGSERIAL columns correctly
            if ($this->phpBool($atts->fields['attisserial']) &&
                ('integer' === $atts->fields['type'] || 'bigint' === $atts->fields['type'])
            ) {
                if ('integer' === $atts->fields['type']) {
                    $sql .= ' SERIAL';
                } else {
                    $sql .= ' BIGSERIAL';
                }
            } else {
                $sql .= ' ' . $this->formatType($atts->fields['type'], $atts->fields['atttypmod']);

                // Add NOT NULL if necessary
                if ($this->phpBool($atts->fields['attnotnull'])) {
                    $sql .= ' NOT NULL';
                }

                // Add default if necessary
                if (null !== $atts->fields['adsrc']) {
                    $sql .= \sprintf(
                        ' DEFAULT %s',
                        $atts->fields['adsrc']
                    );
                }
            }

            // Output comma or not
            if ($i < $num) {
                $sql .= ",\n";
            } else {
                $sql .= "\n";
            }

            // Does this column have a comment?
            if (null !== $atts->fields['comment']) {
                $this->clean($atts->fields['comment']);
                $col_comments_sql .= \sprintf(
                    'COMMENT ON COLUMN "%s"."%s"  IS \'%s\';
',
                    $tblfields->fields['relname'],
                    $atts->fields['attname'],
                    $atts->fields['comment']
                );
            }

            $atts->MoveNext();
            ++$i;
        }

        return $sql;
    }

    /**
     * Dumps constraints.
     *
     * @param ADORecordSet $cons  The table constraints
     * @param string       $table The table to define
     * @param string       $sql   The sql sentence generated so
     *                            far
     * @param mixed        $i
     * @param int          $num   Table attributes count + table
     *                            constraints count
     */
    private function _dumpConstraints($cons, $table, $sql, $i, $num): ?string
    {
        // Output all table constraints
        while (!$cons->EOF) {
            $this->fieldClean($cons->fields['conname']);
            $sql .= \sprintf(
                '    CONSTRAINT "%s" ',
                $cons->fields['conname']
            );
            // Nasty hack to support pre-7.4 PostgreSQL
            if (null !== $cons->fields['consrc']) {
                $sql .= $cons->fields['consrc'];
            } else {
                switch ($cons->fields['contype']) {
                    case 'p':
                        $keys = $this->getAttributeNames($table, \explode(' ', $cons->fields['indkey']));
                        $sql .= 'PRIMARY KEY (' . \implode(',', $keys) . ')';

                        break;
                    case 'u':
                        $keys = $this->getAttributeNames($table, \explode(' ', $cons->fields['indkey']));
                        $sql .= 'UNIQUE (' . \implode(',', $keys) . ')';

                        break;

                    default:
                        // Unrecognised constraint
                        $this->rollbackTransaction();

                        return null;
                }
            }

            // Output comma or not
            if ($i < $num) {
                $sql .= ",\n";
            } else {
                $sql .= "\n";
            }

            $cons->MoveNext();
            ++$i;
        }

        return $sql;
    }

    /**
     * Dumps col statistics.
     *
     * @param ADORecordSet $atts      table attributes
     * @param ADORecordSet $tblfields table field attributes
     * @param string       $sql       The sql sentence generated so far
     */
    private function _dumpColStats($atts, $tblfields, $sql): ?string
    {
        // Column storage and statistics
        $atts->moveFirst();
        $first = true;

        while (!$atts->EOF) {
            $this->fieldClean($atts->fields['attname']);
            // Statistics first
            if (0 <= $atts->fields['attstattarget']) {
                if ($first) {
                    $sql .= "\n";
                    $first = false;
                }
                $sql .= \sprintf(
                    'ALTER TABLE ONLY "%s"."%s" ALTER COLUMN "%s" SET STATISTICS %s;
',
                    $tblfields->fields['nspname'],
                    $tblfields->fields['relname'],
                    $atts->fields['attname'],
                    $atts->fields['attstattarget']
                );
            }
            // Then storage
            if ($atts->fields['attstorage'] !== $atts->fields['typstorage']) {
                switch ($atts->fields['attstorage']) {
                    case 'p':
                        $storage = 'PLAIN';

                        break;
                    case 'e':
                        $storage = 'EXTERNAL';

                        break;
                    case 'm':
                        $storage = 'MAIN';

                        break;
                    case 'x':
                        $storage = 'EXTENDED';

                        break;

                    default:
                        // Unknown storage type
                        $this->rollbackTransaction();

                        return null;
                }
                $sql .= \sprintf(
                    'ALTER TABLE ONLY "%s"."%s" ALTER COLUMN "%s" SET STORAGE %s;
',
                    $tblfields->fields['nspname'],
                    $tblfields->fields['relname'],
                    $atts->fields['attname'],
                    $storage
                );
            }

            $atts->MoveNext();
        }

        return $sql;
    }

    /**
     * Dumps privileges.
     *
     * @param ADORecordSet $privs     The table privileges
     * @param ADORecordSet $tblfields The table fields definition
     * @param string       $sql       The sql sentence generated so far
     */
    private function _dumpPrivileges($privs, $tblfields, $sql): ?string
    {
        if (0 >= \count($privs)) {
            return $sql;
        }
        $sql .= "\n-- Privileges\n\n";
        /*
         * Always start with REVOKE ALL FROM PUBLIC, so that we don't have to
         * wire-in knowledge about the default public privileges for different
         * kinds of objects.
         */
        $sql .= \sprintf(
            'REVOKE ALL ON TABLE "%s"."%s" FROM PUBLIC;
',
            $tblfields->fields['nspname'],
            $tblfields->fields['relname']
        );

        foreach ($privs as $v) {
            // Get non-GRANT OPTION privs
            $nongrant = \array_diff($v[2], $v[4]);

            // Skip empty or owner ACEs
            if (0 === \count($v[2]) || ('user' === $v[0] && $v[1] === $tblfields->fields['relowner'])) {
                continue;
            }

            // Change user if necessary
            if ($this->hasGrantOption() && $v[3] !== $tblfields->fields['relowner']) {
                $grantor = $v[3];
                $this->clean($grantor);
                $sql .= \sprintf(
                    'SET SESSION AUTHORIZATION \'%s\';
',
                    $grantor
                );
            }

            // Output privileges with no GRANT OPTION
            $sql .= 'GRANT ' . \implode(', ', $nongrant) . \sprintf(
                ' ON TABLE "%s" TO ',
                $tblfields->fields['relname']
            );

            switch ($v[0]) {
                case 'public':
                    $sql .= "PUBLIC;\n";

                    break;
                case 'user':
                case 'role':
                    $this->fieldClean($v[1]);
                    $sql .= \sprintf(
                        '"%s";
',
                        $v[1]
                    );

                    break;
                case 'group':
                    $this->fieldClean($v[1]);
                    $sql .= \sprintf(
                        'GROUP "%s";
',
                        $v[1]
                    );

                    break;

                default:
                    // Unknown privilege type - fail
                    $this->rollbackTransaction();

                    return null;
            }

            // Reset user if necessary
            if ($this->hasGrantOption() && $v[3] !== $tblfields->fields['relowner']) {
                $sql .= "RESET SESSION AUTHORIZATION;\n";
            }

            // Output privileges with GRANT OPTION

            // Skip empty or owner ACEs
            if (!$this->hasGrantOption() || 0 === \count($v[4])) {
                continue;
            }

            // Change user if necessary
            if ($this->hasGrantOption() && $v[3] !== $tblfields->fields['relowner']) {
                $grantor = $v[3];
                $this->clean($grantor);
                $sql .= \sprintf(
                    'SET SESSION AUTHORIZATION \'%s\';
',
                    $grantor
                );
            }

            $sql .= 'GRANT ' . \implode(', ', $v[4]) . \sprintf(
                ' ON "%s" TO ',
                $tblfields->fields['relname']
            );

            switch ($v[0]) {
                case 'public':
                    $sql .= 'PUBLIC';

                    break;
                case 'user':
                case 'role':
                    $this->fieldClean($v[1]);
                    $sql .= \sprintf(
                        '"%s"',
                        $v[1]
                    );

                    break;
                case 'group':
                    $this->fieldClean($v[1]);
                    $sql .= \sprintf(
                        'GROUP "%s"',
                        $v[1]
                    );

                    break;

                default:
                    // Unknown privilege type - fail
                    return null;
            }
            $sql .= " WITH GRANT OPTION;\n";

            // Reset user if necessary
            if ($this->hasGrantOption() && $v[3] !== $tblfields->fields['relowner']) {
                $sql .= "RESET SESSION AUTHORIZATION;\n";
            }
        }

        return $sql;
    }

    /**
     * Dumps a create.
     *
     * @param ADORecordSet $tblfields   table fields object
     * @param string       $sql         The sql sentence generated so far
     * @param string       $cleanprefix set to '-- ' to avoid issuing DROP statement
     * @param mixed        $fields
     *
     * @return string original $sql plus appended strings
     */
    private function _dumpCreate($tblfields, $sql, $cleanprefix)
    {
        // Set schema search path
        $sql .= \sprintf(
            'SET search_path = "%s", pg_catalog;

',
            $tblfields->fields['nspname']
        );

        // Begin CREATE TABLE definition
        $sql .= "-- Definition\n\n";
        // DROP TABLE must be fully qualified in case a table with the same name exists
        $sql .= $cleanprefix . 'DROP TABLE ';
        $sql .= \sprintf(
            '"%s"."%s";
',
            $tblfields->fields['nspname'],
            $tblfields->fields['relname']
        );
        $sql .= \sprintf(
            'CREATE TABLE "%s"."%s" (
',
            $tblfields->fields['nspname'],
            $tblfields->fields['relname']
        );

        return $sql;
    }

    /**
     * Retrieve all attributes definition of a table.
     *
     * @param string $table    The name of the table
     * @param string $c_schema The name of the schema
     *
     * @return ADORecordSet|int
     */
    private function _getTableAttributesAll($table, $c_schema)
    {
        $sql = \sprintf(
            '
            SELECT
                a.attname,
                a.attnum,
                pg_catalog.format_type(a.atttypid, a.atttypmod) AS TYPE,
                a.atttypmod,
                a.attnotnull,
                a.atthasdef,
                pg_catalog.pg_get_expr(adef.adbin, adef.adrelid, TRUE) AS adsrc,
                a.attstattarget,
                a.attstorage,
                t.typstorage,
                CASE
                WHEN pc.oid IS NULL THEN FALSE
                ELSE TRUE
                END AS attisserial,
                pg_catalog.col_description(a.attrelid, a.attnum) AS COMMENT

            FROM pg_catalog.pg_tables tbl
            JOIN pg_catalog.pg_class tbl_class ON tbl.tablename=tbl_class.relname
            JOIN  pg_catalog.pg_attribute a ON tbl_class.oid = a.attrelid
            JOIN pg_catalog.pg_namespace    ON pg_namespace.oid = tbl_class.relnamespace
                                            AND pg_namespace.nspname=tbl.schemaname
            LEFT JOIN pg_catalog.pg_attrdef adef    ON a.attrelid=adef.adrelid
                                                    AND a.attnum=adef.adnum
            LEFT JOIN pg_catalog.pg_type t  ON a.atttypid=t.oid
            LEFT JOIN  pg_catalog.pg_depend pd  ON pd.refobjid=a.attrelid
                                                AND pd.refobjsubid=a.attnum
                                                AND pd.deptype=\'i\'
            LEFT JOIN pg_catalog.pg_class pc ON pd.objid=pc.oid
                                            AND pd.classid=pc.tableoid
                                            AND pd.refclassid=pc.tableoid
                                            AND pc.relkind=\'S\'
            WHERE tbl.tablename=\'%s\'
            AND tbl.schemaname=\'%s\'
            AND a.attnum > 0 AND NOT a.attisdropped
            ORDER BY a.attnum',
            $table,
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Retrieve single attribute definition of a table.
     *
     * @param string $table    The name of the table
     * @param string $c_schema The schema of the table
     * @param string $field    (optional) The name of a field to return
     *
     * @return ADORecordSet|int
     */
    private function _getTableAttribute($table, $c_schema, $field)
    {
        $sql = \sprintf(
            '
                SELECT
                    a.attname, a.attnum,
                    pg_catalog.format_type(a.atttypid, a.atttypmod) as type,
                    pg_catalog.format_type(a.atttypid, NULL) as base_type,
                    a.atttypmod,
                    a.attnotnull, a.atthasdef, pg_catalog.pg_get_expr(adef.adbin, adef.adrelid, true) as adsrc,
                    a.attstattarget, a.attstorage, t.typstorage,
                    pg_catalog.col_description(a.attrelid, a.attnum) AS comment
                FROM
                    pg_catalog.pg_attribute a LEFT JOIN pg_catalog.pg_attrdef adef
                    ON a.attrelid=adef.adrelid
                    AND a.attnum=adef.adnum
                    LEFT JOIN pg_catalog.pg_type t ON a.atttypid=t.oid
                WHERE
                    a.attrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=\'%s\'
                        AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE
                        nspname = \'%s\'))
                    AND a.attname = \'%s\'',
            $table,
            $c_schema,
            $field
        );

        return $this->selectSet($sql);
    }
}
