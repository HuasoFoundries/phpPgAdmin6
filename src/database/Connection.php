<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * Class to represent a database connection
 *
 * Id: Connection.php,v 1.15 2008/02/18 21:42:47 ioguix Exp $
 *
 * @package PHPPgAdmin
 */
class Connection
{
    use \PHPPgAdmin\HelperTrait;

    public $conn;
    public $platform = 'UNKNOWN';
    // The backend platform.  Set to UNKNOWN by default.
    private $connection_result;

    protected $container;
    protected $server_info;

    /**
     * Creates a new connection.  Will actually make a database connection.
     *
     * @param       $host
     * @param       $port
     * @param       $sslmode
     * @param       $user
     * @param       $password
     * @param       $database
     * @param int   $fetchMode   Defaults to associative.  Override for different behaviour
     * @param mixed $server_info
     * @param mixed $container
     */
    public function __construct($server_info, $database, $container, $fetchMode = ADODB_FETCH_ASSOC)
    {
        $host     = $server_info['host'];
        $port     = $server_info['port'];
        $sslmode  = $server_info['sslmode'];
        $user     = $server_info['username'];
        $password = $server_info['password'];

        $this->server_info = $server_info;

        $this->container = $container;

        $this->conn = ADONewConnection('postgres9');
        //$this->conn->debug = true;
        $this->conn->setFetchMode($fetchMode);

        // Ignore host if null
        if ($host === null || $host == '') {
            if ($port !== null && $port != '') {
                $pghost = ':'.$port;
            } else {
                $pghost = '';
            }
        } else {
            $pghost = "{$host}:{$port}";
        }

        // Add sslmode to $pghost as needed
        if (($sslmode == 'disable') || ($sslmode == 'allow') || ($sslmode == 'prefer') || ($sslmode == 'require')) {
            $pghost .= ':'.$sslmode;
        } elseif ($sslmode == 'legacy') {
            $pghost .= ' requiressl=1';
        }

        /*try {
        $this->connection_result = $this->conn->connect($pghost, $user, $password, $database);
        $this->prtrace(['connection_result' => $this->connection_result, 'conn' => $this->conn]);
        } catch (\PHPPgAdmin\ADOdbException $e) {
        $this->prtrace(['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
         */

        $this->conn->connect($pghost, $user, $password, $database);
    }

    public function getConnectionResult()
    {
        return $this->connection_result;
    }

    /**
     * Gets the name of the correct database driver to use.  As a side effect,
     * sets the platform.
     *
     * @param (return-by-ref) $description A description of the database and version
     *
     * @return string The driver. e.g. Postgres96
     */
    public function getDriver(&$description)
    {
        $v = pg_version($this->conn->_connectionID);

        //\PhpConsole\Handler::getInstance()->debug($v, 'pg_version');

        if (isset($v['server'])) {
            $version = $v['server'];
        }

        // If we didn't manage to get the version without a query, query...
        if (!isset($version)) {
            $adodb = new ADOdbBase($this->conn, $this->container, $this->server_info);

            $sql   = 'SELECT VERSION() AS version';
            $field = $adodb->selectField($sql, 'version');

            // Check the platform, if it's mingw, set it
            if (preg_match('/ mingw /i', $field)) {
                $this->platform = 'MINGW';
            }

            $params = explode(' ', $field);
            if (!isset($params[1])) {
                return -3;
            }

            $version = $params[1]; // eg. 8.4.4
        }

        $description = "PostgreSQL {$version}";

        $version_parts = explode('.', $version);

        if ($version_parts[0] == '10') {
            $major_version = '10';
        } else {
            $major_version = implode('.', [$version_parts[0], $version_parts[1]]);
        }

        //$this->prtrace(['pg_version' => pg_version($this->conn->_connectionID), 'version' => $version, 'major_version' => $major_version]);
        // Detect version and choose appropriate database driver
        switch ($major_version) {
            case '10':
                return 'Postgres10';
                break;
            case '9.7':
            case '9.6':
                return 'Postgres96';
                break;
            case '9.5':
                return 'Postgres95';
                break;
            case '9.4':
                return 'Postgres94';
                break;
            case '9.3':
                return 'Postgres93';
                break;
            case '9.2':
                return 'Postgres92';
                break;
            case '9.1':
                return 'Postgres91';
                break;
            case '9.0':
                return 'Postgres90';
                break;
            case '8.4':
                return 'Postgres84';
                break;
            case '8.3':
                return 'Postgres83';
                break;
            case '8.2':
                return 'Postgres82';
                break;
            case '8.1':
                return 'Postgres81';
                break;
            case '8.0':
            case '7.5':
                return 'Postgres80';
                break;
            case '7.4':
                return 'Postgres74';
                break;
        }

        /* All <7.4 versions are not supported */
        // if major version is 7 or less and wasn't cought in the
        // switch/case block, we have an unsupported version.
        if ((int) substr($version, 0, 1) < 8) {
            return null;
        }

        // If unknown version, then default to latest driver
        return 'Postgres';
    }

    /**
     * Get the last error in the connection.
     *
     * @return Error string
     */
    public function getLastError()
    {
        return pg_last_error($this->conn->_connectionID);
    }
}
