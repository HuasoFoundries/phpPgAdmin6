<?php
namespace PHPPgAdmin\Database;
/**
 * Class to represent a database connection
 *
 * $Id: Connection.php,v 1.15 2008/02/18 21:42:47 ioguix Exp $
 */

class Connection {

	use \PHPPgAdmin\HelperTrait;

	public  $conn;
	private $connection_result;
	// The backend platform.  Set to UNKNOWN by default.
	public $platform = 'UNKNOWN';

	/**
	 * Creates a new connection.  Will actually make a database connection.
	 * @param $fetchMode Defaults to associative.  Override for different behaviour
	 */
	public function __construct($host, $port, $sslmode, $user, $password, $database, $fetchMode = ADODB_FETCH_ASSOC) {
		$this->conn = ADONewConnection('postgres9');
		//$this->conn->debug = true;
		$this->conn->setFetchMode($fetchMode);

		// Ignore host if null
		if ($host === null || $host == '') {
			if ($port !== null && $port != '') {
				$pghost = ':' . $port;
			} else {
				$pghost = '';
			}
		} else {
			$pghost = "{$host}:{$port}";
		}

		// Add sslmode to $pghost as needed
		if (($sslmode == 'disable') || ($sslmode == 'allow') || ($sslmode == 'prefer') || ($sslmode == 'require')) {
			$pghost .= ':' . $sslmode;
		} elseif ($sslmode == 'legacy') {
			$pghost .= ' requiressl=1';
		}

		/*try {
				$this->connection_result = $this->conn->connect($pghost, $user, $password, $database);
				$this->prtrace(['connection_result' => $this->connection_result, 'conn' => $this->conn]);
			} catch (\PHPPgAdmin\ADODB_Exception $e) {
				$this->prtrace(['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
		*/
		$this->conn->connect($pghost, $user, $password, $database);

	}

	public function getConnectionResult() {
		return $this->connection_result;
	}
	/**
	 * Gets the name of the correct database driver to use.  As a side effect,
	 * sets the platform.
	 * @param (return-by-ref) $description A description of the database and version
	 * @return The class name of the driver eg. Postgres84
	 * @return null if version is < 7.4
	 * @return -3 Database-specific failure
	 */
	public function getDriver(&$description) {

		$v = pg_version($this->conn->_connectionID);

		//\PhpConsole\Handler::getInstance()->debug($v, 'pg_version');

		if (isset($v['server'])) {
			$version = $v['server'];
		}

		// If we didn't manage to get the version without a query, query...
		if (!isset($version)) {
			$adodb = new ADODB_base($this->conn);

			$sql = 'SELECT VERSION() AS version';
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

		//$this->prtrace(['pg_version' => pg_version($this->conn->_connectionID), '$version' => $version]);

		// Detect version and choose appropriate database driver
		switch (substr($version, 0, 3)) {
		case '9.7':return 'Postgres96';
			break;
		case '9.6':return 'Postgres96';
			break;
		case '9.5':return 'Postgres95';
			break;
		case '9.4':return 'Postgres94';
			break;
		case '9.3':return 'Postgres93';
			break;
		case '9.2':return 'Postgres92';
			break;
		case '9.1':return 'Postgres91';
			break;
		case '9.0':return 'Postgres90';
			break;
		case '8.4':return 'Postgres84';
			break;
		case '8.3':return 'Postgres83';
			break;
		case '8.2':return 'Postgres82';
			break;
		case '8.1':return 'Postgres81';
			break;
		case '8.0':
		case '7.5':return 'Postgres80';
			break;
		case '7.4':return 'Postgres74';
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
	 * Get the last error in the connection
	 * @return Error string
	 */
	public function getLastError() {
		return pg_last_error($this->conn->_connectionID);
	}
}
