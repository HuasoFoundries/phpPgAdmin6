<?php

/**
 * PHPPgAdmin6
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
     * @return int|string
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
            $val = \pg_parameter_status($this->conn->_connectionID, 'is_superuser');

            if (false !== $val) {
                return 'on' === $val;
            }
        }

        $sql = \sprintf(
            'SELECT usesuper FROM pg_user WHERE usename=\'%s\'',
            $username
        );

        $usesuper = $this->selectField($sql, 'usesuper');

        if (-1 === $usesuper) {
            return false;
        }

        return 't' === $usesuper;
    }

    /**
     * Analyze a database.
     *
     * @param string $table (optional) The table to analyze
     *
     * @return int|string
     */
    public function analyzeDB($table = '')
    {
        if ('' !== $table) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $this->fieldClean($table);

            $sql = \sprintf(
                'ANALYZE "%s"."%s"',
                $f_schema,
                $table
            );
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
     * @return \ADORecordSet|bool|int|string
     */
    public function getDatabase($database)
    {
        $this->clean($database);
        $sql = \sprintf(
            'SELECT * FROM pg_database WHERE datname=\'%s\'',
            $database
        );

        return $this->selectSet($sql);
    }

    /**
     * Return all database available on the server.
     *
     * @param null|string $currentdatabase database name that should be on top of the resultset
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getDatabases($currentdatabase = null)
    {
        $conf = $this->conf;
        $server_info = $this->server_info;

        //$this->prtrace('server_info', $server_info);

        if (isset($conf['owned_only']) && $conf['owned_only'] && !$this->isSuperUser()) {
            $username = $server_info['username'];
            $this->clean($username);
            $clause = \sprintf(
                ' AND pr.rolname=\'%s\'',
                $username
            );
        } else {
            $clause = '';
        }

        if (isset($server_info['useonlydefaultdb']) && $server_info['useonlydefaultdb']) {
            $currentdatabase = $server_info['defaultdb'];
            $clause .= \sprintf(
                ' AND pdb.datname = \'%s\' ',
                $currentdatabase
            );
        }

        if (isset($server_info['hiddendbs']) && $server_info['hiddendbs']) {
            $hiddendbs = $server_info['hiddendbs'];

            $not_in = "('" . \implode("','", $hiddendbs) . "')";
            $clause .= \sprintf(
                ' AND pdb.datname NOT IN %s ',
                $not_in
            );
        }

        if (null !== $currentdatabase) {
            $this->clean($currentdatabase);
            $orderby = \sprintf(
                'ORDER BY pdb.datname = \'%s\' DESC, pdb.datname',
                $currentdatabase
            );
        } else {
            $orderby = 'ORDER BY pdb.datname';
        }

        $where = $conf['show_system'] ? ' AND pdb.datallowconn' : ' AND NOT pdb.datistemplate';

        $sql = \sprintf(
            '
            SELECT pdb.datname AS datname,
                    pr.rolname AS datowner,
                    pg_encoding_to_char(encoding) AS datencoding,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pdb.oid=pd.objoid AND pd.classoid=\'pg_database\'::regclass) AS datcomment,
                    (SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=pdb.dattablespace) AS tablespace,
                CASE WHEN pg_catalog.has_database_privilege(current_user, pdb.oid, \'CONNECT\')
                    THEN pg_catalog.pg_database_size(pdb.oid)
                    ELSE -1 -- set this magic value, which we will convert to no access later
                END as dbsize,
                pdb.datcollate,
                pdb.datctype
            FROM pg_catalog.pg_database pdb
            LEFT JOIN pg_catalog.pg_roles pr ON (pdb.datdba = pr.oid)
            WHERE true
                %s
                %s
            %s',
            $where,
            $clause,
            $orderby
        );

        return $this->selectSet($sql);
    }

    /**
     * Return the database comment of a db from the shared description table.
     *
     * @param string $database the name of the database to get the comment for
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getDatabaseComment($database)
    {
        $this->clean($database);
        $sql = \sprintf(
            'SELECT description
                FROM pg_catalog.pg_database
                JOIN pg_catalog.pg_shdescription
                ON (oid=objoid AND classoid=\'pg_database\'::regclass)
                WHERE pg_database.datname = \'%s\' ',
            $database
        );

        return $this->selectSet($sql);
    }

    /**
     * Return the database owner of a db.
     *
     * @param string $database the name of the database to get the owner for
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getDatabaseOwner($database)
    {
        $this->clean($database);
        $sql = \sprintf(
            'SELECT usename FROM pg_user, pg_database WHERE pg_user.usesysid = pg_database.datdba AND pg_database.datname = \'%s\' ',
            $database
        );

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
        return \pg_parameter_status($this->conn->_connectionID, 'server_encoding');
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
     * @return int
     *
     * @psalm-return -2|-1|0
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

        $sql = \sprintf(
            'CREATE DATABASE "%s" WITH TEMPLATE="%s"',
            $database,
            $template
        );

        if ('' !== $encoding) {
            $sql .= \sprintf(
                ' ENCODING=\'%s\'',
                $encoding
            );
        }

        if ('' !== $lc_collate) {
            $sql .= \sprintf(
                ' LC_COLLATE=\'%s\'',
                $lc_collate
            );
        }

        if ('' !== $lc_ctype) {
            $sql .= \sprintf(
                ' LC_CTYPE=\'%s\'',
                $lc_ctype
            );
        }

        if ('' !== $tablespace && $this->hasTablespaces()) {
            $sql .= \sprintf(
                ' TABLESPACE "%s"',
                $tablespace
            );
        }

        $status = $this->execute($sql);

        if (0 !== $status) {
            return -1;
        }

        if ('' !== $comment && $this->hasSharedComments()) {
            $status = $this->setComment('DATABASE', $database, '', $comment);

            if (0 !== $status) {
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
     * @return int|string
     */
    public function dropDatabase($database)
    {
        $this->fieldClean($database);
        $sql = \sprintf(
            'DROP DATABASE "%s"',
            $database
        );

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
     * @return int
     *
     * @psalm-return -4|-3|-2|-1|0|1
     */
    public function alterDatabase($dbName, $newName, $newOwner = '', $comment = '')
    {
        $status = $this->beginTransaction();

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        if ($dbName !== $newName) {
            $status = $this->alterDatabaseRename($dbName, $newName);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -3;
            }
            $dbName = $newName;
        }

        if ('' !== $newOwner) {
            $status = $this->alterDatabaseOwner($newName, $newOwner);

            if (0 !== $status) {
                $this->rollbackTransaction();

                return -2;
            }
        }

        $this->fieldClean($dbName);
        $status = $this->setComment('DATABASE', $dbName, '', $comment);

        if (0 !== $status) {
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
     * @return int|string
     */
    public function alterDatabaseRename($oldName, $newName)
    {
        $this->fieldClean($oldName);
        $this->fieldClean($newName);

        if ($oldName !== $newName) {
            $sql = \sprintf(
                'ALTER DATABASE "%s" RENAME TO "%s"',
                $oldName,
                $newName
            );

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
     * @return int|string
     */
    public function alterDatabaseOwner($dbName, $newOwner)
    {
        $this->fieldClean($dbName);
        $this->fieldClean($newOwner);

        $sql = \sprintf(
            'ALTER DATABASE "%s" OWNER TO "%s"',
            $dbName,
            $newOwner
        );

        return $this->execute($sql);
    }

    /**
     * Returns prepared transactions information.
     *
     * @param null|string $database (optional) Find only prepared transactions executed in a specific database
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getPreparedXacts($database = null)
    {
        if (null === $database) {
            $sql = 'SELECT * FROM pg_prepared_xacts';
        } else {
            $this->clean($database);
            $sql = \sprintf(
                'SELECT transaction, gid, prepared, owner FROM pg_prepared_xacts
                WHERE database=\'%s\' ORDER BY owner',
                $database
            );
        }

        return $this->selectSet($sql);
    }

    /**
     * Returns all available process information.
     *
     * @param null|string $database (optional) Find only connections to specified database
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getProcesses($database = null)
    {
        if (null === $database) {
            $sql = "SELECT datname, usename, pid, waiting, state_change as query_start,
                  case when state='idle in transaction' then '<IDLE> in transaction' when state = 'idle' then '<IDLE>' else query end as query
                FROM pg_catalog.pg_stat_activity
                ORDER BY datname, usename, pid";
        } else {
            $this->clean($database);
            $sql = \sprintf(
                'SELECT datname, usename, pid, waiting, state_change as query_start,
                  case when state=\'idle in transaction\' then \'<IDLE> in transaction\' when state = \'idle\' then \'<IDLE>\' else query end as query
                FROM pg_catalog.pg_stat_activity
                WHERE datname=\'%s\'
                ORDER BY usename, pid',
                $database
            );
        }

        return $this->selectSet($sql);
    }

    // interfaces Statistics collector functions

    /**
     * Returns table locks information in the current database.
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getLocks()
    {
        $conf = $this->conf;

        $where = $conf['show_system'] ? "AND nspname !~ '^pg_t(emp_[0-9]+|oast)$'" : 'AND pn.nspname NOT LIKE $$pg\_%$$';

        $sql = \sprintf(
            '
            SELECT
                pn.nspname, pc.relname AS tablename, pl.pid, pl.mode, pl.granted, pl.virtualtransaction,
                (select transactionid from pg_catalog.pg_locks l2 where l2.locktype=\'transactionid\'
                    and l2.mode=\'ExclusiveLock\' and l2.virtualtransaction=pl.virtualtransaction) as transaction
            FROM
                pg_catalog.pg_locks pl,
                pg_catalog.pg_class pc,
                pg_catalog.pg_namespace pn
            WHERE
                pl.relation = pc.oid AND pc.relnamespace=pn.oid
            %s
            ORDER BY pid,nspname,tablename',
            $where
        );

        return $this->selectSet($sql);
    }

    /**
     * Sends a cancel or kill command to a process.
     *
     * @param int    $pid    The ID of the backend process
     * @param string $signal 'CANCEL' or 'KILL'
     *
     * @return int
     *
     * @psalm-return -1|0
     */
    public function sendSignal($pid, $signal)
    {
        // Clean
        $pid = (int) $pid;

        if ('CANCEL' === $signal) {
            $sql = \sprintf(
                'SELECT pg_catalog.pg_cancel_backend(%s) AS val',
                $pid
            );
        } elseif ('KILL' === $signal) {
            $sql = \sprintf(
                'SELECT pg_catalog.pg_terminate_backend(%s) AS val',
                $pid
            );
        } else {
            return -1;
        }

        // Execute the query
        $val = $this->selectField($sql, 'val');

        if ('f' === $val) {
            return -1;
        }

        if ('t' === $val) {
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
     * @return (int|string)[]
     *
     * @psalm-return array{0: int|string, 1: string}
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

        if ('' !== $table) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $this->fieldClean($table);
            $sql .= \sprintf(
                ' "%s"."%s"',
                $f_schema,
                $table
            );
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
            $_defaults->MoveNext();
        }

        return $ret;
    }

    /**
     * Returns all available variable information.
     *
     * @return \ADORecordSet|bool|int|string
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
