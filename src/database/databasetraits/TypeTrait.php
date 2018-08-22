<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for types manipulation.
 */
trait TypeTrait
{
    /**
     * Formats a type correctly for display.  Postgres 7.0 had no 'format_type'
     * built-in function, and hence we need to do it manually.
     *
     * @param string $typname The name of the type
     * @param string $typmod  The contents of the typmod field
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
     * Returns all details for a particular type.
     *
     * @param string $typname The name of the view to retrieve
     *
     * @return \PHPPgAdmin\ADORecordSet type info
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
     * @param string $typname The name of the type to drop
     * @param bool   $cascade True to cascade drop, false to restrict
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
