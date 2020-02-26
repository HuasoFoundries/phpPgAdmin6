<?php

// declare(strict_types=1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
 */

namespace PHPPgAdmin\Database;

/**
 * @file
 * Class to represent a database connection
 *
 * Id: Connection.php,v 1.15 2008/02/18 21:42:47 ioguix Exp $
 */
class Connection
{
    use \PHPPgAdmin\Traits\HelperTrait;

    public $conn;

    public $platform = 'UNKNOWN';

    protected $container;

    protected $server_info;

    protected $version_dictionary = [
        '13'  => 'Postgres13',
        '12'  => 'Postgres12',
        '11'  => 'Postgres11',
        '10'  => 'Postgres10',
        '9.7' => 'Postgres96',
        '9.6' => 'Postgres96',
        '9.5' => 'Postgres95',
        '9.4' => 'Postgres94',
        '9.3' => 'Postgres93',
        '9.2' => 'Postgres92',
        '9.1' => 'Postgres91',
        '9.0' => 'Postgres90',
        '8.4' => 'Postgres84',
        '8.3' => 'Postgres83',
        '8.2' => 'Postgres82',
        '8.1' => 'Postgres81',
        '8.0' => 'Postgres80',
        '7.5' => 'Postgres80',
        '7.4' => 'Postgres74',
    ];

    // The backend platform.  Set to UNKNOWN by default.
    private $_connection_result;

    /**
     * Creates a new connection.  Will actually make a database connection.
     *
     * @param array           $server_info
     * @param string          $database    database name
     * @param \Slim\Container $container
     * @param int             $fetchMode   Defaults to associative.  Override for different behaviour
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
        if (null === $host || '' === $host) {
            if (null !== $port && '' !== $port) {
                $pghost = ':' . $port;
            } else {
                $pghost = '';
            }
        } else {
            $pghost = "{$host}:{$port}";
        }

        // Add sslmode to $pghost as needed
        if (('disable' === $sslmode) || ('allow' === $sslmode) || ('prefer' === $sslmode) || ('require' === $sslmode)) {
            $pghost .= ':' . $sslmode;
        } elseif ('legacy' === $sslmode) {
            $pghost .= ' requiressl=1';
        }

        /*try {
        $this->_connection_result = $this->conn->connect($pghost, $user, $password, $database);
        $this->prtrace(['_connection_result' => $this->_connection_result, 'conn' => $this->conn]);
        } catch (\PHPPgAdmin\ADOdbException $e) {
        $this->prtrace(['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
         */
        try {
            $this->conn->connect($pghost, $user, $password, $database);
            //$this->prtrace($this->conn);
        } catch (\Exception $e) {
            $this->prtrace($e->getMessage(), $e->getTrace());
        }
    }

    public function getConnectionResult()
    {
        return $this->_connection_result;
    }

    /**
     * Gets the name of the correct database driver to use.  As a side effect,
     * sets the platform.
     *
     * @param string $description A description of the database and version (returns by reference)
     *
     * @return string The driver. e.g. Postgres96
     */
    public function getDriver(&$description)
    {
        $v = \pg_version($this->conn->_connectionID);

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
            if (\preg_match('/ mingw /i', $field)) {
                $this->platform = 'MINGW';
            }

            $params = \explode(' ', $field);

            if (!isset($params[1])) {
                return -3;
            }

            $version = $params[1]; // eg. 8.4.4
        }

        $description = "PostgreSQL {$version}";

        $version_parts = \explode('.', $version);

        if ((int) (10 <= $version_parts[0])) {
            $major_version = $version_parts[0];
        } else {
            $major_version = \implode('.', [$version_parts[0], $version_parts[1]]);
        }

        //$this->prtrace(['pg_version' => pg_version($this->conn->_connectionID), 'version' => $version, 'major_version' => $major_version]);
        // Detect version and choose appropriate database driver
        if (\array_key_exists($major_version, $this->version_dictionary)) {
            return $this->version_dictionary[$major_version];
        }

        /* All <7.4 versions are not supported */
        // if major version is 7 or less and wasn't cought in the
        // switch/case block, we have an unsupported version.
        if (8 > (int) \mb_substr($version, 0, 1)) {
            return null;
        }

        // If unknown version, then default to latest driver
        return 'Postgres';
    }

    /**
     * Get the last error in the connection.
     *
     * @return string Error string
     */
    public function getLastError()
    {
        return \pg_last_error($this->conn->_connectionID);
    }
}
