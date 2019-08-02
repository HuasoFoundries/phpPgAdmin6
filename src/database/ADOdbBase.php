<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * Parent class of all ADODB objects.
 *
 * Id: ADOdbBase.php,v 1.24 2008/02/20 20:43:10 ioguix Exp $
 *
 * @package PHPPgAdmin
 */
class ADOdbBase
{
    use \PHPPgAdmin\Traits\HelperTrait;
    use \PHPPgAdmin\Database\Traits\HasTrait;

    public $lang;
    public $conf;
    protected $container;
    protected $server_info;

    /**
     * Base constructor.
     *
     * @param \PHPPgAdmin\ADONewConnection $conn        The connection object
     * @param mixed                        $container
     * @param mixed                        $server_info
     */
    public function __construct(&$conn, $container, $server_info)
    {
        $this->container   = $container;
        $this->server_info = $server_info;

        $this->lang = $container->get('lang');
        $this->conf = $container->get('conf');

        $this->prtrace('instanced connection class');
        $this->conn = $conn;
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
     * @return int|\PHPPgAdmin\ADORecordSet recordset of results or error code
     */
    public function setComment($obj_type, $obj_name, $table, $comment, $basetype = null)
    {
        $sql = "COMMENT ON {$obj_type} ";

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
                $sql .= "\"{$f_schema}\".\"{$table}\" IS ";

                break;
            case 'COLUMN':
                $sql .= "\"{$f_schema}\".\"{$table}\".\"{$obj_name}\" IS ";

                break;
            case 'SEQUENCE':
            case 'VIEW':
            case 'MATERIALIZED VIEW':
            case 'TEXT SEARCH CONFIGURATION':
            case 'TEXT SEARCH DICTIONARY':
            case 'TEXT SEARCH TEMPLATE':
            case 'TEXT SEARCH PARSER':
            case 'TYPE':
                $sql .= "\"{$f_schema}\".";
            // no break
            case 'DATABASE':
            case 'ROLE':
            case 'SCHEMA':
            case 'TABLESPACE':
                $sql .= "\"{$obj_name}\" IS ";

                break;
            case 'FUNCTION':
                $sql .= "\"{$f_schema}\".{$obj_name} IS ";

                break;
            case 'AGGREGATE':
                $sql .= "\"{$f_schema}\".\"{$obj_name}\" (\"{$basetype}\") IS ";

                break;
            default:
                // Unknown object type
                return -1;
        }

        if ($comment != '') {
            $sql .= "'{$comment}';";
        } else {
            $sql .= 'NULL;';
        }

        return $this->execute($sql);
    }

    /**
     * Turns on or off query debugging.
     *
     * @param bool $debug True to turn on debugging, false otherwise
     */
    public function setDebug($debug)
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
            if ($v === null) {
                continue;
            }

            $arr[$k] = str_replace('"', '""', $v);
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
            if ($v === null) {
                continue;
            }
            $arr[$k] = pg_escape_string($v);
        }

        return $arr;
    }

    /**
     * Executes a query on the underlying connection.
     *
     * @param string $sql The SQL query to execute
     *
     * @return int|\PHPPgAdmin\ADORecordSet A recordset or an error code
     */
    public function execute($sql)
    {
        // Execute the statement
        try {
            $rs = $this->conn->Execute($sql);

            return $this->conn->ErrorNo();
        } catch (\Exception $e) {
            return $e->getCode();
        }
    }

    /**
     * Closes the connection the database class
     * relies on.
     */
    public function close()
    {
        $this->conn->close();
    }

    /**
     * Retrieves a ResultSet from a query.
     *
     * @param string $sql The SQL statement to be executed
     *
     * @return int|\PHPPgAdmin\ADORecordSet A recordset or an error number
     */
    public function selectSet($sql)
    {
        // Execute the statement
        try {
            $rs = $this->conn->Execute($sql);

            return $rs;
        } catch (\Exception $e) {
            return $e->getCode();
        }
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
            return $this->conn->ErrorNo();
        }

        if ($rs->recordCount() == 0) {
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

        reset($conditions);

        if (!empty($schema)) {
            $this->fieldClean($schema);
            $schema = "\"{$schema}\".";
        }

        // Build clause
        $sql = '';
        //while (list($key, $value) = each($conditions)) {
        foreach ($conditions as $key => $value) {
            $this->clean($key);
            $this->clean($value);
            if ($sql) {
                $sql .= " AND \"{$key}\"='{$value}'";
            } else {
                $sql = "DELETE FROM {$schema}\"{$table}\" WHERE \"{$key}\"='{$value}'";
            }
        }

        // Check for failures
        if (!$this->conn->Execute($sql)) {
            // Check for referential integrity failure
            if (stristr($this->conn->ErrorMsg(), 'referential')) {
                return -1;
            }
        }

        // Check for no rows modified
        if ($this->conn->Affected_Rows() == 0) {
            return -2;
        }

        return $this->conn->ErrorNo();
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
        if ($str === null) {
            return null;
        }
        $str = str_replace('"', '""', $str);

        return $str;
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
        if ($str === null) {
            return null;
        }
        $str = str_replace("\r\n", "\n", $str);
        $str = pg_escape_string($str);

        return $str;
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
        return htmlentities($data, ENT_QUOTES, 'UTF-8');
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
        if (sizeof($vars) > 0) {
            $fields = '';
            $values = '';
            foreach ($vars as $key => $value) {
                $this->clean($key);
                $this->clean($value);

                if ($fields) {
                    $fields .= ", \"{$key}\"";
                } else {
                    $fields = "INSERT INTO \"{$table}\" (\"{$key}\"";
                }

                if ($values) {
                    $values .= ", '{$value}'";
                } else {
                    $values = ") VALUES ('{$value}'";
                }
            }
            $sql .= $fields.$values.')';
        }

        // Check for failures
        if (!$this->conn->Execute($sql)) {
            // Check for unique constraint failure
            if (stristr($this->conn->ErrorMsg(), 'unique')) {
                return -1;
            }

            if (stristr($this->conn->ErrorMsg(), 'referential')) {
                return -2;
            } // Check for referential integrity failure
        }

        return $this->conn->ErrorNo();
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

        $setClause   = '';
        $whereClause = '';

        // Populate the syntax arrays
        reset($vars);
        //while (list($key, $value) = each($vars)) {
        foreach ($vars as $key => $value) {
            $this->fieldClean($key);
            $this->clean($value);
            if ($setClause) {
                $setClause .= ", \"{$key}\"='{$value}'";
            } else {
                $setClause = "UPDATE \"{$table}\" SET \"{$key}\"='{$value}'";
            }
        }

        reset($nulls);
        //while (list(, $value) = each($nulls)) {
        foreach ($nulls as $key => $value) {
            $this->fieldClean($value);
            if ($setClause) {
                $setClause .= ", \"{$value}\"=NULL";
            } else {
                $setClause = "UPDATE \"{$table}\" SET \"{$value}\"=NULL";
            }
        }

        reset($where);
        //while (list($key, $value) = each($where)) {
        foreach ($where as $key => $value) {
            $this->fieldClean($key);
            $this->clean($value);
            if ($whereClause) {
                $whereClause .= " AND \"{$key}\"='{$value}'";
            } else {
                $whereClause = " WHERE \"{$key}\"='{$value}'";
            }
        }

        // Check for failures
        if (!$this->conn->Execute($setClause.$whereClause)) {
            // Check for unique constraint failure
            if (stristr($this->conn->ErrorMsg(), 'unique')) {
                return -1;
            }

            if (stristr($this->conn->ErrorMsg(), 'referential')) {
                return -2;
            } // Check for referential integrity failure
        }

        // Check for no rows modified
        if ($this->conn->Affected_Rows() == 0) {
            return -3;
        }

        return $this->conn->ErrorNo();
    }

    /**
     * Begin a transaction.
     *
     * @return bool 0 success
     */
    public function beginTransaction()
    {
        return !$this->conn->BeginTrans();
    }

    /**
     * End a transaction.
     *
     * @return bool 0 success
     */
    public function endTransaction()
    {
        return !$this->conn->CommitTrans();
    }

    /**
     * Roll back a transaction.
     *
     * @return bool 0 success
     */
    public function rollbackTransaction()
    {
        return !$this->conn->RollbackTrans();
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
        } catch (\Exception $e) {
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
        if ($parameter) {
            $parameter = 't';
        } else {
            $parameter = 'f';
        }

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
        return $parameter === 't';
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
        $arr = substr($dbarr, 1, strlen($dbarr) - 2);

        // Pick out array entries by carefully parsing.  This is necessary in order
        // to cope with double quotes and commas, etc.
        $elements  = [];
        $i         = $j         = 0;
        $in_quotes = false;
        while ($i < strlen($arr)) {
            // If current char is a double quote and it's not escaped, then
            // enter quoted bit
            $char = substr($arr, $i, 1);
            if ($char == '"' && ($i == 0 || substr($arr, $i - 1, 1) != '\\')) {
                $in_quotes = !$in_quotes;
            } elseif ($char == ',' && !$in_quotes) {
                // Add text so far to the array
                $elements[] = substr($arr, $j, $i - $j);
                $j          = $i + 1;
            }
            ++$i;
        }
        // Add final text to the array
        $elements[] = substr($arr, $j);

        $elementcount = sizeof($elements);
        // Do one further loop over the elements array to remote double quoting
        // and escaping of double quotes and backslashes
        for ($i = 0; $i < $elementcount; ++$i) {
            $v = $elements[$i];
            if (strpos($v, '"') === 0) {
                $v            = substr($v, 1, strlen($v) - 2);
                $v            = str_replace('\\"', '"', $v);
                $v            = str_replace('\\\\', '\\', $v);
                $elements[$i] = $v;
            }
        }

        return $elements;
    }
}
