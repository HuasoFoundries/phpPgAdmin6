<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin;

use ADODB_pdo;
use ADODB_postgres9;
use PHPPgAdmin\Traits\HelperTrait;

/**
 * @file
 * Class to represent a database connection
 *
 * Id: Connection.php,v 1.15 2008/02/18 21:42:47 ioguix Exp $
 */
class Connection
{
    use HelperTrait;

    public $conn;

    public $platform = 'UNKNOWN';

    /**
     * @var string
     */
    public $driver;

    protected $container;

    protected $server_info;

    protected $version_dictionary = [
        '13' => 'Postgres13',
        '12' => 'Postgres12',
        '11' => 'Postgres11',
        '10' => 'Postgres10',
        '9.7' => 'Postgres96',
        '9.6' => 'Postgres96',
        '9.5' => 'Postgres95',
        '9.4' => 'Postgres94',
        '9.3' => 'Postgres93',
        '9.2' => 'Postgres92',
        '9.1' => 'Postgres91',
        '9.0' => 'Postgres90',
    ];

    /**
     * @var string
     */
    private $pgVersion;

    /**
     * @var string
     */
    private $_captured_error;

    private $adodb_driver = 'postgres9';

    // The backend platform.  Set to UNKNOWN by default.
    private $_connection_result;

    /**
     * Creates a new connection.  Will actually make a database connection.
     *
     * @param array          $server_info
     * @param string         $database    database name
     * @param ContainerUtils $container
     * @param int            $fetchMode   Defaults to associative.  Override for different behaviour
     */
    public function __construct($server_info, $database, $container, $fetchMode = ADODB_FETCH_ASSOC)
    {
        $host = $server_info['host'];
        $port = $server_info['port'];
        $sslmode = $server_info['sslmode'];
        $user = $server_info['username'];
        $password = $server_info['password'];

        $this->server_info = $server_info;

        $this->container = $container;

        $this->conn = 'pdo' === $this->adodb_driver ?
                $this->getPDOConnection($host, $port, $sslmode, $database, $user, $password, $fetchMode) :
                $this->getPG9Connection($host, $port, $sslmode, $database, $user, $password, $fetchMode);
        $this->conn->setFetchMode($fetchMode);
        //$this->prtrace($this->conn);
    }

    public function getVersion(): string
    {
        return $this->pgVersion;
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
        if (!$this->conn->IsConnected()) {
            return null;
        }
        $serverInfo = $this->conn->ServerInfo();
        dump($serverInfo);
        $this->pgVersion = $serverInfo['version'];
        $description = \sprintf(
            'PostgreSQL %s',
            $this->pgVersion
        );

        $version_parts = \explode('.', $this->pgVersion);

        if ((int) (10 <= $version_parts[0])) {
            $major_version = $version_parts[0];
        } else {
            $major_version = \implode('.', [$version_parts[0], $version_parts[1]]);
        }

        // if major version is less than 9 return null, we don't support it
        if (9 > (float) $major_version) {
            $this->driver = null;

            return null;
        }

        $this->driver = 'Postgres';
        //$this->prtrace(['pg_version' => pg_version($this->conn->_connectionID), 'version' => $version, 'major_version' => $major_version]);
        // Detect version and choose appropriate database driver
        if (\array_key_exists($major_version, $this->version_dictionary)) {
            $this->driver = $this->version_dictionary[$major_version];
        }

        // If unknown version, then default to latest driver
        return $this->driver;
    }

    /**
     * Get the last error in the connection.
     *
     * @return string Error string
     */
    public function getLastError()
    {
        return $this->conn->ErrorMsg();
    }

    private function getPG9Connection(
        string $host,
        int $port,
        string $sslmode,
        ?string $database,
        ?string $user,
        ?string $password,
        int $fetchMode = \ADODB_FETCH_ASSOC
    ): ADODB_postgres9 {
        $this->conn = ADONewConnection('postgres9');
        $this->conn->setFetchMode($fetchMode);
        // Ignore host if null
        if (null === $host || '' === $host) {
            if (null !== $port && '' !== $port) {
                $pghost = ':' . $port;
            } else {
                $pghost = '';
            }
        } else {
            $pghost = \sprintf(
                '%s:%s',
                $host,
                $port
            );
        }

        // Add sslmode to $pghost as needed
        if (('disable' === $sslmode) || ('allow' === $sslmode) || ('prefer' === $sslmode) || ('require' === $sslmode)) {
            $pghost .= ':' . $sslmode;
        } elseif ('legacy' === $sslmode) {
            $pghost .= ' requiressl=1';
        }
        \ob_start();
        $this->_connection_result = $this->conn->connect($pghost, $user, $password, $database);

        $this->_captured_error = \ob_get_clean();

        return $this->conn;
    }

    private function getPDOConnection(
        string $host,
        int $port,
        string $sslmode,
        ?string $database,
        ?string $user,
        ?string $password,
        int $fetchMode = \ADODB_FETCH_ASSOC
    ): ADODB_pdo {
        $this->conn = ADONewConnection('pdo');
        $this->conn->setFetchMode($fetchMode);
        $dsnString = \sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s;application_name=PHPPgAdmin6',
            $host,
            $port,
            $database,
            $sslmode
        );
        $this->conn->connect($dsnString, $user, $password);

        return $this->conn;
    }
}
