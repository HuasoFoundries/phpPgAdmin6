<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

namespace PHPPgAdmin\Database;

/**
 * A Class that implements the DB Interface for Postgres
 * Note: This Class uses ADODB and returns RecordSets.
 *
 * Id: Postgres.php,v 1.320 2008/02/20 20:43:09 ioguix Exp $
 */
class Postgres extends ADOdbBase
{
    use \PHPPgAdmin\Traits\HelperTrait;
    use \PHPPgAdmin\Database\Traits\AggregateTrait;
    use \PHPPgAdmin\Database\Traits\DatabaseTrait;
    use \PHPPgAdmin\Database\Traits\DomainTrait;
    use \PHPPgAdmin\Database\Traits\FtsTrait;
    use \PHPPgAdmin\Database\Traits\FunctionTrait;
    use \PHPPgAdmin\Database\Traits\IndexTrait;
    use \PHPPgAdmin\Database\Traits\OperatorTrait;
    use \PHPPgAdmin\Database\Traits\RoleTrait;
    use \PHPPgAdmin\Database\Traits\SchemaTrait;
    use \PHPPgAdmin\Database\Traits\SequenceTrait;
    use \PHPPgAdmin\Database\Traits\TablespaceTrait;
    use \PHPPgAdmin\Database\Traits\TableTrait;
    use \PHPPgAdmin\Database\Traits\TypeTrait;
    use \PHPPgAdmin\Database\Traits\ViewTrait;
    use \PHPPgAdmin\Database\Traits\StatsTrait;
    use \PHPPgAdmin\Database\Traits\PrivilegesTrait;

    public $lang;

    public $conf;

    protected $container;

    protected $server_info;

    public function __construct(&$conn, $container, $server_info)
    {
        //$this->prtrace('major_version :' . $this->major_version);
        $this->conn      = $conn;
        $this->container = $container;

        $this->lang        = $container->get('lang');
        $this->conf        = $container->get('conf');
        $this->server_info = $server_info;
    }

    /**
     * Fetch a URL (or array of URLs) for a given help page.
     *
     * @param string $help
     *
     * @return null|array|string the help page or pages related to the $help topic, or null if none exists
     */
    public function getHelp($help)
    {
        $this->getHelpPages();

        if (isset($this->help_page[$help])) {
            if (\is_array($this->help_page[$help])) {
                $urls = [];

                foreach ($this->help_page[$help] as $link) {
                    $urls[] = $this->help_base . $link;
                }

                return $urls;
            }

            return $this->help_base . $this->help_page[$help];
        }

        return null;
    }

    /**
     * Gets the help pages.
     * get help page by instancing the corresponding help class
     * if $this->help_page and $this->help_base are set, this function is a noop.
     */
    public function getHelpPages(): void
    {
        if (null === $this->help_page || null === $this->help_base) {
            $help_classname = '\PHPPgAdmin\Help\PostgresDoc' . \str_replace('.', '', $this->major_version);

            $help_class      = new $help_classname($this->conf, $this->major_version);
            $this->help_page = $help_class->getHelpPage();
            $this->help_base = $help_class->getHelpBase();
        }
    }

    // Formatting functions

    /**
     * Outputs the HTML code for a particular field.
     *
     * @param string $name   The name to give the field
     * @param mixed  $value  The value of the field.  Note this could be 'numeric(7,2)' sort of thing...
     * @param string $type   The database type of the field
     * @param array  $extras An array of attributes name as key and attributes' values as value
     */
    public function printField($name, $value, $type, $extras = []): void
    {
        $lang = $this->lang;

        // Determine actions string
        $extra_str = '';

        foreach ($extras as $k => $v) {
            $extra_str .= " {$k}=\"" . \htmlspecialchars($v) . '"';
        }

        switch (\mb_substr($type, 0, 9)) {
            case 'bool':
            case 'boolean':
                if (null !== $value && '' === $value) {
                    $value = null;
                } elseif ('true' === $value) {
                    $value = 't';
                } elseif ('false' === $value) {
                    $value = 'f';
                }

                // If value is null, 't' or 'f'...
                if (null === $value || 't' === $value || 'f' === $value) {
                    echo '<select name="', \htmlspecialchars($name), "\"{$extra_str}>\n";
                    echo '<option value=""', (null === $value) ? ' selected="selected"' : '', "></option>\n";
                    echo '<option value="t"', ('t' === $value) ? ' selected="selected"' : '', ">{$lang['strtrue']}</option>\n";
                    echo '<option value="f"', ('f' === $value) ? ' selected="selected"' : '', ">{$lang['strfalse']}</option>\n";
                    echo "</select>\n";
                } else {
                    echo '<input name="', \htmlspecialchars($name), '" value="', \htmlspecialchars($value), "\" size=\"35\"{$extra_str} />\n";
                }

                break;
            case 'bytea':
            case 'bytea[]':
                if (null !== $value) {
                    $value = $this->escapeBytea($value);
                }
            // no break
            case 'text':
            case 'text[]':
            case 'json':
            case 'jsonb':
            case 'xml':
            case 'xml[]':
                $n = \mb_substr_count($value, "\n");
                $n = 5 > $n ? \max(2, $n) : $n;
                $n = 20 < $n ? 20 : $n;
                echo '<textarea name="', \htmlspecialchars($name), "\" rows=\"{$n}\" cols=\"85\"{$extra_str}>\n";
                echo \htmlspecialchars($value);
                echo "</textarea>\n";

                break;
            case 'character':
            case 'character[]':
                $n = \mb_substr_count($value, "\n");
                $n = 5 > $n ? 5 : $n;
                $n = 20 < $n ? 20 : $n;
                echo '<textarea name="', \htmlspecialchars($name), "\" rows=\"{$n}\" cols=\"35\"{$extra_str}>\n";
                echo \htmlspecialchars($value);
                echo "</textarea>\n";

                break;

            default:
                echo '<input name="', \htmlspecialchars($name), '" value="', \htmlspecialchars($value), "\" size=\"35\"{$extra_str} />\n";

                break;
        }
    }

    /**
     * Searches all system catalogs to find objects that match a certain name.
     *
     * @param string $term   The search term
     * @param string $filter The object type to restrict to ('' means no restriction)
     *
     * @return int|\PHPPgAdmin\ADORecordSet A recordset
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
        $term = \str_replace('_', '\_', $term);
        $term = \str_replace('%', '\%', $term);

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

        if ('' !== $filter) {
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

        //\Kint::dump($sql);

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

        if ('' !== $filter) {
            // We use like to make RULE, CONSTRAINT and COLUMN searches work
            $sql .= ") AS sub WHERE type LIKE '{$filter}%' ";
        }

        $sql .= 'ORDER BY type, schemaname, relname, name';

        return $this->selectSet($sql);
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

        $sql = "SELECT attnum, attname FROM pg_catalog.pg_attribute WHERE
			attrelid=(SELECT oid FROM pg_catalog.pg_class WHERE relname='{$table}' AND
			relnamespace=(SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='{$c_schema}'))
			AND attnum IN ('" . \implode("','", $atts) . "')";

        $rs = $this->selectSet($sql);

        if ($rs->recordCount() !== \count($atts)) {
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
     * Gets all languages.
     *
     * @param bool $all True to get all languages, regardless of show_system
     *
     * @return int|\PHPPgAdmin\ADORecordSet A recordset
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
     * Executes an SQL script as a series of SQL statements.  Returns
     * the result of the final step.  This is a very complicated lexer
     * based on the REL7_4_STABLE src/bin/psql/mainloop.c lexer in
     * the PostgreSQL source code.
     * XXX: It does not handle multibyte languages properly.
     *
     * @param string        $name     Entry in $_FILES to use
     * @param null|callable $callback (optional) Callback function to call with each query, its result and line number
     *
     * @return bool|mixed true for general success, false on any failure, or resultset
     */
    public function executeScript($name, $callback = null)
    {
        // This whole function isn't very encapsulated, but hey...
        $conn = $this->conn->_connectionID;

        if (!\is_uploaded_file($_FILES[$name]['tmp_name'])) {
            return false;
        }

        $fd = \fopen($_FILES[$name]['tmp_name'], 'rb');

        if (false === $fd) {
            return false;
        }

        // Build up each SQL statement, they can be multiline
        $query_buf    = null;
        $query_start  = 0;
        $in_quote     = 0;
        $in_xcomment  = 0;
        $bslash_count = 0;
        $dol_quote    = '';
        $paren_level  = 0;
        $len          = 0;
        $i            = 0;
        $prevlen      = 0;
        $thislen      = 0;
        $lineno       = 0;

        // Loop over each line in the file
        while (!\feof($fd)) {
            $line = \fgets($fd);
            ++$lineno;

            // Nothing left on line? Then ignore...
            if ('' === \trim($line)) {
                continue;
            }

            $len         = \mb_strlen($line);
            $query_start = 0;

            /**
             * Parse line, looking for command separators.
             *
             * The current character is at line[i], the prior character at line[i
             * - prevlen], the next character at line[i + thislen].
             */
            $prevlen = 0;
            $thislen = (0 < $len) ? 1 : 0;

            for ($i = 0; $i < $len; $this->advance_1($i, $prevlen, $thislen)) {
                /* was the previous character a backslash? */
                if (0 < $i && '\\' === \mb_substr($line, $i - $prevlen, 1)) {
                    $this->prtrace('bslash_count', $bslash_count, $line);
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
                if (0 !== $in_quote) {
                    //$this->prtrace('in_quote', $in_quote, $line);
                    /*
                     * end of quote if matching non-backslashed character.
                     * backslashes don't count for double quotes, though.
                     */
                    if (\mb_substr($line, $i, 1) === $in_quote &&
                        (0 === $bslash_count % 2 || '"' === $in_quote)
                    ) {
                        $in_quote = 0;
                    }
                } elseif ($dol_quote) {
                    $this->prtrace('dol_quote', $dol_quote, $line);

                    if (0 === \strncmp(\mb_substr($line, $i), $dol_quote, \mb_strlen($dol_quote))) {
                        $this->advance_1($i, $prevlen, $thislen);

                        while ('$' !== \mb_substr($line, $i, 1)) {
                            $this->advance_1($i, $prevlen, $thislen);
                        }

                        $dol_quote = '';
                    }
                } elseif ('/*' === \mb_substr($line, $i, 2)) {
                    $this->prtrace('open_xcomment', $in_xcomment, $line, $i, $prevlen, $thislen);

                    if (0 === $in_xcomment) {
                        ++$in_xcomment;
                        $finishpos = \mb_strpos(\mb_substr($line, $i, $len), '*/');

                        if (false === $finishpos) {
                            $line = \mb_substr($line, 0, $i); /* remove comment */
                            break;
                        }
                        $pre         = \mb_substr($line, 0, $i);
                        $post        = \mb_substr($line, $i + 2 + $finishpos, $len);
                        $line        = $pre . ' ' . $post;
                        $in_xcomment = 0;
                        $i           = 0;
                    }
                } elseif ($in_xcomment) {
                    $position = \mb_strpos(\mb_substr($line, $i, $len), '*/');

                    if (false === $position) {
                        $line = '';

                        break;
                    }

                    $substr = \mb_substr($line, $i, 2);

                    if ('*/' === $substr && !--$in_xcomment) {
                        $line = \mb_substr($line, $i + 2, $len);
                        $i += 2;
                        $this->advance_1($i, $prevlen, $thislen);
                    }
                    // old logic
                    //  } else if (substr($line, $i, 2) == '/*') {
                    //      if ($in_xcomment == 0) {
                    //          ++$in_xcomment;
                    //          $this->advance_1($i, $prevlen, $thislen);
                    //      }
                    //  } else if ($in_xcomment) {
                    //      $substr = substr($line, $i, 2);
                    //      if ($substr == '*/' && !--$in_xcomment) {
                    //          $this->advance_1($i, $prevlen, $thislen);
                    //      }
                } elseif ('\'' === \mb_substr($line, $i, 1) || '"' === \mb_substr($line, $i, 1)) {
                    $in_quote = \mb_substr($line, $i, 1);
                } elseif (!$dol_quote && $this->valid_dolquote(\mb_substr($line, $i))) {
                    $dol_end   = \mb_strpos(\mb_substr($line, $i + 1), '$');
                    $dol_quote = \mb_substr($line, $i, $dol_end + 1);
                    $this->advance_1($i, $prevlen, $thislen);

                    while ('$' !== \mb_substr($line, $i, 1)) {
                        $this->advance_1($i, $prevlen, $thislen);
                    }
                } else {
                    if ('--' === \mb_substr($line, $i, 2)) {
                        $line = \mb_substr($line, 0, $i); /* remove comment */
                        break;
                    } /* count nested parentheses */

                    if ('(' === \mb_substr($line, $i, 1)) {
                        ++$paren_level;
                    } elseif (')' === \mb_substr($line, $i, 1) && 0 < $paren_level) {
                        --$paren_level;
                    } elseif (';' === \mb_substr($line, $i, 1) && !$bslash_count && !$paren_level) {
                        $subline = \mb_substr(\mb_substr($line, 0, $i), $query_start);
                        /*
                         * insert a cosmetic newline, if this is not the first
                         * line in the buffer
                         */
                        if (0 < \mb_strlen($query_buf)) {
                            $query_buf .= "\n";
                        }

                        /* append the line to the query buffer */
                        $query_buf .= $subline;
                        /* is there anything in the query_buf? */
                        if (\trim($query_buf)) {
                            $query_buf .= ';';

                            // Execute the query. PHP cannot execute
                            // empty queries, unlike libpq
                            $res = \pg_query($conn, $query_buf);

                            // Call the callback function for display
                            if (null !== $callback) {
                                $callback($query_buf, $res, $lineno);
                            }

                            // Check for COPY request
                            if (4 === \pg_result_status($res)) {
                                // 4 == PGSQL_COPY_FROM
                                while (!\feof($fd)) {
                                    $copy = \fgets($fd, 32768);
                                    ++$lineno;
                                    \pg_put_line($conn, $copy);

                                    if ("\\.\n" === $copy || "\\.\r\n" === $copy) {
                                        \pg_end_copy($conn);

                                        break;
                                    }
                                }
                            }
                        }
                        $query_buf   = null;
                        $query_start = $i + $thislen;
                    } elseif (\preg_match('/^[_[:alpha:]]$/', \mb_substr($line, $i, 1))) {
                        $sub = \mb_substr($line, $i, $thislen);

                        while (\preg_match('/^[\$_A-Za-z0-9]$/', $sub)) {
                            /* keep going while we still have identifier chars */
                            $this->advance_1($i, $prevlen, $thislen);
                            $sub = \mb_substr($line, $i, $thislen);
                        }
                        // Since we're now over the next character to be examined, it is necessary
                        // to move back one space.
                        $i -= $prevlen;
                    }
                }
            } // end for

            /* Put the rest of the line in the query buffer. */
            $subline = \mb_substr($line, $query_start);

            if ($in_quote || $dol_quote || \strspn($subline, " \t\n\r") !== \mb_strlen($subline)) {
                if (0 < \mb_strlen($query_buf)) {
                    $query_buf .= "\n";
                }

                $query_buf .= $subline;
            }

            $line = null;
        } // end while

        $res = true;
        /*
         * Process query at the end of file without a semicolon, so long as
         * it's non-empty.
         */
        if (0 < \mb_strlen($query_buf) && \strspn($query_buf, " \t\n\r") !== \mb_strlen($query_buf)) {
            // Execute the query
            $res = \pg_query($conn, $query_buf);

            // Call the callback function for display
            if (null !== $callback) {
                $callback($query_buf, $res, $lineno);
            }

            // Check for COPY request
            if (4 === \pg_result_status($res)) {
                // 4 == PGSQL_COPY_FROM
                while (!\feof($fd)) {
                    $copy = \fgets($fd, 32768);
                    ++$lineno;
                    \pg_put_line($conn, $copy);

                    if ("\\.\n" === $copy || "\\.\r\n" === $copy) {
                        \pg_end_copy($conn);

                        break;
                    }
                }
            }
        }

        \fclose($fd);

        return $res;
    }

    // Capabilities

    /**
     * Returns a recordset of all columns in a query.  Supports paging.
     *
     * @param string   $type      Either 'QUERY' if it is an SQL query, or 'TABLE' if it is a table identifier,
     *                            or 'SELECT" if it's a select query
     * @param string   $table     The base table of the query.  NULL for no table.
     * @param string   $query     The query that is being executed.  NULL for no query.
     * @param string   $sortkey   The column number to sort by, or '' or null for no sorting
     * @param string   $sortdir   The direction in which to sort the specified column ('asc' or 'desc')
     * @param null|int $page      The page of the relation to retrieve
     * @param null|int $page_size The number of rows per page
     * @param int      $max_pages (return-by-ref) The max number of pages in the relation
     *
     * @return int|\PHPPgAdmin\ADORecordSet A  recordset on success or an int with error code
     *                                      - -1 transaction error
     *                                      - -2 counting error
     *                                      - -3 page or page_size invalid
     *                                      - -4 unknown type
     *                                      - -5 failed setting transaction read only
     */
    public function browseQuery($type, $table, $query, $sortkey, $sortdir, $page, $page_size, &$max_pages)
    {
        // Check that we're not going to divide by zero
        if (!\is_numeric($page_size) || (int) $page_size !== $page_size || 0 >= $page_size) {
            return -3;
        }

        // If $type is TABLE, then generate the query
        switch ($type) {
            case 'TABLE':
                if (\preg_match('/^[0-9]+$/', $sortkey) && 0 < $sortkey) {
                    $orderby = [$sortkey => $sortdir];
                } else {
                    $orderby = [];
                }

                $query = $this->getSelectSQL($table, [], [], [], $orderby);

                break;
            case 'QUERY':
            case 'SELECT':
                // Trim query
                $query = \trim($query);
                // Trim off trailing semi-colon if there is one
                if (';' === \mb_substr($query, \mb_strlen($query) - 1, 1)) {
                    $query = \mb_substr($query, 0, \mb_strlen($query) - 1);
                }

                break;

            default:
                return -4;
        }

        // Generate count query
        $count = "SELECT COUNT(*) AS total FROM ({$query}) AS sub";

        // Open a transaction
        $status = $this->beginTransaction();

        if ($status !== false) {
            return -1;
        }

        // If backend supports read only queries, then specify read only mode
        // to avoid side effects from repeating queries that do writes.
        if ($this->hasReadOnlyQueries()) {
            $status = $this->execute('SET TRANSACTION READ ONLY');

            if ($status !== false) {
                $this->rollbackTransaction();

                return -5;
            }
        }

        // Count the number of rows
        $total = $this->browseQueryCount($count);

        if (0 > $total) {
            $this->rollbackTransaction();

            return -2;
        }

        // Calculate max pages
        $max_pages = \ceil($total / $page_size);

        // Check that page is less than or equal to max pages
        if (!\is_numeric($page) || (int) $page !== $page || $page > $max_pages || 1 > $page) {
            $this->rollbackTransaction();

            return -3;
        }

        // Set fetch mode to NUM so that duplicate field names are properly returned
        // for non-table queries.  Since the SELECT feature only allows selecting one
        // table, duplicate fields shouldn't appear.
        if ('QUERY' === $type) {
            $this->conn->setFetchMode(ADODB_FETCH_NUM);
        }

        // Figure out ORDER BY.  Sort key is always the column number (based from one)
        // of the column to order by.  Only need to do this for non-TABLE queries
        if ('TABLE' !== $type && \preg_match('/^[0-9]+$/', $sortkey) && 0 < $sortkey) {
            $orderby = " ORDER BY {$sortkey}";
            // Add sort order
            if ('desc' === $sortdir) {
                $orderby .= ' DESC';
            } else {
                $orderby .= ' ASC';
            }
        } else {
            $orderby = '';
        }

        // Actually retrieve the rows, with offset and limit
        $rs     = $this->selectSet("SELECT * FROM ({$query}) AS sub {$orderby} LIMIT {$page_size} OFFSET " . ($page - 1) * $page_size);
        $status = $this->endTransaction();

        if ($status !== false) {
            $this->rollbackTransaction();

            return -1;
        }

        return $rs;
    }

    /**
     * Generates the SQL for the 'select' function.
     *
     * @param string $table   The table from which to select
     * @param array  $show    An array of columns to show.  Empty array means all columns.
     * @param array  $values  An array mapping columns to values
     * @param array  $ops     An array of the operators to use
     * @param array  $orderby (optional) An array of column numbers or names (one based)
     *                        mapped to sort direction (asc or desc or '' or null) to order by
     *
     * @return string The SQL query
     */
    public function getSelectSQL($table, $show, $values = [], $ops = [], $orderby = [])
    {
        $this->fieldArrayClean($show);

        // If an empty array is passed in, then show all columns
        if (0 === \count($show)) {
            if ($this->hasObjectID($table)) {
                $sql = "SELECT \"{$this->id}\", * FROM ";
            } else {
                $sql = 'SELECT * FROM ';
            }
        } else {
            // Add oid column automatically to results for editing purposes
            if (!\in_array($this->id, $show, true) && $this->hasObjectID($table)) {
                $sql = "SELECT \"{$this->id}\", \"";
            } else {
                $sql = 'SELECT "';
            }

            $sql .= \implode('","', $show) . '" FROM ';
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

        if (\is_array($values) && 0 < \count($values)) {
            foreach ($values as $k => $v) {
                if ('' !== $v || 'p' === $this->selectOps[$ops[$k]]) {
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
        if (\is_array($orderby) && 0 < \count($orderby)) {
            $sql .= ' ORDER BY ';
            $first = true;

            foreach ($orderby as $k => $v) {
                if ($first) {
                    $first = false;
                } else {
                    $sql .= ', ';
                }

                if (\preg_match('/^[0-9]+$/', $k)) {
                    $sql .= $k;
                } else {
                    $this->fieldClean($k);
                    $sql .= '"' . $k . '"';
                }

                if ('DESC' === \mb_strtoupper($v)) {
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
     * @param string $count The count query
     *
     * @return int|string The count of rows or -1 of no rows are found
     */
    public function browseQueryCount($count)
    {
        return $this->selectField($count, 'total');
    }

    /**
     * A private helper method for executeScript that advances the
     * character by 1.  In psql this is careful to take into account
     * multibyte languages, but we don't at the moment, so this function
     * is someone redundant, since it will always advance by 1.
     *
     * @param int $i       The current character position in the line
     * @param int $prevlen Length of previous character (ie. 1)
     * @param int $thislen Length of current character (ie. 1)
     */
    protected function advance_1(&$i, &$prevlen, &$thislen): void
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
     * @return bool true if valid, false otherwise
     */
    protected function valid_dolquote($dquote)
    {
        // XXX: support multibyte
        return \preg_match('/^[$][$]/', $dquote) || \preg_match('/^[$][_[:alpha:]][_[:alnum:]]*[$]/', $dquote);
    }
}
