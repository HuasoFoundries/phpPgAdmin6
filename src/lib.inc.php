<?php

/**
 * Function library read in upon startup
 *
 * $Id: lib.inc.php,v 1.123 2008/04/06 01:10:35 xzilla Exp $
 */
DEFINE('BASE_PATH', dirname(__DIR__));

ini_set('error_log', BASE_PATH . '/temp/logs/phppga.php_error.log');

require_once BASE_PATH . '/vendor/autoload.php';
include_once BASE_PATH . '/src/errorhandler.inc.php';
include_once BASE_PATH . '/src/decorator.inc.php';

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
$phpMinVer        = '5.5';
$debugmode        = true;

// Check the version of PHP
if (version_compare(phpversion(), $phpMinVer, '<')) {
	exit(sprintf('Version of PHP not supported. Please upgrade to version %s or later.', $phpMinVer));
}

// Check to see if the configuration file exists, if not, explain
if (file_exists(BASE_PATH . '/config.inc.php')) {
	$conf = [];
	include BASE_PATH . '/config.inc.php';
} else {
	die('Configuration error: Copy config.inc.php-dist to config.inc.php and edit appropriately.');

}

// Check if a given server is "greedy" in which case the $_REQUEST['server'] parameter is ignored
$serverstoshow = [];
foreach ($conf['servers'] as $server) {
	if (isset($server['forcehost']) && $server['forcehost'] === true) {
		$serverstoshow = [$server];
		break;
	} else {
		$serverstoshow[] = $server;
	}
}
$conf['servers'] = $serverstoshow;
// Configuration file version.  If this is greater than that in config.inc.php, then
// the app will refuse to run.  This and $conf['version'] should be incremented whenever
// backwards incompatible changes are made to config.inc.php-dist.
$conf['base_version'] = 60;

include_once BASE_PATH . '/src/translations.php';

// Create Misc class references

// Start session (if not auto-started)
if (!ini_get('session.auto_start')) {
	session_name('PPA_ID');
	session_start();
}
//Kint::dump($_SERVER);

$config = [
	'msg' => '',
	'appLangFiles' => $appLangFiles,
	'conf' => $conf,
	'lang' => $lang,
	'language' => $_language,
	'settings' => [
		'debug' => $debugmode,
		'appVersion' => $appVersion,
		'appName' => htmlspecialchars($appName),
		'displayErrorDetails' => true,
		'addContentLengthHeader' => false,
	],
];

$app = new \Slim\App($config);

// Fetch DI Container
$container   = $app->getContainer();
$environment = $container->get('environment');

//$container['lang'] = $lang;

$plugin_manager              = new \PHPPgAdmin\PluginManager($app);
$container['plugin_manager'] = $plugin_manager;

$container['serializer'] = function ($c) {
	$serializerbuilder = \JMS\Serializer\SerializerBuilder::create();
	$serializer        = $serializerbuilder
		->setCacheDir(BASE_PATH . '/temp/jms')
		->setDebug($c->get('settings')['debug'])
		->build();
	return $serializer;
};

$container['logger'] = function ($c) {
	$logger       = new \Monolog\Logger($c->get('settings')['appName']);
	$file_handler = new \Monolog\Handler\StreamHandler(BASE_PATH . '/temp/logs/app.log');
	$logger->pushHandler($file_handler);
	return $logger;
};

// Register Twig View helper
$container['view'] = function ($c) {
	$view = new \Slim\Views\Twig(BASE_PATH . '/templates', [
		'cache' => BASE_PATH . '/temp/twigcache',
		'auto_reload' => $c->get('settings')['debug'],
		'debug' => $c->get('settings')['debug'],
	]);
	$environment               = $c->get('environment');
	$base_script_trailing_shit = substr($environment['SCRIPT_NAME'], 1);
	// Instantiate and add Slim specific extension
	$basePath = rtrim(str_ireplace($base_script_trailing_shit, '', $c['request']->getUri()->getBasePath()), '/');
	$view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

	return $view;
};

$misc              = new \PHPPgAdmin\Misc($app);
$container['misc'] = $misc;

// 4. Check for theme by server/db/user
$_server_info = $misc->getServerInfo();
include_once BASE_PATH . '/src/themes.php';

$misc->setThemeConf($conf['theme']);

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

	$misc->setReloadBrowser(true);
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

//PC::debug($_server_info, 'server_info');

// Create data accessor object, if necessary
if (!isset($_no_db_connection)) {
	if ($misc->getServerId() === null) {
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

	// Connect to database and set the global $data variable
	$data = $misc->getDatabaseAccessor();

	// If schema is defined and database supports schemas, then set the
	// schema explicitly.
	if ($misc->getDatabase() !== null && isset($_REQUEST['schema'])) {
		$status = $data->setSchema($_REQUEST['schema']);
		if ($status != 0) {
			echo $lang['strbadschema'];
			exit;
		}
	}
}
