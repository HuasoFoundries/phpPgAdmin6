<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

namespace PHPPgAdmin;

use PHPPgAdmin\Decorators\Decorator;

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

    public function getSubjectParams($subject)
    {
        $plugin_manager = $this->plugin_manager;

        $vars = [];

        switch ($subject) {
            case 'root':
                $vars = [
                    'params' => [
                        'subject' => 'root',
                    ],
                ];

                break;
            case 'server':
                $vars = ['params' => [
                    'server'  => $_REQUEST['server'],
                    'subject' => 'server',
                ]];

                break;
            case 'role':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'role',
                    'action'   => 'properties',
                    'rolename' => $_REQUEST['rolename'],
                ]];

                break;
            case 'database':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'database',
                    'database' => $_REQUEST['database'],
                ]];

                break;
            case 'schema':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'schema',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                ]];

                break;
            case 'table':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'table',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'table'    => $_REQUEST['table'],
                ]];

                break;
            case 'selectrows':
                $vars = [
                    'url'    => 'tables',
                    'params' => [
                        'server'   => $_REQUEST['server'],
                        'subject'  => 'table',
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'table'    => $_REQUEST['table'],
                        'action'   => 'confselectrows',
                    ], ];

                break;
            case 'view':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'view',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'view'     => $_REQUEST['view'],
                ]];

                break;
            case 'matview':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'matview',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'matview'  => $_REQUEST['matview'],
                ]];

                break;
            case 'fulltext':
            case 'ftscfg':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'fulltext',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'action'   => 'viewconfig',
                    'ftscfg'   => $_REQUEST['ftscfg'],
                ]];

                break;
            case 'function':
                $vars = ['params' => [
                    'server'       => $_REQUEST['server'],
                    'subject'      => 'function',
                    'database'     => $_REQUEST['database'],
                    'schema'       => $_REQUEST['schema'],
                    'function'     => $_REQUEST['function'],
                    'function_oid' => $_REQUEST['function_oid'],
                ]];

                break;
            case 'aggregate':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'aggregate',
                    'action'   => 'properties',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'aggrname' => $_REQUEST['aggrname'],
                    'aggrtype' => $_REQUEST['aggrtype'],
                ]];

                break;
            case 'column':
                if (isset($_REQUEST['table'])) {
                    $vars = ['params' => [
                        'server'   => $_REQUEST['server'],
                        'subject'  => 'column',
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'table'    => $_REQUEST['table'],
                        'column'   => $_REQUEST['column'],
                    ]];
                } else {
                    $vars = ['params' => [
                        'server'   => $_REQUEST['server'],
                        'subject'  => 'column',
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'view'     => $_REQUEST['view'],
                        'column'   => $_REQUEST['column'],
                    ]];
                }

                break;
            case 'plugin':
                $vars = [
                    'url'    => 'plugin',
                    'params' => [
                        'server'  => $_REQUEST['server'],
                        'subject' => 'plugin',
                        'plugin'  => $_REQUEST['plugin'],
                    ], ];

                if (!is_null($plugin_manager->getPlugin($_REQUEST['plugin']))) {
                    $vars['params'] = array_merge($vars['params'], $plugin_manager->getPlugin($_REQUEST['plugin'])->get_subject_params());
                }

                break;
            default:
                return false;
        }

        if (!isset($vars['url'])) {
            $vars['url'] = SUBFOLDER.'/redirect';
        }
        if ($vars['url'] == SUBFOLDER.'/redirect' && isset($vars['params']['subject'])) {
            $vars['url'] = SUBFOLDER.'/redirect/'.$vars['params']['subject'];
            unset($vars['params']['subject']);
        }

        return $vars;
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
     * Render a value into HTML using formatting rules specified
     * by a type name and parameters.
     *
     * @param string $str    The string to change
     * @param string $type   Field type (optional), this may be an internal PostgreSQL type, or:
     *                       yesno    - same as bool, but renders as 'Yes' or 'No'.
     *                       pre      - render in a <pre> block.
     *                       nbsp     - replace all spaces with &nbsp;'s
     *                       verbatim - render exactly as supplied, no escaping what-so-ever.
     *                       callback - render using a callback function supplied in the 'function' param.
     * @param array  $params Type parameters (optional), known parameters:
     *                       null     - string to display if $str is null, or set to TRUE to use a default 'NULL' string,
     *                       otherwise nothing is rendered.
     *                       clip     - if true, clip the value to a fixed length, and append an ellipsis...
     *                       cliplen  - the maximum length when clip is enabled (defaults to $conf['max_chars'])
     *                       ellipsis - the string to append to a clipped value (defaults to $lang['strellipsis'])
     *                       tag      - an HTML element name to surround the value.
     *                       class    - a class attribute to apply to any surrounding HTML element.
     *                       align    - an align attribute ('left','right','center' etc.)
     *                       true     - (type='bool') the representation of true.
     *                       false    - (type='bool') the representation of false.
     *                       function - (type='callback') a function name, accepts args ($str, $params) and returns a rendering.
     *                       lineno   - prefix each line with a line number.
     *                       map      - an associative array.
     *
     * @return string The HTML rendered value
     */
    public function printVal($str, $type = null, $params = [])
    {
        $lang = $this->lang;
        $data = $this->getDatabaseAccessor();

        // Shortcircuit for a NULL value
        if (!$str) {
            return isset($params['null'])
            ? ($params['null'] === true ? '<i>NULL</i>' : $params['null'])
            : '';
        }

        if (isset($params['map'], $params['map'][$str])) {
            $str = $params['map'][$str];
        }

        // Clip the value if the 'clip' parameter is true.
        if (isset($params['clip']) && $params['clip'] === true) {
            $maxlen   = isset($params['cliplen']) && is_integer($params['cliplen']) ? $params['cliplen'] : $this->conf['max_chars'];
            $ellipsis = isset($params['ellipsis']) ? $params['ellipsis'] : $lang['strellipsis'];
            if (strlen($str) > $maxlen) {
                $str = substr($str, 0, $maxlen - 1).$ellipsis;
            }
        }

        $out   = '';
        $class = '';

        switch ($type) {
            case 'int2':
            case 'int4':
            case 'int8':
            case 'float4':
            case 'float8':
            case 'money':
            case 'numeric':
            case 'oid':
            case 'xid':
            case 'cid':
            case 'tid':
                $align = 'right';
                $out   = nl2br(htmlspecialchars(\PHPPgAdmin\Traits\HelperTrait::br2ln($str)));

                break;
            case 'yesno':
                if (!isset($params['true'])) {
                    $params['true'] = $lang['stryes'];
                }

                if (!isset($params['false'])) {
                    $params['false'] = $lang['strno'];
                }

            // no break - fall through to boolean case.
            case 'bool':
            case 'boolean':
                if (is_bool($str)) {
                    $str = $str ? 't' : 'f';
                }

                switch ($str) {
                    case 't':
                        $out   = (isset($params['true']) ? $params['true'] : $lang['strtrue']);
                        $align = 'center';

                        break;
                    case 'f':
                        $out   = (isset($params['false']) ? $params['false'] : $lang['strfalse']);
                        $align = 'center';

                        break;
                    default:
                        $out = htmlspecialchars($str);
                }

                break;
            case 'bytea':
                $tag   = 'div';
                $class = 'pre';
                $out   = $data->escapeBytea($str);

                break;
            case 'errormsg':
                $tag   = 'pre';
                $class = 'error';
                $out   = htmlspecialchars($str);

                break;
            case 'pre':
                $tag = 'pre';
                $out = htmlspecialchars($str);

                break;
            case 'prenoescape':
                $tag = 'pre';
                $out = $str;

                break;
            case 'nbsp':
                $out = nl2br(str_replace(' ', '&nbsp;', \PHPPgAdmin\Traits\HelperTrait::br2ln($str)));

                break;
            case 'verbatim':
                $out = $str;

                break;
            case 'callback':
                $out = $params['function']($str, $params);

                break;
            case 'prettysize':
                if ($str == -1) {
                    $out = $lang['strnoaccess'];
                } else {
                    $limit = 10 * 1024;
                    $mult  = 1;
                    if ($str < $limit * $mult) {
                        $out = $str.' '.$lang['strbytes'];
                    } else {
                        $mult *= 1024;
                        if ($str < $limit * $mult) {
                            $out = floor(($str + $mult / 2) / $mult).' '.$lang['strkb'];
                        } else {
                            $mult *= 1024;
                            if ($str < $limit * $mult) {
                                $out = floor(($str + $mult / 2) / $mult).' '.$lang['strmb'];
                            } else {
                                $mult *= 1024;
                                if ($str < $limit * $mult) {
                                    $out = floor(($str + $mult / 2) / $mult).' '.$lang['strgb'];
                                } else {
                                    $mult *= 1024;
                                    if ($str < $limit * $mult) {
                                        $out = floor(($str + $mult / 2) / $mult).' '.$lang['strtb'];
                                    }
                                }
                            }
                        }
                    }
                }

                break;
            default:
                // If the string contains at least one instance of >1 space in a row, a tab
                // character, a space at the start of a line, or a space at the start of
                // the whole string then render within a pre-formatted element (<pre>).
                if (preg_match('/(^ |  |\t|\n )/m', $str)) {
                    $tag   = 'pre';
                    $class = 'data';
                    $out   = htmlspecialchars($str);
                } else {
                    $out = nl2br(htmlspecialchars(\PHPPgAdmin\Traits\HelperTrait::br2ln($str)));
                }
        }

        if (isset($params['class'])) {
            $class = $params['class'];
        }

        if (isset($params['align'])) {
            $align = $params['align'];
        }

        if (!isset($tag) && (isset($class) || isset($align))) {
            $tag = 'div';
        }

        if (isset($tag)) {
            $alignattr = isset($align) ? " style=\"text-align: {$align}\"" : '';
            $classattr = isset($class) ? " class=\"{$class}\"" : '';
            $out       = "<{$tag}{$alignattr}{$classattr}>{$out}</{$tag}>";
        }

        // Add line numbers if 'lineno' param is true
        if (isset($params['lineno']) && $params['lineno'] === true) {
            $lines = explode("\n", $str);
            $num   = count($lines);
            if ($num > 0) {
                $temp = "<table>\n<tr><td class=\"{$class}\" style=\"vertical-align: top; padding-right: 10px;\"><pre class=\"{$class}\">";
                for ($i = 1; $i <= $num; ++$i) {
                    $temp .= $i."\n";
                }
                $temp .= "</pre></td><td class=\"{$class}\" style=\"vertical-align: top;\">{$out}</td></tr></table>\n";
                $out = $temp;
            }
            unset($lines);
        }

        return $out;
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
     * Retrieve the tab info for a specific tab bar.
     *
     * @param string $section the name of the tab bar
     *
     * @return array array of tabs
     */
    public function getNavTabs($section)
    {
        $data           = $this->getDatabaseAccessor();
        $lang           = $this->lang;
        $plugin_manager = $this->plugin_manager;

        $hide_advanced = ($this->conf['show_advanced'] === false);
        $tabs          = [];

        switch ($section) {
            case 'root':
                $tabs = [
                    'intro'   => [
                        'title' => $lang['strintroduction'],
                        'url'   => 'intro',
                        'icon'  => 'Introduction',
                    ],
                    'servers' => [
                        'title' => $lang['strservers'],
                        'url'   => 'servers',
                        'icon'  => 'Servers',
                    ],
                ];

                break;
            case 'server':
                $hide_users = true;
                $hide_roles = false;
                if ($data) {
                    $hide_users = !$data->isSuperUser();
                }

                $tabs = [
                    'databases' => [
                        'title'   => $lang['strdatabases'],
                        'url'     => 'alldb',
                        'urlvars' => ['subject' => 'server'],
                        'help'    => 'pg.database',
                        'icon'    => 'Databases',
                    ],
                ];
                if ($data && $data->hasRoles()) {
                    $tabs = array_merge($tabs, [
                        'users' => [
                            'title'   => $lang['strusers'],
                            'url'     => 'users',
                            'urlvars' => ['subject' => 'server'],
                            'hide'    => $hide_roles,
                            'help'    => 'pg.user',
                            'icon'    => 'Users',
                        ],
                        'roles' => [
                            'title'   => $lang['strroles'],
                            'url'     => 'roles',
                            'urlvars' => ['subject' => 'server'],
                            'hide'    => $hide_roles,
                            'help'    => 'pg.role',
                            'icon'    => 'Roles',
                        ],
                    ]);
                } else {
                    $tabs = array_merge($tabs, [
                        'users'  => [
                            'title'   => $lang['strusers'],
                            'url'     => 'users',
                            'urlvars' => ['subject' => 'server'],
                            'hide'    => $hide_users,
                            'help'    => 'pg.user',
                            'icon'    => 'Users',
                        ],
                        'groups' => [
                            'title'   => $lang['strgroups'],
                            'url'     => 'groups',
                            'urlvars' => ['subject' => 'server'],
                            'hide'    => $hide_users,
                            'help'    => 'pg.group',
                            'icon'    => 'UserGroups',
                        ],
                    ]);
                }

                $tabs = array_merge($tabs, [
                    'account'     => [
                        'title'   => $lang['straccount'],
                        'url'     => ($data && $data->hasRoles()) ? 'roles' : 'users',
                        'urlvars' => ['subject' => 'server', 'action' => 'account'],
                        'hide'    => !$hide_users,
                        'help'    => 'pg.role',
                        'icon'    => 'User',
                    ],
                    'tablespaces' => [
                        'title'   => $lang['strtablespaces'],
                        'url'     => 'tablespaces',
                        'urlvars' => ['subject' => 'server'],
                        'hide'    => !$data || !$data->hasTablespaces(),
                        'help'    => 'pg.tablespace',
                        'icon'    => 'Tablespaces',
                    ],
                    'export'      => [
                        'title'   => $lang['strexport'],
                        'url'     => 'alldb',
                        'urlvars' => ['subject' => 'server', 'action' => 'export'],
                        'hide'    => !$this->isDumpEnabled(),
                        'icon'    => 'Export',
                    ],
                ]);

                break;
            case 'database':
                $tabs = [
                    'schemas'    => [
                        'title'   => $lang['strschemas'],
                        'url'     => 'schemas',
                        'urlvars' => ['subject' => 'database'],
                        'help'    => 'pg.schema',
                        'icon'    => 'Schemas',
                    ],
                    'sql'        => [
                        'title'   => $lang['strsql'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'sql', 'new' => 1],
                        'help'    => 'pg.sql',
                        'tree'    => false,
                        'icon'    => 'SqlEditor',
                    ],
                    'find'       => [
                        'title'   => $lang['strfind'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'find'],
                        'tree'    => false,
                        'icon'    => 'Search',
                    ],
                    'variables'  => [
                        'title'   => $lang['strvariables'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'variables'],
                        'help'    => 'pg.variable',
                        'tree'    => false,
                        'icon'    => 'Variables',
                    ],
                    'processes'  => [
                        'title'   => $lang['strprocesses'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'processes'],
                        'help'    => 'pg.process',
                        'tree'    => false,
                        'icon'    => 'Processes',
                    ],
                    'locks'      => [
                        'title'   => $lang['strlocks'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'locks'],
                        'help'    => 'pg.locks',
                        'tree'    => false,
                        'icon'    => 'Key',
                    ],
                    'admin'      => [
                        'title'   => $lang['stradmin'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'admin'],
                        'tree'    => false,
                        'icon'    => 'Admin',
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'database'],
                        'hide'    => !isset($data->privlist['database']),
                        'help'    => 'pg.privilege',
                        'tree'    => false,
                        'icon'    => 'Privileges',
                    ],
                    'languages'  => [
                        'title'   => $lang['strlanguages'],
                        'url'     => 'languages',
                        'urlvars' => ['subject' => 'database'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.language',
                        'icon'    => 'Languages',
                    ],
                    'casts'      => [
                        'title'   => $lang['strcasts'],
                        'url'     => 'casts',
                        'urlvars' => ['subject' => 'database'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.cast',
                        'icon'    => 'Casts',
                    ],
                    'export'     => [
                        'title'   => $lang['strexport'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'export'],
                        'hide'    => !$this->isDumpEnabled(),
                        'tree'    => false,
                        'icon'    => 'Export',
                    ],
                ];

                break;
            case 'schema':
                $tabs = [
                    'tables'      => [
                        'title'   => $lang['strtables'],
                        'url'     => 'tables',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.table',
                        'icon'    => 'Tables',
                    ],
                    'views'       => [
                        'title'   => $lang['strviews'],
                        'url'     => 'views',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.view',
                        'icon'    => 'Views',
                    ],
                    'matviews'    => [
                        'title'   => 'M '.$lang['strviews'],
                        'url'     => 'materializedviews',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.matview',
                        'icon'    => 'MViews',
                    ],
                    'sequences'   => [
                        'title'   => $lang['strsequences'],
                        'url'     => 'sequences',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.sequence',
                        'icon'    => 'Sequences',
                    ],
                    'functions'   => [
                        'title'   => $lang['strfunctions'],
                        'url'     => 'functions',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.function',
                        'icon'    => 'Functions',
                    ],
                    'fulltext'    => [
                        'title'   => $lang['strfulltext'],
                        'url'     => 'fulltext',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.fts',
                        'tree'    => true,
                        'icon'    => 'Fts',
                    ],
                    'domains'     => [
                        'title'   => $lang['strdomains'],
                        'url'     => 'domains',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.domain',
                        'icon'    => 'Domains',
                    ],
                    'aggregates'  => [
                        'title'   => $lang['straggregates'],
                        'url'     => 'aggregates',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.aggregate',
                        'icon'    => 'Aggregates',
                    ],
                    'types'       => [
                        'title'   => $lang['strtypes'],
                        'url'     => 'types',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.type',
                        'icon'    => 'Types',
                    ],
                    'operators'   => [
                        'title'   => $lang['stroperators'],
                        'url'     => 'operators',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.operator',
                        'icon'    => 'Operators',
                    ],
                    'opclasses'   => [
                        'title'   => $lang['stropclasses'],
                        'url'     => 'opclasses',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.opclass',
                        'icon'    => 'OperatorClasses',
                    ],
                    'conversions' => [
                        'title'   => $lang['strconversions'],
                        'url'     => 'conversions',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.conversion',
                        'icon'    => 'Conversions',
                    ],
                    'privileges'  => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.privilege',
                        'tree'    => false,
                        'icon'    => 'Privileges',
                    ],
                    'export'      => [
                        'title'   => $lang['strexport'],
                        'url'     => 'schemas',
                        'urlvars' => ['subject' => 'schema', 'action' => 'export'],
                        'hide'    => !$this->isDumpEnabled(),
                        'tree'    => false,
                        'icon'    => 'Export',
                    ],
                ];
                if (!$data->hasFTS()) {
                    unset($tabs['fulltext']);
                }

                break;
            case 'table':
                $tabs = [
                    'columns'     => [
                        'title'   => $lang['strcolumns'],
                        'url'     => 'tblproperties',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'icon'    => 'Columns',
                        'branch'  => true,
                    ],
                    'browse'      => [
                        'title'   => $lang['strbrowse'],
                        'icon'    => 'Columns',
                        'url'     => 'display',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'return'  => 'table',
                        'branch'  => true,
                    ],
                    'select'      => [
                        'title'   => $lang['strselect'],
                        'icon'    => 'Search',
                        'url'     => 'tables',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'confselectrows'],
                        'help'    => 'pg.sql.select',
                    ],
                    'insert'      => [
                        'title'   => $lang['strinsert'],
                        'url'     => 'tables',
                        'urlvars' => [
                            'action' => 'confinsertrow',
                            'table'  => Decorator::field('table'),
                        ],
                        'help'    => 'pg.sql.insert',
                        'icon'    => 'Operator',
                    ],
                    'indexes'     => [
                        'title'   => $lang['strindexes'],
                        'url'     => 'indexes',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.index',
                        'icon'    => 'Indexes',
                        'branch'  => true,
                    ],
                    'constraints' => [
                        'title'   => $lang['strconstraints'],
                        'url'     => 'constraints',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.constraint',
                        'icon'    => 'Constraints',
                        'branch'  => true,
                    ],
                    'triggers'    => [
                        'title'   => $lang['strtriggers'],
                        'url'     => 'triggers',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.trigger',
                        'icon'    => 'Triggers',
                        'branch'  => true,
                    ],
                    'rules'       => [
                        'title'   => $lang['strrules'],
                        'url'     => 'rules',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.rule',
                        'icon'    => 'Rules',
                        'branch'  => true,
                    ],
                    'admin'       => [
                        'title'   => $lang['stradmin'],
                        'url'     => 'tables',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'admin'],
                        'icon'    => 'Admin',
                    ],
                    'info'        => [
                        'title'   => $lang['strinfo'],
                        'url'     => 'info',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'icon'    => 'Statistics',
                    ],
                    'privileges'  => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.privilege',
                        'icon'    => 'Privileges',
                    ],
                    'import'      => [
                        'title'   => $lang['strimport'],
                        'url'     => 'tblproperties',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'import'],
                        'icon'    => 'Import',
                        'hide'    => false,
                    ],
                    'export'      => [
                        'title'   => $lang['strexport'],
                        'url'     => 'tblproperties',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'export'],
                        'icon'    => 'Export',
                        'hide'    => false,
                    ],
                ];

                break;
            case 'view':
                $tabs = [
                    'columns'    => [
                        'title'   => $lang['strcolumns'],
                        'url'     => 'viewproperties',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                        'icon'    => 'Columns',
                        'branch'  => true,
                    ],
                    'browse'     => [
                        'title'   => $lang['strbrowse'],
                        'icon'    => 'Columns',
                        'url'     => 'display',
                        'urlvars' => [
                            'action'  => 'confselectrows',
                            'return'  => 'schema',
                            'subject' => 'view',
                            'view'    => Decorator::field('view'),
                        ],
                        'branch'  => true,
                    ],
                    'select'     => [
                        'title'   => $lang['strselect'],
                        'icon'    => 'Search',
                        'url'     => 'views',
                        'urlvars' => ['action' => 'confselectrows', 'view' => Decorator::field('view')],
                        'help'    => 'pg.sql.select',
                    ],
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'viewproperties',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view'), 'action' => 'definition'],
                        'icon'    => 'Definition',
                    ],
                    'rules'      => [
                        'title'   => $lang['strrules'],
                        'url'     => 'rules',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                        'help'    => 'pg.rule',
                        'icon'    => 'Rules',
                        'branch'  => true,
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                        'help'    => 'pg.privilege',
                        'icon'    => 'Privileges',
                    ],
                    'export'     => [
                        'title'   => $lang['strexport'],
                        'url'     => 'viewproperties',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view'), 'action' => 'export'],
                        'icon'    => 'Export',
                        'hide'    => false,
                    ],
                ];

                break;
            case 'matview':
                $tabs = [
                    'columns'    => [
                        'title'   => $lang['strcolumns'],
                        'url'     => 'materializedviewproperties',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                        'icon'    => 'Columns',
                        'branch'  => true,
                    ],
                    'browse'     => [
                        'title'   => $lang['strbrowse'],
                        'icon'    => 'Columns',
                        'url'     => 'display',
                        'urlvars' => [
                            'action'  => 'confselectrows',
                            'return'  => 'schema',
                            'subject' => 'matview',
                            'matview' => Decorator::field('matview'),
                        ],
                        'branch'  => true,
                    ],
                    'select'     => [
                        'title'   => $lang['strselect'],
                        'icon'    => 'Search',
                        'url'     => 'materializedviews',
                        'urlvars' => ['action' => 'confselectrows', 'matview' => Decorator::field('matview')],
                        'help'    => 'pg.sql.select',
                    ],
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'materializedviewproperties',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview'), 'action' => 'definition'],
                        'icon'    => 'Definition',
                    ],
                    'indexes'    => [
                        'title'   => $lang['strindexes'],
                        'url'     => 'indexes',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                        'help'    => 'pg.index',
                        'icon'    => 'Indexes',
                        'branch'  => true,
                    ],
                    /*'constraints' => [
                    'title' => $lang['strconstraints'],
                    'url' => 'constraints',
                    'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                    'help' => 'pg.constraint',
                    'icon' => 'Constraints',
                    'branch' => true,
                     */

                    'rules'      => [
                        'title'   => $lang['strrules'],
                        'url'     => 'rules',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                        'help'    => 'pg.rule',
                        'icon'    => 'Rules',
                        'branch'  => true,
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                        'help'    => 'pg.privilege',
                        'icon'    => 'Privileges',
                    ],
                    'export'     => [
                        'title'   => $lang['strexport'],
                        'url'     => 'materializedviewproperties',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview'), 'action' => 'export'],
                        'icon'    => 'Export',
                        'hide'    => false,
                    ],
                ];

                break;
            case 'function':
                $tabs = [
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'functions',
                        'urlvars' => [
                            'subject'      => 'function',
                            'function'     => Decorator::field('function'),
                            'function_oid' => Decorator::field('function_oid'),
                            'action'       => 'properties',
                        ],
                        'icon'    => 'Definition',
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => [
                            'subject'      => 'function',
                            'function'     => Decorator::field('function'),
                            'function_oid' => Decorator::field('function_oid'),
                        ],
                        'icon'    => 'Privileges',
                    ],
                ];

                break;
            case 'aggregate':
                $tabs = [
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'aggregates',
                        'urlvars' => [
                            'subject'  => 'aggregate',
                            'aggrname' => Decorator::field('aggrname'),
                            'aggrtype' => Decorator::field('aggrtype'),
                            'action'   => 'properties',
                        ],
                        'icon'    => 'Definition',
                    ],
                ];

                break;
            case 'role':
                $tabs = [
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'roles',
                        'urlvars' => [
                            'subject'  => 'role',
                            'rolename' => Decorator::field('rolename'),
                            'action'   => 'properties',
                        ],
                        'icon'    => 'Definition',
                    ],
                ];

                break;
            case 'popup':
                $tabs = [
                    'sql'  => [
                        'title'   => $lang['strsql'],
                        'url'     => '/src/views/sqledit',
                        'urlvars' => ['action' => 'sql', 'subject' => 'schema'],
                        'help'    => 'pg.sql',
                        'icon'    => 'SqlEditor',
                    ],
                    'find' => [
                        'title'   => $lang['strfind'],
                        'url'     => '/src/views/sqledit',
                        'urlvars' => ['action' => 'find', 'subject' => 'schema'],
                        'icon'    => 'Search',
                    ],
                ];

                break;
            case 'column':
                $tabs = [
                    'properties' => [
                        'title'   => $lang['strcolprop'],
                        'url'     => 'colproperties',
                        'urlvars' => [
                            'subject' => 'column',
                            'table'   => Decorator::field('table'),
                            'column'  => Decorator::field('column'),
                        ],
                        'icon'    => 'Column',
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => [
                            'subject' => 'column',
                            'table'   => Decorator::field('table'),
                            'column'  => Decorator::field('column'),
                        ],
                        'help'    => 'pg.privilege',
                        'icon'    => 'Privileges',
                    ],
                ];

                break;
            case 'fulltext':
                $tabs = [
                    'ftsconfigs' => [
                        'title'   => $lang['strftstabconfigs'],
                        'url'     => 'fulltext',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => !$data->hasFTS(),
                        'help'    => 'pg.ftscfg',
                        'tree'    => true,
                        'icon'    => 'FtsCfg',
                    ],
                    'ftsdicts'   => [
                        'title'   => $lang['strftstabdicts'],
                        'url'     => 'fulltext',
                        'urlvars' => ['subject' => 'schema', 'action' => 'viewdicts'],
                        'hide'    => !$data->hasFTS(),
                        'help'    => 'pg.ftsdict',
                        'tree'    => true,
                        'icon'    => 'FtsDict',
                    ],
                    'ftsparsers' => [
                        'title'   => $lang['strftstabparsers'],
                        'url'     => 'fulltext',
                        'urlvars' => ['subject' => 'schema', 'action' => 'viewparsers'],
                        'hide'    => !$data->hasFTS(),
                        'help'    => 'pg.ftsparser',
                        'tree'    => true,
                        'icon'    => 'FtsParser',
                    ],
                ];

                break;
        }

        // Tabs hook's place
        $plugin_functions_parameters = [
            'tabs'    => &$tabs,
            'section' => $section,
        ];
        $plugin_manager->do_hook('tabs', $plugin_functions_parameters);

        return $tabs;
    }

    /**
     * Get the URL for the last active tab of a particular tab bar.
     *
     * @param string $section
     *
     * @return null|mixed
     */
    public function getLastTabURL($section)
    {
        //$data = $this->getDatabaseAccessor();

        $tabs = $this->getNavTabs($section);
        $this->prtrace('webdbLastTab', $_SESSION['webdbLastTab']);
        if (isset($_SESSION['webdbLastTab'][$section])) {
            $tab = $tabs[$_SESSION['webdbLastTab'][$section]];
        } else {
            $tab = reset($tabs);
        }
        //$this->prtrace(['section' => $section, 'tabs' => $tabs, 'tab' => $tab]);
        return isset($tab['url']) ? $tab : null;
    }

    /**
     * Do multi-page navigation.  Displays the prev, next and page options.
     *
     * @param int    $page      - the page currently viewed
     * @param int    $pages     - the maximum number of pages
     * @param string $gets      -  the parameters to include in the link to the wanted page
     * @param int    $max_width - the number of pages to make available at any one time (default = 20)
     */
    public function printPages($page, $pages, $gets, $max_width = 20)
    {
        $lang = $this->lang;

        $window = 10;

        if ($page < 0 || $page > $pages) {
            return;
        }

        if ($pages < 0) {
            return;
        }

        if ($max_width <= 0) {
            return;
        }

        unset($gets['page']);
        $url = http_build_query($gets);

        if ($pages > 1) {
            echo "<p style=\"text-align: center\">\n";
            if ($page != 1) {
                echo "<a class=\"pagenav\" href=\"?{$url}&amp;page=1\">{$lang['strfirst']}</a>\n";
                $temp = $page - 1;
                echo "<a class=\"pagenav\" href=\"?{$url}&amp;page={$temp}\">{$lang['strprev']}</a>\n";
            }

            if ($page <= $window) {
                $min_page = 1;
                $max_page = min(2 * $window, $pages);
            } elseif ($page > $window && $pages >= $page + $window) {
                $min_page = ($page - $window) + 1;
                $max_page = $page + $window;
            } else {
                $min_page = ($page - (2 * $window - ($pages - $page))) + 1;
                $max_page = $pages;
            }

            // Make sure min_page is always at least 1
            // and max_page is never greater than $pages
            $min_page = max($min_page, 1);
            $max_page = min($max_page, $pages);

            for ($i = $min_page; $i <= $max_page; ++$i) {
                //if ($i != $page) echo "<a class=\"pagenav\" href=\"?{$url}&amp;page={$i}\">$i</a>\n";
                if ($i != $page) {
                    echo "<a class=\"pagenav\" href=\"display?{$url}&amp;page={$i}\">${i}</a>\n";
                } else {
                    echo "${i}\n";
                }
            }
            if ($page != $pages) {
                $temp = $page + 1;
                echo "<a class=\"pagenav\" href=\"display?{$url}&amp;page={$temp}\">{$lang['strnext']}</a>\n";
                echo "<a class=\"pagenav\" href=\"display?{$url}&amp;page={$pages}\">{$lang['strlast']}</a>\n";
            }
            echo "</p>\n";
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
