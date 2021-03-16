<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin;

use Exception;
use PHPPgAdmin\Database\Postgres;
use PHPPgAdmin\Traits\HelperTrait;
use PHPPgAdmin\Traits\MiscTrait;

/**
 * @file
 * Class to hold various commonly used functions
 *
 * Id: Misc.php,v 1.171 2008/03/17 21:35:48 ioguix Exp $
 */

/**
 * Class to hold various commonly used functions.
 *
 * Release: Misc.php,v 1.171 2008/03/17 21:35:48 ioguix Exp $
 */
class Misc
{
    use HelperTrait;
    use MiscTrait;

    /**
     * @var array
     */
    public $appLangFiles = [];

    /**
     * @var string
     */
    public $appName = '';

    /**
     * @var string
     */
    public $appVersion = '';

    public $form = '';

    /**
     * @var string
     */
    public $href = '';

    /**
     * @var array
     */
    public $lang = [];

    /**
     * @var array
     */
    public $conf;

    /**
     * @var string
     */
    public $phpMinVer;

    /**
     * @var string
     */
    public $postgresqlMinVer;

    /**
     * @var ViewManager
     */
    public $view;

    /**
     * @var ContainerUtils
     */
    protected $container;

    /**
     * @var null|Connection
     */
    private $_connection;

    /**
     * @var bool
     */
    private $_no_db_connection = false;

    /**
     * @var null|Postgres
     */
    private $_data;

    /**
     * @var null|string
     */
    private $_database;

    /**
     * @var null|string
     */
    private $_server_id;

    /**
     * @var null|array
     */
    private $_server_info;

    /**
     * @var string
     */
    private $_error_msg = '';

    /**
     * @param ContainerUtils $container The container
     */
    public function __construct(ContainerUtils $container)
    {
        $this->container = $container;

        $this->lang = $container->get('lang');
        $this->conf = $container->get('conf');

        //$this->view           = $container->get('view');

        $this->appLangFiles = $container->get('appLangFiles');

        $this->appName = $container->get('settings')['appName'];
        $this->appVersion = $container->get('settings')['appVersion'];
        $this->postgresqlMinVer = $container->get('settings')['postgresqlMinVer'];
        $this->phpMinVer = $container->get('settings')['phpMinVer'];

        $base_version = $container->get('settings')['base_version'];

        // Check for config file version mismatch
        if (!isset($this->conf['version']) || $base_version > $this->conf['version']) {
            $container->addError($this->lang['strbadconfig']);
        }

        // Check database support is properly compiled in
        if (!\function_exists('pg_connect')) {
            $container->addError($this->lang['strnotloaded']);
        }

        // Check the version of PHP
        if (\version_compare(\PHP_VERSION, $this->phpMinVer, '<')) {
            $container->addError(\sprintf(
                'Version of PHP not supported. Please upgrade to version %s or later.',
                $this->phpMinVer
            ));
        }
        //$this->dumpAndDie($this->);

        $this->getServerId();
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
        if (null === $key) {
            return $this->conf;
        }

        if (\array_key_exists($key, $this->conf)) {
            return $this->conf[$key];
        }

        return null;
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
     * @return null|string
     */
    public function serverToSha()
    {
        $request_server = $this->container->request->getParam('server');

        if (null === $request_server) {
            return null;
        }
        $srv_array = \explode(':', $request_server);

        if (3 === \count($srv_array)) {
            return \sha1($request_server);
        }

        return $request_server;
    }

    /**
     * @return null|string
     */
    public function getServerId()
    {
        if ($this->_server_id) {
            return $this->_server_id;
        }

        $request_server = $this->serverToSha();

        if (1 === \count($this->conf['servers'])) {
            $info = $this->conf['servers'][0];
            $this->_server_id = \sha1($info['host'] . ':' . $info['port'] . ':' . $info['sslmode']);
        } elseif (null !== $request_server) {
            $this->_server_id = $request_server;
        } elseif (isset($_SESSION['webdbLogin']) && 0 < \count($_SESSION['webdbLogin'])) {
            $this->_server_id = \array_keys($_SESSION['webdbLogin'])[0];
        }

        return $this->_server_id;
    }

    /**
     * Sets the view instance property of this class.
     *
     * @param ViewManager $view view instance
     *
     * @return \PHPPgAdmin\Misc this class instance
     */
    public function setView(ViewManager $view)
    {
        $this->view = $view;

        return $this;
    }

    public function getContainer(): ContainerUtils
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
     *
     * @return null|Postgres|void the database accessor instance
     */
    public function getDatabaseAccessor($database = '', $server_id = null)
    {
        $lang = $this->lang;

        if (null !== $server_id) {
            $this->_server_id = $server_id;
        }

        $server_info = $this->getServerInfo($this->_server_id);

        if ($this->getNoDBConnection() || !isset($server_info['username'])) {
            return null;
        }

        if (null === $this->_data) {
            try {
                $_connection = $this->getConnection($database, $this->_server_id);
            } catch (Exception $e) {
                $this->setServerInfo(null, null, $this->_server_id);
                $this->setNoDBConnection(true);
                $this->setErrorMsg($e->getMessage());

                return null;
            }

            if ($_connection === null) {
                $this->container->addError($lang['strloginfailed']);
                $this->setErrorMsg($lang['strloginfailed']);

                return null;
            }
            $platform = '';
            // Get the name of the database driver we need to use.
            // The description of the server is returned in $platform.
            $_type = $_connection->getDriver($platform);

            if (null === $_type ?? null) {
                $errormsg = \sprintf(
                    $lang['strpostgresqlversionnotsupported'],
                    $this->postgresqlMinVer
                );
                $this->container->addError($errormsg);
                $this->setErrorMsg($errormsg);

                return null;
            }
            /**
             * @var \class-string<Postgres>
             */
            $_type = '\\PHPPgAdmin\\Database\\' . $_type;

            $this->setServerInfo('platform', $platform, $this->_server_id);
            $this->setServerInfo('pgVersion', $_connection->getVersion(), $this->_server_id);

            // Create a database wrapper class for easy manipulation of the
            // connection.

            $this->_data = new $_type($_connection->conn, $this->container, $server_info);
            $this->_data->platform = $_connection->platform;

            //$this->_data->getHelpPages();

            /* we work on UTF-8 only encoding */
            $this->_data->execute("SET client_encoding TO 'UTF-8'");

            if ($this->_data->hasByteaHexDefault()) {
                $this->_data->execute('SET bytea_output TO escape');
            }
        }

        if ($this->getNoDBConnection() ||
            null === $this->getDatabase() ||
            !isset($_REQUEST['schema'])
        ) {
            return $this->_data;
        }

        $status = $this->_data->setSchema($_REQUEST['schema']);

        if (0 !== $status) {
            $this->container->addError($this->lang['strbadschema']);
            $this->setErrorMsg($this->lang['strbadschema']);
        }

        return $this->_data;
    }

    /**
     * Undocumented function.
     *
     * @param string $database
     * @param string $server_id
     *
     * @return null|Connection
     */
    public function getConnection(string $database = '', $server_id = null): ?Connection
    {
        $lang = $this->lang;

        if (null === $this->_connection) {
            if (null !== $server_id) {
                $this->_server_id = $server_id;
            }
            $server_info = $this->getServerInfo($this->_server_id);
            $database_to_use = $this->getDatabase($database);

            // Perform extra security checks if this config option is set
            if ($this->conf['extra_login_security']) {
                // Disallowed logins if extra_login_security is enabled.
                // These must be lowercase.
                $bad_usernames = [
                    'pgsql' => 'pgsql',
                    'postgres' => 'postgres',
                    'root' => 'root',
                    'administrator' => 'administrator',
                ];

                if (isset($server_info['username']) &&
                    \array_key_exists(\mb_strtolower($server_info['username']), $bad_usernames)
                ) {
                    $msg = $lang['strlogindisallowed'];

                    throw new Exception($msg);
                }

                if (!isset($server_info['password']) ||
                    '' === $server_info['password']
                ) {
                    $msg = $lang['strlogindisallowed'];

                    throw new Exception($msg);
                }
            }

            try {
                // Create the connection object and make the connection
                $this->_connection = new Connection(
                    $server_info,
                    $database_to_use,
                    $this->container
                );
            } catch (ADOdbException $e) {
                throw new Exception($lang['strloginfailed'], $e->getCode(), $e);
            }
        }

        return $this->_connection;
    }

    /**
     * Validate and retrieve information on a server. If the parameter isn't supplied then the currently connected
     * server is returned.
     *
     * @param string $server_id A server identifier (host:port)
     *
     * @return null|array An associative array of server properties
     */
    public function getServerInfo($server_id = null)
    {
        if (null !== $server_id) {
            $this->_server_id = $server_id;
        } elseif (null !== $this->_server_info) {
            return $this->_server_info;
        }

        // Check for the server in the logged-in list
        if (isset($_SESSION['webdbLogin'][$this->_server_id])) {
            $this->_server_info = $_SESSION['webdbLogin'][$this->_server_id];

            return $this->_server_info;
        }

        // Otherwise, look for it in the conf file
        foreach ($this->conf['servers'] as $idx => $info) {
            $server_string = $info['host'] . ':' . $info['port'] . ':' . $info['sslmode'];
            $server_sha = \sha1($server_string);

            if ($this->_server_id === $server_string ||
                $this->_server_id === $server_sha
            ) {
                if (isset($info['username'])) {
                    $this->setServerInfo(null, $info, $this->_server_id);
                } elseif (isset($_SESSION['sharedUsername'])) {
                    $info['username'] = $_SESSION['sharedUsername'];
                    $info['password'] = $_SESSION['sharedPassword'];
                    $this->container->get('view')->setReloadBrowser(true);
                    $this->setServerInfo(null, $info, $this->_server_id);
                }
                $this->_server_info = $info;

                return $this->_server_info;
            }
        }

        if (null === $server_id) {
            $this->_server_info = null;

            return $this->_server_info;
        }

        //      //$this->prtrace('Invalid server param');
        $this->_server_info = null;
        // Unable to find a matching server, are we being hacked?
        $this->halt($this->lang['strinvalidserverparam']);

        return $this->_server_info;
    }

    /**
     * Set server information.
     *
     * @param null|string $key       parameter name to set, or null to replace all
     *                               params with the assoc-array in $value
     * @param mixed       $value     the new value, or null to unset the parameter
     * @param null|string $server_id the server identifier, or null for current server
     */
    public function setServerInfo($key, $value, $server_id = null): void
    {
        if (null === $server_id) {
            $server_id = $this->container->request->getParam('server');
        }

        if (null === $key) {
            if (null === $value) {
                unset($_SESSION['webdbLogin'][$server_id]);
            } else {
                $_SESSION['webdbLogin'][$server_id] = $value;
            }
        } elseif (null === $value) {
            unset($_SESSION['webdbLogin'][$server_id][$key]);
        } else {
            $_SESSION['webdbLogin'][$server_id][$key] = $value;
        }
    }

    public function getDatabase(string $database = '')
    {
        if (null === $this->_server_id && !isset($_REQUEST['database'])) {
            return null;
        }

        $server_info = $this->getServerInfo($this->_server_id);

        if (null !== $this->_server_id &&
            isset($server_info['useonlydefaultdb']) &&
            true === $server_info['useonlydefaultdb'] &&
            isset($server_info['defaultdb'])
        ) {
            $this->_database = $server_info['defaultdb'];
        } elseif ('' !== $database) {
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

        if (0 !== $status) {
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

        $server = $this->container->server || isset($_REQUEST['server']) ? $_REQUEST['server'] : null;
        $database = $this->container->database || isset($_REQUEST['database']) ? $_REQUEST['database'] : null;
        $schema = $this->container->schema || isset($_REQUEST['schema']) ? $_REQUEST['schema'] : null;

        if ($server && 'server' !== $exclude_from) {
            $href[] = 'server=' . \urlencode($server);
        }

        if ($database && 'database' !== $exclude_from) {
            $href[] = 'database=' . \urlencode($database);
        }

        if ($schema && 'schema' !== $exclude_from) {
            $href[] = 'schema=' . \urlencode($schema);
        }

        $this->href = \htmlentities(\implode('&', $href));

        return $this->href;
    }

    /**
     * A function to recursively strip slashes.  Used to
     * enforce magic_quotes_gpc being off.
     *
     * @param mixed $var The variable to strip (passed by reference)
     */
    public function stripVar(&$var): void
    {
        if (\is_array($var)) {
            foreach (array_keys($var) as $k) {
                $this->stripVar($var[$k]);

                /* magic_quotes_gpc escape keys as well ...*/
                if (\is_string($k)) {
                    $ek = \stripslashes($k);

                    if ($ek !== $k) {
                        $var[$ek] = $var[$k];
                        unset($var[$k]);
                    }
                }
            }
        } else {
            $var = \stripslashes($var);
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

        if (!\is_string($strIniSize)) {
            return false;
        }

        if (!\preg_match('/^(\d+)([bkm]*)$/i', $strIniSize, $a_IniParts)) {
            return false;
        }

        $nSize = (float) $a_IniParts[1];
        $strUnit = \mb_strtolower($a_IniParts[2]);

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

        if (null !== $this->_server_id && 'root' !== $subject) {
            $v['server'] = $this->_server_id;

            if (null !== $this->_database && 'server' !== $subject) {
                $v['database'] = $this->_database;

                if (isset($_REQUEST['schema']) && 'database' !== $subject) {
                    $v['schema'] = $_REQUEST['schema'];
                }
            }
        }

        return $v;
    }

    /**
     * Function to escape command line parameters.
     *
     * @param string $str The string to escape
     *
     * @return null|string The escaped string
     */
    public function escapeShellArg($str): ?string
    {
        //$data = $this->getDatabaseAccessor();
        $lang = $this->lang;

        if ('WIN' === \mb_strtoupper(\mb_substr(\PHP_OS, 0, 3))) {
            // Due to annoying PHP bugs, shell arguments cannot be escaped
            // (command simply fails), so we cannot allow complex objects
            // to be dumped.
            if (\preg_match('/^[_.[:alnum:]]+$/', $str)) {
                return $str;
            }

            return $this->halt($lang['strcannotdumponwindows']);
        }

        return \escapeshellarg($str);
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

        if ('WIN' === \mb_strtoupper(\mb_substr(\PHP_OS, 0, 3))) {
            $data->fieldClean($str);

            return '"' . $str . '"';
        }

        return \escapeshellcmd($str);
    }

    /**
     * Save the given SQL script in the history
     * of the database and server.
     *
     * @param string $script the SQL script to save
     */
    public function saveScriptHistory($script): void
    {
        [$usec, $sec] = \explode(' ', \microtime());
        $time = ((float) $usec + (float) $sec);

        $server = $this->container->server !== '' ? $this->container->server : $_REQUEST['server'];
        $database = $this->container->database !== '' ? $this->container->database : $_REQUEST['database'];

        $_SESSION['history'][$server][$database][\sprintf(
            '%s',
            $time
        )] = [
            'query' => $script,
            'paginate' => isset($_REQUEST['paginate']) ? 't' : 'f',
            'queryid' => $time,
        ];
    }
}
