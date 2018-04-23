<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Traits;

/**
 * Common trait for full text search manipulation.
 */
trait FtsTrait
{
    /**
     * Creates a new FTS configuration.
     *
     * @param string $cfgname  The name of the FTS configuration to create
     * @param string $parser   The parser to be used in new FTS configuration
     * @param string $template The existing FTS configuration to be used as template for the new one
     * @param string $comment  If omitted, defaults to nothing
     *
     * @return bool|int 0 success
     *
     * @internal param string $locale Locale of the FTS configuration
     * @internal param string $withmap Should we copy whole map of existing FTS configuration to the new one
     * @internal param string $makeDefault Should this configuration be the default for locale given
     */
    public function createFtsConfiguration($cfgname, $parser = '', $template = '', $comment = '')
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($cfgname);

        $sql = "CREATE TEXT SEARCH CONFIGURATION \"{$f_schema}\".\"{$cfgname}\" (";
        if ($parser != '') {
            $this->fieldClean($parser['schema']);
            $this->fieldClean($parser['parser']);
            $parser = "\"{$parser['schema']}\".\"{$parser['parser']}\"";
            $sql .= " PARSER = {$parser}";
        }
        if ($template != '') {
            $this->fieldClean($template['schema']);
            $this->fieldClean($template['name']);
            $sql .= " COPY = \"{$template['schema']}\".\"{$template['name']}\"";
        }
        $sql .= ')';

        if ($comment != '') {
            $status = $this->beginTransaction();
            if ($status != 0) {
                return -1;
            }
        }

        // Create the FTS configuration
        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Set the comment
        if ($comment != '') {
            $status = $this->setComment('TEXT SEARCH CONFIGURATION', $cfgname, '', $comment);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -1;
            }

            return $this->endTransaction();
        }

        return 0;
    }

    // Language functions

    /**
     * Returns available FTS configurations.
     *
     * @param bool $all if false, returns schema qualified FTS confs
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getFtsConfigurations($all = true)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = "
            SELECT
                n.nspname as schema,
                c.cfgname as name,
                pg_catalog.obj_description(c.oid, 'pg_ts_config') as comment
            FROM
                pg_catalog.pg_ts_config c
                JOIN pg_catalog.pg_namespace n ON n.oid = c.cfgnamespace
            WHERE
                pg_catalog.pg_ts_config_is_visible(c.oid)";

        if (!$all) {
            $sql .= " AND  n.nspname='{$c_schema}'\n";
        }

        $sql .= 'ORDER BY name';

        return $this->selectSet($sql);
    }

    // Aggregate functions

    /**
     * Returns the map of FTS configuration given
     * (list of mappings (tokens) and their processing dictionaries).
     *
     * @param string $ftscfg Name of the FTS configuration
     *
     * @return \PHPPgAdmin\ADORecordSet recordset
     */
    public function getFtsConfigurationMap($ftscfg)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->fieldClean($ftscfg);

        $oidSet = $this->selectSet("SELECT c.oid
            FROM pg_catalog.pg_ts_config AS c
                LEFT JOIN pg_catalog.pg_namespace n ON (n.oid = c.cfgnamespace)
            WHERE c.cfgname = '{$ftscfg}'
                AND n.nspname='{$c_schema}'");

        $oid = $oidSet->fields['oid'];

        $sql = "
            SELECT
                (SELECT t.alias FROM pg_catalog.ts_token_type(c.cfgparser) AS t WHERE t.tokid = m.maptokentype) AS name,
                (SELECT t.description FROM pg_catalog.ts_token_type(c.cfgparser) AS t WHERE t.tokid = m.maptokentype) AS description,
                c.cfgname AS cfgname, n.nspname ||'.'|| d.dictname as dictionaries
            FROM
                pg_catalog.pg_ts_config AS c, pg_catalog.pg_ts_config_map AS m, pg_catalog.pg_ts_dict d,
                pg_catalog.pg_namespace n
            WHERE
                c.oid = {$oid}
                AND m.mapcfg = c.oid
                AND m.mapdict = d.oid
                AND d.dictnamespace = n.oid
            ORDER BY name
            ";

        return $this->selectSet($sql);
    }

    /**
     * Returns FTS parsers available.
     *
     * @param bool $all if false, return only Parsers from the current schema
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getFtsParsers($all = true)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = "
            SELECT
               n.nspname as schema,
               p.prsname as name,
               pg_catalog.obj_description(p.oid, 'pg_ts_parser') as comment
            FROM pg_catalog.pg_ts_parser p
                LEFT JOIN pg_catalog.pg_namespace n ON (n.oid = p.prsnamespace)
            WHERE pg_catalog.pg_ts_parser_is_visible(p.oid)";

        if (!$all) {
            $sql .= " AND n.nspname='{$c_schema}'\n";
        }

        $sql .= 'ORDER BY name';

        return $this->selectSet($sql);
    }

    /**
     * Returns FTS dictionaries available.
     *
     * @param bool $all if false, return only Dics from the current schema
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getFtsDictionaries($all = true)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $sql = "
            SELECT
                n.nspname as schema, d.dictname as name,
                pg_catalog.obj_description(d.oid, 'pg_ts_dict') as comment
            FROM pg_catalog.pg_ts_dict d
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = d.dictnamespace
            WHERE pg_catalog.pg_ts_dict_is_visible(d.oid)";

        if (!$all) {
            $sql .= " AND n.nspname='{$c_schema}'\n";
        }

        $sql .= 'ORDER BY name;';

        return $this->selectSet($sql);
    }

    /**
     * Returns all FTS dictionary templates available.
     *
     * @return \PHPPgAdmin\ADORecordSet all FTS dictionary templates available
     */
    public function getFtsDictionaryTemplates()
    {
        $sql = "
            SELECT
                n.nspname as schema,
                t.tmplname as name,
                ( SELECT COALESCE(np.nspname, '(null)')::pg_catalog.text || '.' || p.proname
                    FROM pg_catalog.pg_proc p
                    LEFT JOIN pg_catalog.pg_namespace np ON np.oid = p.pronamespace
                    WHERE t.tmplinit = p.oid ) AS  init,
                ( SELECT COALESCE(np.nspname, '(null)')::pg_catalog.text || '.' || p.proname
                    FROM pg_catalog.pg_proc p
                    LEFT JOIN pg_catalog.pg_namespace np ON np.oid = p.pronamespace
                    WHERE t.tmpllexize = p.oid ) AS  lexize,
                pg_catalog.obj_description(t.oid, 'pg_ts_template') as comment
            FROM pg_catalog.pg_ts_template t
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = t.tmplnamespace
            WHERE pg_catalog.pg_ts_template_is_visible(t.oid)
            ORDER BY name;";

        return $this->selectSet($sql);
    }

    /**
     * Drops FTS coniguration.
     *
     * @param string $ftscfg  The configuration's name
     * @param bool   $cascade true to Cascade to dependenced objects
     *
     * @return int 0 if operation was successful
     */
    public function dropFtsConfiguration($ftscfg, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($ftscfg);

        $sql = "DROP TEXT SEARCH CONFIGURATION \"{$f_schema}\".\"{$ftscfg}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Drops FTS dictionary.
     *
     * @param string $ftsdict The dico's name
     * @param bool   $cascade Cascade to dependenced objects
     *
     * @return int 0 if operation was successful
     *
     * @todo Support of dictionary templates dropping
     */
    public function dropFtsDictionary($ftsdict, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($ftsdict);

        $sql = 'DROP TEXT SEARCH DICTIONARY';
        $sql .= " \"{$f_schema}\".\"{$ftsdict}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Alters FTS configuration.
     *
     * @param string $cfgname The conf's name
     * @param string $comment A comment on for the conf
     * @param string $name    The new conf name
     *
     * @return bool|int 0 on success
     */
    public function updateFtsConfiguration($cfgname, $comment, $name)
    {
        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $this->fieldClean($cfgname);

        $status = $this->setComment('TEXT SEARCH CONFIGURATION', $cfgname, '', $comment);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Only if the name has changed
        if ($name != $cfgname) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $this->fieldClean($name);

            $sql    = "ALTER TEXT SEARCH CONFIGURATION \"{$f_schema}\".\"{$cfgname}\" RENAME TO \"{$name}\"";
            $status = $this->execute($sql);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Creates a new FTS dictionary or FTS dictionary template.
     *
     * @param string $dictname   The name of the FTS dictionary to create
     * @param bool   $isTemplate Flag whether we create usual dictionary or dictionary template
     * @param string $template   The existing FTS dictionary to be used as template for the new one
     * @param string $lexize     The name of the function, which does transformation of input word
     * @param string $init       The name of the function, which initializes dictionary
     * @param string $option     Usually, it stores various options required for the dictionary
     * @param string $comment    If omitted, defaults to nothing
     *
     * @return bool|int 0 success
     */
    public function createFtsDictionary(
        $dictname,
        $isTemplate = false,
        $template = '',
        $lexize = '',
        $init = '',
        $option = '',
        $comment = ''
    ) {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($dictname);
        $this->fieldClean($template);
        $this->fieldClean($lexize);
        $this->fieldClean($init);
        $this->fieldClean($option);

        $sql = 'CREATE TEXT SEARCH';
        if ($isTemplate) {
            $sql .= " TEMPLATE \"{$f_schema}\".\"{$dictname}\" (";
            if ($lexize != '') {
                $sql .= " LEXIZE = {$lexize}";
            }

            if ($init != '') {
                $sql .= ", INIT = {$init}";
            }

            $sql .= ')';
            $whatToComment = 'TEXT SEARCH TEMPLATE';
        } else {
            $sql .= " DICTIONARY \"{$f_schema}\".\"{$dictname}\" (";
            if ($template != '') {
                $this->fieldClean($template['schema']);
                $this->fieldClean($template['name']);
                $template = "\"{$template['schema']}\".\"{$template['name']}\"";

                $sql .= " TEMPLATE = {$template}";
            }
            if ($option != '') {
                $sql .= ", {$option}";
            }

            $sql .= ')';
            $whatToComment = 'TEXT SEARCH DICTIONARY';
        }

        /* if comment, begin a transaction to
         * run both commands */
        if ($comment != '') {
            $status = $this->beginTransaction();
            if ($status != 0) {
                return -1;
            }
        }

        // Create the FTS dictionary
        $status = $this->execute($sql);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Set the comment
        if ($comment != '') {
            $status = $this->setComment($whatToComment, $dictname, '', $comment);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    // Role, User/Group functions

    /**
     * Alters FTS dictionary or dictionary template.
     *
     * @param string $dictname The dico's name
     * @param string $comment  The comment
     * @param string $name     The new dico's name
     *
     * @return bool|int 0 on success
     */
    public function updateFtsDictionary($dictname, $comment, $name)
    {
        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $this->fieldClean($dictname);
        $status = $this->setComment('TEXT SEARCH DICTIONARY', $dictname, '', $comment);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        // Only if the name has changed
        if ($name != $dictname) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $this->fieldClean($name);

            $sql    = "ALTER TEXT SEARCH DICTIONARY \"{$f_schema}\".\"{$dictname}\" RENAME TO \"{$name}\"";
            $status = $this->execute($sql);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -1;
            }
        }

        return $this->endTransaction();
    }

    /**
     * Return all information relating to a FTS dictionary.
     *
     * @param string $ftsdict The name of the FTS dictionary
     *
     * @return \PHPPgAdmin\ADORecordSet recordset of FTS dictionary information
     */
    public function getFtsDictionaryByName($ftsdict)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($ftsdict);

        $sql = "SELECT
               n.nspname as schema,
               d.dictname as name,
               ( SELECT COALESCE(nt.nspname, '(null)')::pg_catalog.text || '.' || t.tmplname FROM
                 pg_catalog.pg_ts_template t
                                      LEFT JOIN pg_catalog.pg_namespace nt ON nt.oid = t.tmplnamespace
                                      WHERE d.dicttemplate = t.oid ) AS  template,
               d.dictinitoption as init,
               pg_catalog.obj_description(d.oid, 'pg_ts_dict') as comment
            FROM pg_catalog.pg_ts_dict d
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = d.dictnamespace
            WHERE d.dictname = '{$ftsdict}'
               AND pg_catalog.pg_ts_dict_is_visible(d.oid)
               AND n.nspname='{$c_schema}'
            ORDER BY name";

        return $this->selectSet($sql);
    }

    /**
     * Creates/updates/deletes FTS mapping.
     *
     * @param string $ftscfg   The name of the FTS dictionary
     * @param array  $mapping  Array of tokens' names
     * @param string $action   What to do with the mapping: add, alter or drop
     * @param string $dictname Dictionary that will process tokens given or null in case of drop action
     *
     * @return int 0 if operation was successful
     *
     * @internal param string $cfgname The name of the FTS configuration to alter
     */
    public function changeFtsMapping($ftscfg, $mapping, $action, $dictname = null)
    {
        if (count($mapping) > 0) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $this->fieldClean($ftscfg);
            $this->fieldClean($dictname);
            $this->arrayClean($mapping);

            switch ($action) {
                case 'alter':
                    $whatToDo = 'ALTER';

                    break;
                case 'drop':
                    $whatToDo = 'DROP';

                    break;
                default:
                    $whatToDo = 'ADD';

                    break;
            }

            $sql = "ALTER TEXT SEARCH CONFIGURATION \"{$f_schema}\".\"{$ftscfg}\" {$whatToDo} MAPPING FOR ";
            $sql .= implode(',', $mapping);
            if ($action != 'drop' && !empty($dictname)) {
                $sql .= " WITH {$dictname}";
            }

            return $this->execute($sql);
        }

        return -1;
    }

    /**
     * Return all information related to a given FTS configuration's mapping.
     *
     * @param string $ftscfg  The name of the FTS configuration
     * @param string $mapping The name of the mapping
     *
     * @return \PHPPgAdmin\ADORecordSet FTS configuration information
     */
    public function getFtsMappingByName($ftscfg, $mapping)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($ftscfg);
        $this->clean($mapping);

        $oidSet = $this->selectSet("SELECT c.oid, cfgparser
            FROM pg_catalog.pg_ts_config AS c
                LEFT JOIN pg_catalog.pg_namespace AS n ON n.oid = c.cfgnamespace
            WHERE c.cfgname = '{$ftscfg}'
                AND n.nspname='{$c_schema}'");

        $oid       = $oidSet->fields['oid'];
        $cfgparser = $oidSet->fields['cfgparser'];

        $tokenIdSet = $this->selectSet("SELECT tokid
            FROM pg_catalog.ts_token_type({$cfgparser})
            WHERE alias = '{$mapping}'");

        $tokid = $tokenIdSet->fields['tokid'];

        $sql = "SELECT
                (SELECT t.alias FROM pg_catalog.ts_token_type(c.cfgparser) AS t WHERE t.tokid = m.maptokentype) AS name,
                    d.dictname as dictionaries
            FROM pg_catalog.pg_ts_config AS c, pg_catalog.pg_ts_config_map AS m, pg_catalog.pg_ts_dict d
            WHERE c.oid = {$oid} AND m.mapcfg = c.oid AND m.maptokentype = {$tokid} AND m.mapdict = d.oid
            LIMIT 1;";

        return $this->selectSet($sql);
    }

    /**
     * Return list of FTS mappings possible for given parser
     * (specified by given configuration since configuration can only have 1 parser).
     *
     * @param string $ftscfg The config's name that use the parser
     *
     * @return int 0 if operation was successful
     */
    public function getFtsMappings($ftscfg)
    {
        $cfg = $this->getFtsConfigurationByName($ftscfg);

        $sql = "SELECT alias AS name, description
            FROM pg_catalog.ts_token_type({$cfg->fields['parser_id']})
            ORDER BY name";

        return $this->selectSet($sql);
    }

    /**
     * Return all information related to a FTS configuration.
     *
     * @param string $ftscfg The name of the FTS configuration
     *
     * @return \PHPPgAdmin\ADORecordSet FTS configuration information
     */
    public function getFtsConfigurationByName($ftscfg)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($ftscfg);
        $sql = "
            SELECT
                n.nspname as schema,
                c.cfgname as name,
                p.prsname as parser,
                c.cfgparser as parser_id,
                pg_catalog.obj_description(c.oid, 'pg_ts_config') as comment
            FROM pg_catalog.pg_ts_config c
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.cfgnamespace
                LEFT JOIN pg_catalog.pg_ts_parser p ON p.oid = c.cfgparser
            WHERE pg_catalog.pg_ts_config_is_visible(c.oid)
                AND c.cfgname = '{$ftscfg}'
                AND n.nspname='{$c_schema}'";

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
}
