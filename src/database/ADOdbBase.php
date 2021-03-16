<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Database;

use ADODB_postgres9;
use Exception;
use PHPPgAdmin\ADORecordSet;
use PHPPgAdmin\ContainerUtils;
use PHPPgAdmin\Database\Traits\DatabaseTrait;
use PHPPgAdmin\Database\Traits\HasTrait;
use PHPPgAdmin\Traits\HelperTrait;

/**
 * @file
 * Parent class of all ADODB objects.
 *
 * Id: ADOdbBase.php,v 1.24 2008/02/20 20:43:10 ioguix Exp $
 */
class ADOdbBase
{
    use HelperTrait;
    use HasTrait;
    use DatabaseTrait;

    /**
     * @var array
     */
    public $lang;

    /**
     * @var array
     */
    public $conf;

    /**
     * @var ADODB_postgres9
     */
    public $conn;

    /**
     * @var ContainerUtils
     */
    protected $container;

    /**
     * @var array
     */
    protected $server_info;

    /**
     * @var string
     */
    protected $lastExecutedSql;

    /**
     * Base constructor.
     *
     * @param ADODB_postgres9 $conn        The connection object
     * @param mixed           $container
     * @param mixed           $server_info
     */
    public function __construct(&$conn, $container, $server_info)
    {
        $this->container = $container;
        $this->server_info = $server_info;

        $this->lang = $container->get('lang');
        $this->conf = $container->get('conf');

        $this->prtrace('instanced connection class');
        $this->lastExecutedSql = '';
        $this->conn = $conn;
    }

    /**
     * Given an array of attnums and a relation, returns an array mapping
     * attribute number to attribute name.
     *
     * @param string $table The table to get attributes for
     * @param array  $atts  An array of attribute numbers
     *
     * @return array|int An array mapping attnum to attname or error code
     *                   - -1 $atts must be an array
     *                   - -2 wrong number of attributes found
     */
    public function getAttributeNames($table, $atts)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);
        $this->arrayClean($atts);

        if (!\is_array($atts)) {
            return -1;
        }

        if (0 === \count($atts)) {
            return [];
        }

        $sql = \sprintf(
            'SELECT attnum, attname FROM pg_catalog.pg_attribute WHERE
			attrelid=(SELECT oid FROM pg_catalog.pg_class WHERE relname=\'%s\' AND
			relnamespace=(SELECT oid FROM pg_catalog.pg_namespace WHERE nspname=\'%s\'))
			AND attnum IN (\'',
            $table,
            $c_schema
        ) . \implode("','", $atts) . "')";

        $rs = $this->selectSet($sql);

        if ($rs->RecordCount() !== \count($atts)) {
            return -2;
        }

        $temp = [];

        while (!$rs->EOF) {
            $temp[$rs->fields['attnum']] = $rs->fields['attname'];
            $rs->MoveNext();
        }

        return $temp;
    }

    /**
     * Sets the comment for an object in the database.
     *
     * @pre All parameters must already be cleaned
     *
     * @param string      $obj_type One of 'TABLE' | 'COLUMN' | 'VIEW' | 'SCHEMA' | 'SEQUENCE' | 'TYPE' | 'FUNCTION' | 'AGGREGATE'
     * @param string      $obj_name the name of the object for which to attach a comment
     * @param string      $table    Name of table that $obj_name belongs to.  Ignored unless $obj_type is 'TABLE' or 'COLUMN'.
     * @param string      $comment  the comment to add
     * @param null|string $basetype
     *
     * @return ADORecordSet|int recordset of results or error code
     */
    public function setComment($obj_type, $obj_name, $table, $comment, $basetype = null)
    {
        $sql = \sprintf(
            'COMMENT ON %s ',
            $obj_type
        );

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->clean($comment); // Passing in an already cleaned comment will lead to double escaped data
        // So, while counter-intuitive, it is important to not clean comments before
        // calling setComment. We will clean it here instead.
        /*
         * $this->fieldClean($table);
         * $this->fieldClean($obj_name);
         */

        switch ($obj_type) {
            case 'TABLE':
                $sql .= \sprintf(
                    '"%s"."%s" IS ',
                    $f_schema,
                    $table
                );

                break;
            case 'COLUMN':
                $sql .= \sprintf(
                    '"%s"."%s"."%s" IS ',
                    $f_schema,
                    $table,
                    $obj_name
                );

                break;
            case 'SEQUENCE':
            case 'VIEW':
            case 'MATERIALIZED VIEW':
            case 'TEXT SEARCH CONFIGURATION':
            case 'TEXT SEARCH DICTIONARY':
            case 'TEXT SEARCH TEMPLATE':
            case 'TEXT SEARCH PARSER':
            case 'TYPE':
                $sql .= \sprintf(
                    '"%s".',
                    $f_schema
                );
            // no break
            case 'DATABASE':
            case 'ROLE':
            case 'SCHEMA':
            case 'TABLESPACE':
                $sql .= \sprintf(
                    '"%s" IS ',
                    $obj_name
                );

                break;
            case 'FUNCTION':
                $sql .= \sprintf(
                    '"%s".%s IS ',
                    $f_schema,
                    $obj_name
                );

                break;
            case 'AGGREGATE':
                $sql .= \sprintf(
                    '"%s"."%s" ("%s") IS ',
                    $f_schema,
                    $obj_name,
                    $basetype
                );

                break;

            default:
                // Unknown object type
                return -1;
        }

        if ('' !== $comment) {
            $sql .= \sprintf(
                '\'%s\';',
                $comment
            );
        } else {
            $sql .= 'NULL;';
        }
        $this->lastExecutedSql = $sql;

        return $this->execute($sql);
    }

    public function getLastExecutedSQL(): string
    {
        $lastExecutedSql = $this->lastExecutedSql;
        $this->lastExecutedSql = '';

        return $lastExecutedSql;
    }

    /**
     * Turns on or off query debugging.
     *
     * @param bool $debug True to turn on debugging, false otherwise
     */
    public function setDebug($debug): void
    {
        $this->conn->debug = $debug;
    }

    /**
     * Cleans (escapes) an array of field names.
     *
     * @param array $arr The array to clean, by reference
     *
     * @return array The cleaned array
     */
    public function fieldArrayClean(&$arr)
    {
        foreach ($arr as $k => $v) {
            if (null === $v) {
                continue;
            }

            $arr[$k] = \str_replace('"', '""', $v);
        }

        return $arr;
    }

    /**
     * Cleans (escapes) an array.
     *
     * @param array $arr The array to clean, by reference
     *
     * @return array The cleaned array
     */
    public function arrayClean(&$arr)
    {
        foreach ($arr as $k => $v) {
            if (null === $v) {
                continue;
            }
            $arr[$k] = \pg_escape_string($v);
        }

        return $arr;
    }

    /**
     * Executes a query on the underlying connection.
     *
     * @param string $sql The SQL query to execute
     *
     * @return int|string A recordset or an error code
     */
    public function execute($sql)
    {
        // Execute the statement
        try {
            $rs = $this->conn->Execute($sql);

            return $this->ErrorNo();
        } catch (Exception $e) {
            return $e->getCode();
        }
    }

    /**
     * Closes the connection the database class
     * relies on.
     */
    public function close(): void
    {
        $this->conn->close();
    }

    /**
     * Retrieves a ResultSet from a query.
     *
     * @param string $sql The SQL statement to be executed
     *
     * @return int|\RecordSet|string A recordset or an error number
     */
    public function selectSet($sql)
    {
        // Execute the statement
        try {
            return $this->conn->Execute($sql);
        } catch (Exception $e) {
            return $e->getCode();
        }
    }

    /**
     * @return \the
     */
    public function ErrorNo(): int
    {
        return $this->conn->ErrorNo();
    }

    /**
     * @return \the
     */
    public function ErrorMsg(): string
    {
        return $this->conn->ErrorMsg();
    }

    /**
     * Retrieves a single value from a query.
     *
     * @@ assumes that the query will return only one row - returns field value in the first row
     *
     * @param string $sql   The SQL statement to be executed
     * @param string $field The field name to be returned
     *
     * @return int|string single field value, error number on error or -1 if no rows where found
     */
    public function selectField($sql, $field)
    {
        // Execute the statement
        $rs = $this->conn->Execute($sql);

        // If failure, or no rows returned, return error value
        if (!$rs) {
            return $this->ErrorNo();
        }

        if (0 === $rs->RecordCount()) {
            return -1;
        }

        return $rs->fields[$field];
    }

    /**
     * Delete from the database.
     *
     * @param string $table      The name of the table
     * @param array  $conditions (array) A map of field names to conditions
     * @param string $schema     (optional) The table's schema
     *
     * @return int 0 success
     */
    public function delete($table, $conditions, $schema = '')
    {
        $this->fieldClean($table);

        \reset($conditions);

        if (!empty($schema)) {
            $this->fieldClean($schema);
            $schema = \sprintf(
                '"%s".',
                $schema
            );
        }

        // Build clause
        $sql = '';
        //while (list($key, $value) = each($conditions)) {
        foreach ($conditions as $key => $value) {
            $this->clean($key);
            $this->clean($value);

            if ($sql !== '') {
                $sql .= \sprintf(
                    ' AND "%s"=\'%s\'',
                    $key,
                    $value
                );
            } else {
                $sql = \sprintf(
                    'DELETE FROM %s"%s" WHERE "%s"=\'%s\'',
                    $schema,
                    $table,
                    $key,
                    $value
                );
            }
        }

        // Check for failures
        // Check for referential integrity failure
        if (!$this->conn->Execute($sql) && \mb_stristr($this->ErrorMsg(), 'referential')) {
            return -1;
        }

        // Check for no rows modified
        if (0 === $this->conn->Affected_Rows()) {
            return -2;
        }

        return $this->ErrorNo();
    }

    /**
     * Cleans (escapes) an object name (eg. table, field).
     *
     * @param null|string $str The string to clean, by reference
     *
     * @return null|string The cleaned string
     */
    public function fieldClean(&$str)
    {
        if (null === $str) {
            return null;
        }

        return \str_replace('"', '""', $str);
    }

    /**
     * Cleans (escapes) a string.
     *
     * @param null|string $str The string to clean, by reference
     *
     * @return null|string The cleaned string
     */
    public function clean(&$str)
    {
        if (null === $str) {
            return null;
        }
        $str = \str_replace("\r\n", "\n", $str);

        return \pg_escape_string($str);
    }

    /**
     * Escapes bytea data for display on the screen.
     *
     * @param string $data The bytea data
     *
     * @return string Data formatted for on-screen display
     */
    public function escapeBytea($data)
    {
        return \htmlentities($data, \ENT_QUOTES, 'UTF-8');
    }

    /**
     * Insert a set of values into the database.
     *
     * @param string $table The table to insert into
     * @param array  $vars  (array) A mapping of the field names to the values to be inserted
     *
     * @return int 0 success
     */
    public function insert($table, $vars)
    {
        $this->fieldClean($table);
        $sql = '';
        // Build clause
        if (0 < \count($vars)) {
            $fields = '';
            $values = '';

            foreach ($vars as $key => $value) {
                $this->clean($key);
                $this->clean($value);

                if ($fields !== '') {
                    $fields .= \sprintf(
                        ', "%s"',
                        $key
                    );
                } else {
                    $fields = \sprintf(
                        'INSERT INTO "%s" ("%s"',
                        $table,
                        $key
                    );
                }

                if ($values !== '') {
                    $values .= \sprintf(
                        ', \'%s\'',
                        $value
                    );
                } else {
                    $values = \sprintf(
                        ') VALUES (\'%s\'',
                        $value
                    );
                }
            }
            $sql .= $fields . $values . ')';
        }

        // Check for failures
        if (!$this->conn->Execute($sql)) {
            // Check for unique constraint failure
            if (\mb_stristr($this->ErrorMsg(), 'unique')) {
                return -1;
            }

            if (\mb_stristr($this->ErrorMsg(), 'referential')) {
                return -2;
            } // Check for referential integrity failure
        }

        return $this->ErrorNo();
    }

    /**
     * Update a row in the database.
     *
     * @param string $table The table that is to be updated
     * @param array  $vars  (array) A mapping of the field names to the values to be updated
     * @param array  $where (array) A mapping of field names to values for the where clause
     * @param array  $nulls (array, optional) An array of fields to be set null
     *
     * @return int 0 success
     */
    public function update($table, $vars, $where, $nulls = [])
    {
        $this->fieldClean($table);

        $setClause = '';
        $whereClause = '';

        // Populate the syntax arrays
        \reset($vars);
        //while (list($key, $value) = each($vars)) {
        foreach ($vars as $key => $value) {
            $this->fieldClean($key);
            $this->clean($value);

            if ($setClause !== '') {
                $setClause .= \sprintf(
                    ', "%s"=\'%s\'',
                    $key,
                    $value
                );
            } else {
                $setClause = \sprintf(
                    'UPDATE "%s" SET "%s"=\'%s\'',
                    $table,
                    $key,
                    $value
                );
            }
        }

        \reset($nulls);
        //while (list(, $value) = each($nulls)) {
        foreach ($nulls as $key => $value) {
            $this->fieldClean($value);

            if ($setClause !== '') {
                $setClause .= \sprintf(
                    ', "%s"=NULL',
                    $value
                );
            } else {
                $setClause = \sprintf(
                    'UPDATE "%s" SET "%s"=NULL',
                    $table,
                    $value
                );
            }
        }

        \reset($where);
        //while (list($key, $value) = each($where)) {
        foreach ($where as $key => $value) {
            $this->fieldClean($key);
            $this->clean($value);

            if ($whereClause !== '') {
                $whereClause .= \sprintf(
                    ' AND "%s"=\'%s\'',
                    $key,
                    $value
                );
            } else {
                $whereClause = \sprintf(
                    ' WHERE "%s"=\'%s\'',
                    $key,
                    $value
                );
            }
        }

        // Check for failures
        if (!$this->conn->Execute($setClause . $whereClause)) {
            // Check for unique constraint failure
            if (\mb_stristr($this->ErrorMsg(), 'unique')) {
                return -1;
            }

            if (\mb_stristr($this->ErrorMsg(), 'referential')) {
                return -2;
            } // Check for referential integrity failure
        }

        // Check for no rows modified
        if (0 === $this->conn->Affected_Rows()) {
            return -3;
        }

        return $this->ErrorNo();
    }

    /**
     * Begin a transaction.
     *
     * @return int 0 success
     */
    public function beginTransaction()
    {
        return (int) (!$this->conn->BeginTrans());
    }

    /**
     * End a transaction.
     *
     * @return int 0 success
     */
    public function endTransaction()
    {
        return (int) (!$this->conn->CommitTrans());
    }

    /**
     * Roll back a transaction.
     *
     * @return int 0 success
     */
    public function rollbackTransaction()
    {
        return (int) (!$this->conn->RollbackTrans());
    }

    /**
     * Get the backend platform.
     *
     * @return string The backend platform
     */
    public function getPlatform()
    {
        try {
            return $this->conn->platform;
        } catch (Exception $e) {
            $this->prtrace($e->getMessage());

            return 'UNKNOWN';
        }
    }

    // Type conversion routines

    /**
     * Change the value of a parameter to database representation depending on whether it evaluates to true or false.
     *
     * @param mixed $parameter the parameter
     *
     * @return string boolean  database representation
     */
    public function dbBool(&$parameter)
    {
        $parameter = $parameter ? 't' : 'f';

        return $parameter;
    }

    /**
     * Change a parameter from database representation to a boolean, (others evaluate to false).
     *
     * @param string $parameter the parameter
     *
     * @return bool
     */
    public function phpBool($parameter)
    {
        return 't' === $parameter;
    }

    /**
     * Change a db array into a PHP array.
     *
     * @param string $dbarr
     *
     * @return array A PHP array
     *
     * @internal param String $arr representing the DB array
     */
    public function phpArray($dbarr)
    {
        // Take off the first and last characters (the braces)
        $arr = \mb_substr($dbarr, 1, \mb_strlen($dbarr) - 2);

        // Pick out array entries by carefully parsing.  This is necessary in order
        // to cope with double quotes and commas, etc.
        $elements = [];
        $i = $j = 0;
        $in_quotes = false;

        while (\mb_strlen($arr) > $i) {
            // If current char is a double quote and it's not escaped, then
            // enter quoted bit
            $char = \mb_substr($arr, $i, 1);

            if ('"' === $char && (0 === $i || '\\' !== \mb_substr($arr, $i - 1, 1))) {
                $in_quotes = !$in_quotes;
            } elseif (',' === $char && !$in_quotes) {
                // Add text so far to the array
                $elements[] = \mb_substr($arr, $j, $i - $j);
                $j = $i + 1;
            }
            ++$i;
        }
        // Add final text to the array
        $elements[] = \mb_substr($arr, $j);

        $elementcount = \count($elements);
        // Do one further loop over the elements array to remote double quoting
        // and escaping of double quotes and backslashes
        for ($i = 0; $i < $elementcount; ++$i) {
            $v = $elements[$i];

            if (0 === \mb_strpos($v, '"')) {
                $v = \mb_substr($v, 1, \mb_strlen($v) - 2);
                $v = \str_replace('\\"', '"', $v);
                $v = \str_replace('\\\\', '\\', $v);
                $elements[$i] = $v;
            }
        }

        return $elements;
    }
}
