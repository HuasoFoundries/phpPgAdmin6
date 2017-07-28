<?php

/**
 * Function library read in upon startup
 *
 * $Id: lib.inc.php,v 1.123 2008/04/06 01:10:35 xzilla Exp $
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

define('THEME_PATH', BASE_PATH . "/src/themes");
// Enforce PHP environment
ini_set('arg_separator.output', '&amp;');

ini_set('error_log', BASE_PATH . '/temp/logs/phppga.php_error.log');

// Check to see if the configuration file exists, if not, explain
if (file_exists(BASE_PATH . '/config.inc.php')) {
	$conf = [];
	include BASE_PATH . '/config.inc.php';
} else {
	die('Configuration error: Copy config.inc.php-dist to config.inc.php and edit appropriately.');

}
$debugmode = (!isset($conf['debugmode'])) ? false : $conf['debugmode'];

if ($debugmode) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

if (!defined('ADODB_ERROR_HANDLER_TYPE')) {
	define('ADODB_ERROR_HANDLER_TYPE', E_USER_ERROR);
}
if (!defined('ADODB_ERROR_HANDLER')) {
	//define('ADODB_ERROR_HANDLER', '\PHPPgAdmin\Misc::Error_Handler');
	define('ADODB_ERROR_HANDLER', '\PHPPgAdmin\Misc::adodb_throw');
}

require_once BASE_PATH . '/vendor/autoload.php';

// Start session (if not auto-started)
if (!ini_get('session.auto_start')) {
	session_name('PPA_ID');
	session_start();
}
\Kint::$enabled_mode = ($debugmode);

//echo readlink(dirname(__FILE__));
define('SUBFOLDER', str_replace($_SERVER['DOCUMENT_ROOT'], '', BASE_PATH));

$handler = PhpConsole\Handler::getInstance();
$handler->start(); // initialize handlers*/
PhpConsole\Helper::register(); // it will register global PC class

$config = [
	'msg' => '',
	'appThemes' => [
		'default' => 'Default',
		'cappuccino' => 'Cappuccino',
		'gotar' => 'Blue/Green',
		'bootstrap' => 'Bootstrap3',
	],
	'settings' => [
		'base_path' => BASE_PATH,
		'debug' => $debugmode,

		// Configuration file version.  If this is greater than that in config.inc.php, then
		// the app will refuse to run.  This and $conf['version'] should be incremented whenever
		// backwards incompatible changes are made to config.inc.php-dist.
		'base_version' => 60,
		// Application version
		'appVersion' => '6.0.0-alpha',
		// Application name
		'appName' => 'phpPgAdmin',

		// PostgreSQL and PHP minimum version
		'postgresqlMinVer' => '9.3',
		'phpMinVer' => '5.5',
		'displayErrorDetails' => true,
		'addContentLengthHeader' => false,
	],
];

$app = new \Slim\App($config);

// Fetch DI Container
$container = $app->getContainer();

$container['conf'] = function ($c) use ($conf) {

	// Plugins are removed
	$conf['plugins'] = [];

	return $conf;
};

$container['lang'] = function ($c) {
	include_once BASE_PATH . '/src/translations.php';

	$c['appLangFiles'] = $appLangFiles;
	$c['language'] = $_language;
	$c['isolang'] = $_isolang;
	return $lang;
};

$container['plugin_manager'] = function ($c) {
	$plugin_manager = new \PHPPgAdmin\PluginManager($c);
	return $plugin_manager;
};

$container['serializer'] = function ($c) {
	$serializerbuilder = \JMS\Serializer\SerializerBuilder::create();
	$serializer = $serializerbuilder
		->setCacheDir(BASE_PATH . '/temp/jms')
		->setDebug($c->get('settings')['debug'])
		->build();
	return $serializer;
};

// Register Twig View helper
$container['view'] = function ($c) {

	//\Kint::dump($c->get('settings'));
	//die();

	$view = new \Slim\Views\Twig(BASE_PATH . '/templates', [
		'cache' => BASE_PATH . '/temp/twigcache',
		'auto_reload' => $c->get('settings')['debug'],
		'debug' => $c->get('settings')['debug'],
	]);
	$environment = $c->get('environment');
	$base_script_trailing_shit = substr($environment['SCRIPT_NAME'], 1);
	$request_basepath = $c['request']->getUri()->getBasePath();
	// Instantiate and add Slim specific extension
	$basePath = rtrim(str_ireplace($base_script_trailing_shit, '', $request_basepath), '/');

	$view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

	$view->offsetSet('subfolder', SUBFOLDER);

	//\Kint::dump(SUBFOLDER, $request_basepath, $base_script_trailing_shit, $basePath);
	//die();

	return $view;
};

// Create Misc class references
$container['misc'] = function ($c) {

	$misc = new \PHPPgAdmin\Misc($c);
	$conf = $c->get('conf');

	// 4. Check for theme by server/db/user
	$_server_info = $misc->getServerInfo();

	/* starting with PostgreSQL 9.0, we can set the application name */
	if (isset($_server_info['pgVersion']) && $_server_info['pgVersion'] >= 9) {
		putenv("PGAPPNAME=" . $c->get('settings')['appName'] . '_' . $c->get('settings')['appVersion']);
	}

	$themefolders = [];
	if ($gestor = opendir(THEME_PATH)) {

		/* Esta es la forma correcta de iterar sobre el directorio. */
		while (false !== ($file = readdir($gestor))) {
			if ($file == '.' || $file == '..') {
				continue;
			}

			$folder = THEME_PATH . DIRECTORY_SEPARATOR . $file;
			if (is_dir($folder) && is_file($folder . DIRECTORY_SEPARATOR . 'global.css')) {
				$themefolders[$file] = $folder;

			}
		}
		closedir($gestor);
	}

	//\PC::debug($themefolders, 'themefolders');
	/* select the theme */
	unset($_theme);

	// List of themes
	if (!isset($conf['theme'])) {
		$conf['theme'] = 'default';
	}
	// 1. Check for the theme from a request var
	if (isset($_REQUEST['theme']) && array_key_exists($_REQUEST['theme'], $themefolders)) {
		$_theme = $_REQUEST['theme'];

	} else if (!isset($_theme) && isset($_SESSION['ppaTheme']) && array_key_exists($_SESSION['ppaTheme'], $themefolders)) {
		// 2. Check for theme session var
		$_theme = $_SESSION['ppaTheme'];
	} else if (!isset($_theme) && isset($_COOKIE['ppaTheme']) && array_key_exists($_COOKIE['ppaTheme'], $themefolders)) {
		// 3. Check for theme in cookie var
		$_theme = $_COOKIE['ppaTheme'];
	}

	if (!isset($_theme) && !is_null($_server_info) && array_key_exists('theme', $_server_info)) {

		$server_theme = $_server_info['theme'];

		if (isset($server_theme['default']) && array_key_exists($server_theme['default'], $themefolders)) {
			$_theme = $server_theme['default'];
		}

		if (isset($_REQUEST['database'])
			&& isset($server_theme['db'][$_REQUEST['database']])
			&& array_key_exists($server_theme['db'][$_REQUEST['database']], $themefolders)

		) {
			$_theme = $server_theme['db'][$_REQUEST['database']];
		}

		if (isset($_server_info['username'])
			&& isset($server_theme['user'][$_server_info['username']])
			&& array_key_exists($server_theme['user'][$_server_info['username']], $themefolders)
		) {
			$_theme = $server_theme['user'][$_server_info['username']];
		}

	}
	if (isset($_theme)) {
		/* save the selected theme in cookie for a year */
		setcookie('ppaTheme', $_theme, time() + 31536000, '/');
		$_SESSION['ppaTheme'] = $_theme;
		$conf['theme'] = $_theme;
	}
	//\PC::debug($conf['theme'], 'conf.theme');

	$misc->setThemeConf($conf['theme']);

	// This has to be deferred until after stripVar above
	$misc->setHREF();
	$misc->setForm();

	return $misc;
};

$container['action'] = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';

if (!isset($msg)) {
	$msg = '';
}
$container['msg'] = $msg;
