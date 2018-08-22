<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for privileges manipulation.
 */
trait PrivilegesTrait
{
    /**
     * Grabs an array of users and their privileges for an object,
     * given its type.
     *
     * @param string      $object The name of the object whose privileges are to be retrieved
     * @param string      $type   The type of the object (eg. database, schema, relation, function or language)
     * @param null|string $table  Optional, column's table if type = column
     *
     * @return array|int Privileges array or error code
     *                   - -1         invalid type
     *                   - -2         object not found
     *                   - -3         unknown privilege type
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

        return $this->parseACL($acl);
    }

    /**
     * Internal function used for parsing ACLs.
     *
     * @param string $acl The ACL to parse (of type aclitem[])
     *
     * @return array|int Privileges array or integer with error code
     *
     * @internal bool $in_quotes toggles acl in_quotes attribute
     */
    protected function parseACL($acl)
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
            // $unquote = false;
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
                } elseif ($char == '"' && $next_char == '"') {
                    // Skip over escaped double quotes
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

    /**
     * Grants a privilege to a user, group or public.
     *
     * @param string $mode        'GRANT' or 'REVOKE';
     * @param mixed  $type        The type of object
     * @param string $object      The name of the object
     * @param bool   $public      True to grant to public, false otherwise
     * @param mixed  $usernames   the array of usernames to grant privs to
     * @param mixed  $groupnames  the array of group names to grant privs to
     * @param mixed  $privileges  The array of privileges to grant (eg. ('SELECT', 'ALL PRIVILEGES', etc.) )
     * @param bool   $grantoption True if has grant option, false otherwise
     * @param bool   $cascade     True for cascade revoke, false otherwise
     * @param string $table       the column's table if type=column
     *
     * @return int 0 if operation was successful
     */
    public function setPrivileges(
        $mode,
        $type,
        $object,
        $public,
        $usernames,
        $groupnames,
        $privileges,
        $grantoption,
        $cascade,
        $table
    ) {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldArrayClean($usernames);
        $this->fieldArrayClean($groupnames);

        // Input checking
        if (!is_array($privileges) || sizeof($privileges) == 0) {
            return -3;
        }

        if (!is_array($usernames) || !is_array($groupnames) ||
            (!$public && sizeof($usernames) == 0 && sizeof($groupnames) == 0)) {
            return -4;
        }

        if ($mode != 'GRANT' && $mode != 'REVOKE') {
            return -5;
        }

        $sql = $mode;

        // Grant option
        if ($this->hasGrantOption() && $mode == 'REVOKE' && $grantoption) {
            $sql .= ' GRANT OPTION FOR';
        }

        if (in_array('ALL PRIVILEGES', $privileges, true)) {
            $sql .= ' ALL PRIVILEGES';
        } else {
            if ($type == 'column') {
                $this->fieldClean($object);
                $sql .= ' '.join(" (\"{$object}\"), ", $privileges);
            } else {
                $sql .= ' '.join(', ', $privileges);
            }
        }

        switch ($type) {
            case 'column':
                $sql .= " (\"{$object}\")";
                $object = $table;
            // no break
            case 'table':
            case 'view':
            case 'sequence':
                $this->fieldClean($object);
                $sql .= " ON \"{$f_schema}\".\"{$object}\"";

                break;
            case 'database':
                $this->fieldClean($object);
                $sql .= " ON DATABASE \"{$object}\"";

                break;
            case 'function':
                // Function comes in with $object as function OID
                $fn = $this->getFunction($object);
                $this->fieldClean($fn->fields['proname']);
                $sql .= " ON FUNCTION \"{$f_schema}\".\"{$fn->fields['proname']}\"({$fn->fields['proarguments']})";

                break;
            case 'language':
                $this->fieldClean($object);
                $sql .= " ON LANGUAGE \"{$object}\"";

                break;
            case 'schema':
                $this->fieldClean($object);
                $sql .= " ON SCHEMA \"{$object}\"";

                break;
            case 'tablespace':
                $this->fieldClean($object);
                $sql .= " ON TABLESPACE \"{$object}\"";

                break;
            default:
                return -1;
        }

        // Dump PUBLIC
        $first = true;
        $sql .= ($mode == 'GRANT') ? ' TO ' : ' FROM ';
        if ($public) {
            $sql .= 'PUBLIC';
            $first = false;
        }
        // Dump users
        foreach ($usernames as $v) {
            if ($first) {
                $sql .= "\"{$v}\"";
                $first = false;
            } else {
                $sql .= ", \"{$v}\"";
            }
        }
        // Dump groups
        foreach ($groupnames as $v) {
            if ($first) {
                $sql .= "GROUP \"{$v}\"";
                $first = false;
            } else {
                $sql .= ", GROUP \"{$v}\"";
            }
        }

        // Grant option
        if ($this->hasGrantOption() && $mode == 'GRANT' && $grantoption) {
            $sql .= ' WITH GRANT OPTION';
        }

        // Cascade revoke
        if ($this->hasGrantOption() && $mode == 'REVOKE' && $cascade) {
            $sql .= ' CASCADE';
        }

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

    abstract public function hasGrantOption();

    abstract public function getFunction($function_oid);

    abstract public function fieldArrayClean(&$arr);

    abstract public function selectField($sql, $field);

    abstract public function hasRoles();
}
