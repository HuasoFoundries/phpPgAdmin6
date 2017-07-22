<?php

namespace PHPPgAdmin;

use \PHPPgAdmin\Controller\LoginController;
use \PHPPgAdmin\Decorators\Decorator;

/**
 * Class to hold various commonly used functions
 *
 * $Id: Misc.php,v 1.171 2008/03/17 21:35:48 ioguix Exp $
 */

class Misc {

	use \PHPPgAdmin\HelperTrait;

	private $_connection = null;
	private $_no_db_connection = false;
	private $_reload_drop_database = false;
	private $_reload_browser = false;
	private $_no_bottom_link = false;
	private $app = null;
	private $data = null;
	private $database = null;
	private $server_id = null;
	public $appLangFiles = [];
	public $appName = '';
	public $appVersion = '';
	public $form = '';
	public $href = '';
	public $_name = 'Misc';
	public $lang = [];
	private $server_info = null;
	private $_no_output = false;
	private $container = null;

	/* Constructor */
	public function __construct(\Slim\Container $container) {

		$this->container = $container;

		$this->lang = $container->get('lang');
		$this->conf = $container->get('conf');
		$this->view = $container->get('view');
		$this->plugin_manager = $container->get('plugin_manager');
		$this->appLangFiles = $container->get('appLangFiles');

		$this->appName = $container->get('settings')['appName'];
		$this->appVersion = $container->get('settings')['appVersion'];
		$this->postgresqlMinVer = $container->get('settings')['postgresqlMinVer'];
		$this->phpMinVer = $container->get('settings')['phpMinVer'];

		$base_version = $container->get('settings')['base_version'];
		// Check for config file version mismatch
		if (!isset($this->conf['version']) || $base_version > $this->conf['version']) {
			die($this->lang['strbadconfig']);

		}

		// Check database support is properly compiled in
		if (!function_exists('pg_connect')) {
			die($this->lang['strnotloaded']);

		}

		// Check the version of PHP
		if (version_compare(phpversion(), $this->phpMinVer, '<')) {
			exit(sprintf('Version of PHP not supported. Please upgrade to version %s or later.', $this->phpMinVer));
		}

		if (count($this->conf['servers']) === 1) {
			$info = $this->conf['servers'][0];
			$this->server_id = $info['host'] . ':' . $info['port'] . ':' . $info['sslmode'];
		} else if (isset($_REQUEST['server'])) {
			$this->server_id = $_REQUEST['server'];
		} else if (isset($_SESSION['webdbLogin']) && count($_SESSION['webdbLogin']) > 0) {
			$this->server_id = array_keys($_SESSION['webdbLogin'])[0];
		}
	}

	/**
	 * Default Error Handler. This will be called with the following params
	 *
	 * @param $dbms		the RDBMS you are connecting to
	 * @param $fn		the name of the calling function (in uppercase)
	 * @param $errno		the native error number from the database
	 * @param $errmsg	the native error msg from the database
	 * @param $p1		$fn specific parameter - see below
	 * @param $P2		$fn specific parameter - see below
	 */
	public static function adodb_throw($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection) {

		if (error_reporting() == 0) {
			return;
		}

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		$btarray0 = [
			'class' => $backtrace[1]['class'],
			'type' => $backtrace[1]['type'],
			'function' => $backtrace[1]['function'],
			'spacer' => ' ',
			'line' => $backtrace[0]['line'],
		];

		switch ($fn) {
		case 'EXECUTE':
			$sql = $p1;
			$inputparams = $p2;

			/*$s = "<p><b>{$lang['strsqlerror']}</b><br />" . $misc->printVal($errmsg, 'errormsg') . "</p> <p><b>{$lang['strinstatement']}</b><br />" . $misc->printVal($sql). "</p>	";*/

			$s = '<p><b>strsqlerror</b><br />' . $errmsg . '</p> <p><b>SQL:</b><br />' . $sql . '</p>	';

			echo '<table class="error" cellpadding="5"><tr><td>' . $s . '</td></tr></table><br />' . "\n";

			break;

		case 'PCONNECT':
		case 'CONNECT':
			// do nothing;
			break;
		default:
			$s = "$dbms error: [$errno: $errmsg] in $fn($p1, $p2)\n";
			echo "<table class=\"error\" cellpadding=\"5\"><tr><td>{$s}</td></tr></table><br />\n";
			break;
		}

		$tag = implode('', $btarray0);

		\PC::debug(['errno' => $errno, 'fn' => $fn, 'errmsg' => $errmsg], $tag);

		throw new \PHPPgAdmin\ADODB_Exception($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection);
	}

	public function getContainer() {
		return $this->container;
	}

	/**
	 * sets $_no_bottom_link boolean value
	 * @param boolean $flag [description]
	 */
	public function setNoBottomLink($flag) {
		$this->_no_bottom_link = boolval($flag);
		return $this;
	}

	/**
	 * sets $_no_db_connection boolean value, allows to render scripts that do not need an active session
	 * @param boolean $flag [description]
	 */
	public function setNoDBConnection($flag) {
		$this->_no_db_connection = boolval($flag);
		return $this;
	}

	public function setNoOutput($flag) {
		$this->_no_output = boolval($flag);
		return $this;
	}

	public function getNoDBConnection() {
		return $this->_no_db_connection;
	}

	/**
	 * Creates a database accessor
	 */
	public function getDatabaseAccessor($database = '', $server_id = null) {
		$lang = $this->lang;

		if ($server_id !== null) {
			$this->server_id = $server_id;
		}

		$server_info = $this->getServerInfo($this->server_id);

		if ($this->_no_db_connection || !isset($server_info['username'])) {
			return null;
		}

		if ($this->data === null) {
			$_connection = $this->getConnection($database, $this->server_id);

			//$this->prtrace('_connection', $_connection);
			if (!$_connection) {
				die($lang['strloginfailed']);
			}
			// Get the name of the database driver we need to use.
			// The description of the server is returned in $platform.
			$_type = $_connection->getDriver($platform);

			$this->prtrace(['type' => $_type, 'platform' => $platform, 'pgVersion' => $_connection->conn->pgVersion]);

			if ($_type === null) {
				die(sprintf($lang['strpostgresqlversionnotsupported'], $this->postgresqlMinVer));
			}
			$_type = '\PHPPgAdmin\Database\\' . $_type;

			$this->setServerInfo('platform', $platform, $this->server_id);
			$this->setServerInfo('pgVersion', $_connection->conn->pgVersion, $this->server_id);

			// Create a database wrapper class for easy manipulation of the
			// connection.

			$this->data = new $_type($_connection->conn, $this->conf);
			$this->data->platform = $_connection->platform;
			$this->data->server_info = $server_info;
			$this->data->conf = $this->conf;
			$this->data->lang = $this->lang;

			/* we work on UTF-8 only encoding */
			$this->data->execute("SET client_encoding TO 'UTF-8'");

			if ($this->data->hasByteaHexDefault()) {
				$this->data->execute('SET bytea_output TO escape');
			}

		}

		if ($this->_no_db_connection === false && $this->getDatabase() !== null && isset($_REQUEST['schema'])) {
			$status = $this->data->setSchema($_REQUEST['schema']);

			if ($status != 0) {
				\Kint::dump($status);
				echo $this->lang['strbadschema'];
				exit;
			}
		}

		return $this->data;
	}
	public function getConnection($database = '', $server_id = null) {
		$lang = $this->lang;

		if ($this->_connection === null) {
			if ($server_id !== null) {
				$this->server_id = $server_id;
			}
			$server_info = $this->getServerInfo($this->server_id);
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

				if (isset($server_info['username']) && array_key_exists(strtolower($server_info['username']), $bad_usernames)) {
					unset($_SESSION['webdbLogin'][$this->server_id]);
					$msg = $lang['strlogindisallowed'];
					$this->prtrace('msg', $msg);
					$login_controller = new LoginController($this->container);
					return $login_controller->render();
				}

				if (!isset($server_info['password']) || $server_info['password'] == '') {
					unset($_SESSION['webdbLogin'][$this->server_id]);
					$msg = $lang['strlogindisallowed'];
					$this->prtrace('msg', $msg);
					$login_controller = new LoginController($this->container);
					return $login_controller->render();
				}
			}
			try {

				// Create the connection object and make the connection
				$this->_connection = new \PHPPgAdmin\Database\Connection(
					$server_info['host'],
					$server_info['port'],
					$server_info['sslmode'],
					$server_info['username'],
					$server_info['password'],
					$database_to_use
				);

			} catch (\PHPPgAdmin\ADODB_Exception $e) {
				unset($_SESSION['webdbLogin'][$this->server_id]);
				$msg = $lang['strloginfailed'];
				$login_controller = new LoginController($this->container);
				return $login_controller->render();
			}

		}
		return $this->_connection;
	}

	public function getDatabase($database = '') {

		if ($this->server_id === null && !isset($_REQUEST['database'])) {
			return null;
		}

		$server_info = $this->getServerInfo($this->server_id);

		if ($this->server_id !== null && isset($server_info['useonlydefaultdb']) && $server_info['useonlydefaultdb'] === true) {
			$this->database = $server_info['defaultdb'];
		} else if ($database !== '') {
			$this->database = $database;
		} else if (isset($_REQUEST['database'])) {
			// Connect to the current database
			$this->database = $_REQUEST['database'];
		} else {
			// or if one is not specified then connect to the default database.
			$this->database = $server_info['defaultdb'];
		}

		return $this->database;

	}

	/**
	 * [setReloadBrowser description]
	 * @param boolean $flag sets internal $_reload_browser var which will be passed to the footer methods
	 */
	public function setReloadBrowser($flag) {
		$this->_reload_browser = boolval($flag);
		return $this;
	}
	/**
	 * [setReloadBrowser description]
	 * @param boolean $flag sets internal $_reload_drop_database var which will be passed to the footer methods
	 */
	public function setReloadDropDatabase($flag) {
		$this->_reload_drop_database = boolval($flag);
		return $this;
	}

	public static function _cmp_desc($a, $b) {
		return strcmp($a['desc'], $b['desc']);
	}
	/**
	 * Checks if dumps are properly set up
	 * @param $all (optional) True to check pg_dumpall, false to just check pg_dump
	 * @return True, dumps are set up, false otherwise
	 */
	public function isDumpEnabled($all = false) {
		$info = $this->getServerInfo();

		return !empty($info[$all ? 'pg_dumpall_path' : 'pg_dump_path']);
	}

	public function setThemeConf($theme_conf) {

		$this->conf['theme'] = $theme_conf;
		//\PC::debug(['theme_conf' => $theme_conf, 'this->theme' => $this->conf['theme']], 'setThemeConf');
		$this->view->offsetSet('theme', $this->conf['theme']);
		return $this;
	}

	public function getConf() {
		return $this->conf;
	}
	/**
	 * Sets the href tracking variable
	 */
	public function setHREF() {
		$this->href = $this->getHREF();
		//\PC::debug($this->href, 'Misc::href');
		return $this;

	}

	/**
	 * Get a href query string, excluding objects below the given object type (inclusive)
	 */
	public function getHREF($exclude_from = null) {
		$href = [];
		if (isset($_REQUEST['server']) && $exclude_from != 'server') {
			$href[] = 'server=' . urlencode($_REQUEST['server']);
		}
		if (isset($_REQUEST['database']) && $exclude_from != 'database') {
			$href[] = 'database=' . urlencode($_REQUEST['database']);
		}
		if (isset($_REQUEST['schema']) && $exclude_from != 'schema') {
			$href[] = 'schema=' . urlencode($_REQUEST['schema']);
		}

		return htmlentities(implode('&', $href));
	}

	public function getSubjectParams($subject) {
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
				'server' => $_REQUEST['server'],
				'subject' => 'server',
			]];
			break;
		case 'role':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'role',
				'action' => 'properties',
				'rolename' => $_REQUEST['rolename'],
			]];
			break;
		case 'database':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'database',
				'database' => $_REQUEST['database'],
			]];
			break;
		case 'schema':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'schema',
				'database' => $_REQUEST['database'],
				'schema' => $_REQUEST['schema'],
			]];
			break;
		case 'table':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'table',
				'database' => $_REQUEST['database'],
				'schema' => $_REQUEST['schema'],
				'table' => $_REQUEST['table'],
			]];
			break;
		case 'selectrows':
			$vars = [
				'url' => 'tables.php',
				'params' => [
					'server' => $_REQUEST['server'],
					'subject' => 'table',
					'database' => $_REQUEST['database'],
					'schema' => $_REQUEST['schema'],
					'table' => $_REQUEST['table'],
					'action' => 'confselectrows',
				]];
			break;
		case 'view':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'view',
				'database' => $_REQUEST['database'],
				'schema' => $_REQUEST['schema'],
				'view' => $_REQUEST['view'],
			]];
			break;
		case 'matview':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'matview',
				'database' => $_REQUEST['database'],
				'schema' => $_REQUEST['schema'],
				'matview' => $_REQUEST['matview'],
			]];
			break;

		case 'fulltext':
		case 'ftscfg':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'fulltext',
				'database' => $_REQUEST['database'],
				'schema' => $_REQUEST['schema'],
				'action' => 'viewconfig',
				'ftscfg' => $_REQUEST['ftscfg'],
			]];
			break;
		case 'function':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'function',
				'database' => $_REQUEST['database'],
				'schema' => $_REQUEST['schema'],
				'function' => $_REQUEST['function'],
				'function_oid' => $_REQUEST['function_oid'],
			]];
			break;
		case 'aggregate':
			$vars = ['params' => [
				'server' => $_REQUEST['server'],
				'subject' => 'aggregate',
				'action' => 'properties',
				'database' => $_REQUEST['database'],
				'schema' => $_REQUEST['schema'],
				'aggrname' => $_REQUEST['aggrname'],
				'aggrtype' => $_REQUEST['aggrtype'],
			]];
			break;
		case 'column':
			if (isset($_REQUEST['table'])) {
				$vars = ['params' => [
					'server' => $_REQUEST['server'],
					'subject' => 'column',
					'database' => $_REQUEST['database'],
					'schema' => $_REQUEST['schema'],
					'table' => $_REQUEST['table'],
					'column' => $_REQUEST['column'],
				]];
			} else {
				$vars = ['params' => [
					'server' => $_REQUEST['server'],
					'subject' => 'column',
					'database' => $_REQUEST['database'],
					'schema' => $_REQUEST['schema'],
					'view' => $_REQUEST['view'],
					'column' => $_REQUEST['column'],
				]];
			}

			break;
		case 'plugin':
			$vars = [
				'url' => 'plugin.php',
				'params' => [
					'server' => $_REQUEST['server'],
					'subject' => 'plugin',
					'plugin' => $_REQUEST['plugin'],
				]];

			if (!is_null($plugin_manager->getPlugin($_REQUEST['plugin']))) {
				$vars['params'] = array_merge($vars['params'], $plugin_manager->getPlugin($_REQUEST['plugin'])->get_subject_params());
			}

			break;
		default:
			return false;
		}

		if (!isset($vars['url'])) {
			$vars['url'] = '/redirect';
		}
		//\PC::debug($vars, 'getSubjectParams');
		if ($vars['url'] == '/redirect' && isset($vars['params']['subject'])) {
			$vars['url'] = '/redirect/' . $vars['params']['subject'];
			unset($vars['params']['subject']);
		}

		return $vars;
	}

	public function getHREFSubject($subject) {
		$vars = $this->getSubjectParams($subject);
		return "{$vars['url']}?" . http_build_query($vars['params'], '', '&amp;');
	}

	/**
	 * Sets the form tracking variable
	 */
	public function setForm() {
		$form = [];
		if (isset($_REQUEST['server'])) {
			$form[] = '<input type="hidden" name="server" value="' . htmlspecialchars($_REQUEST['server']) . '" />';
		}
		if (isset($_REQUEST['database'])) {
			$form[] = '<input type="hidden" name="database" value="' . htmlspecialchars($_REQUEST['database']) . '" />';
		}

		if (isset($_REQUEST['schema'])) {
			$form[] = '<input type="hidden" name="schema" value="' . htmlspecialchars($_REQUEST['schema']) . '" />';
		}
		$this->form = implode("\n", $form);
		return $this->form;

		//\PC::debug($this->form, 'Misc::form');
	}

	/**
	 * Render a value into HTML using formatting rules specified
	 * by a type name and parameters.
	 *
	 * @param $str The string to change
	 *
	 * @param $type Field type (optional), this may be an internal PostgreSQL type, or:
	 *			yesno    - same as bool, but renders as 'Yes' or 'No'.
	 *			pre      - render in a <pre> block.
	 *			nbsp     - replace all spaces with &nbsp;'s
	 *			verbatim - render exactly as supplied, no escaping what-so-ever.
	 *			callback - render using a callback function supplied in the 'function' param.
	 *
	 * @param $params Type parameters (optional), known parameters:
	 *			null     - string to display if $str is null, or set to TRUE to use a default 'NULL' string,
	 *			           otherwise nothing is rendered.
	 *			clip     - if true, clip the value to a fixed length, and append an ellipsis...
	 *			cliplen  - the maximum length when clip is enabled (defaults to $conf['max_chars'])
	 *			ellipsis - the string to append to a clipped value (defaults to $lang['strellipsis'])
	 *			tag      - an HTML element name to surround the value.
	 *			class    - a class attribute to apply to any surrounding HTML element.
	 *			align    - an align attribute ('left','right','center' etc.)
	 *			true     - (type='bool') the representation of true.
	 *			false    - (type='bool') the representation of false.
	 *			function - (type='callback') a function name, accepts args ($str, $params) and returns a rendering.
	 *			lineno   - prefix each line with a line number.
	 *			map      - an associative array.
	 *
	 * @return The HTML rendered value
	 */
	public function printVal($str, $type = null, $params = []) {
		$lang = $this->lang;
		$data = $this->data;

		// Shortcircuit for a NULL value
		if (is_null($str)) {
			return isset($params['null'])
			? ($params['null'] === true ? '<i>NULL</i>' : $params['null'])
			: '';
		}

		if (isset($params['map']) && isset($params['map'][$str])) {
			$str = $params['map'][$str];
		}

		// Clip the value if the 'clip' parameter is true.
		if (isset($params['clip']) && $params['clip'] === true) {
			$maxlen = isset($params['cliplen']) && is_integer($params['cliplen']) ? $params['cliplen'] : $this->conf['max_chars'];
			$ellipsis = isset($params['ellipsis']) ? $params['ellipsis'] : $lang['strellipsis'];
			if (strlen($str) > $maxlen) {
				$str = substr($str, 0, $maxlen - 1) . $ellipsis;
			}
		}

		$out = '';

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
			$out = nl2br(htmlspecialchars($str));
			break;
		case 'yesno':
			if (!isset($params['true'])) {
				$params['true'] = $lang['stryes'];
			}

			if (!isset($params['false'])) {
				$params['false'] = $lang['strno'];
			}

		// No break - fall through to boolean case.
		case 'bool':
		case 'boolean':
			if (is_bool($str)) {
				$str = $str ? 't' : 'f';
			}

			switch ($str) {
			case 't':
				$out = (isset($params['true']) ? $params['true'] : $lang['strtrue']);
				$align = 'center';
				break;
			case 'f':
				$out = (isset($params['false']) ? $params['false'] : $lang['strfalse']);
				$align = 'center';
				break;
			default:
				$out = htmlspecialchars($str);
			}
			break;
		case 'bytea':
			$tag = 'div';
			$class = 'pre';
			$out = $data->escapeBytea($str);
			break;
		case 'errormsg':
			$tag = 'pre';
			$class = 'error';
			$out = htmlspecialchars($str);
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
			$out = nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($str)));
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
				$mult = 1;
				if ($str < $limit * $mult) {
					$out = $str . ' ' . $lang['strbytes'];
				} else {
					$mult *= 1024;
					if ($str < $limit * $mult) {
						$out = floor(($str + $mult / 2) / $mult) . ' ' . $lang['strkb'];
					} else {
						$mult *= 1024;
						if ($str < $limit * $mult) {
							$out = floor(($str + $mult / 2) / $mult) . ' ' . $lang['strmb'];
						} else {
							$mult *= 1024;
							if ($str < $limit * $mult) {
								$out = floor(($str + $mult / 2) / $mult) . ' ' . $lang['strgb'];
							} else {
								$mult *= 1024;
								if ($str < $limit * $mult) {
									$out = floor(($str + $mult / 2) / $mult) . ' ' . $lang['strtb'];
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
				$tag = 'pre';
				$class = 'data';
				$out = htmlspecialchars($str);
			} else {
				$out = nl2br(htmlspecialchars($str));
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
			$out = "<{$tag}{$alignattr}{$classattr}>{$out}</{$tag}>";
		}

		// Add line numbers if 'lineno' param is true
		if (isset($params['lineno']) && $params['lineno'] === true) {
			$lines = explode("\n", $str);
			$num = count($lines);
			if ($num > 0) {
				$temp = "<table>\n<tr><td class=\"{$class}\" style=\"vertical-align: top; padding-right: 10px;\"><pre class=\"{$class}\">";
				for ($i = 1; $i <= $num; $i++) {
					$temp .= $i . "\n";
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
	 * @param &var The variable to strip
	 */
	public function stripVar(&$var) {
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
	 * Print out the page heading and help link
	 * @param $title Title, already escaped
	 * @param $help (optional) The identifier for the help link

	function printTitle($title, $help = null, $do_print = true) {
	$data = $this->data;
	$lang = $this->lang;

	$title_html = "<h2>";
	$title_html .= $this->printHelp($title, $help, false);
	$title_html .= "</h2>\n";

	if ($do_print) {
	echo $title_html;
	} else {
	return $title_html;
	}
	} */

	/**
	 * Print out a message
	 * @param $msg The message to print
	 */
	public function printMsg($msg, $do_print = true) {
		$html = '';
		if ($msg != '') {
			$html .= "<p class=\"message\">{$msg}</p>\n";
		}
		if ($do_print) {
			echo $html;
		} else {
			return $html;
		}

	}

	/**
	 * Prints the page header.  If member variable $this->_no_output is
	 * set then no header is drawn.
	 * @param $title The title of the page
	 * @param $script script tag
	 * @param $do_print boolean if false, the function will return the header content

	function printHeader($title = '', $script = null, $do_print = true, $template = 'header.twig') {

	if (function_exists('newrelic_disable_autorum')) {
	newrelic_disable_autorum();
	}
	$appName = $this->appName;
	$lang = $this->lang;
	$plugin_manager = $this->plugin_manager;

	$viewVars = $this->lang;
	$viewVars['dir'] = (strcasecmp($lang['applangdir'], 'ltr') != 0) ? ' dir="' . htmlspecialchars($lang['applangdir']) . '"' : '';

	$viewVars['appName'] = htmlspecialchars($this->appName) . ($title != '') ? htmlspecialchars(" - {$title}") : '';

	$header_html = $this->view->fetch($template, $viewVars);

	if ($script) {
	$header_html .= "{$script}\n";
	}

	$plugins_head = [];
	$_params = ['heads' => &$plugins_head];

	$plugin_manager->do_hook('head', $_params);

	foreach ($plugins_head as $tag) {
	$header_html .= $tag;
	}

	$header_html .= "</head>\n";

	if (!$this->_no_output && $do_print) {

	header("Content-Type: text/html; charset=utf-8");
	echo $header_html;

	} else {
	return $header_html;
	}
	}*/

	/**
	 * Retrieve the tab info for a specific tab bar.
	 * @param $section The name of the tab bar.
	 */
	public function getNavTabs($section) {

		$data = $this->data;
		$lang = $this->lang;
		$plugin_manager = $this->plugin_manager;

		$hide_advanced = ($this->conf['show_advanced'] === false);
		$tabs = [];

		switch ($section) {
		case 'root':
			$tabs = [
				'intro' => [
                    'title' => $lang['strintroduction'],
                    'url' => 'intro.php',
                    'icon' => 'Introduction',
				],
				'servers' => [
                    'title' => $lang['strservers'],
                    'url' => 'servers.php',
                    'icon' => 'Servers',
				],
			];
			break;

		case 'server':
			$hide_users = true;
			if ($data) {
				$hide_users = !$data->isSuperUser();
			}

			$tabs = [
				'databases' => [
					'title' => $lang['strdatabases'],
					'url' => 'all_db.php',
					'urlvars' => ['subject' => 'server'],
					'help' => 'pg.database',
					'icon' => 'Databases',
				],
			];
			if ($data && $data->hasRoles()) {
				$tabs = array_merge($tabs, [
					'roles' => [
						'title' => $lang['strroles'],
						'url' => 'roles.php',
						'urlvars' => ['subject' => 'server'],
						'hide' => $hide_users,
						'help' => 'pg.role',
						'icon' => 'Roles',
					],
				]);
			} else {
				$tabs = array_merge($tabs, [
					'users' => [
						'title' => $lang['strusers'],
						'url' => 'users.php',
						'urlvars' => ['subject' => 'server'],
						'hide' => $hide_users,
						'help' => 'pg.user',
						'icon' => 'Users',
					],
					'groups' => [
						'title' => $lang['strgroups'],
						'url' => 'groups.php',
						'urlvars' => ['subject' => 'server'],
						'hide' => $hide_users,
						'help' => 'pg.group',
						'icon' => 'UserGroups',
					],
				]);
			}

			$tabs = array_merge($tabs, [
				'account' => [
					'title' => $lang['straccount'],
					'url' => ($data && $data->hasRoles()) ? 'roles.php' : 'users.php',
					'urlvars' => ['subject' => 'server', 'action' => 'account'],
					'hide' => !$hide_users,
					'help' => 'pg.role',
					'icon' => 'User',
				],
				'tablespaces' => [
                    'title' => $lang['strtablespaces'],
                    'url' => 'tablespaces.php',
                    'urlvars' => ['subject' => 'server'],
                    'hide' => !$data || !$data->hasTablespaces(),
                    'help' => 'pg.tablespace',
                    'icon' => 'Tablespaces',
				],
				'export' => [
                    'title' => $lang['strexport'],
                    'url' => 'all_db.php',
                    'urlvars' => ['subject' => 'server', 'action' => 'export'],
                    'hide' => !$this->isDumpEnabled(),
                    'icon' => 'Export',
				],
			]);
			break;
		case 'database':
			$tabs = [
				'schemas' => [
					'title' => $lang['strschemas'],
					'url' => 'schemas.php',
					'urlvars' => ['subject' => 'database'],
					'help' => 'pg.schema',
					'icon' => 'Schemas',
				],
				'sql' => [
					'title' => $lang['strsql'],
					'url' => 'database.php',
					'urlvars' => ['subject' => 'database', 'action' => 'sql', 'new' => 1],
					'help' => 'pg.sql',
					'tree' => false,
					'icon' => 'SqlEditor',
				],
				'find' => [
					'title' => $lang['strfind'],
					'url' => 'database.php',
					'urlvars' => ['subject' => 'database', 'action' => 'find'],
					'tree' => false,
					'icon' => 'Search',
				],
				'variables' => [
					'title' => $lang['strvariables'],
					'url' => 'database.php',
					'urlvars' => ['subject' => 'database', 'action' => 'variables'],
					'help' => 'pg.variable',
					'tree' => false,
					'icon' => 'Variables',
				],
				'processes' => [
					'title' => $lang['strprocesses'],
					'url' => 'database.php',
					'urlvars' => ['subject' => 'database', 'action' => 'processes'],
					'help' => 'pg.process',
					'tree' => false,
					'icon' => 'Processes',
				],
				'locks' => [
					'title' => $lang['strlocks'],
					'url' => 'database.php',
					'urlvars' => ['subject' => 'database', 'action' => 'locks'],
					'help' => 'pg.locks',
					'tree' => false,
					'icon' => 'Key',
				],
				'admin' => [
					'title' => $lang['stradmin'],
					'url' => 'database.php',
					'urlvars' => ['subject' => 'database', 'action' => 'admin'],
					'tree' => false,
					'icon' => 'Admin',
				],
				'privileges' => [
                    'title' => $lang['strprivileges'],
                    'url' => 'privileges.php',
                    'urlvars' => ['subject' => 'database'],
                    'hide' => !isset($data->privlist['database']),
                    'help' => 'pg.privilege',
                    'tree' => false,
                    'icon' => 'Privileges',
				],
				'languages' => [
					'title' => $lang['strlanguages'],
					'url' => 'languages.php',
					'urlvars' => ['subject' => 'database'],
					'hide' => $hide_advanced,
					'help' => 'pg.language',
					'icon' => 'Languages',
				],
				'casts' => [
                    'title' => $lang['strcasts'],
                    'url' => 'casts.php',
                    'urlvars' => ['subject' => 'database'],
                    'hide' => $hide_advanced,
                    'help' => 'pg.cast',
                    'icon' => 'Casts',
				],
				'export' => [
                    'title' => $lang['strexport'],
                    'url' => 'database.php',
                    'urlvars' => ['subject' => 'database', 'action' => 'export'],
                    'hide' => !$this->isDumpEnabled(),
                    'tree' => false,
                    'icon' => 'Export',
				],
			];
			break;

		case 'schema':
			$tabs = [
				'tables' => [
					'title' => $lang['strtables'],
					'url' => 'tables.php',
					'urlvars' => ['subject' => 'schema'],
					'help' => 'pg.table',
					'icon' => 'Tables',
				],
				'views' => [
					'title' => $lang['strviews'],
					'url' => 'views.php',
					'urlvars' => ['subject' => 'schema'],
					'help' => 'pg.view',
					'icon' => 'Views',
				],
				'matviews' => [
					'title' => 'M ' . $lang['strviews'],
					'url' => 'materialized_views.php',
					'urlvars' => ['subject' => 'schema'],
					'help' => 'pg.matview',
					'icon' => 'MViews',
				],
				'sequences' => [
					'title' => $lang['strsequences'],
					'url' => 'sequences.php',
					'urlvars' => ['subject' => 'schema'],
					'help' => 'pg.sequence',
					'icon' => 'Sequences',
				],
				'functions' => [
					'title' => $lang['strfunctions'],
					'url' => 'functions.php',
					'urlvars' => ['subject' => 'schema'],
					'help' => 'pg.function',
					'icon' => 'Functions',
				],
				'fulltext' => [
					'title' => $lang['strfulltext'],
					'url' => 'fulltext.php',
					'urlvars' => ['subject' => 'schema'],
					'help' => 'pg.fts',
					'tree' => true,
					'icon' => 'Fts',
				],
				'domains' => [
					'title' => $lang['strdomains'],
					'url' => 'domains.php',
					'urlvars' => ['subject' => 'schema'],
					'help' => 'pg.domain',
					'icon' => 'Domains',
				],
				'aggregates' => [
					'title' => $lang['straggregates'],
					'url' => 'aggregates.php',
					'urlvars' => ['subject' => 'schema'],
					'hide' => $hide_advanced,
					'help' => 'pg.aggregate',
					'icon' => 'Aggregates',
				],
				'types' => [
					'title' => $lang['strtypes'],
					'url' => 'types.php',
					'urlvars' => ['subject' => 'schema'],
					'hide' => $hide_advanced,
					'help' => 'pg.type',
					'icon' => 'Types',
				],
				'operators' => [
					'title' => $lang['stroperators'],
					'url' => 'operators.php',
					'urlvars' => ['subject' => 'schema'],
					'hide' => $hide_advanced,
					'help' => 'pg.operator',
					'icon' => 'Operators',
				],
				'opclasses' => [
					'title' => $lang['stropclasses'],
					'url' => 'opclasses.php',
					'urlvars' => ['subject' => 'schema'],
					'hide' => $hide_advanced,
					'help' => 'pg.opclass',
					'icon' => 'OperatorClasses',
				],
				'conversions' => [
					'title' => $lang['strconversions'],
					'url' => 'conversions.php',
					'urlvars' => ['subject' => 'schema'],
					'hide' => $hide_advanced,
					'help' => 'pg.conversion',
					'icon' => 'Conversions',
				],
				'privileges' => [
					'title' => $lang['strprivileges'],
					'url' => 'privileges.php',
					'urlvars' => ['subject' => 'schema'],
					'help' => 'pg.privilege',
					'tree' => false,
					'icon' => 'Privileges',
				],
				'export' => [
                    'title' => $lang['strexport'],
                    'url' => 'schemas.php',
                    'urlvars' => ['subject' => 'schema', 'action' => 'export'],
                    'hide' => !$this->isDumpEnabled(),
                    'tree' => false,
                    'icon' => 'Export',
				],
			];
			if (!$data->hasFTS()) {
				unset($tabs['fulltext']);
			}

			break;

		case 'table':
			$tabs = [
				'columns' => [
					'title' => $lang['strcolumns'],
					'url' => 'tblproperties.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
					'icon' => 'Columns',
					'branch' => true,
				],
				'browse' => [
					'title' => $lang['strbrowse'],
					'icon' => 'Columns',
					'url' => 'display.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
					'return' => 'table',
					'branch' => true,
				],
				'select' => [
					'title' => $lang['strselect'],
					'icon' => 'Search',
					'url' => 'tables.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'confselectrows'],
					'help' => 'pg.sql.select',
				],
				'insert' => [
					'title' => $lang['strinsert'],
					'url' => 'tables.php',
					'urlvars' => [
						'action' => 'confinsertrow',
						'table' => Decorator::field('table'),
					],
					'help' => 'pg.sql.insert',
					'icon' => 'Operator',
				],
				'indexes' => [
					'title' => $lang['strindexes'],
					'url' => 'indexes.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
					'help' => 'pg.index',
					'icon' => 'Indexes',
					'branch' => true,
				],
				'constraints' => [
					'title' => $lang['strconstraints'],
					'url' => 'constraints.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
					'help' => 'pg.constraint',
					'icon' => 'Constraints',
					'branch' => true,
				],
				'triggers' => [
					'title' => $lang['strtriggers'],
					'url' => 'triggers.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
					'help' => 'pg.trigger',
					'icon' => 'Triggers',
					'branch' => true,
				],
				'rules' => [
					'title' => $lang['strrules'],
					'url' => 'rules.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
					'help' => 'pg.rule',
					'icon' => 'Rules',
					'branch' => true,
				],
				'admin' => [
					'title' => $lang['stradmin'],
					'url' => 'tables.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'admin'],
					'icon' => 'Admin',
				],
				'info' => [
					'title' => $lang['strinfo'],
					'url' => 'info.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
					'icon' => 'Statistics',
				],
				'privileges' => [
					'title' => $lang['strprivileges'],
					'url' => 'privileges.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
					'help' => 'pg.privilege',
					'icon' => 'Privileges',
				],
				'import' => [
					'title' => $lang['strimport'],
					'url' => 'tblproperties.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'import'],
					'icon' => 'Import',
					'hide' => false,
				],
				'export' => [
					'title' => $lang['strexport'],
					'url' => 'tblproperties.php',
					'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'export'],
					'icon' => 'Export',
					'hide' => false,
				],
			];
			break;

		case 'view':
			$tabs = [
				'columns' => [
					'title' => $lang['strcolumns'],
					'url' => 'viewproperties.php',
					'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
					'icon' => 'Columns',
					'branch' => true,
				],
				'browse' => [
					'title' => $lang['strbrowse'],
					'icon' => 'Columns',
					'url' => 'display.php',
					'urlvars' => [
						'action' => 'confselectrows',
						'return' => 'schema',
						'subject' => 'view',
						'view' => Decorator::field('view'),
					],
					'branch' => true,
				],
				'select' => [
					'title' => $lang['strselect'],
					'icon' => 'Search',
					'url' => 'views.php',
					'urlvars' => ['action' => 'confselectrows', 'view' => Decorator::field('view')],
					'help' => 'pg.sql.select',
				],
				'definition' => [
					'title' => $lang['strdefinition'],
					'url' => 'viewproperties.php',
					'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view'), 'action' => 'definition'],
					'icon' => 'Definition',
				],
				'rules' => [
					'title' => $lang['strrules'],
					'url' => 'rules.php',
					'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
					'help' => 'pg.rule',
					'icon' => 'Rules',
					'branch' => true,
				],
				'privileges' => [
					'title' => $lang['strprivileges'],
					'url' => 'privileges.php',
					'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
					'help' => 'pg.privilege',
					'icon' => 'Privileges',
				],
				'export' => [
					'title' => $lang['strexport'],
					'url' => 'viewproperties.php',
					'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view'), 'action' => 'export'],
					'icon' => 'Export',
					'hide' => false,
				],
			];
			break;

		case 'matview':
			$tabs = [
				'columns' => [
					'title' => $lang['strcolumns'],
					'url' => 'materializedviewproperties.php',
					'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
					'icon' => 'Columns',
					'branch' => true,
				],
				'browse' => [
					'title' => $lang['strbrowse'],
					'icon' => 'Columns',
					'url' => 'display.php',
					'urlvars' => [
						'action' => 'confselectrows',
						'return' => 'schema',
						'subject' => 'matview',
						'matview' => Decorator::field('matview'),
					],
					'branch' => true,
				],
				'select' => [
					'title' => $lang['strselect'],
					'icon' => 'Search',
					'url' => 'materialized_views.php',
					'urlvars' => ['action' => 'confselectrows', 'matview' => Decorator::field('matview')],
					'help' => 'pg.sql.select',
				],
				'definition' => [
					'title' => $lang['strdefinition'],
					'url' => 'materializedviewproperties.php',
					'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview'), 'action' => 'definition'],
					'icon' => 'Definition',
				],
				'indexes' => [
					'title' => $lang['strindexes'],
					'url' => 'indexes.php',
					'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
					'help' => 'pg.index',
					'icon' => 'Indexes',
					'branch' => true,
				],
				/*'constraints' => [
					'title' => $lang['strconstraints'],
					'url' => 'constraints.php',
					'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
					'help' => 'pg.constraint',
					'icon' => 'Constraints',
					'branch' => true,
				],*/

				'rules' => [
					'title' => $lang['strrules'],
					'url' => 'rules.php',
					'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
					'help' => 'pg.rule',
					'icon' => 'Rules',
					'branch' => true,
				],
				'privileges' => [
					'title' => $lang['strprivileges'],
					'url' => 'privileges.php',
					'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
					'help' => 'pg.privilege',
					'icon' => 'Privileges',
				],
				'export' => [
					'title' => $lang['strexport'],
					'url' => 'materializedviewproperties.php',
					'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview'), 'action' => 'export'],
					'icon' => 'Export',
					'hide' => false,
				],
			];
			break;

		case 'function':
			$tabs = [
				'definition' => [
					'title' => $lang['strdefinition'],
					'url' => 'functions.php',
					'urlvars' => [
						'subject' => 'function',
						'function' => Decorator::field('function'),
						'function_oid' => Decorator::field('function_oid'),
						'action' => 'properties',
					],
					'icon' => 'Definition',
				],
				'privileges' => [
					'title' => $lang['strprivileges'],
					'url' => 'privileges.php',
					'urlvars' => [
						'subject' => 'function',
						'function' => Decorator::field('function'),
						'function_oid' => Decorator::field('function_oid'),
					],
					'icon' => 'Privileges',
				],
			];
			break;

		case 'aggregate':
			$tabs = [
				'definition' => [
					'title' => $lang['strdefinition'],
					'url' => 'aggregates.php',
					'urlvars' => [
						'subject' => 'aggregate',
						'aggrname' => Decorator::field('aggrname'),
						'aggrtype' => Decorator::field('aggrtype'),
						'action' => 'properties',
					],
					'icon' => 'Definition',
				],
			];
			break;

		case 'role':
			$tabs = [
				'definition' => [
					'title' => $lang['strdefinition'],
					'url' => 'roles.php',
					'urlvars' => [
						'subject' => 'role',
						'rolename' => Decorator::field('rolename'),
						'action' => 'properties',
					],
					'icon' => 'Definition',
				],
			];
			break;

		case 'popup':
			$tabs = [
				'sql' => [
					'title' => $lang['strsql'],
					'url' => '/src/views/sqledit.php',
					'urlvars' => ['action' => 'sql', 'subject' => 'schema'],
					'help' => 'pg.sql',
					'icon' => 'SqlEditor',
				],
				'find' => [
					'title' => $lang['strfind'],
					'url' => '/src/views/sqledit.php',
					'urlvars' => ['action' => 'find', 'subject' => 'schema'],
					'icon' => 'Search',
				],
			];
			break;

		case 'column':
			$tabs = [
				'properties' => [
					'title' => $lang['strcolprop'],
					'url' => 'colproperties.php',
					'urlvars' => [
						'subject' => 'column',
						'table' => Decorator::field('table'),
						'column' => Decorator::field('column'),
					],
					'icon' => 'Column',
				],
				'privileges' => [
					'title' => $lang['strprivileges'],
					'url' => 'privileges.php',
					'urlvars' => [
						'subject' => 'column',
						'table' => Decorator::field('table'),
						'column' => Decorator::field('column'),
					],
					'help' => 'pg.privilege',
					'icon' => 'Privileges',
				],
			];
			break;

		case 'fulltext':
			$tabs = [
				'ftsconfigs' => [
					'title' => $lang['strftstabconfigs'],
					'url' => 'fulltext.php',
					'urlvars' => ['subject' => 'schema'],
					'hide' => !$data->hasFTS(),
					'help' => 'pg.ftscfg',
					'tree' => true,
					'icon' => 'FtsCfg',
				],
				'ftsdicts' => [
					'title' => $lang['strftstabdicts'],
					'url' => 'fulltext.php',
					'urlvars' => ['subject' => 'schema', 'action' => 'viewdicts'],
					'hide' => !$data->hasFTS(),
					'help' => 'pg.ftsdict',
					'tree' => true,
					'icon' => 'FtsDict',
				],
				'ftsparsers' => [
					'title' => $lang['strftstabparsers'],
					'url' => 'fulltext.php',
					'urlvars' => ['subject' => 'schema', 'action' => 'viewparsers'],
					'hide' => !$data->hasFTS(),
					'help' => 'pg.ftsparser',
					'tree' => true,
					'icon' => 'FtsParser',
				],
			];
			break;
		}

		// Tabs hook's place
		$plugin_functions_parameters = [
			'tabs' => &$tabs,
			'section' => $section,
		];
		$plugin_manager->do_hook('tabs', $plugin_functions_parameters);

		return $tabs;
	}

	/**
	 * Get the URL for the last active tab of a particular tab bar.
	 */
	public function getLastTabURL($section) {
		$data = $this->getDatabaseAccessor();

		$tabs = $this->getNavTabs($section);

		if (isset($_SESSION['webdbLastTab'][$section]) && isset($tabs[$_SESSION['webdbLastTab'][$section]])) {
			$tab = $tabs[$_SESSION['webdbLastTab'][$section]];
		} else {
			$tab = reset($tabs);
		}
		\PC::debug(['section' => $section, 'tabs' => $tabs, 'tab' => $tab], 'getLastTabURL');
		return isset($tab['url']) ? $tab : null;
	}

	/**
	 * Do multi-page navigation.  Displays the prev, next and page options.
	 * @param $page - the page currently viewed
	 * @param $pages - the maximum number of pages
	 * @param $gets -  the parameters to include in the link to the wanted page
	 * @param $max_width - the number of pages to make available at any one time (default = 20)
	 */
	public function printPages($page, $pages, $gets, $max_width = 20) {
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

			for ($i = $min_page; $i <= $max_page; $i++) {
				#if ($i != $page) echo "<a class=\"pagenav\" href=\"?{$url}&amp;page={$i}\">$i</a>\n";
				if ($i != $page) {
					echo "<a class=\"pagenav\" href=\"display.php?{$url}&amp;page={$i}\">$i</a>\n";
				} else {
					echo "$i\n";
				}

			}
			if ($page != $pages) {
				$temp = $page + 1;
				echo "<a class=\"pagenav\" href=\"display.php?{$url}&amp;page={$temp}\">{$lang['strnext']}</a>\n";
				echo "<a class=\"pagenav\" href=\"display.php?{$url}&amp;page={$pages}\">{$lang['strlast']}</a>\n";
			}
			echo "</p>\n";
		}
	}

	/**
	 * Displays link to the context help.
	 * @param $str   - the string that the context help is related to (already escaped)
	 * @param $help  - help section identifier
	 * @param $do_print true to echo, false to return
	 */
	public function printHelp($str, $help = null, $do_print = true) {
		//\PC::debug(['str' => $str, 'help' => $help], 'printHelp');
		if ($help !== null) {
			$helplink = $this->getHelpLink($help);
			$str .= '<a class="help" href="' . $helplink . '" title="' . $this->lang['strhelp'] . '" target="phppgadminhelp">' . $this->lang['strhelpicon'] . '</a>';

		}
		if ($do_print) {
			echo $str;
		} else {
			return $str;
		}
	}

	public function getHelpLink($help) {
		return htmlspecialchars('/src/views/help.php?help=' . urlencode($help) . '&server=' . urlencode($this->server_id));

	}

	/**
	 * Converts a PHP.INI size variable to bytes.  Taken from publically available
	 * function by Chris DeRose, here: http://www.php.net/manual/en/configuration.directives.php#ini.file-uploads
	 * @param $strIniSize The PHP.INI variable
	 * @return size in bytes, false on failure
	 */
	public function inisizeToBytes($strIniSize) {
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

		$nSize = (double) $a_IniParts[1];
		$strUnit = strtolower($a_IniParts[2]);

		switch ($strUnit) {
		case 'm':
			return ($nSize * (double) 1048576);
		case 'k':
			return ($nSize * (double) 1024);
		case 'b':
		default:
			return $nSize;
		}
	}

	public function getRequestVars($subject = '') {
		$v = [];
		if (!empty($subject)) {
			$v['subject'] = $subject;
		}

		if ($this->server_id !== null && $subject != 'root') {
			$v['server'] = $this->server_id;
			if ($this->database !== null && $subject != 'server') {
				$v['database'] = $this->database;
				if (isset($_REQUEST['schema']) && $subject != 'database') {
					$v['schema'] = $_REQUEST['schema'];
				}
			}
		}
		return $v;
	}

	public function icon($icon) {
		if (is_string($icon)) {
			$path = "/images/themes/{$this->conf['theme']}/{$icon}";
			if (file_exists(BASE_PATH . $path . '.png')) {
				return $path . '.png';
			}

			if (file_exists(BASE_PATH . $path . '.gif')) {
				return $path . '.gif';
			}

			$path = "/images/themes/default/{$icon}";
			if (file_exists(BASE_PATH . $path . '.png')) {
				return $path . '.png';
			}

			if (file_exists(BASE_PATH . $path . '.gif')) {
				return $path . '.gif';
			}

		} else {
			// Icon from plugins
			$path = "/plugins/{$icon[0]}/images/{$icon[1]}";
			if (file_exists(BASE_PATH . $path . '.png')) {
				return $path . '.png';
			}

			if (file_exists(BASE_PATH . $path . '.gif')) {
				return $path . '.gif';
			}

		}
		return '';
	}

	/**
	 * Function to escape command line parameters
	 * @param $str The string to escape
	 * @return The escaped string
	 */
	public function escapeShellArg($str) {
		$data = $this->getDatabaseAccessor();
		$lang = $this->lang;

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// Due to annoying PHP bugs, shell arguments cannot be escaped
			// (command simply fails), so we cannot allow complex objects
			// to be dumped.
			if (preg_match('/^[_.[:alnum:]]+$/', $str)) {
				return $str;
			} else {
				echo $lang['strcannotdumponwindows'];
				exit;
			}
		} else {
			return escapeshellarg($str);
		}

	}

	/**
	 * Function to escape command line programs
	 * @param $str The string to escape
	 * @return The escaped string
	 */
	public function escapeShellCmd($str) {
		$data = $this->getDatabaseAccessor();

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$data->fieldClean($str);
			return '"' . $str . '"';
		} else {
			return escapeshellcmd($str);
		}

	}

	/**
	 * Get list of servers' groups if existing in the conf
	 * @return a recordset of servers' groups
	 */
	public function getServersGroups($recordset = false, $group_id = false) {
		$lang = $this->lang;
		$grps = [];

		if (isset($this->conf['srv_groups'])) {
			foreach ($this->conf['srv_groups'] as $i => $group) {
				if (
					(($group_id === false) and (!isset($group['parents']))) /* root */
					or (
						($group_id !== false)
						and isset($group['parents'])
						and in_array($group_id, explode(',',
							preg_replace('/\s/', '', $group['parents'])
						))
					) /* nested group */
				) {
					$grps[$i] = [
						'id' => $i,
						'desc' => $group['desc'],
						'icon' => 'Servers',
						'action' => Decorator::url('/views/servers',
							[
								'group' => Decorator::field('id'),
							]
						),
						'branch' => Decorator::url('/tree/servers',
							[
								'group' => $i,
							]
						),
					];
				}

			}

			if ($group_id === false) {
				$grps['all'] = [
					'id' => 'all',
					'desc' => $lang['strallservers'],
					'icon' => 'Servers',
					'action' => Decorator::url('/views/servers',
						[
							'group' => Decorator::field('id'),
						]
					),
					'branch' => Decorator::url('/tree/servers',
						[
							'group' => 'all',
						]
					),
				];
			}

		}

		if ($recordset) {
			return new ArrayRecordSet($grps);
		}

		return $grps;
	}

	/**
	 * Get list of servers
	 * @param $recordset return as RecordSet suitable for printTable if true,
	 *                   otherwise just return an array.
	 * @param $group a group name to filter the returned servers using $this->conf[srv_groups]
	 */
	public function getServers($recordset = false, $group = false) {

		$logins = isset($_SESSION['webdbLogin']) && is_array($_SESSION['webdbLogin']) ? $_SESSION['webdbLogin'] : [];
		$srvs = [];

		if (($group !== false) and ($group !== 'all')) {
			if (isset($this->conf['srv_groups'][$group]['servers'])) {
				$group = array_fill_keys(explode(',', preg_replace('/\s/', '',
					$this->conf['srv_groups'][$group]['servers'])), 1);
			} else {
				$group = '';
			}
		}

		foreach ($this->conf['servers'] as $idx => $info) {
			$server_id = $info['host'] . ':' . $info['port'] . ':' . $info['sslmode'];
			if (($group === false)
				or isset($group[ $idx])
				or ($group === 'all')
			) {
				$server_id = $info['host'] . ':' . $info['port'] . ':' . $info['sslmode'];

				if (isset($logins[$server_id])) {
					$srvs[$server_id] = $logins[$server_id];
				} else {
					$srvs[$server_id] = $info;
				}

				$srvs[$server_id]['id'] = $server_id;
				$srvs[$server_id]['action'] = Decorator::url('/redirect/server',
					[
						'server' => Decorator::field('id'),
					]
				);
				if (isset($srvs[$server_id]['username'])) {
					$srvs[$server_id]['icon'] = 'Server';
					$srvs[$server_id]['branch'] = Decorator::url('all_db.php',
						[
							'action' => 'tree',
							'subject' => 'server',
							'server' => Decorator::field('id'),
						]
					);
				} else {
					$srvs[$server_id]['icon'] = 'DisconnectedServer';
					$srvs[$server_id]['branch'] = false;
				}
			}
		}

		uasort($srvs, ['self', '_cmp_desc']);

		if ($recordset) {
			return new ArrayRecordSet($srvs);
		}
		return $srvs;
	}

	public function getServerId() {
		return $this->server_id;
	}
	/**
	 * Validate and retrieve information on a server.
	 * If the parameter isn't supplied then the currently
	 * connected server is returned.
	 * @param $server_id A server identifier (host:port)
	 * @return An associative array of server properties
	 */
	public function getServerInfo($server_id = null) {

		//\PC::debug(['$server_id' => $server_id]);

		if ($server_id !== null) {
			$this->server_id = $server_id;
		} else if ($this->server_info !== null) {
			return $this->server_info;
		}

		// Check for the server in the logged-in list
		if (isset($_SESSION['webdbLogin'][$this->server_id])) {
			$this->server_info = $_SESSION['webdbLogin'][$this->server_id];
			return $this->server_info;
		}

		// Otherwise, look for it in the conf file
		foreach ($this->conf['servers'] as $idx => $info) {
			if ($this->server_id == $info['host'] . ':' . $info['port'] . ':' . $info['sslmode']) {
				// Automatically use shared credentials if available
				if (!isset($info['username']) && isset($_SESSION['sharedUsername'])) {
					$info['username'] = $_SESSION['sharedUsername'];
					$info['password'] = $_SESSION['sharedPassword'];
					$this->setReloadBrowser(true);
					$this->setServerInfo(null, $info, $this->server_id);
				}
				$this->server_info = $info;
				return $this->server_info;

			}
		}

		if ($server_id === null) {
			$this->server_info = null;
			return $this->server_info;

		} else {
			$this->server_info = null;
			// Unable to find a matching server, are we being hacked?
			echo $this->lang['strinvalidserverparam'];

			exit;
		}
	}

	/**
	 * Set server information.
	 * @param $key parameter name to set, or null to replace all
	 *             params with the assoc-array in $value.
	 * @param $value the new value, or null to unset the parameter
	 * @param $server_id the server identifier, or null for current
	 *                   server.
	 */
	public function setServerInfo($key, $value, $server_id = null) {
		//\PC::debug('setsetverinfo');
		if ($server_id === null && isset($_REQUEST['server'])) {
			$server_id = $_REQUEST['server'];
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

	/**
	 * Set the current schema
	 * @param $schema The schema name
	 * @return 0 on success
	 * @return $data->seSchema() on error
	 */
	public function setCurrentSchema($schema) {
		$data = $this->getDatabaseAccessor();

		$status = $data->setSchema($schema);
		if ($status != 0) {
			return $status;
		}

		$_REQUEST['schema'] = $schema;
		$this->setHREF();
		return 0;
	}

	/**
	 * Save the given SQL script in the history
	 * of the database and server.
	 * @param $script the SQL script to save.
	 */
	public function saveScriptHistory($script) {
		list($usec, $sec) = explode(' ', microtime());
		$time = ((float) $usec + (float) $sec);
		$_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']]["$time"] = [
            'query' => $script,
            'paginate' => !isset($_REQUEST['paginate']) ? 'f' : 't',
            'queryid' => $time,
		];
	}

	/*
		 * Output dropdown list to select server and
		 * databases form the popups windows.
		 * @param $onchange Javascript action to take when selections change.
		 */
	public function printConnection($onchange, $do_print = true) {
		$lang = $this->lang;

		$connection_html = "<table class=\"printconnection\" style=\"width: 100%\"><tr><td class=\"popup_select1\">\n";

		$servers = $this->getServers();
		$forcedserver = null;
		if (count($servers) === 1) {
			$forcedserver = $this->server_id;
			$connection_html .= '<input type="hidden" readonly="readonly" value="' . $this->server_id . '" name="server">';
		} else {
			$connection_html .= '<label>';
			$connection_html .= $this->printHelp($lang['strserver'], 'pg.server', false);
			$connection_html .= ': </label>';
			$connection_html .= " <select name=\"server\" {$onchange}>\n";
			foreach ($servers as $info) {
				if (empty($info['username'])) {
					continue;
				}
				$selected = isset($_REQUEST['server']) && $info['id'] == $_REQUEST['server'] ? ' selected="selected"' : '';
				// not logged on this server
				$connection_html .= '<option value="' . htmlspecialchars($info['id']) . '" ' . $selected . '>';
				$connection_html .= htmlspecialchars("{$info['desc']} ({$info['id']})");
				$connection_html .= "</option>\n";
			}
			$connection_html .= "</select>\n";
		}

		$connection_html .= "</td><td class=\"popup_select2\" style=\"text-align: right\">\n";

		if (count($servers) === 1 && isset($servers[$this->server_id]['useonlydefaultdb']) && $servers[$this->server_id]['useonlydefaultdb'] === true) {

			$connection_html .= '<input type="hidden" name="database" value="' . htmlspecialchars($servers[ $this->server_id]['defaultdb']) . "\" />\n";

		} else {

			// Get the list of all databases
			$data = $this->getDatabaseAccessor();
			$databases = $data->getDatabases();
			if ($databases->recordCount() > 0) {

				$connection_html .= '<label>';
				$connection_html .= $this->printHelp($lang['strdatabase'], 'pg.database', false);
				$connection_html .= ": <select name=\"database\" {$onchange}>\n";

				//if no database was selected, user should select one
				if (!isset($_REQUEST['database'])) {
					$connection_html .= "<option value=\"\">--</option>\n";
				}

				while (!$databases->EOF) {
					$dbname = $databases->fields['datname'];
					$dbselected = isset($_REQUEST['database']) && $dbname == $_REQUEST['database'] ? ' selected="selected"' : '';
					$connection_html .= '<option value="' . htmlspecialchars($dbname) . '" ' . $dbselected . '>' . htmlspecialchars($dbname) . "</option>\n";

					$databases->moveNext();
				}
				$connection_html .= "</select></label>\n";
			} else {
				$server_info = $misc->getServerInfo();
				$connection_html .= '<input type="hidden" name="database" value="' . htmlspecialchars($server_info['defaultdb']) . "\" />\n";
			}
		}

		$connection_html .= "</td></tr></table>\n";

		if ($do_print) {
			echo $connection_html;
		} else {
			return $connection_html;
		}

	}

	/**
	 * returns an array representing FKs definition for a table, sorted by fields
	 * or by constraint.
	 * @param $table The table to retrieve FK contraints from
	 * @returns the array of FK definition:
	 *   array(
	 *     'byconstr' => array(
	 *       constrain id => array(
	 *         confrelid => foreign relation oid
	 *         f_schema => foreign schema name
	 *         f_table => foreign table name
	 *         pattnums => array of parent's fields nums
	 *         pattnames => array of parent's fields names
	 *         fattnames => array of foreign attributes names
	 *       )
	 *     ),
	 *     'byfield' => array(
	 *       attribute num => array (constraint id, ...)
	 *     ),
	 *     'code' => HTML/js code to include in the page for auto-completion
	 *   )
	 **/
	public function getAutocompleteFKProperties($table) {
		$data = $this->getDatabaseAccessor();

		$fksprops = [
			'byconstr' => [],
			'byfield' => [],
			'code' => '',
		];

		$constrs = $data->getConstraintsWithFields($table);

		if (!$constrs->EOF) {
			$conrelid = $constrs->fields['conrelid'];
			while (!$constrs->EOF) {
				if ($constrs->fields['contype'] == 'f') {
					if (!isset($fksprops['byconstr'][$constrs->fields['conid']])) {
						$fksprops['byconstr'][$constrs->fields['conid']] = [
							'confrelid' => $constrs->fields['confrelid'],
							'f_table' => $constrs->fields['f_table'],
							'f_schema' => $constrs->fields['f_schema'],
							'pattnums' => [],
							'pattnames' => [],
							'fattnames' => [],
						];
					}

					$fksprops['byconstr'][$constrs->fields['conid']]['pattnums'][] = $constrs->fields['p_attnum'];
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
				$fksprops['code'] .= 'pattnums: [' . implode(',', $props['pattnums']) . "],\n";
				$fksprops['code'] .= "f_table:'" . addslashes(htmlentities($props['f_table'], ENT_QUOTES, 'UTF-8')) . "',\n";
				$fksprops['code'] .= "f_schema:'" . addslashes(htmlentities($props['f_schema'], ENT_QUOTES, 'UTF-8')) . "',\n";
				$_ = '';
				foreach ($props['pattnames'] as $n) {
					$_ .= ",'" . htmlentities($n, ENT_QUOTES, 'UTF-8') . "'";
				}
				$fksprops['code'] .= 'pattnames: [' . substr($_, 1) . "],\n";

				$_ = '';
				foreach ($props['fattnames'] as $n) {
					$_ .= ",'" . htmlentities($n, ENT_QUOTES, 'UTF-8') . "'";
				}

				$fksprops['code'] .= 'fattnames: [' . substr($_, 1) . "]\n";
				$fksprops['code'] .= "};\n";
			}

			$fksprops['code'] .= "var attrs = {};\n";
			foreach ($fksprops['byfield'] as $attnum => $cstrs) {
				$fksprops['code'] .= "attrs.attr_{$attnum} = [" . implode(',', $fksprops['byfield'][$attnum]) . "];\n";
			}

			$fksprops['code'] .= "var table='" . addslashes(htmlentities($table, ENT_QUOTES, 'UTF-8')) . "';";
			$fksprops['code'] .= "var server='" . htmlentities($_REQUEST['server'], ENT_QUOTES, 'UTF-8') . "';";
			$fksprops['code'] .= "var database='" . addslashes(htmlentities($_REQUEST['database'], ENT_QUOTES, 'UTF-8')) . "';";
			$fksprops['code'] .= "</script>\n";

			$fksprops['code'] .= '<div id="fkbg"></div>';
			$fksprops['code'] .= '<div id="fklist"></div>';
			$fksprops['code'] .= '<script src="/js/ac_insert_row.js" type="text/javascript"></script>';
		} else /* we have no foreign keys on this table */
		{
			return false;
		}

		return $fksprops;
	}
}
