<?php

/**
 * Function library read in upon startup
 *
 * $Id: lib.inc.php,v 1.123 2008/04/06 01:10:35 xzilla Exp $
 */
DEFINE('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';
include_once BASE_PATH . '/libraries/errorhandler.inc.php';
include_once BASE_PATH . '/libraries/decorator.inc.php';

Kint::enabled(true);

$handler = PhpConsole\Handler::getInstance();
/* You can override default Handler behavior:
$handler->setHandleErrors(false);  // disable errors handling
$handler->setHandleExceptions(false); // disable exceptions handling
$handler->setCallOldHandlers(false); // disable passing errors & exceptions to prviously defined handlers
 */
$handler->start(); // initialize handlers
PhpConsole\Helper::register(); // it will register global PC class

// Set error reporting level to max
error_reporting(E_ALL);

// Application name
$appName = 'phpPgAdmin';

// Application version
$appVersion = '6.0.0-alpha';

// PostgreSQL and PHP minimum version
$postgresqlMinVer = '9.3';
$phpMinVer = '5.5';

// Check the version of PHP
if (version_compare(phpversion(), $phpMinVer, '<')) {
	exit(sprintf('Version of PHP not supported. Please upgrade to version %s or later.', $phpMinVer));
}

// Check to see if the configuration file exists, if not, explain
if (file_exists(BASE_PATH . '/libraries/config.inc.php')) {
	$conf = array();
	include BASE_PATH . '/libraries/config.inc.php';
} else {
	echo 'Configuration error: Copy conf/config.inc.php-dist to libraries/config.inc.php and edit appropriately.';
	exit;
}

// Configuration file version.  If this is greater than that in config.inc.php, then
// the app will refuse to run.  This and $conf['version'] should be incremented whenever
// backwards incompatible changes are made to config.inc.php-dist.
$conf['base_version'] = 20;

include_once BASE_PATH . '/libraries/translations.php';

// Create Misc class references

// Start session (if not auto-started)
if (!ini_get('session.auto_start')) {
	session_name('PPA_ID');
	session_start();
}
//Kint::dump($_SERVER);

// Create Slim app
$app = new \Slim\App();

$misc = new \PHPPgAdmin\Misc($app);

// Fetch DI Container
$container = $app->getContainer();
$environment = $container->get('environment');

$container['misc'] = $misc;
// Register Twig View helper
$container['view'] = function ($c) {
	$view = new \Slim\Views\Twig(BASE_PATH . '/templates', [
		'cache' => BASE_PATH . '/temp/twigcache',
		'debug' => true,
	]);
	$environment = $c->get('environment');
	$base_script_trailing_shit = substr($environment['SCRIPT_NAME'], 1);
	// Instantiate and add Slim specific extension
	$basePath = rtrim(str_ireplace($base_script_trailing_shit, '', $c['request']->getUri()->getBasePath()), '/');
	$view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

	return $view;
};

$container['lang'] = $lang;
$misc->setLang($lang);
// 4. Check for theme by server/db/user
$info = $misc->getServerInfo(null, $conf);
include_once BASE_PATH . '/libraries/themes.php';

$container['conf'] = $conf;
$misc->setConf($conf);

// This has to be deferred until after stripVar above
$misc->setHREF();
$misc->setForm();

// Enforce PHP environment
ini_set('arg_separator.output', '&amp;');

// If login action is set, then set session variables
if (isset($_POST['loginServer']) && isset($_POST['loginUsername']) &&
	isset($_POST['loginPassword_' . md5($_POST['loginServer'])])) {

	$_server_info = $misc->getServerInfo($_POST['loginServer']);

	$_server_info['username'] = $_POST['loginUsername'];
	$_server_info['password'] = $_POST['loginPassword_' . md5($_POST['loginServer'])];

	$misc->setServerInfo(null, $_server_info, $_POST['loginServer']);

	// Check for shared credentials
	if (isset($_POST['loginShared'])) {
		$_SESSION['sharedUsername'] = $_POST['loginUsername'];
		$_SESSION['sharedPassword'] = $_POST['loginPassword_' . md5($_POST['loginServer'])];
	}

	$_reload_browser = true;
}

// Check for config file version mismatch
if (!isset($conf['version']) || $conf['base_version'] > $conf['version']) {
	echo $lang['strbadconfig'];
	exit;
}

// Check database support is properly compiled in
if (!function_exists('pg_connect')) {
	echo $lang['strnotloaded'];
	exit;
}

$_server_info = $misc->getServerInfo();

PC::debug($_server_info, 'server_info');

// Create data accessor object, if necessary
if (!isset($_no_db_connection)) {
	if (!isset($_REQUEST['server'])) {
		echo $lang['strnoserversupplied'];
		exit;
	}

	/* starting with PostgreSQL 9.0, we can set the application name */
	if (isset($_server_info['pgVersion']) && $_server_info['pgVersion'] >= 9) {
		putenv("PGAPPNAME={$appName}_{$appVersion}");
	}

	// Redirect to the login form if not logged in
	if (!isset($_server_info['username'])) {
		include BASE_PATH . '/views/login.php';
		exit;
	}

	// Connect to the current database, or if one is not specified
	// then connect to the default database.
	if (isset($_REQUEST['database'])) {
		$_curr_db = $_REQUEST['database'];
	} else {
		$_curr_db = $_server_info['defaultdb'];
	}

	// Connect to database and set the global $data variable
	$data = $misc->getDatabaseAccessor($_curr_db);

	// If schema is defined and database supports schemas, then set the
	// schema explicitly.
	if (isset($_REQUEST['database']) && isset($_REQUEST['schema'])) {
		$status = $data->setSchema($_REQUEST['schema']);
		if ($status != 0) {
			echo $lang['strbadschema'];
			exit;
		}
	}
}

if (!function_exists("htmlspecialchars_decode")) {
	function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT) {
		return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
	}
}

$plugin_manager = new \PHPPgAdmin\PluginManager($_language);
