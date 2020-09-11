<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin;

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

    private $adodb_driver = 'postgres9';

    // or pdo
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
        $host = $server_info['host'];
        $port = $server_info['port'];
        $sslmode = $server_info['sslmode'];
        $user = $server_info['username'];
        $password = $server_info['password'];

        $this->server_info = $server_info;

        $this->container = $container;

        // ADODB_Postgres9 Approach
        //$driver='postgres9';
        $this->conn = \ADONewConnection($this->adodb_driver);
        $this->conn->setFetchMode($fetchMode);

        // PDO Approach

        /*try {
        $this->_connection_result = $this->conn->connect($pghost, $user, $password, $database);
        $this->prtrace(['_connection_result' => $this->_connection_result, 'conn' => $this->conn]);
        } catch (\PHPPgAdmin\ADOdbException $e) {
        $this->prtrace(['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
         */
        try {
            $connectionResult = 'pdo' === $this->adodb_driver ?
            $this->getPDOConnection($host, $port, $sslmode, $database, $user, $password, $fetchMode) :
            $this->getPG9Connection($host, $port, $sslmode, $database, $user, $password, $fetchMode);

            //$this->prtrace($this->conn);
        } catch (\Exception $e) {
            //dump($dsnString, $this->adodb_driver);
            $this->prtrace($e->getMessage(), \array_slice($e->getTrace(), 0, 10));
        }
    }

    public function getConnectionResult()
    {
        return $this->_connection_result;
    }

    public function getVersion()
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
        $this->pgVersion = $serverInfo['version'];
        $description = "PostgreSQL {$this->pgVersion}";

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
    ): \ADODB_postgres9
    {
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
            $pghost = "{$host}:{$port}";
        }

        // Add sslmode to $pghost as needed
        if (('disable' === $sslmode) || ('allow' === $sslmode) || ('prefer' === $sslmode) || ('require' === $sslmode)) {
            $pghost .= ':' . $sslmode;
        } elseif ('legacy' === $sslmode) {
            $pghost .= ' requiressl=1';
        }

        $this->conn->connect($pghost, $user, $password, $database);

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
    ): \ADODB_pdo
    {
        $this->conn = ADONewConnection('pdo');
        $this->conn->setFetchMode($fetchMode);
        $dsnString = \sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=%s;application_name=PHPPgAdmin6', $host, $port, $database, $sslmode);
        $this->conn->connect($dsnString, $user, $password);

        return $this->conn;
    }
}
