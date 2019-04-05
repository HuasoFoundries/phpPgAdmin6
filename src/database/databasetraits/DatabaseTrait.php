<?php

/**
 * PHPPgAdmin v6.0.0-beta.51
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for tables manipulation.
 */
trait DatabaseTrait
{
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
     * Determines whether or not a user is a super user.
     *
     * @param string $username The username of the user
     *
     * @return bool true if is a super user, false otherwise
     */
    public function isSuperUser($username = '')
    {
        $this->clean($username);

        if (empty($username)) {
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
     * Return all information about a particular database.
     *
     * @param string $database The name of the database to retrieve
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
     * @param null|string $currentdatabase database name that should be on top of the resultset
     *
     * @return \PHPPgAdmin\ADORecordSet A list of databases, sorted alphabetically
     */
    public function getDatabases($currentdatabase = null)
    {
        $conf        = $this->conf;
        $server_info = $this->server_info;

        //$this->prtrace('server_info', $server_info);

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

            $not_in = "('".implode("','", $hiddendbs)."')";
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
     * Return the database comment of a db from the shared description table.
     *
     * @param string $database the name of the database to get the comment for
     *
     * @return \PHPPgAdmin\ADORecordSet recordset of the db comment info
     */
    public function getDatabaseComment($database)
    {
        $this->clean($database);
        $sql = "SELECT description
                FROM pg_catalog.pg_database
                JOIN pg_catalog.pg_shdescription
                ON (oid=objoid AND classoid='pg_database'::regclass)
                WHERE pg_database.datname = '{$database}' ";

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
     * Creates a database.
     *
     * @param string $database   The name of the database to create
     * @param string $encoding   Encoding of the database
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
     * Drops a database.
     *
     * @param string $database The name of the database to drop
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
     * @param string $dbName   The name of the database
     * @param string $newName  new name for the database
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
     * @param null|string $database (optional) Find only prepared transactions executed in a specific database
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
     * Returns all available process information.
     *
     * @param null|string $database (optional) Find only connections to specified database
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
     * @param int    $pid    The ID of the backend process
     * @param string $signal 'CANCEL' or 'KILL'
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
     * Vacuums a database.
     *
     * @param string $table   The table to vacuum
     * @param bool   $analyze If true, also does analyze
     * @param bool   $full    If true, selects "full" vacuum
     * @param bool   $freeze  If true, selects aggressive "freezing" of tuples
     *
     * @return array result status and sql sentence
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

        $status = $this->execute($sql);

        return [$status, $sql];
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
     * Returns all available variable information.
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getVariables()
    {
        $sql = 'SHOW ALL';

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

    abstract public function phpBool($parameter);

    abstract public function hasCreateTableLikeWithConstraints();

    abstract public function hasCreateTableLikeWithIndexes();

    abstract public function hasTablespaces();

    abstract public function delete($table, $conditions, $schema = '');

    abstract public function fieldArrayClean(&$arr);

    abstract public function hasCreateFieldWithConstraints();

    abstract public function getAttributeNames($table, $atts);

    abstract public function hasSharedComments();

    abstract public function selectField($sql, $field);
}
