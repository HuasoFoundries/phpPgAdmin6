<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Traits;

/**
 * Common trait for tables manipulation.
 */
trait TableTrait
{
    /**
     * Return all tables in current database (and schema).
     *
     * @param bool|true $all True to fetch all tables, false for just in current schema
     *
     * @return \PHPPgAdmin\ADORecordSet All tables, sorted alphabetically
     */
    public function getTables($all = false)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        if ($all) {
            // Exclude pg_catalog and information_schema tables
            $sql = "SELECT
                        schemaname AS nspname,
                        tablename AS relname,
                        tableowner AS relowner
                    FROM pg_catalog.pg_tables
                    WHERE schemaname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')
                    ORDER BY schemaname, tablename";
        } else {
            $sql = "
                SELECT c.relname,
                    pg_catalog.pg_get_userbyid(c.relowner) AS relowner,
                    pg_catalog.obj_description(c.oid, 'pg_class') AS relcomment,
                    reltuples::bigint,
                    pt.spcname as tablespace,
                    pg_size_pretty(pg_total_relation_size(c.oid)) as table_size
                FROM pg_catalog.pg_class c
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                LEFT JOIN  pg_catalog.pg_tablespace pt ON  pt.oid=c.reltablespace
                WHERE c.relkind = 'r'
                AND nspname='{$c_schema}'
                ORDER BY c.relname";
        }

        return $this->selectSet($sql);
    }

    /**
     * Finds the names and schemas of parent tables (in order).
     *
     * @param string $table The table to find the parents for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTableParents($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "
            SELECT
                pn.nspname, relname
            FROM
                pg_catalog.pg_class pc, pg_catalog.pg_inherits pi, pg_catalog.pg_namespace pn
            WHERE
                pc.oid=pi.inhparent
                AND pc.relnamespace=pn.oid
                AND pi.inhrelid = (SELECT oid from pg_catalog.pg_class WHERE relname='{$table}'
                    AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname = '{$c_schema}'))
            ORDER BY
                pi.inhseqno
        ";

        return $this->selectSet($sql);
    }

    /**
     * Finds the names and schemas of child tables.
     *
     * @param string $table The table to find the children for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTableChildren($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "
            SELECT
                pn.nspname, relname
            FROM
                pg_catalog.pg_class pc, pg_catalog.pg_inherits pi, pg_catalog.pg_namespace pn
            WHERE
                pc.oid=pi.inhrelid
                AND pc.relnamespace=pn.oid
                AND pi.inhparent = (SELECT oid from pg_catalog.pg_class WHERE relname='{$table}'
                    AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname = '{$c_schema}'))
        ";

        return $this->selectSet($sql);
    }

    /**
     * Returns the SQL definition for the table.
     * MUST be run within a transaction.
     *
     * @param string    $table The table to define
     * @param bool|true $clean True to issue drop command, false otherwise
     *
     * @return string A string containing the formatted SQL code
     */
    public function getTableDefPrefix($table, $clean = false)
    {
        // Fetch table
        $t = $this->getTable($table);
        if (!is_object($t) || $t->recordCount() != 1) {
            $this->rollbackTransaction();

            return null;
        }
        $this->fieldClean($t->fields['relname']);
        $this->fieldClean($t->fields['nspname']);

        // Fetch attributes
        $atts = $this->getTableAttributes($table);
        if (!is_object($atts)) {
            $this->rollbackTransaction();

            return null;
        }

        // Fetch constraints
        $cons = $this->getConstraints($table);
        if (!is_object($cons)) {
            $this->rollbackTransaction();

            return null;
        }

        // Output a reconnect command to create the table as the correct user
        $sql = $this->getChangeUserSQL($t->fields['relowner']) . "\n\n";

        // Set schema search path
        $sql .= "SET search_path = \"{$t->fields['nspname']}\", pg_catalog;\n\n";

        // Begin CREATE TABLE definition
        $sql .= "-- Definition\n\n";
        // DROP TABLE must be fully qualified in case a table with the same name exists
        // in pg_catalog.
        if (!$clean) {
            $sql .= '-- ';
        }

        $sql .= 'DROP TABLE ';
        $sql .= "\"{$t->fields['nspname']}\".\"{$t->fields['relname']}\";\n";
        $sql .= "CREATE TABLE \"{$t->fields['nspname']}\".\"{$t->fields['relname']}\" (\n";

        // Output all table columns
        $col_comments_sql = ''; // Accumulate comments on columns
        $num              = $atts->recordCount() + $cons->recordCount();
        $i                = 1;
        while (!$atts->EOF) {
            $this->fieldClean($atts->fields['attname']);
            $sql .= "    \"{$atts->fields['attname']}\"";
            // Dump SERIAL and BIGSERIAL columns correctly
            if ($this->phpBool($atts->fields['attisserial']) &&
                ($atts->fields['type'] == 'integer' || $atts->fields['type'] == 'bigint')) {
                if ($atts->fields['type'] == 'integer') {
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
                if ($atts->fields['adsrc'] !== null) {
                    $sql .= " DEFAULT {$atts->fields['adsrc']}";
                }
            }

            // Output comma or not
            if ($i < $num) {
                $sql .= ",\n";
            } else {
                $sql .= "\n";
            }

            // Does this column have a comment?
            if ($atts->fields['comment'] !== null) {
                $this->clean($atts->fields['comment']);
                $col_comments_sql .= "COMMENT ON COLUMN \"{$t->fields['relname']}\".\"{$atts->fields['attname']}\"  IS '{$atts->fields['comment']}';\n";
            }

            $atts->moveNext();
            ++$i;
        }
        // Output all table constraints
        while (!$cons->EOF) {
            $this->fieldClean($cons->fields['conname']);
            $sql .= "    CONSTRAINT \"{$cons->fields['conname']}\" ";
            // Nasty hack to support pre-7.4 PostgreSQL
            if ($cons->fields['consrc'] !== null) {
                $sql .= $cons->fields['consrc'];
            } else {
                switch ($cons->fields['contype']) {
                    case 'p':
                        $keys = $this->getAttributeNames($table, explode(' ', $cons->fields['indkey']));
                        $sql .= 'PRIMARY KEY (' . join(',', $keys) . ')';

                        break;
                    case 'u':
                        $keys = $this->getAttributeNames($table, explode(' ', $cons->fields['indkey']));
                        $sql .= 'UNIQUE (' . join(',', $keys) . ')';

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

            $cons->moveNext();
            ++$i;
        }

        $sql .= ')';

        // @@@@ DUMP CLUSTERING INFORMATION

        // Inherits
        /**
         * XXX: This is currently commented out as handling inheritance isn't this simple.
         * You also need to make sure you don't dump inherited columns and defaults, as well
         * as inherited NOT NULL and CHECK constraints.  So for the time being, we just do
         * not claim to support inheritance.
         * $parents = $this->getTableParents($table);
         * if ($parents->recordCount() > 0) {
         * $sql .= " INHERITS (";
         * while (!$parents->EOF) {
         * $this->fieldClean($parents->fields['relname']);
         * // Qualify the parent table if it's in another schema
         * if ($parents->fields['schemaname'] != $this->_schema) {
         * $this->fieldClean($parents->fields['schemaname']);
         * $sql .= "\"{$parents->fields['schemaname']}\".";
         * }
         * $sql .= "\"{$parents->fields['relname']}\"";.
         *
         * $parents->moveNext();
         * if (!$parents->EOF) $sql .= ', ';
         * }
         * $sql .= ")";
         * }
         */

        // Handle WITHOUT OIDS
        if ($this->hasObjectID($table)) {
            $sql .= ' WITH OIDS';
        } else {
            $sql .= ' WITHOUT OIDS';
        }

        $sql .= ";\n";

        // Column storage and statistics
        $atts->moveFirst();
        $first = true;
        while (!$atts->EOF) {
            $this->fieldClean($atts->fields['attname']);
            // Statistics first
            if ($atts->fields['attstattarget'] >= 0) {
                if ($first) {
                    $sql .= "\n";
                    $first = false;
                }
                $sql .= "ALTER TABLE ONLY \"{$t->fields['nspname']}\".\"{$t->fields['relname']}\" ALTER COLUMN \"{$atts->fields['attname']}\" SET STATISTICS {$atts->fields['attstattarget']};\n";
            }
            // Then storage
            if ($atts->fields['attstorage'] != $atts->fields['typstorage']) {
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
                $sql .= "ALTER TABLE ONLY \"{$t->fields['nspname']}\".\"{$t->fields['relname']}\" ALTER COLUMN \"{$atts->fields['attname']}\" SET STORAGE {$storage};\n";
            }

            $atts->moveNext();
        }

        // Comment
        if ($t->fields['relcomment'] !== null) {
            $this->clean($t->fields['relcomment']);
            $sql .= "\n-- Comment\n\n";
            $sql .= "COMMENT ON TABLE \"{$t->fields['nspname']}\".\"{$t->fields['relname']}\" IS '{$t->fields['relcomment']}';\n";
        }

        // Add comments on columns, if any
        if ($col_comments_sql != '') {
            $sql .= $col_comments_sql;
        }

        // Privileges
        $privs = $this->getPrivileges($table, 'table');
        if (!is_array($privs)) {
            $this->rollbackTransaction();

            return null;
        }

        if (sizeof($privs) > 0) {
            $sql .= "\n-- Privileges\n\n";
            /*
             * Always start with REVOKE ALL FROM PUBLIC, so that we don't have to
             * wire-in knowledge about the default public privileges for different
             * kinds of objects.
             */
            $sql .= "REVOKE ALL ON TABLE \"{$t->fields['nspname']}\".\"{$t->fields['relname']}\" FROM PUBLIC;\n";
            foreach ($privs as $v) {
                // Get non-GRANT OPTION privs
                $nongrant = array_diff($v[2], $v[4]);

                // Skip empty or owner ACEs
                if (sizeof($v[2]) == 0 || ($v[0] == 'user' && $v[1] == $t->fields['relowner'])) {
                    continue;
                }

                // Change user if necessary
                if ($this->hasGrantOption() && $v[3] != $t->fields['relowner']) {
                    $grantor = $v[3];
                    $this->clean($grantor);
                    $sql .= "SET SESSION AUTHORIZATION '{$grantor}';\n";
                }

                // Output privileges with no GRANT OPTION
                $sql .= 'GRANT ' . join(', ', $nongrant) . " ON TABLE \"{$t->fields['relname']}\" TO ";
                switch ($v[0]) {
                    case 'public':
                        $sql .= "PUBLIC;\n";

                        break;
                    case 'user':
                        $this->fieldClean($v[1]);
                        $sql .= "\"{$v[1]}\";\n";

                        break;
                    case 'group':
                        $this->fieldClean($v[1]);
                        $sql .= "GROUP \"{$v[1]}\";\n";

                        break;
                    default:
                        // Unknown privilege type - fail
                        $this->rollbackTransaction();

                        return null;
                }

                // Reset user if necessary
                if ($this->hasGrantOption() && $v[3] != $t->fields['relowner']) {
                    $sql .= "RESET SESSION AUTHORIZATION;\n";
                }

                // Output privileges with GRANT OPTION

                // Skip empty or owner ACEs
                if (!$this->hasGrantOption() || sizeof($v[4]) == 0) {
                    continue;
                }

                // Change user if necessary
                if ($this->hasGrantOption() && $v[3] != $t->fields['relowner']) {
                    $grantor = $v[3];
                    $this->clean($grantor);
                    $sql .= "SET SESSION AUTHORIZATION '{$grantor}';\n";
                }

                $sql .= 'GRANT ' . join(', ', $v[4]) . " ON \"{$t->fields['relname']}\" TO ";
                switch ($v[0]) {
                    case 'public':
                        $sql .= 'PUBLIC';

                        break;
                    case 'user':
                        $this->fieldClean($v[1]);
                        $sql .= "\"{$v[1]}\"";

                        break;
                    case 'group':
                        $this->fieldClean($v[1]);
                        $sql .= "GROUP \"{$v[1]}\"";

                        break;
                    default:
                        // Unknown privilege type - fail
                        return null;
                }
                $sql .= " WITH GRANT OPTION;\n";

                // Reset user if necessary
                if ($this->hasGrantOption() && $v[3] != $t->fields['relowner']) {
                    $sql .= "RESET SESSION AUTHORIZATION;\n";
                }
            }
        }

        // Add a newline to separate data that follows (if any)
        $sql .= "\n";

        return $sql;
    }

    /**
     * Returns table information.
     *
     * @param string $table The name of the table
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTable($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "
            SELECT
              c.relname, n.nspname, u.usename AS relowner,
              pg_catalog.obj_description(c.oid, 'pg_class') AS relcomment,
              (SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=c.reltablespace) AS tablespace
            FROM pg_catalog.pg_class c
                 LEFT JOIN pg_catalog.pg_user u ON u.usesysid = c.relowner
                 LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE c.relkind = 'r'
                  AND n.nspname = '{$c_schema}'
                  AND n.oid = c.relnamespace
                  AND c.relname = '{$table}'";

        return $this->selectSet($sql);
    }

    /**
     * Retrieve the attribute definition of a table.
     *
     * @param string $table The name of the table
     * @param string $field (optional) The name of a field to return
     *
     * @return \PHPPgAdmin\ADORecordSet All attributes in order
     */
    public function getTableAttributes($table, $field = '')
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);
        $this->clean($field);

        if ($field == '') {
            // This query is made much more complex by the addition of the 'attisserial' field.
            // The subquery to get that field checks to see if there is an internally dependent
            // sequence on the field.
            $sql = "
                SELECT
                    a.attname, a.attnum,
                    pg_catalog.format_type(a.atttypid, a.atttypmod) as type,
                    a.atttypmod,
                    a.attnotnull, a.atthasdef, pg_catalog.pg_get_expr(adef.adbin, adef.adrelid, true) as adsrc,
                    a.attstattarget, a.attstorage, t.typstorage,
                    (
                        SELECT 1 FROM pg_catalog.pg_depend pd, pg_catalog.pg_class pc
                        WHERE pd.objid=pc.oid
                        AND pd.classid=pc.tableoid
                        AND pd.refclassid=pc.tableoid
                        AND pd.refobjid=a.attrelid
                        AND pd.refobjsubid=a.attnum
                        AND pd.deptype='i'
                        AND pc.relkind='S'
                    ) IS NOT NULL AS attisserial,
                    pg_catalog.col_description(a.attrelid, a.attnum) AS comment
                FROM
                    pg_catalog.pg_attribute a LEFT JOIN pg_catalog.pg_attrdef adef
                    ON a.attrelid=adef.adrelid
                    AND a.attnum=adef.adnum
                    LEFT JOIN pg_catalog.pg_type t ON a.atttypid=t.oid
                WHERE
                    a.attrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname='{$table}'
                        AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE
                        nspname = '{$c_schema}'))
                    AND a.attnum > 0 AND NOT a.attisdropped
                ORDER BY a.attnum";
        } else {
            $sql = "
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
                    a.attrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname='{$table}'
                        AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE
                        nspname = '{$c_schema}'))
                    AND a.attname = '{$field}'";
        }

        return $this->selectSet($sql);
    }

    /**
     * Returns a list of all constraints on a table.
     *
     * @param string $table The table to find rules for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getConstraints($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        // This SQL is greatly complicated by the need to retrieve
        // index clustering information for primary and unique constraints
        $sql = "SELECT
                pc.conname,
                pg_catalog.pg_get_constraintdef(pc.oid, true) AS consrc,
                pc.contype,
                CASE WHEN pc.contype='u' OR pc.contype='p' THEN (
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
                pc.conrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname='{$table}'
                    AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace
                    WHERE nspname='{$c_schema}'))
            ORDER BY
                1
        ";

        return $this->selectSet($sql);
    }

    /**
     * Checks to see whether or not a table has a unique id column.
     *
     * @param string $table The table name
     *
     * @return true if it has a unique id, false otherwise
     */
    public function hasObjectID($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "SELECT relhasoids FROM pg_catalog.pg_class WHERE relname='{$table}'
            AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='{$c_schema}')";

        $rs = $this->selectSet($sql);
        if ($rs->recordCount() != 1) {
            return null;
        }

        $rs->fields['relhasoids'] = $this->phpBool($rs->fields['relhasoids']);

        return $rs->fields['relhasoids'];
    }

    /**
     * Returns extra table definition information that is most usefully
     * dumped after the table contents for speed and efficiency reasons.
     *
     * @param string $table The table to define
     *
     * @return string A string containing the formatted SQL code
     */
    public function getTableDefSuffix($table)
    {
        $sql = '';

        // Indexes
        $indexes = $this->getIndexes($table);
        if (!is_object($indexes)) {
            $this->rollbackTransaction();

            return null;
        }

        if ($indexes->recordCount() > 0) {
            $sql .= "\n-- Indexes\n\n";
            while (!$indexes->EOF) {
                $sql .= $indexes->fields['inddef'] . ";\n";

                $indexes->moveNext();
            }
        }

        // Triggers
        $triggers = $this->getTriggers($table);
        if (!is_object($triggers)) {
            $this->rollbackTransaction();

            return null;
        }

        if ($triggers->recordCount() > 0) {
            $sql .= "\n-- Triggers\n\n";
            while (!$triggers->EOF) {
                $sql .= $triggers->fields['tgdef'];
                $sql .= ";\n";

                $triggers->moveNext();
            }
        }

        // Rules
        $rules = $this->getRules($table);
        if (!is_object($rules)) {
            $this->rollbackTransaction();

            return null;
        }

        if ($rules->recordCount() > 0) {
            $sql .= "\n-- Rules\n\n";
            while (!$rules->EOF) {
                $sql .= $rules->fields['definition'] . "\n";

                $rules->moveNext();
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
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getIndexes($table = '', $unique = false)
    {
        $this->clean($table);

        $sql = "
            SELECT c2.relname AS indname, i.indisprimary, i.indisunique, i.indisclustered,
                pg_catalog.pg_get_indexdef(i.indexrelid, 0, true) AS inddef
            FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i
            WHERE c.relname = '{$table}' AND pg_catalog.pg_table_is_visible(c.oid)
                AND c.oid = i.indrelid AND i.indexrelid = c2.oid
        ";
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
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTriggers($table = '')
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "SELECT
                t.tgname, pg_catalog.pg_get_triggerdef(t.oid) AS tgdef,
                CASE WHEN t.tgenabled = 'D' THEN FALSE ELSE TRUE END AS tgenabled, p.oid AS prooid,
                p.proname || ' (' || pg_catalog.oidvectortypes(p.proargtypes) || ')' AS proproto,
                ns.nspname AS pronamespace
            FROM pg_catalog.pg_trigger t, pg_catalog.pg_proc p, pg_catalog.pg_namespace ns
            WHERE t.tgrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname='{$table}'
                AND relnamespace=(SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='{$c_schema}'))
                AND ( tgconstraint = 0 OR NOT EXISTS
                        (SELECT 1 FROM pg_catalog.pg_depend d    JOIN pg_catalog.pg_constraint c
                            ON (d.refclassid = c.tableoid AND d.refobjid = c.oid)
                        WHERE d.classid = t.tableoid AND d.objid = t.oid AND d.deptype = 'i' AND c.contype = 'f'))
                AND p.oid=t.tgfoid
                AND p.pronamespace = ns.oid";

        return $this->selectSet($sql);
    }

    /**
     * Returns a list of all rules on a table OR view.
     *
     * @param string $table The table to find rules for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getRules($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "
            SELECT *
            FROM pg_catalog.pg_rules
            WHERE
                schemaname='{$c_schema}' AND tablename='{$table}'
            ORDER BY rulename
        ";

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
        if ($status != 0) {
            return -1;
        }

        $found       = false;
        $first       = true;
        $comment_sql = ''; //Accumulate comments for the columns
        $sql         = "CREATE TABLE \"{$f_schema}\".\"{$name}\" (";
        for ($i = 0; $i < $fields; ++$i) {
            $this->fieldClean($field[$i]);
            $this->clean($type[$i]);
            $this->clean($length[$i]);
            $this->clean($colcomment[$i]);

            // Skip blank columns - for user convenience
            if ($field[$i] == '' || $type[$i] == '') {
                continue;
            }

            // If not the first column, add a comma
            if (!$first) {
                $sql .= ', ';
            } else {
                $first = false;
            }

            switch ($type[$i]) {
                // Have to account for weird placing of length for with/without
                // time zone types
                case 'timestamp with time zone':
                case 'timestamp without time zone':
                    $qual = substr($type[$i], 9);
                    $sql .= "\"{$field[$i]}\" timestamp";
                    if ($length[$i] != '') {
                        $sql .= "({$length[$i]})";
                    }

                    $sql .= $qual;

                    break;
                case 'time with time zone':
                case 'time without time zone':
                    $qual = substr($type[$i], 4);
                    $sql .= "\"{$field[$i]}\" time";
                    if ($length[$i] != '') {
                        $sql .= "({$length[$i]})";
                    }

                    $sql .= $qual;

                    break;
                default:
                    $sql .= "\"{$field[$i]}\" {$type[$i]}";
                    if ($length[$i] != '') {
                        $sql .= "({$length[$i]})";
                    }
            }
            // Add array qualifier if necessary
            if ($array[$i] == '[]') {
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
            if ($default[$i] != '') {
                $sql .= " DEFAULT {$default[$i]}";
            }

            if ($colcomment[$i] != '') {
                $comment_sql .= "COMMENT ON COLUMN \"{$name}\".\"{$field[$i]}\" IS '{$colcomment[$i]}';\n";
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
                $primarykeycolumns[] = "\"{$field[$i]}\"";
            }
        }
        if (count($primarykeycolumns) > 0) {
            $sql .= ', PRIMARY KEY (' . implode(', ', $primarykeycolumns) . ')';
        }

        $sql .= ')';

        // WITHOUT OIDS
        if ($withoutoids) {
            $sql .= ' WITHOUT OIDS';
        } else {
            $sql .= ' WITH OIDS';
        }

        // Tablespace
        if ($this->hasTablespaces() && $tablespace != '') {
            $this->fieldClean($tablespace);
            $sql .= " TABLESPACE \"{$tablespace}\"";
        }

        $status = $this->execute($sql);
        if ($status) {
            $this->rollbackTransaction();

            return -1;
        }

        if ($tblcomment != '') {
            $status = $this->setComment('TABLE', '', $name, $tblcomment, true);
            if ($status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        if ($comment_sql != '') {
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
        $like = "\"{$like['schema']}\".\"{$like['table']}\"";

        $status = $this->beginTransaction();
        if ($status != 0) {
            return -1;
        }

        $sql = "CREATE TABLE \"{$f_schema}\".\"{$name}\" (LIKE {$like}";

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

        if ($this->hasTablespaces() && $tablespace != '') {
            $this->fieldClean($tablespace);
            $sql .= " TABLESPACE \"{$tablespace}\"";
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
     *
     * @return bool|int 0 success
     */
    public function alterTable($table, $name, $owner, $schema, $comment, $tablespace)
    {
        $data = $this->getTable($table);

        if ($data->recordCount() != 1) {
            return -2;
        }

        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $status = $this->_alterTable($data, $name, $owner, $schema, $comment, $tablespace);

        if ($status != 0) {
            $this->rollbackTransaction();

            return $status;
        }

        return $this->endTransaction();
    }

    /**
     * Protected method which alter a table
     * SHOULDN'T BE CALLED OUTSIDE OF A TRANSACTION.
     *
     * @param \PHPPgAdmin\ADORecordSet $tblrs      The table recordSet returned by getTable()
     * @param string                   $name       The new name for the table
     * @param string                   $owner      The new owner for the table
     * @param string                   $schema     The new schema for the table
     * @param string                   $comment    The comment on the table
     * @param string                   $tablespace The new tablespace for the table ('' means leave as is)
     *
     * @return int 0 success
     */
    protected function _alterTable($tblrs, $name, $owner, $schema, $comment, $tablespace)
    {
        $this->fieldArrayClean($tblrs->fields);

        // Comment
        $status = $this->setComment('TABLE', '', $tblrs->fields['relname'], $comment);
        if ($status != 0) {
            return -4;
        }

        // Owner
        $this->fieldClean($owner);
        $status = $this->alterTableOwner($tblrs, $owner);
        if ($status != 0) {
            return -5;
        }

        // Tablespace
        $this->fieldClean($tablespace);
        $status = $this->alterTableTablespace($tblrs, $tablespace);
        if ($status != 0) {
            return -6;
        }

        // Rename
        $this->fieldClean($name);
        $status = $this->alterTableName($tblrs, $name);
        if ($status != 0) {
            return -3;
        }

        // Schema
        $this->fieldClean($schema);
        $status = $this->alterTableSchema($tblrs, $schema);
        if ($status != 0) {
            return -7;
        }

        return 0;
    }

    /**
     * Alter a table's owner
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param \PHPPgAdmin\ADORecordSet $tblrs The table RecordSet returned by getTable()
     * @param null|string              $owner
     *
     * @return int 0 if operation was successful
     */
    public function alterTableOwner($tblrs, $owner = null)
    {
        /* vars cleaned in _alterTable */
        if (!empty($owner) && ($tblrs->fields['relowner'] != $owner)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            // If owner has been changed, then do the alteration.  We are
            // careful to avoid this generally as changing owner is a
            // superuser only function.
            $sql = "ALTER TABLE \"{$f_schema}\".\"{$tblrs->fields['relname']}\" OWNER TO \"{$owner}\"";

            return $this->execute($sql);
        }

        return 0;
    }

    /**
     * Alter a table's tablespace
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param \PHPPgAdmin\ADORecordSet $tblrs      The table RecordSet returned by getTable()
     * @param null|string              $tablespace
     *
     * @return int 0 if operation was successful
     */
    public function alterTableTablespace($tblrs, $tablespace = null)
    {
        /* vars cleaned in _alterTable */
        if (!empty($tablespace) && ($tblrs->fields['tablespace'] != $tablespace)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);

            // If tablespace has been changed, then do the alteration.  We
            // don't want to do this unnecessarily.
            $sql = "ALTER TABLE \"{$f_schema}\".\"{$tblrs->fields['relname']}\" SET TABLESPACE \"{$tablespace}\"";

            return $this->execute($sql);
        }

        return 0;
    }

    /**
     * Alter a table's name
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param \PHPPgAdmin\ADORecordSet $tblrs The table RecordSet returned by getTable()
     * @param string                   $name  The new table's name
     *
     * @return int 0 if operation was successful
     */
    public function alterTableName($tblrs, $name = null)
    {
        /* vars cleaned in _alterTable */
        // Rename (only if name has changed)
        if (!empty($name) && ($name != $tblrs->fields['relname'])) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);

            $sql    = "ALTER TABLE \"{$f_schema}\".\"{$tblrs->fields['relname']}\" RENAME TO \"{$name}\"";
            $status = $this->execute($sql);
            if ($status == 0) {
                $tblrs->fields['relname'] = $name;
            } else {
                return $status;
            }
        }

        return 0;
    }

    // Row functions

    /**
     * Alter a table's schema
     * /!\ this function is called from _alterTable which take care of escaping fields.
     *
     * @param \PHPPgAdmin\ADORecordSet $tblrs  The table RecordSet returned by getTable()
     * @param null|string              $schema
     *
     * @return int 0 if operation was successful
     */
    public function alterTableSchema($tblrs, $schema = null)
    {
        /* vars cleaned in _alterTable */
        if (!empty($schema) && ($tblrs->fields['nspname'] != $schema)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            // If tablespace has been changed, then do the alteration.  We
            // don't want to do this unnecessarily.
            $sql = "ALTER TABLE \"{$f_schema}\".\"{$tblrs->fields['relname']}\" SET SCHEMA \"{$schema}\"";

            return $this->execute($sql);
        }

        return 0;
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

        $sql = "TRUNCATE TABLE \"{$f_schema}\".\"{$table}\" ";
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
     * @return int 0 if operation was successful
     */
    public function dropTable($table, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        $sql = "DROP TABLE \"{$f_schema}\".\"{$table}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

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
     * @return bool|int 0 success
     */
    public function addColumn($table, $column, $type, $array, $length, $notnull, $default, $comment)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);
        $this->clean($type);
        $this->clean($length);

        if ($length == '') {
            $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ADD COLUMN \"{$column}\" {$type}";
        } else {
            switch ($type) {
                // Have to account for weird placing of length for with/without
                // time zone types
                case 'timestamp with time zone':
                case 'timestamp without time zone':
                    $qual = substr($type, 9);
                    $sql  = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ADD COLUMN \"{$column}\" timestamp({$length}){$qual}";

                    break;
                case 'time with time zone':
                case 'time without time zone':
                    $qual = substr($type, 4);
                    $sql  = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ADD COLUMN \"{$column}\" time({$length}){$qual}";

                    break;
                default:
                    $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ADD COLUMN \"{$column}\" {$type}({$length})";
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
            if ($default != '') {
                $sql .= ' DEFAULT ' . $default;
            }
        }

        $status = $this->beginTransaction();
        if ($status != 0) {
            return -1;
        }

        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $status = $this->setComment('COLUMN', $column, $table, $comment);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        return $this->endTransaction();
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
     * @return array 0 success
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
        $sql    = '';
        if ($status != 0) {
            $this->rollbackTransaction();

            return [-6, $sql];
        }

        // Rename the column, if it has been changed
        if ($column != $name) {
            $status = $this->renameColumn($table, $column, $name);
            if ($status != 0) {
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
        if ($notnull != $oldnotnull) {
            $toAlter[] = "ALTER COLUMN \"{$name}\" " . ($notnull ? 'SET' : 'DROP') . ' NOT NULL';
        }

        // Add default, if it has changed
        if ($default != $olddefault) {
            if ($default == '') {
                $toAlter[] = "ALTER COLUMN \"{$name}\" DROP DEFAULT";
            } else {
                $toAlter[] = "ALTER COLUMN \"{$name}\" SET DEFAULT {$default}";
            }
        }

        // Add type, if it has changed
        if ($length == '') {
            $ftype = $type;
        } else {
            switch ($type) {
                // Have to account for weird placing of length for with/without
                // time zone types
                case 'timestamp with time zone':
                case 'timestamp without time zone':
                    $qual  = substr($type, 9);
                    $ftype = "timestamp({$length}){$qual}";

                    break;
                case 'time with time zone':
                case 'time without time zone':
                    $qual  = substr($type, 4);
                    $ftype = "time({$length}){$qual}";

                    break;
                default:
                    $ftype = "{$type}({$length})";
            }
        }

        // Add array qualifier, if requested
        if ($array) {
            $ftype .= '[]';
        }

        if ($ftype != $oldtype) {
            $toAlter[] = "ALTER COLUMN \"{$name}\" TYPE {$ftype}";
        }

        // Attempt to process the batch alteration, if anything has been changed
        if (!empty($toAlter)) {
            // Initialise an empty SQL string
            $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" "
            . implode(',', $toAlter);

            $status = $this->execute($sql);
            if ($status != 0) {
                $this->rollbackTransaction();

                return [-1, $sql];
            }
        }

        // Update the comment on the column
        $status = $this->setComment('COLUMN', $name, $table, $comment);
        if ($status != 0) {
            $this->rollbackTransaction();

            return [-5, $sql];
        }

        return [$this->endTransaction(), $sql];
    }

    /**
     * Renames a column in a table.
     *
     * @param string $table   The table containing the column to be renamed
     * @param string $column  The column to be renamed
     * @param string $newName The new name for the column
     *
     * @return int 0 if operation was successful
     */
    public function renameColumn($table, $column, $newName)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);
        $this->fieldClean($newName);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" RENAME COLUMN \"{$column}\" TO \"{$newName}\"";

        return $this->execute($sql);
    }

    /**
     * Sets default value of a column.
     *
     * @param string $table   The table from which to drop
     * @param string $column  The column name to set
     * @param mixed  $default The new default value
     *
     * @return int 0 if operation was successful
     */
    public function setColumnDefault($table, $column, $default)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ALTER COLUMN \"{$column}\" SET DEFAULT {$default}";

        return $this->execute($sql);
    }

    /**
     * Sets whether or not a column can contain NULLs.
     *
     * @param string $table  The table that contains the column
     * @param string $column The column to alter
     * @param bool   $state  True to set null, false to set not null
     *
     * @return int 0 if operation was successful
     */
    public function setColumnNull($table, $column, $state)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ALTER COLUMN \"{$column}\" " . ($state ? 'DROP' : 'SET') . ' NOT NULL';

        return $this->execute($sql);
    }

    /**
     * Drops a column from a table.
     *
     * @param string $table   The table from which to drop a column
     * @param string $column  The column to be dropped
     * @param bool   $cascade True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropColumn($table, $column, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" DROP COLUMN \"{$column}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Drops default value of a column.
     *
     * @param string $table  The table from which to drop
     * @param string $column The column name to drop default
     *
     * @return int 0 if operation was successful
     */
    public function dropColumnDefault($table, $column)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($column);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ALTER COLUMN \"{$column}\" DROP DEFAULT";

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
        if ($status != 0) {
            return -1;
        }

        // Set serializable
        $sql    = 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE';
        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Set datestyle to ISO
        $sql    = 'SET DATESTYLE = ISO';
        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Set extra_float_digits to 2
        $sql    = 'SET extra_float_digits TO 2';
        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        return 0;
    }

    /**
     * Ends the data object for a dump.
     *
     * @return bool 0 success
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
     * @return \PHPPgAdmin\ADORecordSet A recordset on success
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

        return $this->selectSet("SELECT {$oid_str}* FROM \"{$relation}\"");
    }

    /**
     * Returns all available autovacuum per table information.
     *
     * @param string $table if given, return autovacuum info for the given table or return all informations for all table
     *
     * @return \PHPPgAdmin\ArrayRecordSet A recordset
     */
    public function getTableAutovacuum($table = '')
    {
        $sql = '';

        if ($table !== '') {
            $this->clean($table);
            $c_schema = $this->_schema;
            $this->clean($c_schema);

            $sql = "SELECT c.oid, nspname, relname, pg_catalog.array_to_string(reloptions, E',') AS reloptions
                FROM pg_class c
                    LEFT JOIN pg_namespace n ON n.oid = c.relnamespace
                WHERE c.relkind = 'r'::\"char\"
                    AND n.nspname NOT IN ('pg_catalog','information_schema')
                    AND c.reloptions IS NOT NULL
                    AND c.relname = '{$table}' AND n.nspname = '{$c_schema}'
                ORDER BY nspname, relname";
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

            foreach (explode(',', $_autovacs->fields['reloptions']) as $var) {
                list($o, $v) = explode('=', $var);
                $_[$o]       = $v;
            }

            $autovacs[] = $_;

            $_autovacs->moveNext();
        }

        return new \PHPPgAdmin\ArrayRecordSet($autovacs);
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
        if ($status != 0) {
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
        if ($rs->recordCount() == 0) {
            // Check for OID column
            $temp = [];
            if ($this->hasObjectID($table)) {
                $temp = ['oid'];
            }
            $this->endTransaction();

            return $temp;
        } // Otherwise find the names of the keys

        $attnames = $this->getAttributeNames($oldtable, explode(' ', $rs->fields['indkey']));
        if (!is_array($attnames)) {
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
     * @return int 0 if operation was successful
     */
    public function insertRow($table, $fields, $values, $nulls, $format, $types)
    {
        if (!is_array($fields) || !is_array($values) || !is_array($nulls)
            || !is_array($format) || !is_array($types)
            || (count($fields) != count($values))
        ) {
            return -1;
        }

        // Build clause
        if (count($values) > 0) {
            // Escape all field names
            $fields   = array_map(['\PHPPgAdmin\Database\Postgres', 'fieldClean'], $fields);
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

            $sql = "INSERT INTO \"{$f_schema}\".\"{$table}\" (\"" . implode('","', $fields) . '")
                VALUES (' . substr($sql, 1) . ')';

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
                if ($value == 't') {
                    return 'TRUE';
                }

                if ($value == 'f') {
                    return 'FALSE';
                }
                if ($value == '') {
                    return 'NULL';
                }

                return $value;
                break;
            default:
                // Checking variable fields is difficult as there might be a size
                // attribute...
                if (strpos($type, 'time') === 0) {
                    // Assume it's one of the time types...
                    if ($value == '') {
                        return "''";
                    }

                    if (strcasecmp($value, 'CURRENT_TIMESTAMP') == 0
                        || strcasecmp($value, 'CURRENT_TIME') == 0
                        || strcasecmp($value, 'CURRENT_DATE') == 0
                        || strcasecmp($value, 'LOCALTIME') == 0
                        || strcasecmp($value, 'LOCALTIMESTAMP') == 0) {
                        return $value;
                    }
                    if ($format == 'EXPRESSION') {
                        return $value;
                    }
                    $this->clean($value);

                    return "'{$value}'";
                }
                if ($format == 'VALUE') {
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
        if (!is_array($vars) || !is_array($nulls) || !is_array($format) || !is_array($types)) {
            return -1;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        // Build clause
        if (sizeof($vars) > 0) {
            foreach ($vars as $key => $value) {
                $this->fieldClean($key);

                // Handle NULL values
                if (isset($nulls[$key])) {
                    $tmp = 'NULL';
                } else {
                    $tmp = $this->formatValue($types[$key], $format[$key], $value);
                }

                if (isset($sql)) {
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
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $status = $this->execute($sql);
        if ($status != 0) {
            // update failed
            $this->rollbackTransaction();

            return -1;
        }

        if ($this->conn->Affected_Rows() != 1) {
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
        if (!is_array($key)) {
            return -1;
        }

        // Begin transaction.  We do this so that we can ensure only one row is
        // deleted
        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        if ($schema === '') {
            $schema = $this->_schema;
        }

        $status = $this->delete($table, $key, $schema);
        if ($status != 0 || $this->conn->Affected_Rows() != 1) {
            $this->rollbackTransaction();

            return -2;
        }

        // End transaction
        return $this->endTransaction();
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

        return "SET SESSION AUTHORIZATION '{$user}';";
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
