<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for tables manipulation.
 */
trait TableTrait
{
    use \PHPPgAdmin\Database\Traits\ColumnTrait;
    use \PHPPgAdmin\Database\Traits\RowTrait;
    use \PHPPgAdmin\Database\Traits\TriggerTrait;

    /**
     * Return all tables in current database excluding schemas 'pg_catalog', 'information_schema' and 'pg_toast'.
     *
     *
     * @return \PHPPgAdmin\ADORecordSet All tables, sorted alphabetically
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
     * @return \PHPPgAdmin\ADORecordSet All tables, sorted alphabetically
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

        if (isset($conf['display_sizes']) && $conf['display_sizes'] === true) {
            $sql .= ' pg_size_pretty(pg_total_relation_size(c.oid)) as table_size ';
        } else {
            $sql .= "   'N/A' as table_size ";
        }

        $sql .= " FROM pg_catalog.pg_class c
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                LEFT JOIN  pg_catalog.pg_tablespace pt ON  pt.oid=c.reltablespace
                WHERE c.relkind = 'r'
                AND nspname='{$c_schema}'
                ORDER BY c.relname";

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
     * @param string $cleanprefix set to '-- ' to avoid issuing DROP statement
     *
     * @return string A string containing the formatted SQL code
     */
    public function getTableDefPrefix($table, $cleanprefix = '')
    {
        // Fetch table
        $t = $this->getTable($table);
        if (!is_object($t) || $t->RecordCount() != 1) {
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
        $sql .= $cleanprefix . 'DROP TABLE ';
        $sql .= "\"{$t->fields['nspname']}\".\"{$t->fields['relname']}\";\n";
        $sql .= "CREATE TABLE \"{$t->fields['nspname']}\".\"{$t->fields['relname']}\" (\n";

        // Output all table columns
        $col_comments_sql = ''; // Accumulate comments on columns
        $num              = $atts->RecordCount() + $cons->RecordCount();
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
         * if ($parents->RecordCount() > 0) {
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
                    case 'role':
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
                    case 'role':
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
            " WHERE c.relkind = 'r'
                  AND n.nspname = '{$c_schema}'
                  AND n.oid = c.relnamespace
                  AND c.relname = '{$table}'";

        return $this->selectSet($sql);
    }

    /**
     * Retrieve all attributes definition of a table.
     *
     * @param string $table The name of the table
     * @param string $c_schema The name of the schema
     *
     * @return \PHPPgAdmin\ADORecordSet All attributes in order
     */
    private function _getTableAttributesAll($table, $c_schema)
    {

        $sql = "
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
                                                AND pd.deptype='i'
            LEFT JOIN pg_catalog.pg_class pc ON pd.objid=pc.oid
                                            AND pd.classid=pc.tableoid
                                            AND pd.refclassid=pc.tableoid
                                            AND pc.relkind='S'
            WHERE tbl.tablename='{$table}'
            AND tbl.schemaname='{$c_schema}'
            AND a.attnum > 0 AND NOT a.attisdropped
            ORDER BY a.attnum";

        return $this->selectSet($sql);

    }

    /**
     * Retrieve single attribute definition of a table.
     *
     * @param string $table The name of the table
     * @param string $c_schema The schema of the table
     * @param string $field (optional) The name of a field to return
     *
     * @return \PHPPgAdmin\ADORecordSet All attributes in order
     */
    private function _getTableAttribute($table, $c_schema, $field)
    {
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

        if ($field == '') {
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
        if ($rs->RecordCount() != 1) {
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

        if ($indexes->RecordCount() > 0) {
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

        if ($triggers->RecordCount() > 0) {
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

        if ($rules->RecordCount() > 0) {
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

        if ($data->RecordCount() != 1) {
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
     * @return bool 0 if successful
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

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" SET (";

        if (!empty($vacenabled)) {
            $this->clean($vacenabled);
            $params[] = "autovacuum_enabled='{$vacenabled}'";
        }
        if (!empty($vacthreshold)) {
            $this->clean($vacthreshold);
            $params[] = "autovacuum_vacuum_threshold='{$vacthreshold}'";
        }
        if (!empty($vacscalefactor)) {
            $this->clean($vacscalefactor);
            $params[] = "autovacuum_vacuum_scale_factor='{$vacscalefactor}'";
        }
        if (!empty($anathresold)) {
            $this->clean($anathresold);
            $params[] = "autovacuum_analyze_threshold='{$anathresold}'";
        }
        if (!empty($anascalefactor)) {
            $this->clean($anascalefactor);
            $params[] = "autovacuum_analyze_scale_factor='{$anascalefactor}'";
        }
        if (!empty($vaccostdelay)) {
            $this->clean($vaccostdelay);
            $params[] = "autovacuum_vacuum_cost_delay='{$vaccostdelay}'";
        }
        if (!empty($vaccostlimit)) {
            $this->clean($vaccostlimit);
            $params[] = "autovacuum_vacuum_cost_limit='{$vaccostlimit}'";
        }

        $sql = $sql . implode(',', $params) . ');';

        return $this->execute($sql);
    }

    // Type conversion routines

    /**
     * Drops autovacuum config for a table.
     *
     * @param string $table The table
     *
     * @return bool 0 if successful
     */
    public function dropAutovacuum($table)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        return $this->execute(
            "
            ALTER TABLE \"{$f_schema}\".\"{$table}\" RESET (autovacuum_enabled, autovacuum_vacuum_threshold,
                autovacuum_vacuum_scale_factor, autovacuum_analyze_threshold, autovacuum_analyze_scale_factor,
                autovacuum_vacuum_cost_delay, autovacuum_vacuum_cost_limit
            );"
        );
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
