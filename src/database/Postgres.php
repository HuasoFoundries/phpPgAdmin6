<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Database;

/**
 * A Class that implements the DB Interface for Postgres
 * Note: This Class uses ADODB and returns RecordSets.
 *
 * Id: Postgres.php,v 1.320 2008/02/20 20:43:09 ioguix Exp $
 *
 * @package PHPPgAdmin
 */
class Postgres extends ADOdbBase
{
    use \PHPPgAdmin\HelperTrait;
    use \PHPPgAdmin\Database\SequenceTrait;
    use \PHPPgAdmin\Database\ViewTrait;
    use \PHPPgAdmin\Database\IndexTrait;
    use \PHPPgAdmin\Database\RoleTrait;
    use \PHPPgAdmin\Database\AggregateTrait;
    use \PHPPgAdmin\Database\TableTrait;
    use \PHPPgAdmin\Database\DomainTrait;
    use \PHPPgAdmin\Database\FtsTrait;

    public $lang;
    public $conf;
    protected $container;

    public function __construct(&$conn, $container)
    {
        //$this->prtrace('major_version :' . $this->major_version);
        $this->conn      = $conn;
        $this->container = $container;

        $this->lang = $container->get('lang');
        $this->conf = $container->get('conf');
    }

    /**
     * Fetch a URL (or array of URLs) for a given help page.
     *
     * @param $help
     *
     * @return null|array|string
     */
    public function getHelp($help)
    {
        $this->getHelpPages();

        if (isset($this->help_page[$help])) {
            if (is_array($this->help_page[$help])) {
                $urls = [];
                foreach ($this->help_page[$help] as $link) {
                    $urls[] = $this->help_base.$link;
                }

                return $urls;
            }

            return $this->help_base.$this->help_page[$help];
        }

        return null;
    }

    /**
     * Gets the help pages.
     * get help page by instancing the corresponding help class
     * if $this->help_page and $this->help_base are set, this function is a noop.
     */
    public function getHelpPages()
    {
        if ($this->help_page === null || $this->help_base === null) {
            $help_classname = '\PHPPgAdmin\Help\PostgresDoc'.str_replace('.', '', $this->major_version);

            $help_class = new $help_classname($this->conf, $this->major_version);

            $this->help_base = $help_class->getHelpBase();
        }
    }

    // Formatting functions

    /**
     * Outputs the HTML code for a particular field.
     *
     * @param       $name   The name to give the field
     * @param       $value  The value of the field.  Note this could be 'numeric(7,2)' sort of thing...
     * @param       $type   The database type of the field
     * @param array $extras An array of attributes name as key and attributes' values as value
     */
    public function printField($name, $value, $type, $extras = [])
    {
        $lang = $this->lang;

        // Determine actions string
        $extra_str = '';
        foreach ($extras as $k => $v) {
            $extra_str .= " {$k}=\"".htmlspecialchars($v).'"';
        }

        switch (substr($type, 0, 9)) {
            case 'bool':
            case 'boolean':
                if ($value !== null && $value == '') {
                    $value = null;
                } elseif ($value == 'true') {
                    $value = 't';
                } elseif ($value == 'false') {
                    $value = 'f';
                }

                // If value is null, 't' or 'f'...
                if ($value === null || $value == 't' || $value == 'f') {
                    echo '<select name="', htmlspecialchars($name), "\"{$extra_str}>\n";
                    echo '<option value=""', ($value === null) ? ' selected="selected"' : '', "></option>\n";
                    echo '<option value="t"', ($value == 't') ? ' selected="selected"' : '', ">{$lang['strtrue']}</option>\n";
                    echo '<option value="f"', ($value == 'f') ? ' selected="selected"' : '', ">{$lang['strfalse']}</option>\n";
                    echo "</select>\n";
                } else {
                    echo '<input name="', htmlspecialchars($name), '" value="', htmlspecialchars($value), "\" size=\"35\"{$extra_str} />\n";
                }

                break;
            case 'bytea':
            case 'bytea[]':
                if (!is_null($value)) {
                    $value = $this->escapeBytea($value);
                }
            // no break
            case 'text':
            case 'text[]':
            case 'json':
            case 'jsonb':
            case 'xml':
            case 'xml[]':
                $n = substr_count($value, "\n");
                $n = $n < 5 ? max(2, $n) : $n;
                $n = $n > 20 ? 20 : $n;
                echo '<textarea name="', htmlspecialchars($name), "\" rows=\"{$n}\" cols=\"85\"{$extra_str}>\n";
                echo htmlspecialchars($value);
                echo "</textarea>\n";

                break;
            case 'character':
            case 'character[]':
                $n = substr_count($value, "\n");
                $n = $n < 5 ? 5 : $n;
                $n = $n > 20 ? 20 : $n;
                echo '<textarea name="', htmlspecialchars($name), "\" rows=\"{$n}\" cols=\"35\"{$extra_str}>\n";
                echo htmlspecialchars($value);
                echo "</textarea>\n";

                break;
            default:
                echo '<input name="', htmlspecialchars($name), '" value="', htmlspecialchars($value), "\" size=\"35\"{$extra_str} />\n";

                break;
        }
    }

    /**
     * Return all information about a particular database.
     *
     * @param $database The name of the database to retrieve
     *
     * @return \PHPPgAdmin\ADORecordSet The database info
     */
    public function getDatabase($database)
    {
        $this->clean($database);
        $sql = "SELECT * FROM pg_database WHERE datname='{$database}'";

        return $this->selectSet($sql);
    }

    /**
     * Return all database available on the server.
     *
     * @param $currentdatabase database name that should be on top of the resultset
     *
     * @return \PHPPgAdmin\ADORecordSet A list of databases, sorted alphabetically
     */
    public function getDatabases($currentdatabase = null)
    {
        $conf        = $this->conf;
        $server_info = $this->server_info;

        if (isset($conf['owned_only']) && $conf['owned_only'] && !$this->isSuperUser()) {
            $username = $server_info['username'];
            $this->clean($username);
            $clause = " AND pr.rolname='{$username}'";
        } else {
            $clause = '';
        }
        if (isset($server_info['useonlydefaultdb']) && $server_info['useonlydefaultdb']) {
            $currentdatabase = $server_info['defaultdb'];
            $clause .= " AND pdb.datname = '{$currentdatabase}' ";
        }

        if (isset($server_info['hiddendbs']) && $server_info['hiddendbs']) {
            $hiddendbs = $server_info['hiddendbs'];
            $not_in    = "('".implode("','", $hiddendbs)."')";
            $clause .= " AND pdb.datname NOT IN {$not_in} ";
        }

        if ($currentdatabase != null) {
            $this->clean($currentdatabase);
            $orderby = "ORDER BY pdb.datname = '{$currentdatabase}' DESC, pdb.datname";
        } else {
            $orderby = 'ORDER BY pdb.datname';
        }

        if (!$conf['show_system']) {
            $where = ' AND NOT pdb.datistemplate';
        } else {
            $where = ' AND pdb.datallowconn';
        }

        $sql = "
			SELECT pdb.datname AS datname,
                    pr.rolname AS datowner,
                    pg_encoding_to_char(encoding) AS datencoding,
				    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pdb.oid=pd.objoid AND pd.classoid='pg_database'::regclass) AS datcomment,
				    (SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=pdb.dattablespace) AS tablespace,
				CASE WHEN pg_catalog.has_database_privilege(current_user, pdb.oid, 'CONNECT')
					THEN pg_catalog.pg_database_size(pdb.oid)
					ELSE -1 -- set this magic value, which we will convert to no access later
				END as dbsize,
                pdb.datcollate,
                pdb.datctype
			FROM pg_catalog.pg_database pdb
            LEFT JOIN pg_catalog.pg_roles pr ON (pdb.datdba = pr.oid)
			WHERE true
				{$where}
				{$clause}
			{$orderby}";

        return $this->selectSet($sql);
    }

    /**
     * Determines whether or not a user is a super user.
     *
     * @param string $username The username of the user
     *
     * @return bool true if is a super user, false otherwise
     */
    public function isSuperUser($username = '')
    {
        $this->clean($username);

        if (empty($usename)) {
            $val = pg_parameter_status($this->conn->_connectionID, 'is_superuser');
            if ($val !== false) {
                return $val == 'on';
            }
        }

        $sql = "SELECT usesuper FROM pg_user WHERE usename='{$username}'";

        $usesuper = $this->selectField($sql, 'usesuper');
        if ($usesuper == -1) {
            return false;
        }

        return $usesuper == 't';
    }

    /**
     * Return the database comment of a db from the shared description table.
     *
     * @param string $database the name of the database to get the comment for
     *
     * @return \PHPPgAdmin\ADORecordSet recordset of the db comment info
     */
    public function getDatabaseComment($database)
    {
        $this->clean($database);
        $sql =
            "SELECT description FROM pg_catalog.pg_database JOIN pg_catalog.pg_shdescription ON (oid=objoid AND classoid='pg_database'::regclass) WHERE pg_database.datname = '{$database}' ";

        return $this->selectSet($sql);
    }

    /**
     * Return the database owner of a db.
     *
     * @param string $database the name of the database to get the owner for
     *
     * @return \PHPPgAdmin\ADORecordSet recordset of the db owner info
     */
    public function getDatabaseOwner($database)
    {
        $this->clean($database);
        $sql = "SELECT usename FROM pg_user, pg_database WHERE pg_user.usesysid = pg_database.datdba AND pg_database.datname = '{$database}' ";

        return $this->selectSet($sql);
    }

    // Help functions

    // Database functions

    /**
     * Returns the current database encoding.
     *
     * @return string The encoding.  eg. SQL_ASCII, UTF-8, etc.
     */
    public function getDatabaseEncoding()
    {
        return pg_parameter_status($this->conn->_connectionID, 'server_encoding');
    }

    /**
     * Returns the current default_with_oids setting.
     *
     * @return string default_with_oids setting
     */
    public function getDefaultWithOid()
    {
        $sql = 'SHOW default_with_oids';

        return $this->selectField($sql, 'default_with_oids');
    }

    /**
     * Creates a database.
     *
     * @param        $database   The name of the database to create
     * @param        $encoding   Encoding of the database
     * @param string $tablespace (optional) The tablespace name
     * @param string $comment
     * @param string $template
     * @param string $lc_collate
     * @param string $lc_ctype
     *
     * @return int 0 success
     */
    public function createDatabase(
        $database,
        $encoding,
        $tablespace = '',
        $comment = '',
        $template = 'template1',
        $lc_collate = '',
        $lc_ctype = ''
    ) {
        $this->fieldClean($database);
        $this->clean($encoding);
        $this->fieldClean($tablespace);
        $this->fieldClean($template);
        $this->clean($lc_collate);
        $this->clean($lc_ctype);

        $sql = "CREATE DATABASE \"{$database}\" WITH TEMPLATE=\"{$template}\"";

        if ($encoding != '') {
            $sql .= " ENCODING='{$encoding}'";
        }

        if ($lc_collate != '') {
            $sql .= " LC_COLLATE='{$lc_collate}'";
        }

        if ($lc_ctype != '') {
            $sql .= " LC_CTYPE='{$lc_ctype}'";
        }

        if ($tablespace != '' && $this->hasTablespaces()) {
            $sql .= " TABLESPACE \"{$tablespace}\"";
        }

        $status = $this->execute($sql);
        if ($status != 0) {
            return -1;
        }

        if ($comment != '' && $this->hasSharedComments()) {
            $status = $this->setComment('DATABASE', $database, '', $comment);
            if ($status != 0) {
                return -2;
            }
        }

        return 0;
    }

    /**
     * Cleans (escapes) an object name (eg. table, field).
     *
     * @param string $str The string to clean, by reference
     *
     * @return string The cleaned string
     */
    public function fieldClean(&$str)
    {
        if ($str === null) {
            return null;
        }

        $str = str_replace('"', '""', $str);

        return $str;
    }

    /**
     * Drops a database.
     *
     * @param $database The name of the database to drop
     *
     * @return int 0 if operation was successful
     */
    public function dropDatabase($database)
    {
        $this->fieldClean($database);
        $sql = "DROP DATABASE \"{$database}\"";

        return $this->execute($sql);
    }

    /**
     * Alters a database
     * the multiple return vals are for postgres 8+ which support more functionality in alter database.
     *
     * @param        $dbName   The name of the database
     * @param        $newName  new name for the database
     * @param string $newOwner The new owner for the database
     * @param string $comment
     *
     * @return bool|int 0 success
     */
    public function alterDatabase($dbName, $newName, $newOwner = '', $comment = '')
    {
        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        if ($dbName != $newName) {
            $status = $this->alterDatabaseRename($dbName, $newName);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -3;
            }
            $dbName = $newName;
        }

        if ($newOwner != '') {
            $status = $this->alterDatabaseOwner($newName, $newOwner);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -2;
            }
        }

        $this->fieldClean($dbName);
        $status = $this->setComment('DATABASE', $dbName, '', $comment);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -4;
        }

        return $this->endTransaction();
    }

    /**
     * Renames a database, note that this operation cannot be
     * performed on a database that is currently being connected to.
     *
     * @param string $oldName name of database to rename
     * @param string $newName new name of database
     *
     * @return int 0 on success
     */
    public function alterDatabaseRename($oldName, $newName)
    {
        $this->fieldClean($oldName);
        $this->fieldClean($newName);

        if ($oldName != $newName) {
            $sql = "ALTER DATABASE \"{$oldName}\" RENAME TO \"{$newName}\"";

            return $this->execute($sql);
        }

        return 0;
    }

    /**
     * Changes ownership of a database
     * This can only be done by a superuser or the owner of the database.
     *
     * @param string $dbName   database to change ownership of
     * @param string $newOwner user that will own the database
     *
     * @return int 0 on success
     */
    public function alterDatabaseOwner($dbName, $newOwner)
    {
        $this->fieldClean($dbName);
        $this->fieldClean($newOwner);

        $sql = "ALTER DATABASE \"{$dbName}\" OWNER TO \"{$newOwner}\"";

        return $this->execute($sql);
    }

    /**
     * Returns prepared transactions information.
     *
     * @param $database (optional) Find only prepared transactions executed in a specific database
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getPreparedXacts($database = null)
    {
        if ($database === null) {
            $sql = 'SELECT * FROM pg_prepared_xacts';
        } else {
            $this->clean($database);
            $sql = "SELECT transaction, gid, prepared, owner FROM pg_prepared_xacts
				WHERE database='{$database}' ORDER BY owner";
        }

        return $this->selectSet($sql);
    }

    /**
     * Searches all system catalogs to find objects that match a certain name.
     *
     * @param $term   The search term
     * @param $filter The object type to restrict to ('' means no restriction)
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function findObject($term, $filter)
    {
        $conf = $this->conf;

        /*about escaping:
         * SET standard_conforming_string is not available before 8.2
         * So we must use PostgreSQL specific notation :/
         * E'' notation is not available before 8.1
         * $$ is available since 8.0
         * Nothing specific from 7.4
         */

        // Escape search term for ILIKE match
        $this->clean($term);
        $this->clean($filter);
        $term = str_replace('_', '\_', $term);
        $term = str_replace('%', '\%', $term);

        // Exclude system relations if necessary
        if (!$conf['show_system']) {
            // XXX: The mention of information_schema here is in the wrong place, but
            // it's the quickest fix to exclude the info schema from 7.4
            $where     = " AND pn.nspname NOT LIKE \$_PATERN_\$pg\\_%\$_PATERN_\$ AND pn.nspname != 'information_schema'";
            $lan_where = 'AND pl.lanispl';
        } else {
            $where     = '';
            $lan_where = '';
        }

        // Apply outer filter
        $sql = '';
        if ($filter != '') {
            $sql = 'SELECT * FROM (';
        }

        $term = "\$_PATERN_\$%{$term}%\$_PATERN_\$";

        $sql .= "
			SELECT 'SCHEMA' AS type, oid, NULL AS schemaname, NULL AS relname, nspname AS name
				FROM pg_catalog.pg_namespace pn WHERE nspname ILIKE {$term} {$where}
			UNION ALL
			SELECT CASE WHEN relkind='r' THEN 'TABLE' WHEN relkind='v' THEN 'VIEW' WHEN relkind='S' THEN 'SEQUENCE' END, pc.oid,
				pn.nspname, NULL, pc.relname FROM pg_catalog.pg_class pc, pg_catalog.pg_namespace pn
				WHERE pc.relnamespace=pn.oid AND relkind IN ('r', 'v', 'S') AND relname ILIKE {$term} {$where}
			UNION ALL
			SELECT CASE WHEN pc.relkind='r' THEN 'COLUMNTABLE' ELSE 'COLUMNVIEW' END, NULL, pn.nspname, pc.relname, pa.attname FROM pg_catalog.pg_class pc, pg_catalog.pg_namespace pn,
				pg_catalog.pg_attribute pa WHERE pc.relnamespace=pn.oid AND pc.oid=pa.attrelid
				AND pa.attname ILIKE {$term} AND pa.attnum > 0 AND NOT pa.attisdropped AND pc.relkind IN ('r', 'v') {$where}
			UNION ALL
			SELECT 'FUNCTION', pp.oid, pn.nspname, NULL, pp.proname || '(' || pg_catalog.oidvectortypes(pp.proargtypes) || ')' FROM pg_catalog.pg_proc pp, pg_catalog.pg_namespace pn
				WHERE pp.pronamespace=pn.oid AND NOT pp.proisagg AND pp.proname ILIKE {$term} {$where}
			UNION ALL
			SELECT 'INDEX', NULL, pn.nspname, pc.relname, pc2.relname FROM pg_catalog.pg_class pc, pg_catalog.pg_namespace pn,
				pg_catalog.pg_index pi, pg_catalog.pg_class pc2 WHERE pc.relnamespace=pn.oid AND pc.oid=pi.indrelid
				AND pi.indexrelid=pc2.oid
				AND NOT EXISTS (
					SELECT 1 FROM pg_catalog.pg_depend d JOIN pg_catalog.pg_constraint c
					ON (d.refclassid = c.tableoid AND d.refobjid = c.oid)
					WHERE d.classid = pc2.tableoid AND d.objid = pc2.oid AND d.deptype = 'i' AND c.contype IN ('u', 'p')
				)
				AND pc2.relname ILIKE {$term} {$where}
			UNION ALL
			SELECT 'CONSTRAINTTABLE', NULL, pn.nspname, pc.relname, pc2.conname FROM pg_catalog.pg_class pc, pg_catalog.pg_namespace pn,
				pg_catalog.pg_constraint pc2 WHERE pc.relnamespace=pn.oid AND pc.oid=pc2.conrelid AND pc2.conrelid != 0
				AND CASE WHEN pc2.contype IN ('f', 'c') THEN TRUE ELSE NOT EXISTS (
					SELECT 1 FROM pg_catalog.pg_depend d JOIN pg_catalog.pg_constraint c
					ON (d.refclassid = c.tableoid AND d.refobjid = c.oid)
					WHERE d.classid = pc2.tableoid AND d.objid = pc2.oid AND d.deptype = 'i' AND c.contype IN ('u', 'p')
				) END
				AND pc2.conname ILIKE {$term} {$where}
			UNION ALL
			SELECT 'CONSTRAINTDOMAIN', pt.oid, pn.nspname, pt.typname, pc.conname FROM pg_catalog.pg_type pt, pg_catalog.pg_namespace pn,
				pg_catalog.pg_constraint pc WHERE pt.typnamespace=pn.oid AND pt.oid=pc.contypid AND pc.contypid != 0
				AND pc.conname ILIKE {$term} {$where}
			UNION ALL
			SELECT 'TRIGGER', NULL, pn.nspname, pc.relname, pt.tgname FROM pg_catalog.pg_class pc, pg_catalog.pg_namespace pn,
				pg_catalog.pg_trigger pt WHERE pc.relnamespace=pn.oid AND pc.oid=pt.tgrelid
					AND ( pt.tgconstraint = 0 OR NOT EXISTS
					(SELECT 1 FROM pg_catalog.pg_depend d JOIN pg_catalog.pg_constraint c
					ON (d.refclassid = c.tableoid AND d.refobjid = c.oid)
					WHERE d.classid = pt.tableoid AND d.objid = pt.oid AND d.deptype = 'i' AND c.contype = 'f'))
				AND pt.tgname ILIKE {$term} {$where}
			UNION ALL
			SELECT 'RULETABLE', NULL, pn.nspname AS schemaname, c.relname AS tablename, r.rulename FROM pg_catalog.pg_rewrite r
				JOIN pg_catalog.pg_class c ON c.oid = r.ev_class
				LEFT JOIN pg_catalog.pg_namespace pn ON pn.oid = c.relnamespace
				WHERE c.relkind='r' AND r.rulename != '_RETURN' AND r.rulename ILIKE {$term} {$where}
			UNION ALL
			SELECT 'RULEVIEW', NULL, pn.nspname AS schemaname, c.relname AS tablename, r.rulename FROM pg_catalog.pg_rewrite r
				JOIN pg_catalog.pg_class c ON c.oid = r.ev_class
				LEFT JOIN pg_catalog.pg_namespace pn ON pn.oid = c.relnamespace
				WHERE c.relkind='v' AND r.rulename != '_RETURN' AND r.rulename ILIKE {$term} {$where}
		";

        // Add advanced objects if show_advanced is set
        if ($conf['show_advanced']) {
            $sql .= "
				UNION ALL
				SELECT CASE WHEN pt.typtype='d' THEN 'DOMAIN' ELSE 'TYPE' END, pt.oid, pn.nspname, NULL,
					pt.typname FROM pg_catalog.pg_type pt, pg_catalog.pg_namespace pn
					WHERE pt.typnamespace=pn.oid AND typname ILIKE {$term}
					AND (pt.typrelid = 0 OR (SELECT c.relkind = 'c' FROM pg_catalog.pg_class c WHERE c.oid = pt.typrelid))
					{$where}
			 	UNION ALL
				SELECT 'OPERATOR', po.oid, pn.nspname, NULL, po.oprname FROM pg_catalog.pg_operator po, pg_catalog.pg_namespace pn
					WHERE po.oprnamespace=pn.oid AND oprname ILIKE {$term} {$where}
				UNION ALL
				SELECT 'CONVERSION', pc.oid, pn.nspname, NULL, pc.conname FROM pg_catalog.pg_conversion pc,
					pg_catalog.pg_namespace pn WHERE pc.connamespace=pn.oid AND conname ILIKE {$term} {$where}
				UNION ALL
				SELECT 'LANGUAGE', pl.oid, NULL, NULL, pl.lanname FROM pg_catalog.pg_language pl
					WHERE lanname ILIKE {$term} {$lan_where}
				UNION ALL
				SELECT DISTINCT ON (p.proname) 'AGGREGATE', p.oid, pn.nspname, NULL, p.proname FROM pg_catalog.pg_proc p
					LEFT JOIN pg_catalog.pg_namespace pn ON p.pronamespace=pn.oid
					WHERE p.proisagg AND p.proname ILIKE {$term} {$where}
				UNION ALL
				SELECT DISTINCT ON (po.opcname) 'OPCLASS', po.oid, pn.nspname, NULL, po.opcname FROM pg_catalog.pg_opclass po,
					pg_catalog.pg_namespace pn WHERE po.opcnamespace=pn.oid
					AND po.opcname ILIKE {$term} {$where}
			";
        } else {
            // Otherwise just add domains
            $sql .= "
				UNION ALL
				SELECT 'DOMAIN', pt.oid, pn.nspname, NULL,
					pt.typname FROM pg_catalog.pg_type pt, pg_catalog.pg_namespace pn
					WHERE pt.typnamespace=pn.oid AND pt.typtype='d' AND typname ILIKE {$term}
					AND (pt.typrelid = 0 OR (SELECT c.relkind = 'c' FROM pg_catalog.pg_class c WHERE c.oid = pt.typrelid))
					{$where}
			";
        }

        if ($filter != '') {
            // We use like to make RULE, CONSTRAINT and COLUMN searches work
            $sql .= ") AS sub WHERE type LIKE '{$filter}%' ";
        }

        $sql .= 'ORDER BY type, schemaname, relname, name';

        return $this->selectSet($sql);
    }

    /**
     * Returns all available variable information.
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getVariables()
    {
        $sql = 'SHOW ALL';

        return $this->selectSet($sql);
    }

    // Schema functons

    /**
     * Return all schemas in the current database.
     *
     * @return \PHPPgAdmin\ADORecordSet All schemas, sorted alphabetically
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
				   pg_catalog.obj_description(pn.oid, 'pg_namespace') AS nspcomment,
                   pg_size_pretty(SUM(pg_total_relation_size(pg_class.oid))) as schema_size
			FROM pg_catalog.pg_namespace pn
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
     * @param $schema The the name of the schema to work in
     *
     * @return int 0 if operation was successful
     */
    public function setSchema($schema)
    {
        // Get the current schema search path, including 'pg_catalog'.
        $search_path = $this->getSearchPath();
        // Prepend $schema to search path
        array_unshift($search_path, $schema);
        $status = $this->setSearchPath($search_path);
        if ($status == 0) {
            $this->_schema = $schema;

            return 0;
        }

        return $status;
    }

    /**
     * Return the current schema search path.
     *
     * @return \PHPPgAdmin\ADORecordSet array of schema names
     */
    public function getSearchPath()
    {
        $sql = 'SELECT current_schemas(false) AS search_path';

        return $this->phpArray($this->selectField($sql, 'search_path'));
    }

    /**
     * Sets the current schema search path.
     *
     * @param $paths An array of schemas in required search order
     *
     * @return int 0 if operation was successful
     */
    public function setSearchPath($paths)
    {
        if (!is_array($paths)) {
            return -1;
        }

        if (sizeof($paths) == 0) {
            return -2;
        }
        if (sizeof($paths) == 1 && $paths[0] == '') {
            // Need to handle empty paths in some cases
            $paths[0] = 'pg_catalog';
        }

        // Loop over all the paths to check that none are empty
        $temp = [];
        foreach ($paths as $schema) {
            if ($schema != '') {
                $temp[] = $schema;
            }
        }
        $this->fieldArrayClean($temp);

        $sql = 'SET SEARCH_PATH TO "'.implode('","', $temp).'"';

        return $this->execute($sql);
    }

    /**
     * Creates a new schema.
     *
     * @param        $schemaname    The name of the schema to create
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
        if ($authorization != '') {
            $sql .= " AUTHORIZATION \"{$authorization}\"";
        }

        if ($comment != '') {
            $status = $this->beginTransaction();
            if ($status != 0) {
                return -1;
            }
        }

        // Create the new schema
        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Set the comment
        if ($comment != '') {
            $status = $this->setComment('SCHEMA', $schemaname, '', $comment);
            if ($status != 0) {
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
     * @param $schemaname The name of the schema to drop
     * @param $comment    The new comment for this schema
     * @param $name
     * @param $owner      The new owner for this schema
     *
     * @return bool|int 0 success
     */
    public function updateSchema($schemaname, $comment, $name, $owner)
    {
        $this->fieldClean($schemaname);
        $this->fieldClean($name);
        $this->fieldClean($owner);

        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $status = $this->setComment('SCHEMA', $schemaname, '', $comment);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $schema_rs = $this->getSchemaByName($schemaname);
        /* Only if the owner change */
        if ($schema_rs->fields['ownername'] != $owner) {
            $sql    = "ALTER SCHEMA \"{$schemaname}\" OWNER TO \"{$owner}\"";
            $status = $this->execute($sql);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        // Only if the name has changed
        if ($name != $schemaname) {
            $sql    = "ALTER SCHEMA \"{$schemaname}\" RENAME TO \"{$name}\"";
            $status = $this->execute($sql);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Return all information relating to a schema.
     *
     * @param $schema The name of the schema
     *
     * @return Schema information
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
     * @param $schemaname The name of the schema to drop
     * @param $cascade    True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
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

    /**
     * Formats a type correctly for display.  Postgres 7.0 had no 'format_type'
     * built-in function, and hence we need to do it manually.
     *
     * @param $typname The name of the type
     * @param $typmod  The contents of the typmod field
     *
     * @return bool|string
     */
    public function formatType($typname, $typmod)
    {
        // This is a specific constant in the 7.0 source
        $varhdrsz = 4;

        // If the first character is an underscore, it's an array type
        $is_array = false;
        if (substr($typname, 0, 1) == '_') {
            $is_array = true;
            $typname  = substr($typname, 1);
        }

        // Show lengths on bpchar and varchar
        if ($typname == 'bpchar') {
            $len  = $typmod - $varhdrsz;
            $temp = 'character';
            if ($len > 1) {
                $temp .= "({$len})";
            }
        } elseif ($typname == 'varchar') {
            $temp = 'character varying';
            if ($typmod != -1) {
                $temp .= '('.($typmod - $varhdrsz).')';
            }
        } elseif ($typname == 'numeric') {
            $temp = 'numeric';
            if ($typmod != -1) {
                $tmp_typmod = $typmod - $varhdrsz;
                $precision  = ($tmp_typmod >> 16) & 0xffff;
                $scale      = $tmp_typmod & 0xffff;
                $temp .= "({$precision}, {$scale})";
            }
        } else {
            $temp = $typname;
        }

        // Add array qualifier if it's an array
        if ($is_array) {
            $temp .= '[]';
        }

        return $temp;
    }

    /**
     * Given an array of attnums and a relation, returns an array mapping
     * attribute number to attribute name.
     *
     * @param $table The table to get attributes for
     * @param $atts  An array of attribute numbers
     *
     * @return An array mapping attnum to attname
     * @return -1 $atts must be an array
     * @return -2 wrong number of attributes found
     */
    public function getAttributeNames($table, $atts)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);
        $this->arrayClean($atts);

        if (!is_array($atts)) {
            return -1;
        }

        if (sizeof($atts) == 0) {
            return [];
        }

        $sql = "SELECT attnum, attname FROM pg_catalog.pg_attribute WHERE
			attrelid=(SELECT oid FROM pg_catalog.pg_class WHERE relname='{$table}' AND
			relnamespace=(SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='{$c_schema}'))
			AND attnum IN ('".join("','", $atts)."')";

        $rs = $this->selectSet($sql);
        if ($rs->recordCount() != sizeof($atts)) {
            return -2;
        }

        $temp = [];
        while (!$rs->EOF) {
            $temp[$rs->fields['attnum']] = $rs->fields['attname'];
            $rs->moveNext();
        }

        return $temp;
    }

    /**
     * Cleans (escapes) an array.
     *
     * @param $arr The array to clean, by reference
     *
     * @return The cleaned array
     */
    public function arrayClean(&$arr)
    {
        foreach ($arr as $k => $v) {
            if ($v === null) {
                continue;
            }

            $arr[$k] = pg_escape_string($v);
        }

        return $arr;
    }

    /**
     * Grabs an array of users and their privileges for an object,
     * given its type.
     *
     * @param $object The name of the object whose privileges are to be retrieved
     * @param $type   The type of the object (eg. database, schema, relation, function or language)
     * @param $table  Optional, column's table if type = column
     *
     * @return arrray|int Privileges array
     * @return -1         invalid type
     * @return -2         object not found
     * @return -3         unknown privilege type
     */
    public function getPrivileges($object, $type, $table = null)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($object);

        switch ($type) {
            case 'column':
                $this->clean($table);
                $sql = "
					SELECT E'{' || pg_catalog.array_to_string(attacl, E',') || E'}' as acl
					FROM pg_catalog.pg_attribute a
						LEFT JOIN pg_catalog.pg_class c ON (a.attrelid = c.oid)
						LEFT JOIN pg_catalog.pg_namespace n ON (c.relnamespace=n.oid)
					WHERE n.nspname='{$c_schema}'
						AND c.relname='{$table}'
						AND a.attname='{$object}'";

                break;
            case 'table':
            case 'view':
            case 'sequence':
                $sql = "
					SELECT relacl AS acl FROM pg_catalog.pg_class
					WHERE relname='{$object}'
						AND relnamespace=(SELECT oid FROM pg_catalog.pg_namespace
							WHERE nspname='{$c_schema}')";

                break;
            case 'database':
                $sql = "SELECT datacl AS acl FROM pg_catalog.pg_database WHERE datname='{$object}'";

                break;
            case 'function':
                // Since we fetch functions by oid, they are already constrained to
                // the current schema.
                $sql = "SELECT proacl AS acl FROM pg_catalog.pg_proc WHERE oid='{$object}'";

                break;
            case 'language':
                $sql = "SELECT lanacl AS acl FROM pg_catalog.pg_language WHERE lanname='{$object}'";

                break;
            case 'schema':
                $sql = "SELECT nspacl AS acl FROM pg_catalog.pg_namespace WHERE nspname='{$object}'";

                break;
            case 'tablespace':
                $sql = "SELECT spcacl AS acl FROM pg_catalog.pg_tablespace WHERE spcname='{$object}'";

                break;
            default:
                return -1;
        }

        // Fetch the ACL for object
        $acl = $this->selectField($sql, 'acl');
        if ($acl == -1) {
            return -2;
        }

        if ($acl == '' || $acl === null || !(bool) $acl) {
            return [];
        }

        return $this->_parseACL($acl);
    }

    /**
     * Internal function used for parsing ACLs.
     *
     * @param $acl The ACL to parse (of type aclitem[])
     *
     * @return Privileges array
     */
    public function _parseACL($acl)
    {
        // Take off the first and last characters (the braces)
        $acl = substr($acl, 1, strlen($acl) - 2);

        // Pick out individual ACE's by carefully parsing.  This is necessary in order
        // to cope with usernames and stuff that contain commas
        $aces      = [];
        $i         = $j         = 0;
        $in_quotes = false;
        while ($i < strlen($acl)) {
            // If current char is a double quote and it's not escaped, then
            // enter quoted bit
            $char = substr($acl, $i, 1);
            if ($char == '"' && ($i == 0 || substr($acl, $i - 1, 1) != '\\')) {
                $in_quotes = !$in_quotes;
            } elseif ($char == ',' && !$in_quotes) {
                // Add text so far to the array
                $aces[] = substr($acl, $j, $i - $j);
                $j      = $i + 1;
            }
            ++$i;
        }
        // Add final text to the array
        $aces[] = substr($acl, $j);

        // Create the array to be returned
        $temp = [];

        // For each ACE, generate an entry in $temp
        foreach ($aces as $v) {
            // If the ACE begins with a double quote, strip them off both ends
            // and unescape backslashes and double quotes
            $unquote = false;
            if (strpos($v, '"') === 0) {
                $v = substr($v, 1, strlen($v) - 2);
                $v = str_replace('\\"', '"', $v);
                $v = str_replace('\\\\', '\\', $v);
            }

            // Figure out type of ACE (public, user or group)
            if (strpos($v, '=') === 0) {
                $atype = 'public';
            } else {
                if ($this->hasRoles()) {
                    $atype = 'role';
                } else {
                    if (strpos($v, 'group ') === 0) {
                        $atype = 'group';
                        // Tear off 'group' prefix
                        $v = substr($v, 6);
                    } else {
                        $atype = 'user';
                    }
                }
            }

            // Break on unquoted equals sign...
            $i         = 0;
            $in_quotes = false;
            $entity    = null;
            $chars     = null;
            while ($i < strlen($v)) {
                // If current char is a double quote and it's not escaped, then
                // enter quoted bit
                $char      = substr($v, $i, 1);
                $next_char = substr($v, $i + 1, 1);
                if ($char == '"' && ($i == 0 || $next_char != '"')) {
                    $in_quotes = !$in_quotes;
                } // Skip over escaped double quotes
                elseif ($char == '"' && $next_char == '"') {
                    ++$i;
                } elseif ($char == '=' && !$in_quotes) {
                    // Split on current equals sign
                    $entity = substr($v, 0, $i);
                    $chars  = substr($v, $i + 1);

                    break;
                }
                ++$i;
            }

            // Check for quoting on entity name, and unescape if necessary
            if (strpos($entity, '"') === 0) {
                $entity = substr($entity, 1, strlen($entity) - 2);
                $entity = str_replace('""', '"', $entity);
            }

            // New row to be added to $temp
            // (type, grantee, privileges, grantor, grant option?
            $row = [$atype, $entity, [], '', []];

            // Loop over chars and add privs to $row
            for ($i = 0; $i < strlen($chars); ++$i) {
                // Append to row's privs list the string representing
                // the privilege
                $char = substr($chars, $i, 1);
                if ($char == '*') {
                    $row[4][] = $this->privmap[substr($chars, $i - 1, 1)];
                } elseif ($char == '/') {
                    $grantor = substr($chars, $i + 1);
                    // Check for quoting
                    if (strpos($grantor, '"') === 0) {
                        $grantor = substr($grantor, 1, strlen($grantor) - 2);
                        $grantor = str_replace('""', '"', $grantor);
                    }
                    $row[3] = $grantor;

                    break;
                } else {
                    if (!isset($this->privmap[$char])) {
                        return -3;
                    }

                    $row[2][] = $this->privmap[$char];
                }
            }

            // Append row to temp
            $temp[] = $row;
        }

        return $temp;
    }

    // Rule functions

    /**
     * Returns an array containing a function's properties.
     *
     * @param array $f The array of data for the function
     *
     * @return array An array containing the properties
     */
    public function getFunctionProperties($f)
    {
        $temp = [];

        // Volatility
        if ($f['provolatile'] == 'v') {
            $temp[] = 'VOLATILE';
        } elseif ($f['provolatile'] == 'i') {
            $temp[] = 'IMMUTABLE';
        } elseif ($f['provolatile'] == 's') {
            $temp[] = 'STABLE';
        } else {
            return -1;
        }

        // Null handling
        $f['proisstrict'] = $this->phpBool($f['proisstrict']);
        if ($f['proisstrict']) {
            $temp[] = 'RETURNS NULL ON NULL INPUT';
        } else {
            $temp[] = 'CALLED ON NULL INPUT';
        }

        // Security
        $f['prosecdef'] = $this->phpBool($f['prosecdef']);
        if ($f['prosecdef']) {
            $temp[] = 'SECURITY DEFINER';
        } else {
            $temp[] = 'SECURITY INVOKER';
        }

        return $temp;
    }

    /**
     * Updates (replaces) a function.
     *
     * @param int    $function_oid The OID of the function
     * @param string $funcname     The name of the function to create
     * @param string $newname      The new name for the function
     * @param array  $args         The array of argument types
     * @param string $returns      The return type
     * @param string $definition   The definition for the new function
     * @param string $language     The language the function is written for
     * @param array  $flags        An array of optional flags
     * @param bool   $setof        True if returns a set, false otherwise
     * @param string $funcown
     * @param string $newown
     * @param string $funcschema
     * @param string $newschema
     * @param float  $cost
     * @param int    $rows
     * @param string $comment      The comment on the function
     *
     * @return bool|int 0 success
     */
    public function setFunction(
        $function_oid,
        $funcname,
        $newname,
        $args,
        $returns,
        $definition,
        $language,
        $flags,
        $setof,
        $funcown,
        $newown,
        $funcschema,
        $newschema,
        $cost,
        $rows,
        $comment
    ) {
        // Begin a transaction
        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Replace the existing function
        $status = $this->createFunction($funcname, $args, $returns, $definition, $language, $flags, $setof, $cost, $rows, $comment, true);
        if ($status != 0) {
            $this->rollbackTransaction();

            return $status;
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);

        // Rename the function, if necessary
        $this->fieldClean($newname);
        /* $funcname is escaped in createFunction */
        if ($funcname != $newname) {
            $sql    = "ALTER FUNCTION \"{$f_schema}\".\"{$funcname}\"({$args}) RENAME TO \"{$newname}\"";
            $status = $this->execute($sql);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -5;
            }

            $funcname = $newname;
        }

        // Alter the owner, if necessary
        if ($this->hasFunctionAlterOwner()) {
            $this->fieldClean($newown);
            if ($funcown != $newown) {
                $sql    = "ALTER FUNCTION \"{$f_schema}\".\"{$funcname}\"({$args}) OWNER TO \"{$newown}\"";
                $status = $this->execute($sql);
                if ($status != 0) {
                    $this->rollbackTransaction();

                    return -6;
                }
            }
        }

        // Alter the schema, if necessary
        if ($this->hasFunctionAlterSchema()) {
            $this->fieldClean($newschema);
            /* $funcschema is escaped in createFunction */
            if ($funcschema != $newschema) {
                $sql    = "ALTER FUNCTION \"{$f_schema}\".\"{$funcname}\"({$args}) SET SCHEMA \"{$newschema}\"";
                $status = $this->execute($sql);
                if ($status != 0) {
                    $this->rollbackTransaction();

                    return -7;
                }
            }
        }

        return $this->endTransaction();
    }

    /**
     * Creates a new function.
     *
     * @param string $funcname   The name of the function to create
     * @param string $args       A comma separated string of types
     * @param string $returns    The return type
     * @param string $definition The definition for the new function
     * @param string $language   The language the function is written for
     * @param array  $flags      An array of optional flags
     * @param bool   $setof      True if it returns a set, false otherwise
     * @param string $cost       cost the planner should use in the function  execution step
     * @param int    $rows       number of rows planner should estimate will be returned
     * @param string $comment    Comment for the function
     * @param bool   $replace    (optional) True if OR REPLACE, false for
     *                           normal
     *
     * @return bool|int 0 success
     */
    public function createFunction($funcname, $args, $returns, $definition, $language, $flags, $setof, $cost, $rows, $comment, $replace = false)
    {
        // Begin a transaction
        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $this->fieldClean($funcname);
        $this->clean($args);
        $this->fieldClean($language);
        $this->arrayClean($flags);
        $this->clean($cost);
        $this->clean($rows);
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);

        $sql = 'CREATE';
        if ($replace) {
            $sql .= ' OR REPLACE';
        }

        $sql .= " FUNCTION \"{$f_schema}\".\"{$funcname}\" (";

        if ($args != '') {
            $sql .= $args;
        }

        // For some reason, the returns field cannot have quotes...
        $sql .= ') RETURNS ';
        if ($setof) {
            $sql .= 'SETOF ';
        }

        $sql .= "{$returns} AS ";

        if (is_array($definition)) {
            $this->arrayClean($definition);
            $sql .= "'".$definition[0]."'";
            if ($definition[1]) {
                $sql .= ",'".$definition[1]."'";
            }
        } else {
            $this->clean($definition);
            $sql .= "'".$definition."'";
        }

        $sql .= " LANGUAGE \"{$language}\"";

        // Add costs
        if (!empty($cost)) {
            $sql .= " COST {$cost}";
        }

        if ($rows != 0) {
            $sql .= " ROWS {$rows}";
        }

        // Add flags
        foreach ($flags as $v) {
            // Skip default flags
            if ($v == '') {
                continue;
            }

            $sql .= "\n{$v}";
        }

        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -3;
        }

        /* set the comment */
        $status = $this->setComment('FUNCTION', "\"{$funcname}\"({$args})", null, $comment);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -4;
        }

        return $this->endTransaction();
    }

    /**
     * Drops a function.
     *
     * @param int  $function_oid The OID of the function to drop
     * @param bool $cascade      True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropFunction($function_oid, $cascade)
    {
        // Function comes in with $object as function OID
        $fn       = $this->getFunction($function_oid);
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($fn->fields['proname']);

        $sql = "DROP FUNCTION \"{$f_schema}\".\"{$fn->fields['proname']}\"({$fn->fields['proarguments']})";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Returns all details for a particular function.
     *
     * @param int $function_oid
     *
     * @return \PHPPgAdmin\ADORecordSet Function info
     *
     * @internal param string The $func name of the function to retrieve
     */
    public function getFunction($function_oid)
    {
        $this->clean($function_oid);

        $sql = "
			SELECT
				pc.oid AS prooid, proname,
				pg_catalog.pg_get_userbyid(proowner) AS proowner,
				nspname as proschema, lanname as prolanguage, procost, prorows,
				pg_catalog.format_type(prorettype, NULL) as proresult, prosrc,
				probin, proretset, proisstrict, provolatile, prosecdef,
				pg_catalog.oidvectortypes(pc.proargtypes) AS proarguments,
				proargnames AS proargnames,
				pg_catalog.obj_description(pc.oid, 'pg_proc') AS procomment,
				proconfig,
				(select array_agg( (select typname from pg_type pt
					where pt.oid = p.oid) ) from unnest(proallargtypes) p)
				AS proallarguments,
				proargmodes
			FROM
				pg_catalog.pg_proc pc, pg_catalog.pg_language pl,
				pg_catalog.pg_namespace pn
			WHERE
				pc.oid = '{$function_oid}'::oid AND pc.prolang = pl.oid
				AND pc.pronamespace = pn.oid
			";

        return $this->selectSet($sql);
    }

    /**
     * Returns all details for a particular type.
     *
     * @param string $typname The name of the view to retrieve
     *
     * @return \PHPPgAdmin\ADORecordSet info
     */
    public function getType($typname)
    {
        $this->clean($typname);

        $sql = "SELECT typtype, typbyval, typname, typinput AS typin, typoutput AS typout, typlen, typalign
			FROM pg_type WHERE typname='{$typname}'";

        return $this->selectSet($sql);
    }

    /**
     * Returns a list of all types in the database.
     *
     * @param bool $all        If true, will find all available types, if false just those in search path
     * @param bool $tabletypes If true, will include table types
     * @param bool $domains    If true, will include domains
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTypes($all = false, $tabletypes = false, $domains = false)
    {
        if ($all) {
            $where = '1 = 1';
        } else {
            $c_schema = $this->_schema;
            $this->clean($c_schema);
            $where = "n.nspname = '{$c_schema}'";
        }
        // Never show system table types
        $where2 = "AND c.relnamespace NOT IN (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname LIKE 'pg@_%' ESCAPE '@')";

        // Create type filter
        $tqry = "'c'";
        if ($tabletypes) {
            $tqry .= ", 'r', 'v'";
        }

        // Create domain filter
        if (!$domains) {
            $where .= " AND t.typtype != 'd'";
        }

        $sql = "SELECT
				t.typname AS basename,
				pg_catalog.format_type(t.oid, NULL) AS typname,
				pu.usename AS typowner,
				t.typtype,
				pg_catalog.obj_description(t.oid, 'pg_type') AS typcomment
			FROM (pg_catalog.pg_type t
				LEFT JOIN pg_catalog.pg_namespace n ON n.oid = t.typnamespace)
				LEFT JOIN pg_catalog.pg_user pu ON t.typowner = pu.usesysid
			WHERE (t.typrelid = 0 OR (SELECT c.relkind IN ({$tqry}) FROM pg_catalog.pg_class c WHERE c.oid = t.typrelid {$where2}))
			AND t.typname !~ '^_'
			AND {$where}
			ORDER BY typname
		";

        return $this->selectSet($sql);
    }

    /**
     * Creates a new type.
     *
     * @param string $typname
     * @param string $typin
     * @param string $typout
     * @param string $typlen
     * @param string $typdef
     * @param string $typelem
     * @param string $typdelim
     * @param string $typbyval
     * @param string $typalign
     * @param string $typstorage
     *
     * @return int 0 if operation was successful
     *
     * @internal param $ ...
     */
    public function createType(
        $typname,
        $typin,
        $typout,
        $typlen,
        $typdef,
        $typelem,
        $typdelim,
        $typbyval,
        $typalign,
        $typstorage
    ) {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($typname);
        $this->fieldClean($typin);
        $this->fieldClean($typout);

        $sql = "
			CREATE TYPE \"{$f_schema}\".\"{$typname}\" (
				INPUT = \"{$typin}\",
				OUTPUT = \"{$typout}\",
				INTERNALLENGTH = {$typlen}";
        if ($typdef != '') {
            $sql .= ", DEFAULT = {$typdef}";
        }

        if ($typelem != '') {
            $sql .= ", ELEMENT = {$typelem}";
        }

        if ($typdelim != '') {
            $sql .= ", DELIMITER = {$typdelim}";
        }

        if ($typbyval) {
            $sql .= ', PASSEDBYVALUE, ';
        }

        if ($typalign != '') {
            $sql .= ", ALIGNMENT = {$typalign}";
        }

        if ($typstorage != '') {
            $sql .= ", STORAGE = {$typstorage}";
        }

        $sql .= ')';

        return $this->execute($sql);
    }

    /**
     * Drops a type.
     *
     * @param $typname The name of the type to drop
     * @param $cascade True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropType($typname, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($typname);

        $sql = "DROP TYPE \"{$f_schema}\".\"{$typname}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Creates a new enum type in the database.
     *
     * @param string $name       The name of the type
     * @param array  $values     An array of values
     * @param string $typcomment Type comment
     *
     * @return bool|int 0 success
     */
    public function createEnumType($name, $values, $typcomment)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);

        if (empty($values)) {
            return -2;
        }

        $status = $this->beginTransaction();
        if ($status != 0) {
            return -1;
        }

        $values = array_unique($values);

        $nbval = count($values);

        for ($i = 0; $i < $nbval; ++$i) {
            $this->clean($values[$i]);
        }

        $sql = "CREATE TYPE \"{$f_schema}\".\"{$name}\" AS ENUM ('";
        $sql .= implode("','", $values);
        $sql .= "')";

        $status = $this->execute($sql);
        if ($status) {
            $this->rollbackTransaction();

            return -1;
        }

        if ($typcomment != '') {
            $status = $this->setComment('TYPE', $name, '', $typcomment, true);
            if ($status) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Get defined values for a given enum.
     *
     * @param string $name
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getEnumValues($name)
    {
        $this->clean($name);

        $sql = "SELECT enumlabel AS enumval
		FROM pg_catalog.pg_type t JOIN pg_catalog.pg_enum e ON (t.oid=e.enumtypid)
		WHERE t.typname = '{$name}' ORDER BY e.oid";

        return $this->selectSet($sql);
    }

    // Operator functions

    /**
     * Creates a new composite type in the database.
     *
     * @param string $name       The name of the type
     * @param int    $fields     The number of fields
     * @param array  $field      An array of field names
     * @param array  $type       An array of field types
     * @param array  $array      An array of '' or '[]' for each type if it's an array or not
     * @param array  $length     An array of field lengths
     * @param array  $colcomment An array of comments
     * @param string $typcomment Type comment
     *
     * @return bool|int 0 success
     */
    public function createCompositeType($name, $fields, $field, $type, $array, $length, $colcomment, $typcomment)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);

        $status = $this->beginTransaction();
        if ($status != 0) {
            return -1;
        }

        $found       = false;
        $first       = true;
        $comment_sql = ''; // Accumulate comments for the columns
        $sql         = "CREATE TYPE \"{$f_schema}\".\"{$name}\" AS (";
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

            if ($colcomment[$i] != '') {
                $comment_sql .= "COMMENT ON COLUMN \"{$f_schema}\".\"{$name}\".\"{$field[$i]}\" IS '{$colcomment[$i]}';\n";
            }

            $found = true;
        }

        if (!$found) {
            return -1;
        }

        $sql .= ')';

        $status = $this->execute($sql);
        if ($status) {
            $this->rollbackTransaction();

            return -1;
        }

        if ($typcomment != '') {
            $status = $this->setComment('TYPE', $name, '', $typcomment, true);
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
     * Returns a list of all casts in the database.
     *
     * @return \PHPPgAdmin\ADORecordSet All casts
     */
    public function getCasts()
    {
        $conf = $this->conf;

        if ($conf['show_system']) {
            $where = '';
        } else {
            $where = '
				AND n1.nspname NOT LIKE $$pg\_%$$
				AND n2.nspname NOT LIKE $$pg\_%$$
				AND n3.nspname NOT LIKE $$pg\_%$$
			';
        }

        $sql = "
			SELECT
				c.castsource::pg_catalog.regtype AS castsource,
				c.casttarget::pg_catalog.regtype AS casttarget,
				CASE WHEN c.castfunc=0 THEN NULL
				ELSE c.castfunc::pg_catalog.regprocedure END AS castfunc,
				c.castcontext,
				obj_description(c.oid, 'pg_cast') as castcomment
			FROM
				(pg_catalog.pg_cast c LEFT JOIN pg_catalog.pg_proc p ON c.castfunc=p.oid JOIN pg_catalog.pg_namespace n3 ON p.pronamespace=n3.oid),
				pg_catalog.pg_type t1,
				pg_catalog.pg_type t2,
				pg_catalog.pg_namespace n1,
				pg_catalog.pg_namespace n2
			WHERE
				c.castsource=t1.oid
				AND c.casttarget=t2.oid
				AND t1.typnamespace=n1.oid
				AND t2.typnamespace=n2.oid
				{$where}
			ORDER BY 1, 2
		";

        return $this->selectSet($sql);
    }

    /**
     * Returns a list of all conversions in the database.
     *
     * @return \PHPPgAdmin\ADORecordSet All conversions
     */
    public function getConversions()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = "
			SELECT
			       c.conname,
			       pg_catalog.pg_encoding_to_char(c.conforencoding) AS conforencoding,
			       pg_catalog.pg_encoding_to_char(c.contoencoding) AS contoencoding,
			       c.condefault,
			       pg_catalog.obj_description(c.oid, 'pg_conversion') AS concomment
			FROM pg_catalog.pg_conversion c, pg_catalog.pg_namespace n
			WHERE n.oid = c.connamespace
			      AND n.nspname='{$c_schema}'
			ORDER BY 1;
		";

        return $this->selectSet($sql);
    }

    // Operator Class functions

    /**
     * Edits a rule on a table OR view.
     *
     * @param string $name The name of the new rule
     * @param $event   SELECT, INSERT, UPDATE or DELETE
     * @param $table   Table on which to create the rule
     * @param $where   When to execute the rule, '' indicates always
     * @param $instead True if an INSTEAD rule, false otherwise
     * @param $type    NOTHING for a do nothing rule, SOMETHING to use given action
     * @param $action  The action to take
     *
     * @return int 0 if operation was successful
     */
    public function setRule($name, $event, $table, $where, $instead, $type, $action)
    {
        return $this->createRule($name, $event, $table, $where, $instead, $type, $action, true);
    }

    // FTS functions

    /**
     * Creates a rule.
     *
     * @param string $name    The name of the new rule
     * @param string $event   SELECT, INSERT, UPDATE or DELETE
     * @param string $table   Table on which to create the rule
     * @param string $where   When to execute the rule, '' indicates always
     * @param bool   $instead True if an INSTEAD rule, false otherwise
     * @param string $type    NOTHING for a do nothing rule, SOMETHING to use given action
     * @param string $action  The action to take
     * @param bool   $replace (optional) True to replace existing rule, false
     *                        otherwise
     *
     * @return int 0 if operation was successful
     */
    public function createRule($name, $event, $table, $where, $instead, $type, $action, $replace = false)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);
        $this->fieldClean($table);
        if (!in_array($event, $this->rule_events, true)) {
            return -1;
        }

        $sql = 'CREATE';
        if ($replace) {
            $sql .= ' OR REPLACE';
        }

        $sql .= " RULE \"{$name}\" AS ON {$event} TO \"{$f_schema}\".\"{$table}\"";
        // Can't escape WHERE clause
        if ($where != '') {
            $sql .= " WHERE {$where}";
        }

        $sql .= ' DO';
        if ($instead) {
            $sql .= ' INSTEAD';
        }

        if ($type == 'NOTHING') {
            $sql .= ' NOTHING';
        } else {
            $sql .= " ({$action})";
        }

        return $this->execute($sql);
    }

    /**
     * Removes a rule from a table OR view.
     *
     * @param string $rule     The rule to drop
     * @param string $relation The relation from which to drop
     * @param string $cascade  True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropRule($rule, $relation, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($rule);
        $this->fieldClean($relation);

        $sql = "DROP RULE \"{$rule}\" ON \"{$f_schema}\".\"{$relation}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Grabs a single trigger.
     *
     * @param string $table   The name of a table whose triggers to retrieve
     * @param string $trigger The name of the trigger to retrieve
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTrigger($table, $trigger)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);
        $this->clean($trigger);

        $sql = "
			SELECT * FROM pg_catalog.pg_trigger t, pg_catalog.pg_class c
			WHERE t.tgrelid=c.oid AND c.relname='{$table}' AND t.tgname='{$trigger}'
				AND c.relnamespace=(
					SELECT oid FROM pg_catalog.pg_namespace
					WHERE nspname='{$c_schema}')";

        return $this->selectSet($sql);
    }

    /**
     * A helper function for getTriggers that translates
     * an array of attribute numbers to an array of field names.
     * Note: Only needed for pre-7.4 servers, this function is deprecated.
     *
     * @param string $trigger An array containing fields from the trigger table
     *
     * @return string The trigger definition string
     */
    public function getTriggerDef($trigger)
    {
        $this->fieldArrayClean($trigger);
        // Constants to figure out tgtype
        if (!defined('TRIGGER_TYPE_ROW')) {
            define('TRIGGER_TYPE_ROW', 1 << 0);
        }

        if (!defined('TRIGGER_TYPE_BEFORE')) {
            define('TRIGGER_TYPE_BEFORE', 1 << 1);
        }

        if (!defined('TRIGGER_TYPE_INSERT')) {
            define('TRIGGER_TYPE_INSERT', 1 << 2);
        }

        if (!defined('TRIGGER_TYPE_DELETE')) {
            define('TRIGGER_TYPE_DELETE', 1 << 3);
        }

        if (!defined('TRIGGER_TYPE_UPDATE')) {
            define('TRIGGER_TYPE_UPDATE', 1 << 4);
        }

        $trigger['tgisconstraint'] = $this->phpBool($trigger['tgisconstraint']);
        $trigger['tgdeferrable']   = $this->phpBool($trigger['tgdeferrable']);
        $trigger['tginitdeferred'] = $this->phpBool($trigger['tginitdeferred']);

        // Constraint trigger or normal trigger
        if ($trigger['tgisconstraint']) {
            $tgdef = 'CREATE CONSTRAINT TRIGGER ';
        } else {
            $tgdef = 'CREATE TRIGGER ';
        }

        $tgdef .= "\"{$trigger['tgname']}\" ";

        // Trigger type
        $findx = 0;
        if (($trigger['tgtype'] & TRIGGER_TYPE_BEFORE) == TRIGGER_TYPE_BEFORE) {
            $tgdef .= 'BEFORE';
        } else {
            $tgdef .= 'AFTER';
        }

        if (($trigger['tgtype'] & TRIGGER_TYPE_INSERT) == TRIGGER_TYPE_INSERT) {
            $tgdef .= ' INSERT';
            ++$findx;
        }
        if (($trigger['tgtype'] & TRIGGER_TYPE_DELETE) == TRIGGER_TYPE_DELETE) {
            if ($findx > 0) {
                $tgdef .= ' OR DELETE';
            } else {
                $tgdef .= ' DELETE';
                ++$findx;
            }
        }
        if (($trigger['tgtype'] & TRIGGER_TYPE_UPDATE) == TRIGGER_TYPE_UPDATE) {
            if ($findx > 0) {
                $tgdef .= ' OR UPDATE';
            } else {
                $tgdef .= ' UPDATE';
            }
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        // Table name
        $tgdef .= " ON \"{$f_schema}\".\"{$trigger['relname']}\" ";

        // Deferrability
        if ($trigger['tgisconstraint']) {
            if ($trigger['tgconstrrelid'] != 0) {
                // Assume constrelname is not null
                $tgdef .= " FROM \"{$trigger['tgconstrrelname']}\" ";
            }
            if (!$trigger['tgdeferrable']) {
                $tgdef .= 'NOT ';
            }

            $tgdef .= 'DEFERRABLE INITIALLY ';
            if ($trigger['tginitdeferred']) {
                $tgdef .= 'DEFERRED ';
            } else {
                $tgdef .= 'IMMEDIATE ';
            }
        }

        // Row or statement
        if ($trigger['tgtype'] & TRIGGER_TYPE_ROW == TRIGGER_TYPE_ROW) {
            $tgdef .= 'FOR EACH ROW ';
        } else {
            $tgdef .= 'FOR EACH STATEMENT ';
        }

        // Execute procedure
        $tgdef .= "EXECUTE PROCEDURE \"{$trigger['tgfname']}\"(";

        // Parameters
        // Escape null characters
        $v = addcslashes($trigger['tgargs'], "\0");
        // Split on escaped null characters
        $params = explode('\\000', $v);
        for ($findx = 0; $findx < $trigger['tgnargs']; ++$findx) {
            $param = "'".str_replace('\'', '\\\'', $params[$findx])."'";
            $tgdef .= $param;
            if ($findx < ($trigger['tgnargs'] - 1)) {
                $tgdef .= ', ';
            }
        }

        // Finish it off
        $tgdef .= ')';

        return $tgdef;
    }

    /**
     * Returns a list of all functions that can be used in triggers.
     */
    public function getTriggerFunctions()
    {
        return $this->getFunctions(true, 'trigger');
    }

    /**
     * Returns a list of all functions in the database.
     *
     * @param bool $all  If true, will find all available functions, if false just those in search path
     * @param      $type If not null, will find all functions with return value = type
     *
     * @return \PHPPgAdmin\ADORecordSet All functions
     */
    public function getFunctions($all = false, $type = null)
    {
        if ($all) {
            $where    = 'pg_catalog.pg_function_is_visible(p.oid)';
            $distinct = 'DISTINCT ON (p.proname)';

            if ($type) {
                $where .= " AND p.prorettype = (select oid from pg_catalog.pg_type p where p.typname = 'trigger') ";
            }
        } else {
            $c_schema = $this->_schema;
            $this->clean($c_schema);
            $where    = "n.nspname = '{$c_schema}'";
            $distinct = '';
        }

        $sql = "
			SELECT
				{$distinct}
				p.oid AS prooid,
				p.proname,
				p.proretset,
				pg_catalog.format_type(p.prorettype, NULL) AS proresult,
				pg_catalog.oidvectortypes(p.proargtypes) AS proarguments,
				pl.lanname AS prolanguage,
				pg_catalog.obj_description(p.oid, 'pg_proc') AS procomment,
				p.proname || ' (' || pg_catalog.oidvectortypes(p.proargtypes) || ')' AS proproto,
				CASE WHEN p.proretset THEN 'setof ' ELSE '' END || pg_catalog.format_type(p.prorettype, NULL) AS proreturns,
				coalesce(u.usename::text,p.proowner::text) AS proowner

			FROM pg_catalog.pg_proc p
				INNER JOIN pg_catalog.pg_namespace n ON n.oid = p.pronamespace
				INNER JOIN pg_catalog.pg_language pl ON pl.oid = p.prolang
				LEFT JOIN pg_catalog.pg_user u ON u.usesysid = p.proowner
			WHERE NOT p.proisagg
				AND {$where}
			ORDER BY p.proname, proresult
			";

        return $this->selectSet($sql);
    }

    /**
     * Creates a trigger.
     *
     * @param $tgname  The name of the trigger to create
     * @param $table   The name of the table
     * @param $tgproc  The function to execute
     * @param $tgtime  BEFORE or AFTER
     * @param $tgevent Event
     * @param $tgfrequency
     * @param $tgargs  The function arguments
     *
     * @return int 0 if operation was successful
     */
    public function createTrigger($tgname, $table, $tgproc, $tgtime, $tgevent, $tgfrequency, $tgargs)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($tgname);
        $this->fieldClean($table);
        $this->fieldClean($tgproc);

        /* No Statement Level Triggers in PostgreSQL (by now) */
        $sql = "CREATE TRIGGER \"{$tgname}\" {$tgtime}
				{$tgevent} ON \"{$f_schema}\".\"{$table}\"
				FOR EACH {$tgfrequency} EXECUTE PROCEDURE \"{$tgproc}\"({$tgargs})";

        return $this->execute($sql);
    }

    /**
     * Alters a trigger.
     *
     * @param $table   The name of the table containing the trigger
     * @param $trigger The name of the trigger to alter
     * @param $name    The new name for the trigger
     *
     * @return int 0 if operation was successful
     */
    public function alterTrigger($table, $trigger, $name)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($trigger);
        $this->fieldClean($name);

        $sql = "ALTER TRIGGER \"{$trigger}\" ON \"{$f_schema}\".\"{$table}\" RENAME TO \"{$name}\"";

        return $this->execute($sql);
    }

    /**
     * Drops a trigger.
     *
     * @param $tgname  The name of the trigger to drop
     * @param $table   The table from which to drop the trigger
     * @param $cascade True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropTrigger($tgname, $table, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($tgname);
        $this->fieldClean($table);

        $sql = "DROP TRIGGER \"{$tgname}\" ON \"{$f_schema}\".\"{$table}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Enables a trigger.
     *
     * @param $tgname The name of the trigger to enable
     * @param $table  The table in which to enable the trigger
     *
     * @return int 0 if operation was successful
     */
    public function enableTrigger($tgname, $table)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($tgname);
        $this->fieldClean($table);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ENABLE TRIGGER \"{$tgname}\"";

        return $this->execute($sql);
    }

    /**
     * Disables a trigger.
     *
     * @param $tgname The name of the trigger to disable
     * @param $table  The table in which to disable the trigger
     *
     * @return int 0 if operation was successful
     */
    public function disableTrigger($tgname, $table)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($tgname);
        $this->fieldClean($table);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" DISABLE TRIGGER \"{$tgname}\"";

        return $this->execute($sql);
    }

    /**
     * Returns a list of all operators in the database.
     *
     * @return \PHPPgAdmin\ADORecordSet All operators
     */
    public function getOperators()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        // We stick with the subselects here, as you cannot ORDER BY a regtype
        $sql = "
			SELECT
            	po.oid,	po.oprname,
				(SELECT pg_catalog.format_type(oid, NULL) FROM pg_catalog.pg_type pt WHERE pt.oid=po.oprleft) AS oprleftname,
				(SELECT pg_catalog.format_type(oid, NULL) FROM pg_catalog.pg_type pt WHERE pt.oid=po.oprright) AS oprrightname,
				po.oprresult::pg_catalog.regtype AS resultname,
		        pg_catalog.obj_description(po.oid, 'pg_operator') AS oprcomment
			FROM
				pg_catalog.pg_operator po
			WHERE
				po.oprnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='{$c_schema}')
			ORDER BY
				po.oprname, oprleftname, oprrightname
		";

        return $this->selectSet($sql);
    }

    /**
     * Drops an operator.
     *
     * @param $operator_oid The OID of the operator to drop
     * @param $cascade      True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropOperator($operator_oid, $cascade)
    {
        // Function comes in with $object as operator OID
        $opr      = $this->getOperator($operator_oid);
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($opr->fields['oprname']);

        $sql = "DROP OPERATOR \"{$f_schema}\".{$opr->fields['oprname']} (";
        // Quoting or formatting here???
        if ($opr->fields['oprleftname'] !== null) {
            $sql .= $opr->fields['oprleftname'].', ';
        } else {
            $sql .= 'NONE, ';
        }

        if ($opr->fields['oprrightname'] !== null) {
            $sql .= $opr->fields['oprrightname'].')';
        } else {
            $sql .= 'NONE)';
        }

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Returns all details for a particular operator.
     *
     * @param $operator_oid The oid of the operator
     *
     * @return Function info
     */
    public function getOperator($operator_oid)
    {
        $this->clean($operator_oid);

        $sql = "
			SELECT
            	po.oid, po.oprname,
				oprleft::pg_catalog.regtype AS oprleftname,
				oprright::pg_catalog.regtype AS oprrightname,
				oprresult::pg_catalog.regtype AS resultname,
				po.oprcanhash,
				oprcanmerge,
				oprcom::pg_catalog.regoperator AS oprcom,
				oprnegate::pg_catalog.regoperator AS oprnegate,
				po.oprcode::pg_catalog.regproc AS oprcode,
				po.oprrest::pg_catalog.regproc AS oprrest,
				po.oprjoin::pg_catalog.regproc AS oprjoin
			FROM
				pg_catalog.pg_operator po
			WHERE
				po.oid='{$operator_oid}'
		";

        return $this->selectSet($sql);
    }

    /**
     *  Gets all opclasses.
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getOpClasses()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = "
			SELECT
				pa.amname, po.opcname,
				po.opcintype::pg_catalog.regtype AS opcintype,
				po.opcdefault,
				pg_catalog.obj_description(po.oid, 'pg_opclass') AS opccomment
			FROM
				pg_catalog.pg_opclass po, pg_catalog.pg_am pa, pg_catalog.pg_namespace pn
			WHERE
				po.opcmethod=pa.oid
				AND po.opcnamespace=pn.oid
				AND pn.nspname='{$c_schema}'
			ORDER BY 1,2
			";

        return $this->selectSet($sql);
    }

    /**
     * Gets all languages.
     *
     * @param bool|true $all True to get all languages, regardless of show_system
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getLanguages($all = false)
    {
        $conf = $this->conf;

        if ($conf['show_system'] || $all) {
            $where = '';
        } else {
            $where = 'WHERE lanispl';
        }

        $sql = "
			SELECT
				lanname, lanpltrusted,
				lanplcallfoid::pg_catalog.regproc AS lanplcallf
			FROM
				pg_catalog.pg_language
			{$where}
			ORDER BY lanname
		";

        return $this->selectSet($sql);
    }

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
     * @param $spcname
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
     * @param        $spcname  The name of the tablespace to create
     * @param        $spcowner The owner of the tablespace. '' for current
     * @param        $spcloc   The directory in which to create the tablespace
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
     * @param        $spcname The name of the tablespace
     * @param        $name    The new name for the tablespace
     * @param        $owner   The new owner for the tablespace
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
     * @param $spcname The name of the domain to drop
     *
     * @return int 0 if operation was successful
     */
    public function dropTablespace($spcname)
    {
        $this->fieldClean($spcname);

        $sql = "DROP TABLESPACE \"{$spcname}\"";

        return $this->execute($sql);
    }

    /**
     * Analyze a database.
     *
     * @param string $table (optional) The table to analyze
     *
     * @return bool 0 if successful
     */
    public function analyzeDB($table = '')
    {
        if ($table != '') {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $this->fieldClean($table);

            $sql = "ANALYZE \"{$f_schema}\".\"{$table}\"";
        } else {
            $sql = 'ANALYZE';
        }

        return $this->execute($sql);
    }

    /**
     * Vacuums a database.
     *
     * @param string $table   The table to vacuum
     * @param bool   $analyze If true, also does analyze
     * @param bool   $full    If true, selects "full" vacuum
     * @param bool   $freeze  If true, selects aggressive "freezing" of tuples
     *
     * @return bool 0 if successful
     */
    public function vacuumDB($table = '', $analyze = false, $full = false, $freeze = false)
    {
        $sql = 'VACUUM';
        if ($full) {
            $sql .= ' FULL';
        }

        if ($freeze) {
            $sql .= ' FREEZE';
        }

        if ($analyze) {
            $sql .= ' ANALYZE';
        }

        if ($table != '') {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $this->fieldClean($table);
            $sql .= " \"{$f_schema}\".\"{$table}\"";
        }

        return $this->execute($sql);
    }

    /**
     * Returns all autovacuum global configuration.
     *
     * @return array associative array array( param => value, ...)
     */
    public function getAutovacuum()
    {
        $_defaults = $this->selectSet(
            "SELECT name, setting
			FROM pg_catalog.pg_settings
			WHERE
				name = 'autovacuum'
				OR name = 'autovacuum_vacuum_threshold'
				OR name = 'autovacuum_vacuum_scale_factor'
				OR name = 'autovacuum_analyze_threshold'
				OR name = 'autovacuum_analyze_scale_factor'
				OR name = 'autovacuum_vacuum_cost_delay'
				OR name = 'autovacuum_vacuum_cost_limit'
				OR name = 'vacuum_freeze_min_age'
				OR name = 'autovacuum_freeze_max_age'
			"
        );

        $ret = [];
        while (!$_defaults->EOF) {
            $ret[$_defaults->fields['name']] = $_defaults->fields['setting'];
            $_defaults->moveNext();
        }

        return $ret;
    }

    /**
     * Returns all available autovacuum per table information.
     *
     * @param string $table
     * @param bool   $vacenabled
     * @param int    $vacthreshold
     * @param int    $vacscalefactor
     * @param int    $anathresold
     * @param int    $anascalefactor
     * @param int    $vaccostdelay
     * @param int    $vaccostlimit
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

        $sql = $sql.implode(',', $params).');';

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

    /**
     * Returns all available process information.
     *
     * @param $database (optional) Find only connections to specified database
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getProcesses($database = null)
    {
        if ($database === null) {
            $sql = "SELECT datname, usename, pid, waiting, state_change as query_start,
                  case when state='idle in transaction' then '<IDLE> in transaction' when state = 'idle' then '<IDLE>' else query end as query
				FROM pg_catalog.pg_stat_activity
				ORDER BY datname, usename, pid";
        } else {
            $this->clean($database);
            $sql = "SELECT datname, usename, pid, waiting, state_change as query_start,
                  case when state='idle in transaction' then '<IDLE> in transaction' when state = 'idle' then '<IDLE>' else query end as query
				FROM pg_catalog.pg_stat_activity
				WHERE datname='{$database}'
				ORDER BY usename, pid";
        }

        return $this->selectSet($sql);
    }

    // interfaces Statistics collector functions

    /**
     * Returns table locks information in the current database.
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getLocks()
    {
        $conf = $this->conf;

        if (!$conf['show_system']) {
            $where = 'AND pn.nspname NOT LIKE $$pg\_%$$';
        } else {
            $where = "AND nspname !~ '^pg_t(emp_[0-9]+|oast)$'";
        }

        $sql = "
			SELECT
				pn.nspname, pc.relname AS tablename, pl.pid, pl.mode, pl.granted, pl.virtualtransaction,
				(select transactionid from pg_catalog.pg_locks l2 where l2.locktype='transactionid'
					and l2.mode='ExclusiveLock' and l2.virtualtransaction=pl.virtualtransaction) as transaction
			FROM
				pg_catalog.pg_locks pl,
				pg_catalog.pg_class pc,
				pg_catalog.pg_namespace pn
			WHERE
				pl.relation = pc.oid AND pc.relnamespace=pn.oid
			{$where}
			ORDER BY pid,nspname,tablename";

        return $this->selectSet($sql);
    }

    /**
     * Sends a cancel or kill command to a process.
     *
     * @param $pid    The ID of the backend process
     * @param $signal 'CANCEL'
     *
     * @return int 0 success
     */
    public function sendSignal($pid, $signal)
    {
        // Clean
        $pid = (int) $pid;

        if ($signal == 'CANCEL') {
            $sql = "SELECT pg_catalog.pg_cancel_backend({$pid}) AS val";
        } elseif ($signal == 'KILL') {
            $sql = "SELECT pg_catalog.pg_terminate_backend({$pid}) AS val";
        } else {
            return -1;
        }

        // Execute the query
        $val = $this->selectField($sql, 'val');

        if ($val === 'f') {
            return -1;
        }

        if ($val === 't') {
            return 0;
        }

        return -1;
    }

    /**
     * Executes an SQL script as a series of SQL statements.  Returns
     * the result of the final step.  This is a very complicated lexer
     * based on the REL7_4_STABLE src/bin/psql/mainloop.c lexer in
     * the PostgreSQL source code.
     * XXX: It does not handle multibyte languages properly.
     *
     * @param string        $name     Entry in $_FILES to use
     * @param null|function $callback (optional) Callback function to call with each query, its result and line number
     *
     * @return bool true for general success, false on any failure
     */
    public function executeScript($name, $callback = null)
    {
        // This whole function isn't very encapsulated, but hey...
        $conn = $this->conn->_connectionID;
        if (!is_uploaded_file($_FILES[$name]['tmp_name'])) {
            return false;
        }

        $fd = fopen($_FILES[$name]['tmp_name'], 'rb');
        if (!$fd) {
            return false;
        }

        // Build up each SQL statement, they can be multiline
        $query_buf    = null;
        $query_start  = 0;
        $in_quote     = 0;
        $in_xcomment  = 0;
        $bslash_count = 0;
        $dol_quote    = null;
        $paren_level  = 0;
        $len          = 0;
        $i            = 0;
        $prevlen      = 0;
        $thislen      = 0;
        $lineno       = 0;

        // Loop over each line in the file
        while (!feof($fd)) {
            $line = fgets($fd);
            ++$lineno;

            // Nothing left on line? Then ignore...
            if (trim($line) == '') {
                continue;
            }

            $len         = strlen($line);
            $query_start = 0;

            /**
             * Parse line, looking for command separators.
             *
             * The current character is at line[i], the prior character at line[i
             * - prevlen], the next character at line[i + thislen].
             */
            $prevlen = 0;
            $thislen = ($len > 0) ? 1 : 0;

            for ($i = 0; $i < $len; $this->advance_1($i, $prevlen, $thislen)) {
                /* was the previous character a backslash? */
                if ($i > 0 && substr($line, $i - $prevlen, 1) == '\\') {
                    ++$bslash_count;
                } else {
                    $bslash_count = 0;
                }

                /*
                 * It is important to place the in_* test routines before the
                 * in_* detection routines. i.e. we have to test if we are in
                 * a quote before testing for comments.
                 */

                /* in quote? */
                if ($in_quote !== 0) {
                    /*
                     * end of quote if matching non-backslashed character.
                     * backslashes don't count for double quotes, though.
                     */
                    if (substr($line, $i, 1) == $in_quote &&
                        ($bslash_count % 2 == 0 || $in_quote == '"')) {
                        $in_quote = 0;
                    }
                } else {
                    if ($dol_quote) {
                        if (strncmp(substr($line, $i), $dol_quote, strlen($dol_quote)) == 0) {
                            $this->advance_1($i, $prevlen, $thislen);
                            while (substr($line, $i, 1) != '$') {
                                $this->advance_1($i, $prevlen, $thislen);
                            }

                            $dol_quote = null;
                        }
                    } else {
                        if (substr($line, $i, 2) == '/*') {
                            ++$in_xcomment;
                            if ($in_xcomment == 1) {
                                $this->advance_1($i, $prevlen, $thislen);
                            }
                        } else {
                            if ($in_xcomment) {
                                if (substr($line, $i, 2) == '*/' && !--$in_xcomment) {
                                    $this->advance_1($i, $prevlen, $thislen);
                                }
                            } else {
                                if (substr($line, $i, 1) == '\'' || substr($line, $i, 1) == '"') {
                                    $in_quote = substr($line, $i, 1);
                                }/*
                                 * start of $foo$ type quote?
                                 */
                                else {
                                    if (!$dol_quote && $this->valid_dolquote(substr($line, $i))) {
                                        $dol_end   = strpos(substr($line, $i + 1), '$');
                                        $dol_quote = substr($line, $i, $dol_end + 1);
                                        $this->advance_1($i, $prevlen, $thislen);
                                        while (substr($line, $i, 1) != '$') {
                                            $this->advance_1($i, $prevlen, $thislen);
                                        }
                                    } else {
                                        if (substr($line, $i, 2) == '--') {
                                            $line = substr($line, 0, $i); /* remove comment */
                                            break;
                                        } /* count nested parentheses */

                                        if (substr($line, $i, 1) == '(') {
                                            ++$paren_level;
                                        } else {
                                            if (substr($line, $i, 1) == ')' && $paren_level > 0) {
                                                --$paren_level;
                                            } else {
                                                if (substr($line, $i, 1) == ';' && !$bslash_count && !$paren_level) {
                                                    $subline = substr(substr($line, 0, $i), $query_start);
                                                    /*
                                                     * insert a cosmetic newline, if this is not the first
                                                     * line in the buffer
                                                     */
                                                    if (strlen($query_buf) > 0) {
                                                        $query_buf .= "\n";
                                                    }

                                                    /* append the line to the query buffer */
                                                    $query_buf .= $subline;
                                                    /* is there anything in the query_buf? */
                                                    if (trim($query_buf)) {
                                                        $query_buf .= ';';

                                                        // Execute the query. PHP cannot execute
                                                        // empty queries, unlike libpq
                                                        $res = @pg_query($conn, $query_buf);

                                                        // Call the callback function for display
                                                        if ($callback !== null) {
                                                            $callback($query_buf, $res, $lineno);
                                                        }

                                                        // Check for COPY request
                                                        if (pg_result_status($res) == 4) {
                                                            // 4 == PGSQL_COPY_FROM
                                                            while (!feof($fd)) {
                                                                $copy = fgets($fd, 32768);
                                                                ++$lineno;
                                                                pg_put_line($conn, $copy);
                                                                if ($copy == "\\.\n" || $copy == "\\.\r\n") {
                                                                    pg_end_copy($conn);

                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    $query_buf   = null;
                                                    $query_start = $i + $thislen;
                                                }

                                                /*
                                                 * keyword or identifier?
                                                 * We grab the whole string so that we don't
                                                 * mistakenly see $foo$ inside an identifier as the start
                                                 * of a dollar quote.
                                                 */
                                                // XXX: multibyte here
                                                else {
                                                    if (preg_match('/^[_[:alpha:]]$/', substr($line, $i, 1))) {
                                                        $sub = substr($line, $i, $thislen);
                                                        while (preg_match('/^[\$_A-Za-z0-9]$/', $sub)) {
                                                            /* keep going while we still have identifier chars */
                                                            $this->advance_1($i, $prevlen, $thislen);
                                                            $sub = substr($line, $i, $thislen);
                                                        }
                                                        // Since we're now over the next character to be examined, it is necessary
                                                        // to move back one space.
                                                        $i -= $prevlen;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } // end for

            /* Put the rest of the line in the query buffer. */
            $subline = substr($line, $query_start);
            if ($in_quote || $dol_quote || strspn($subline, " \t\n\r") != strlen($subline)) {
                if (strlen($query_buf) > 0) {
                    $query_buf .= "\n";
                }

                $query_buf .= $subline;
            }

            $line = null;
        } // end while

        /*
         * Process query at the end of file without a semicolon, so long as
         * it's non-empty.
         */
        if (strlen($query_buf) > 0 && strspn($query_buf, " \t\n\r") != strlen($query_buf)) {
            // Execute the query
            $res = @pg_query($conn, $query_buf);

            // Call the callback function for display
            if ($callback !== null) {
                $callback($query_buf, $res, $lineno);
            }

            // Check for COPY request
            if (pg_result_status($res) == 4) {
                // 4 == PGSQL_COPY_FROM
                while (!feof($fd)) {
                    $copy = fgets($fd, 32768);
                    ++$lineno;
                    pg_put_line($conn, $copy);
                    if ($copy == "\\.\n" || $copy == "\\.\r\n") {
                        pg_end_copy($conn);

                        break;
                    }
                }
            }
        }

        fclose($fd);

        return true;
    }

    /**
     * A private helper method for executeScript that advances the
     * character by 1.  In psql this is careful to take into account
     * multibyte languages, but we don't at the moment, so this function
     * is someone redundant, since it will always advance by 1.
     *
     * @param int &$i       The current character position in the line
     * @param int &$prevlen Length of previous character (ie. 1)
     * @param int &$thislen Length of current character (ie. 1)
     */
    private function advance_1(&$i, &$prevlen, &$thislen)
    {
        $prevlen = $thislen;
        $i += $thislen;
        $thislen = 1;
    }

    /**
     * Private helper method to detect a valid $foo$ quote delimiter at
     * the start of the parameter dquote.
     *
     * @param string $dquote
     *
     * @return true if valid, false otherwise
     */
    private function valid_dolquote($dquote)
    {
        // XXX: support multibyte
        return preg_match('/^[$][$]/', $dquote) || preg_match('/^[$][_[:alpha:]][_[:alnum:]]*[$]/', $dquote);
    }

    // Capabilities

    /**
     * Returns a recordset of all columns in a query.  Supports paging.
     *
     * @param string $type       Either 'QUERY' if it is an SQL query, or 'TABLE' if it is a table identifier,
     *                           or 'SELECT" if it's a select query
     * @param string $table      The base table of the query.  NULL for no table.
     * @param string $query      The query that is being executed.  NULL for no query.
     * @param string $sortkey    The column number to sort by, or '' or null for no sorting
     * @param string $sortdir    The direction in which to sort the specified column ('asc' or 'desc')
     * @param int    $page       The page of the relation to retrieve
     * @param int    $page_size  The number of rows per page
     * @param int    &$max_pages (return-by-ref) The max number of pages in the relation
     *
     * @return A  recordset on success
     * @return -1 transaction error
     * @return -2 counting error
     * @return -3 page or page_size invalid
     * @return -4 unknown type
     * @return -5 failed setting transaction read only
     */
    public function browseQuery($type, $table, $query, $sortkey, $sortdir, $page, $page_size, &$max_pages)
    {
        // Check that we're not going to divide by zero
        if (!is_numeric($page_size) || $page_size != (int) $page_size || $page_size <= 0) {
            return -3;
        }

        // If $type is TABLE, then generate the query
        switch ($type) {
            case 'TABLE':
                if (preg_match('/^[0-9]+$/', $sortkey) && $sortkey > 0) {
                    $orderby = [$sortkey => $sortdir];
                } else {
                    $orderby = [];
                }

                $query = $this->getSelectSQL($table, [], [], [], $orderby);

                break;
            case 'QUERY':
            case 'SELECT':
                // Trim query
                $query = trim($query);
                // Trim off trailing semi-colon if there is one
                if (substr($query, strlen($query) - 1, 1) == ';') {
                    $query = substr($query, 0, strlen($query) - 1);
                }

                break;
            default:
                return -4;
        }

        // Generate count query
        $count = "SELECT COUNT(*) AS total FROM (${query}) AS sub";

        // Open a transaction
        $status = $this->beginTransaction();
        if ($status != 0) {
            return -1;
        }

        // If backend supports read only queries, then specify read only mode
        // to avoid side effects from repeating queries that do writes.
        if ($this->hasReadOnlyQueries()) {
            $status = $this->execute('SET TRANSACTION READ ONLY');
            if ($status != 0) {
                $this->rollbackTransaction();

                return -5;
            }
        }

        // Count the number of rows
        $total = $this->browseQueryCount($query, $count);
        if ($total < 0) {
            $this->rollbackTransaction();

            return -2;
        }

        // Calculate max pages
        $max_pages = ceil($total / $page_size);

        // Check that page is less than or equal to max pages
        if (!is_numeric($page) || $page != (int) $page || $page > $max_pages || $page < 1) {
            $this->rollbackTransaction();

            return -3;
        }

        // Set fetch mode to NUM so that duplicate field names are properly returned
        // for non-table queries.  Since the SELECT feature only allows selecting one
        // table, duplicate fields shouldn't appear.
        if ($type == 'QUERY') {
            $this->conn->setFetchMode(ADODB_FETCH_NUM);
        }

        // Figure out ORDER BY.  Sort key is always the column number (based from one)
        // of the column to order by.  Only need to do this for non-TABLE queries
        if ($type != 'TABLE' && preg_match('/^[0-9]+$/', $sortkey) && $sortkey > 0) {
            $orderby = " ORDER BY {$sortkey}";
            // Add sort order
            if ($sortdir == 'desc') {
                $orderby .= ' DESC';
            } else {
                $orderby .= ' ASC';
            }
        } else {
            $orderby = '';
        }

        // Actually retrieve the rows, with offset and limit
        $rs     = $this->selectSet("SELECT * FROM ({$query}) AS sub {$orderby} LIMIT {$page_size} OFFSET ".($page - 1) * $page_size);
        $status = $this->endTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        return $rs;
    }

    /**
     * Generates the SQL for the 'select' function.
     *
     * @param $table   The table from which to select
     * @param $show    An array of columns to show.  Empty array means all columns.
     * @param $values  An array mapping columns to values
     * @param $ops     An array of the operators to use
     * @param $orderby (optional) An array of column numbers or names (one based)
     *                 mapped to sort direction (asc or desc or '' or null) to order by
     *
     * @return The SQL query
     */
    public function getSelectSQL($table, $show, $values, $ops, $orderby = [])
    {
        $this->fieldArrayClean($show);

        // If an empty array is passed in, then show all columns
        if (sizeof($show) == 0) {
            if ($this->hasObjectID($table)) {
                $sql = "SELECT \"{$this->id}\", * FROM ";
            } else {
                $sql = 'SELECT * FROM ';
            }
        } else {
            // Add oid column automatically to results for editing purposes
            if (!in_array($this->id, $show, true) && $this->hasObjectID($table)) {
                $sql = "SELECT \"{$this->id}\", \"";
            } else {
                $sql = 'SELECT "';
            }

            $sql .= join('","', $show).'" FROM ';
        }

        $this->fieldClean($table);

        if (isset($_REQUEST['schema'])) {
            $f_schema = $_REQUEST['schema'];
            $this->fieldClean($f_schema);
            $sql .= "\"{$f_schema}\".";
        }
        $sql .= "\"{$table}\"";

        // If we have values specified, add them to the WHERE clause
        $first = true;
        if (is_array($values) && sizeof($values) > 0) {
            foreach ($values as $k => $v) {
                if ($v != '' || $this->selectOps[$ops[$k]] == 'p') {
                    $this->fieldClean($k);
                    if ($first) {
                        $sql .= ' WHERE ';
                        $first = false;
                    } else {
                        $sql .= ' AND ';
                    }
                    // Different query format depending on operator type
                    switch ($this->selectOps[$ops[$k]]) {
                        case 'i':
                            // Only clean the field for the inline case
                            // this is because (x), subqueries need to
                            // to allow 'a','b' as input.
                            $this->clean($v);
                            $sql .= "\"{$k}\" {$ops[$k]} '{$v}'";

                            break;
                        case 'p':
                            $sql .= "\"{$k}\" {$ops[$k]}";

                            break;
                        case 'x':
                            $sql .= "\"{$k}\" {$ops[$k]} ({$v})";

                            break;
                        case 't':
                            $sql .= "\"{$k}\" {$ops[$k]}('{$v}')";

                            break;
                        default:
                            // Shouldn't happen
                    }
                }
            }
        }

        // ORDER BY
        if (is_array($orderby) && sizeof($orderby) > 0) {
            $sql .= ' ORDER BY ';
            $first = true;
            foreach ($orderby as $k => $v) {
                if ($first) {
                    $first = false;
                } else {
                    $sql .= ', ';
                }

                if (preg_match('/^[0-9]+$/', $k)) {
                    $sql .= $k;
                } else {
                    $this->fieldClean($k);
                    $sql .= '"'.$k.'"';
                }
                if (strtoupper($v) == 'DESC') {
                    $sql .= ' DESC';
                }
            }
        }

        return $sql;
    }

    /**
     * Finds the number of rows that would be returned by a
     * query.
     *
     * @param $query The SQL query
     * @param $count The count query
     *
     * @return int The count of rows or -1 of no rows are found
     */
    public function browseQueryCount($query, $count)
    {
        return $this->selectField($count, 'total');
    }

    /**
     * Returns a recordset of all columns in a table.
     *
     * @param $table The name of a table
     * @param $key   The associative array holding the key to retrieve
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function browseRow($table, $key)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);

        $sql = "SELECT * FROM \"{$f_schema}\".\"{$table}\"";
        if (is_array($key) && sizeof($key) > 0) {
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
     * Change the value of a parameter to 't' or 'f' depending on whether it evaluates to true or false.
     *
     * @param $parameter the parameter
     *
     * @return string
     */
    public function dbBool(&$parameter)
    {
        if ($parameter) {
            $parameter = 't';
        } else {
            $parameter = 'f';
        }

        return $parameter;
    }

    /**
     * Fetches statistics for a database.
     *
     * @param $database The database to fetch stats for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getStatsDatabase($database)
    {
        $this->clean($database);

        $sql = "SELECT * FROM pg_stat_database WHERE datname='{$database}'";

        return $this->selectSet($sql);
    }

    /**
     * Fetches tuple statistics for a table.
     *
     * @param $table The table to fetch stats for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getStatsTableTuples($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "SELECT * FROM pg_stat_all_tables
			WHERE schemaname='{$c_schema}' AND relname='{$table}'";

        return $this->selectSet($sql);
    }

    /**
     * Fetches I/0 statistics for a table.
     *
     * @param $table The table to fetch stats for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getStatsTableIO($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "SELECT * FROM pg_statio_all_tables
			WHERE schemaname='{$c_schema}' AND relname='{$table}'";

        return $this->selectSet($sql);
    }

    /**
     * Fetches tuple statistics for all indexes on a table.
     *
     * @param $table The table to fetch index stats for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getStatsIndexTuples($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "SELECT * FROM pg_stat_all_indexes
			WHERE schemaname='{$c_schema}' AND relname='{$table}' ORDER BY indexrelname";

        return $this->selectSet($sql);
    }

    /**
     * Fetches I/0 statistics for all indexes on a table.
     *
     * @param $table The table to fetch index stats for
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getStatsIndexIO($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = "SELECT * FROM pg_statio_all_indexes
			WHERE schemaname='{$c_schema}' AND relname='{$table}'
			ORDER BY indexrelname";

        return $this->selectSet($sql);
    }
}
