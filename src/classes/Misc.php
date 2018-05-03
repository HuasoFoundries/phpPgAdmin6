<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

namespace PHPPgAdmin;

/**
 * @file
 * Class to hold various commonly used functions
 *
 * Id: Misc.php,v 1.171 2008/03/17 21:35:48 ioguix Exp $
 *
 * @package PHPPgAdmin
 */

/**
 * Class to hold various commonly used functions.
 *
 * Release: Misc.php,v 1.171 2008/03/17 21:35:48 ioguix Exp $
 *
 * @package PHPPgAdmin
 */
class Misc
{
    use \PHPPgAdmin\Traits\HelperTrait;
    use \PHPPgAdmin\Traits\MiscTrait;

    private $_connection;
    private $_no_db_connection = false;
    private $_reload_browser   = false;
    private $_data;
    private $_database;
    private $_server_id;
    private $_server_info;
    private $_error_msg = '';

    public $appLangFiles    = [];
    public $appName         = '';
    public $appVersion      = '';
    public $form            = '';
    public $href            = '';
    public $controller_name = 'Misc';
    public $lang            = [];

    protected $container;

    /**
     * @param \Slim\Container $container The container
     */
    public function __construct(\Slim\Container $container)
    {
        $this->container = $container;

        $this->lang = $container->get('lang');
        $this->conf = $container->get('conf');

        //$this->view           = $container->get('view');
        $this->plugin_manager = $container->get('plugin_manager');
        $this->appLangFiles   = $container->get('appLangFiles');

        $this->appName          = $container->get('settings')['appName'];
        $this->appVersion       = $container->get('settings')['appVersion'];
        $this->postgresqlMinVer = $container->get('settings')['postgresqlMinVer'];
        $this->phpMinVer        = $container->get('settings')['phpMinVer'];

        $base_version = $container->get('settings')['base_version'];

        //$this->prtrace($base_version);

        // Check for config file version mismatch
        if (!isset($this->conf['version']) || $base_version > $this->conf['version']) {
            $container->get('utils')->addError($this->lang['strbadconfig']);
        }

        // Check database support is properly compiled in
        if (!function_exists('pg_connect')) {
            $container->get('utils')->addError($this->lang['strnotloaded']);
        }

        // Check the version of PHP
        if (version_compare(PHP_VERSION, $this->phpMinVer, '<')) {
            $container->get('utils')->addError(sprintf('Version of PHP not supported. Please upgrade to version %s or later.', $this->phpMinVer));
        }
        //$this->dumpAndDie($this);

        $this->getServerId();
    }

    public function serverToSha()
    {
        $request_server = $this->container->requestobj->getParam('server');
        if ($request_server === null) {
            return null;
        }
        $srv_array = explode(':', $request_server);
        if (count($srv_array) === 3) {
            return sha1($request_server);
        }

        return $request_server;
    }

    public function getServerId()
    {
        if ($this->_server_id) {
            return $this->_server_id;
        }

        $request_server = $this->serverToSha();

        if (count($this->conf['servers']) === 1) {
            $info             = $this->conf['servers'][0];
            $this->_server_id = sha1($info['host'].':'.$info['port'].':'.$info['sslmode']);
        } elseif ($request_server !== null) {
            $this->_server_id = $request_server;
        } elseif (isset($_SESSION['webdbLogin']) && count($_SESSION['webdbLogin']) > 0) {
            //$this->prtrace('webdbLogin', $_SESSION['webdbLogin']);
            $this->_server_id = array_keys($_SESSION['webdbLogin'])[0];
        }

        return $this->_server_id;
    }

    /**
     * Sets the view instance property of this class.
     *
     * @param \Slim\Views\Twig $view view instance
     *
     * @return \PHPPgAdmin\Misc this class instance
     */
    public function setView(\Slim\Views\Twig $view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Adds or modifies a key in the $conf instance property of this class.
     *
     * @param string $key   name of the key to set
     * @param mixed  $value value of the key to set
     *
     * @return \PHPPgAdmin\Misc this class instance
     */
    public function setConf($key, $value)
    {
        $this->conf[$key] = $value;

        return $this;
    }

    /**
     * Gets the value of a config property, or the array of all config properties.
     *
     * @param null|string $key value of the key to be retrieved. If null, the full array is returnes
     *
     * @return null|array|string the whole $conf array, the value of $conf[key] or null if said key does not exist
     */
    public function getConf($key = null)
    {
        if ($key === null) {
            return $this->conf;
        }
        if (array_key_exists($key, $this->conf)) {
            return $this->conf[$key];
        }

        return null;
    }

    /**
     * Displays link to the context help.
     *
     * @param string $str      the string that the context help is related to (already escaped)
     * @param string $help     help section identifier
     * @param bool   $do_print true to echo, false to return
     */
    public function printHelp($str, $help = null, $do_print = true)
    {
        //\PC::debug(['str' => $str, 'help' => $help], 'printHelp');
        if ($help !== null) {
            $helplink = $this->getHelpLink($help);
            $str .= '<a class="help" href="'.$helplink.'" title="'.$this->lang['strhelp'].'" target="phppgadminhelp">';
            $str .= $this->lang['strhelpicon'].'</a>';
        }
        if ($do_print) {
            echo $str;
        } else {
            return $str;
        }
    }

    /**
     * Gets the help link.
     *
     * @param string $help The help subject
     *
     * @return string the help link
     */
    public function getHelpLink($help)
    {
        return htmlspecialchars(SUBFOLDER.'/help?help='.urlencode($help).'&server='.urlencode($this->getServerId()));
    }

    /**
     * Internally sets the reload browser property.
     *
     * @param bool $flag sets internal $_reload_browser var which will be passed to the footer methods
     *
     * @return \PHPPgAdmin\Misc this class instance
     */
    public function setReloadBrowser($flag)
    {
        $this->_reload_browser = (bool) $flag;

        return $this;
    }

    public function getReloadBrowser()
    {
        return $this->_reload_browser;
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets $_no_db_connection boolean value, allows to render scripts that do not need an active session.
     *
     * @param bool $flag true or false to allow unconnected clients to access the view
     *
     * @return \PHPPgAdmin\Misc this class instance
     */
    public function setNoDBConnection($flag)
    {
        $this->_no_db_connection = (bool) $flag;

        return $this;
    }

    /**
     * Gets member variable $_no_db_connection.
     *
     * @return bool value of member variable $_no_db_connection
     */
    public function getNoDBConnection()
    {
        return $this->_no_db_connection;
    }

    /**
     * Sets the last error message to display afterwards instead of just dying with the error msg.
     *
     * @param string $msg error message string
     *
     * @return \PHPPgAdmin\Misc this class instance
     */
    public function setErrorMsg($msg)
    {
        $this->_error_msg = $msg;

        return $this;
    }

    /**
     * Returns the error messages stored in member variable $_error_msg.
     *
     * @return string the error message
     */
    public function getErrorMsg()
    {
        return $this->_error_msg;
    }

    /**
     * Creates a database accessor.
     *
     * @param string $database  the name of the database
     * @param mixed  $server_id the id of the server
     *
     * @internal mixed $plaform placeholder that will receive the value of the platform
     */
    public function getDatabaseAccessor($database = '', $server_id = null)
    {
        $lang = $this->lang;

        if ($server_id !== null) {
            $this->_server_id = $server_id;
        }
        //$this->prtrace($this->_server_id);

        $server_info = $this->getServerInfo($this->_server_id);

        if ($this->_no_db_connection || !isset($server_info['username'])) {
            return null;
        }

        if ($this->_data === null) {
            try {
                $_connection = $this->getConnection($database, $this->_server_id);
            } catch (\Exception $e) {
                $this->setServerInfo(null, null, $this->_server_id);
                $this->setNoDBConnection(true);
                $this->setErrorMsg($e->getMessage());

                return null;
            }

            //$this->prtrace('_connection', $_connection);
            if (!$_connection) {
                $this->container->utils->addError($lang['strloginfailed']);
                $this->setErrorMsg($lang['strloginfailed']);

                return null;
            }
            // Get the name of the database driver we need to use.
            // The description of the server is returned in $platform.
            $_type = $_connection->getDriver($platform);

            //$this->prtrace(['type' => $_type, 'platform' => $platform, 'pgVersion' => $_connection->conn->pgVersion]);

            if ($_type === null) {
                $errormsg = sprintf($lang['strpostgresqlversionnotsupported'], $this->postgresqlMinVer);
                $this->container->utils->addError($errormsg);
                $this->setErrorMsg($errormsg);

                return null;
            }
            $_type = '\PHPPgAdmin\Database\\'.$_type;

            $this->setServerInfo('platform', $platform, $this->_server_id);
            $this->setServerInfo('pgVersion', $_connection->conn->pgVersion, $this->_server_id);

            // Create a database wrapper class for easy manipulation of the
            // connection.

            $this->_data           = new $_type($_connection->conn, $this->container, $server_info);
            $this->_data->platform = $_connection->platform;

            //$this->_data->getHelpPages();

            //$this->prtrace('help_page has ' . count($this->_data->help_page) . ' items');

            /* we work on UTF-8 only encoding */
            $this->_data->execute("SET client_encoding TO 'UTF-8'");

            if ($this->_data->hasByteaHexDefault()) {
                $this->_data->execute('SET bytea_output TO escape');
            }
        }

        if ($this->_no_db_connection === false &&
            $this->getDatabase() !== null &&
            isset($_REQUEST['schema'])
        ) {
            $status = $this->_data->setSchema($_REQUEST['schema']);

            if ($status != 0) {
                $this->container->utils->addError($this->lang['strbadschema']);
                $this->setErrorMsg($this->lang['strbadschema']);

                return null;
            }
        }

        return $this->_data;
    }

    public function getConnection($database = '', $server_id = null)
    {
        $lang = $this->lang;

        if ($this->_connection === null) {
            if ($server_id !== null) {
                $this->_server_id = $server_id;
            }
            $server_info     = $this->getServerInfo($this->_server_id);
            $database_to_use = $this->getDatabase($database);

            // Perform extra security checks if this config option is set
            if ($this->conf['extra_login_security']) {
                // Disallowed logins if extra_login_security is enabled.
                // These must be lowercase.
                $bad_usernames = [
                    'pgsql'         => 'pgsql',
                    'postgres'      => 'postgres',
                    'root'          => 'root',
                    'administrator' => 'administrator',
                ];

                if (isset($server_info['username']) &&
                    array_key_exists(strtolower($server_info['username']), $bad_usernames)
                ) {
                    $msg = $lang['strlogindisallowed'];

                    throw new \Exception($msg);
                }

                if (!isset($server_info['password']) ||
                    $server_info['password'] == ''
                ) {
                    $msg = $lang['strlogindisallowed'];

                    throw new \Exception($msg);
                }
            }

            try {
                // Create the connection object and make the connection
                $this->_connection = new \PHPPgAdmin\Database\Connection(
                    $server_info,
                    $database_to_use,
                    $this->container
                );
            } catch (\PHPPgAdmin\ADOdbException $e) {
                throw new \Exception($lang['strloginfailed']);
            }
        }

        return $this->_connection;
    }

    /**
     * Validate and retrieve information on a server.
     * If the parameter isn't supplied then the currently
     * connected server is returned.
     *
     * @param string $server_id A server identifier (host:port)
     *
     * @return array An associative array of server properties
     */
    public function getServerInfo($server_id = null)
    {
        //\PC::debug(['$server_id' => $server_id]);

        if ($server_id !== null) {
            $this->_server_id = $server_id;
        } elseif ($this->_server_info !== null) {
            return $this->_server_info;
        }

        // Check for the server in the logged-in list
        if (isset($_SESSION['webdbLogin'][$this->_server_id])) {
            $this->_server_info = $_SESSION['webdbLogin'][$this->_server_id];

            return $this->_server_info;
        }

        // Otherwise, look for it in the conf file
        foreach ($this->conf['servers'] as $idx => $info) {
            $server_string = $info['host'].':'.$info['port'].':'.$info['sslmode'];
            $server_sha    = sha1($server_string);

            if ($this->_server_id === $server_string ||
                $this->_server_id === $server_sha
            ) {
                if (isset($info['username'])) {
                    $this->setServerInfo(null, $info, $this->_server_id);
                } elseif (isset($_SESSION['sharedUsername'])) {
                    $info['username'] = $_SESSION['sharedUsername'];
                    $info['password'] = $_SESSION['sharedPassword'];
                    $this->setReloadBrowser(true);
                    $this->setServerInfo(null, $info, $this->_server_id);
                }
                $this->_server_info = $info;

                return $this->_server_info;
            }
        }

        if ($server_id === null) {
            $this->_server_info = null;

            return $this->_server_info;
        }

        $this->prtrace('Invalid server param');
        $this->_server_info = null;
        // Unable to find a matching server, are we being hacked?
        return $this->halt($this->lang['strinvalidserverparam']);
    }

    /**
     * Set server information.
     *
     * @param null|string $key       parameter name to set, or null to replace all
     *                               params with the assoc-array in $value
     * @param mixed       $value     the new value, or null to unset the parameter
     * @param null|string $server_id the server identifier, or null for current server
     */
    public function setServerInfo($key, $value, $server_id = null)
    {
        //\PC::debug('setsetverinfo');
        if ($server_id === null) {
            $server_id = $this->container->requestobj->getParam('server');
        }

        if ($key === null) {
            if ($value === null) {
                unset($_SESSION['webdbLogin'][$server_id]);
            } else {
                //\PC::debug(['server_id' => $server_id, 'value' => $value], 'webdbLogin null key');
                $_SESSION['webdbLogin'][$server_id] = $value;
            }
        } else {
            if ($value === null) {
                unset($_SESSION['webdbLogin'][$server_id][$key]);
            } else {
                //\PC::debug(['server_id' => $server_id, 'key' => $key, 'value' => $value], __FILE__ . ' ' . __LINE__ . ' webdbLogin key ' . $key);
                $_SESSION['webdbLogin'][$server_id][$key] = $value;
            }
        }
    }

    public function getDatabase($database = '')
    {
        if ($this->_server_id === null && !isset($_REQUEST['database'])) {
            return null;
        }

        $server_info = $this->getServerInfo($this->_server_id);

        if ($this->_server_id !== null &&
            isset($server_info['useonlydefaultdb']) &&
            $server_info['useonlydefaultdb'] === true &&
            isset($server_info['defaultdb'])
        ) {
            $this->_database = $server_info['defaultdb'];
        } elseif ($database !== '') {
            $this->_database = $database;
        } elseif (isset($_REQUEST['database'])) {
            // Connect to the current database
            $this->_database = $_REQUEST['database'];
        } elseif (isset($server_info['defaultdb'])) {
            // or if one is not specified then connect to the default database.
            $this->_database = $server_info['defaultdb'];
        } else {
            return null;
        }

        return $this->_database;
    }

    /**
     * Set the current schema.
     *
     * @param string $schema The schema name
     *
     * @return int 0 on success
     */
    public function setCurrentSchema($schema)
    {
        $data = $this->getDatabaseAccessor();

        $status = $data->setSchema($schema);
        if ($status != 0) {
            return $status;
        }

        $_REQUEST['schema'] = $schema;
        $this->container->offsetSet('schema', $schema);
        $this->setHREF();

        return 0;
    }

    /**
     * Checks if dumps are properly set up.
     *
     * @param bool $all (optional) True to check pg_dumpall, false to just check pg_dump
     *
     * @return bool True, dumps are set up, false otherwise
     */
    public function isDumpEnabled($all = false)
    {
        $info = $this->getServerInfo();

        return !empty($info[$all ? 'pg_dumpall_path' : 'pg_dump_path']);
    }

    /**
     * Sets the href tracking variable.
     *
     * @return \PHPPgAdmin\Misc this class instance
     */
    public function setHREF()
    {
        $this->href = $this->getHREF();
        //\PC::debug($this->href, 'Misc::href');
        return $this;
    }

    /**
     * Get a href query string, excluding objects below the given object type (inclusive).
     *
     * @param null|string $exclude_from
     *
     * @return string
     */
    public function getHREF($exclude_from = null)
    {
        $href = [];

        $server   = $this->container->server || isset($_REQUEST['server']) ? $_REQUEST['server'] : null;
        $database = $this->container->database || isset($_REQUEST['database']) ? $_REQUEST['database'] : null;
        $schema   = $this->container->schema || isset($_REQUEST['schema']) ? $_REQUEST['schema'] : null;

        if ($server && $exclude_from !== 'server') {
            $href[] = 'server='.urlencode($server);
        }
        if ($database && $exclude_from !== 'database') {
            $href[] = 'database='.urlencode($database);
        }
        if ($schema && $exclude_from !== 'schema') {
            $href[] = 'schema='.urlencode($schema);
        }

        $this->href = htmlentities(implode('&', $href));

        return $this->href;
    }

    /**
     * Sets the form tracking variable.
     */
    public function setForm()
    {
        $form = [];
        if ($this->container->server) {
            $form[] = '<input type="hidden" name="server" value="'.htmlspecialchars($this->container->server).'" />';
        }
        if ($this->container->database) {
            $form[] = '<input type="hidden" name="database" value="'.htmlspecialchars($this->container->database).'" />';
        }

        if ($this->container->schema) {
            $form[] = '<input type="hidden" name="schema" value="'.htmlspecialchars($this->container->schema).'" />';
        }
        $this->form = implode("\n", $form);

        return $this->form;
        //\PC::debug($this->form, 'Misc::form');
    }

    /**
     * A function to recursively strip slashes.  Used to
     * enforce magic_quotes_gpc being off.
     *
     * @param mixed $var The variable to strip (passed by reference)
     */
    public function stripVar(&$var)
    {
        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $this->stripVar($var[$k]);

                /* magic_quotes_gpc escape keys as well ...*/
                if (is_string($k)) {
                    $ek = stripslashes($k);
                    if ($ek !== $k) {
                        $var[$ek] = $var[$k];
                        unset($var[$k]);
                    }
                }
            }
        } else {
            $var = stripslashes($var);
        }
    }

    /**
     * Converts a PHP.INI size variable to bytes.  Taken from publically available
     * function by Chris DeRose, here: http://www.php.net/manual/en/configuration.directives.php#ini.file-uploads.
     *
     * @param mixed $strIniSize The PHP.INI variable
     *
     * @return bool|float|int size in bytes, false on failure
     */
    public function inisizeToBytes($strIniSize)
    {
        // This function will take the string value of an ini 'size' parameter,
        // and return a double (64-bit float) representing the number of bytes
        // that the parameter represents. Or false if $strIniSize is unparseable.
        $a_IniParts = [];

        if (!is_string($strIniSize)) {
            return false;
        }

        if (!preg_match('/^(\d+)([bkm]*)$/i', $strIniSize, $a_IniParts)) {
            return false;
        }

        $nSize   = (float) $a_IniParts[1];
        $strUnit = strtolower($a_IniParts[2]);

        switch ($strUnit) {
            case 'm':
                return $nSize * (float) 1048576;
            case 'k':
                return $nSize * (float) 1024;
            case 'b':
            default:
                return $nSize;
        }
    }

    public function getRequestVars($subject = '')
    {
        $v = [];
        if (!empty($subject)) {
            $v['subject'] = $subject;
        }

        if ($this->_server_id !== null && $subject != 'root') {
            $v['server'] = $this->_server_id;
            if ($this->_database !== null && $subject != 'server') {
                $v['database'] = $this->_database;
                if (isset($_REQUEST['schema']) && $subject != 'database') {
                    $v['schema'] = $_REQUEST['schema'];
                }
            }
        }
        //$this->prtrace($v);
        return $v;
    }

    public function icon($icon)
    {
        if (is_string($icon)) {
            $path = "/assets/images/themes/{$this->conf['theme']}/{$icon}";
            if (file_exists(\BASE_PATH.$path.'.png')) {
                return SUBFOLDER.$path.'.png';
            }

            if (file_exists(\BASE_PATH.$path.'.gif')) {
                return SUBFOLDER.$path.'.gif';
            }

            if (file_exists(\BASE_PATH.$path.'.ico')) {
                return SUBFOLDER.$path.'.ico';
            }

            $path = "/assets/images/themes/default/{$icon}";
            if (file_exists(\BASE_PATH.$path.'.png')) {
                return SUBFOLDER.$path.'.png';
            }

            if (file_exists(\BASE_PATH.$path.'.gif')) {
                return SUBFOLDER.$path.'.gif';
            }

            if (file_exists(\BASE_PATH.$path.'.ico')) {
                return SUBFOLDER.$path.'.ico';
            }
        } else {
            // Icon from plugins
            $path = "/plugins/{$icon[0]}/images/{$icon[1]}";
            if (file_exists(\BASE_PATH.$path.'.png')) {
                return SUBFOLDER.$path.'.png';
            }

            if (file_exists(\BASE_PATH.$path.'.gif')) {
                return SUBFOLDER.$path.'.gif';
            }

            if (file_exists(\BASE_PATH.$path.'.ico')) {
                return SUBFOLDER.$path.'.ico';
            }
        }

        return '';
    }

    /**
     * Function to escape command line parameters.
     *
     * @param string $str The string to escape
     *
     * @return string The escaped string
     */
    public function escapeShellArg($str)
    {
        //$data = $this->getDatabaseAccessor();
        $lang = $this->lang;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Due to annoying PHP bugs, shell arguments cannot be escaped
            // (command simply fails), so we cannot allow complex objects
            // to be dumped.
            if (preg_match('/^[_.[:alnum:]]+$/', $str)) {
                return $str;
            }

            return $this->halt($lang['strcannotdumponwindows']);
        }

        return escapeshellarg($str);
    }

    /**
     * Function to escape command line programs.
     *
     * @param string $str The string to escape
     *
     * @return string The escaped string
     */
    public function escapeShellCmd($str)
    {
        $data = $this->getDatabaseAccessor();

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $data->fieldClean($str);

            return '"'.$str.'"';
        }

        return escapeshellcmd($str);
    }

    /**
     * Save the given SQL script in the history
     * of the database and server.
     *
     * @param string $script the SQL script to save
     */
    public function saveScriptHistory($script)
    {
        list($usec, $sec)                                                           = explode(' ', microtime());
        $time                                                                       = ((float) $usec + (float) $sec);
        $_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']]["${time}"] = [
            'query'    => $script,
            'paginate' => !isset($_REQUEST['paginate']) ? 'f' : 't',
            'queryid'  => $time,
        ];
    }

    /**
     * Returns an array representing FKs definition for a table, sorted by fields
     * or by constraint.
     *
     * @param string $table The table to retrieve FK contraints from
     *
     * @return array|bool the array of FK definition:
     *                    array(
     *                    'byconstr' => array(
     *                    constrain id => array(
     *                    confrelid => foreign relation oid
     *                    f_schema => foreign schema name
     *                    f_table => foreign table name
     *                    pattnums => array of parent's fields nums
     *                    pattnames => array of parent's fields names
     *                    fattnames => array of foreign attributes names
     *                    )
     *                    ),
     *                    'byfield' => array(
     *                    attribute num => array (constraint id, ...)
     *                    ),
     *                    'code' => HTML/js code to include in the page for auto-completion
     *                    )
     */
    public function getAutocompleteFKProperties($table)
    {
        $data = $this->getDatabaseAccessor();

        $fksprops = [
            'byconstr' => [],
            'byfield'  => [],
            'code'     => '',
        ];

        $constrs = $data->getConstraintsWithFields($table);

        if (!$constrs->EOF) {
            //$conrelid = $constrs->fields['conrelid'];
            while (!$constrs->EOF) {
                if ($constrs->fields['contype'] == 'f') {
                    if (!isset($fksprops['byconstr'][$constrs->fields['conid']])) {
                        $fksprops['byconstr'][$constrs->fields['conid']] = [
                            'confrelid' => $constrs->fields['confrelid'],
                            'f_table'   => $constrs->fields['f_table'],
                            'f_schema'  => $constrs->fields['f_schema'],
                            'pattnums'  => [],
                            'pattnames' => [],
                            'fattnames' => [],
                        ];
                    }

                    $fksprops['byconstr'][$constrs->fields['conid']]['pattnums'][]  = $constrs->fields['p_attnum'];
                    $fksprops['byconstr'][$constrs->fields['conid']]['pattnames'][] = $constrs->fields['p_field'];
                    $fksprops['byconstr'][$constrs->fields['conid']]['fattnames'][] = $constrs->fields['f_field'];

                    if (!isset($fksprops['byfield'][$constrs->fields['p_attnum']])) {
                        $fksprops['byfield'][$constrs->fields['p_attnum']] = [];
                    }

                    $fksprops['byfield'][$constrs->fields['p_attnum']][] = $constrs->fields['conid'];
                }
                $constrs->moveNext();
            }

            $fksprops['code'] = "<script type=\"text/javascript\">\n";
            $fksprops['code'] .= "var constrs = {};\n";
            foreach ($fksprops['byconstr'] as $conid => $props) {
                $fksprops['code'] .= "constrs.constr_{$conid} = {\n";
                $fksprops['code'] .= 'pattnums: ['.implode(',', $props['pattnums'])."],\n";
                $fksprops['code'] .= "f_table:'".addslashes(htmlentities($props['f_table'], ENT_QUOTES, 'UTF-8'))."',\n";
                $fksprops['code'] .= "f_schema:'".addslashes(htmlentities($props['f_schema'], ENT_QUOTES, 'UTF-8'))."',\n";
                $_ = '';
                foreach ($props['pattnames'] as $n) {
                    $_ .= ",'".htmlentities($n, ENT_QUOTES, 'UTF-8')."'";
                }
                $fksprops['code'] .= 'pattnames: ['.substr($_, 1)."],\n";

                $_ = '';
                foreach ($props['fattnames'] as $n) {
                    $_ .= ",'".htmlentities($n, ENT_QUOTES, 'UTF-8')."'";
                }

                $fksprops['code'] .= 'fattnames: ['.substr($_, 1)."]\n";
                $fksprops['code'] .= "};\n";
            }

            $fksprops['code'] .= "var attrs = {};\n";
            foreach ($fksprops['byfield'] as $attnum => $cstrs) {
                $fksprops['code'] .= "attrs.attr_{$attnum} = [".implode(',', $fksprops['byfield'][$attnum])."];\n";
            }

            $fksprops['code'] .= "var table='".addslashes(htmlentities($table, ENT_QUOTES, 'UTF-8'))."';";
            $fksprops['code'] .= "var server='".htmlentities($_REQUEST['server'], ENT_QUOTES, 'UTF-8')."';";
            $fksprops['code'] .= "var database='".addslashes(htmlentities($_REQUEST['database'], ENT_QUOTES, 'UTF-8'))."';";
            $fksprops['code'] .= "var subfolder='".SUBFOLDER."';";
            $fksprops['code'] .= "</script>\n";

            $fksprops['code'] .= '<div id="fkbg"></div>';
            $fksprops['code'] .= '<div id="fklist"></div>';
            $fksprops['code'] .= '<script src="'.SUBFOLDER.'/assets/js/ac_insert_row.js" type="text/javascript"></script>';
        } else {
            /* we have no foreign keys on this table */
            return false;
        }

        return $fksprops;
    }
}
