<?php

/**
 * Function library read in upon startup.
 *
 * Release: lib.inc.php,v 1.123 2008/04/06 01:10:35 xzilla Exp $
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

define('THEME_PATH', BASE_PATH . '/assets/themes');
// Enforce PHP environment
ini_set('arg_separator.output', '&amp;');

if (!is_writable(BASE_PATH . '/temp')) {
    die('Your temp folder must have write permissions (use chmod 777 temp -R on linux)');
}
require_once BASE_PATH . '/vendor/autoload.php';
// Check to see if the configuration file exists, if not, explain
if (file_exists(BASE_PATH . '/config.inc.php')) {
    $conf = [];
    include BASE_PATH . '/config.inc.php';
} else {
    die('Configuration error: Copy config.inc.php-dist to config.inc.php and edit appropriately.');
}
$setSession = (defined('PHP_SESSION_ACTIVE') ? session_status() != PHP_SESSION_ACTIVE : !session_id()) && !headers_sent() && !ini_get('session.auto_start');

if ($setSession) {
    session_set_cookie_params(0, '/', null, isset($_SERVER['HTTPS']));
    session_name('PPA_ID');

    session_start();
}

if (isset($conf['error_log'])) {
    ini_set('error_log', BASE_PATH . '/' . $conf['error_log']);
}
$debugmode = (!isset($conf['debugmode'])) ? false : boolval($conf['debugmode']);
define('DEBUGMODE', $debugmode);

if (!defined('ADODB_ERROR_HANDLER_TYPE')) {
    define('ADODB_ERROR_HANDLER_TYPE', E_USER_ERROR);
}

if (!defined('ADODB_ERROR_HANDLER')) {
    define('ADODB_ERROR_HANDLER', '\PHPPgAdmin\ADOdbException::adodb_throw');
}
if (!is_writable(session_save_path())) {
    echo 'Session path "' . session_save_path() . '" is not writable for PHP!';
}

ini_set('display_errors', intval(DEBUGMODE));
ini_set('display_startup_errors', intval(DEBUGMODE));
if (DEBUGMODE) {
    ini_set('opcache.revalidate_freq', 0);
    error_reporting(E_ALL);
    if (array_key_exists('register_debuggers', $conf) && is_callable($conf['register_debuggers'])) {
        $conf['register_debuggers']();
    }
}

// Fetch App and DI Container
list($container, $app) = \PHPPgAdmin\ContainerUtils::createContainer();

if ($container instanceof \Psr\Container\ContainerInterface) {
    if (PHP_SAPI == 'cli-server') {
        $subfolder = '/index.php';
    } elseif (isset($conf['subfolder']) && is_string($conf['subfolder'])) {
        $subfolder = $conf['subfolder'];
    } else {
        $normalized_php_self = str_replace('/src/views', '', $container->environment->get('PHP_SELF'));
        $subfolder           = str_replace('/' . basename($normalized_php_self), '', $normalized_php_self);
    }
    define('SUBFOLDER', $subfolder);
} else {
    trigger_error("App Container must be an instance of \Psr\Container\ContainerInterface", E_USER_ERROR);
}

$container['requestobj']  = $container['request'];
$container['responseobj'] = $container['response'];

// This should be deprecated once we're sure no php scripts are required directly
$container->offsetSet('server', isset($_REQUEST['server']) ? $_REQUEST['server'] : null);
$container->offsetSet('database', isset($_REQUEST['database']) ? $_REQUEST['database'] : null);
$container->offsetSet('schema', isset($_REQUEST['schema']) ? $_REQUEST['schema'] : null);

$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

// Complete missing conf keys
$container['conf'] = function ($c) use ($conf) {

    //\Kint::dump($conf);
    // Plugins are removed
    $conf['plugins'] = [];
    if (!isset($conf['theme'])) {
        $conf['theme'] = 'default';
    }
    foreach ($conf['servers'] as &$server) {
        if (!isset($server['port'])) {
            $server['port'] = 5432;
        }
        if (!isset($server['sslmode'])) {
            $server['sslmode'] = 'unspecified';
        }
    }

    return $conf;
};

$container['lang'] = function ($c) {
    $translations = new \PHPPgAdmin\Translations($c);

    return $translations->lang;
};

$container['plugin_manager'] = function ($c) {
    $plugin_manager = new \PHPPgAdmin\PluginManager($c);

    return $plugin_manager;
};

// Create Misc class references
$container['misc'] = function ($c) {
    $misc = new \PHPPgAdmin\Misc($c);

    $conf = $c->get('conf');

    // 4. Check for theme by server/db/user
    $_server_info = $misc->getServerInfo();

    /* starting with PostgreSQL 9.0, we can set the application name */
    if (isset($_server_info['pgVersion']) && $_server_info['pgVersion'] >= 9) {
        putenv('PGAPPNAME=' . $c->get('settings')['appName'] . '_' . $c->get('settings')['appVersion']);
    }

    $_theme = $c->utils->getTheme($conf, $_server_info);
    if (!is_null($_theme)) {
        /* save the selected theme in cookie for a year */
        setcookie('ppaTheme', $_theme, time() + 31536000, '/');
        $_SESSION['ppaTheme'] = $_theme;
        $misc->setConf('theme', $_theme);
    }

    return $misc;
};

// Register Twig View helper
$container['view'] = function ($c) {
    $conf = $c->get('conf');
    $misc = $c->misc;

    $view = new \Slim\Views\Twig(BASE_PATH . '/assets/templates', [
        'cache'       => BASE_PATH . '/temp/twigcache',
        'auto_reload' => $c->get('settings')['debug'],
        'debug'       => $c->get('settings')['debug'],
    ]);
    $environment              = $c->get('environment');
    $base_script_trailing_str = substr($environment['SCRIPT_NAME'], 1);
    $request_basepath         = $c['request']->getUri()->getBasePath();
    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace($base_script_trailing_str, '', $request_basepath), '/');

    $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

    $view->offsetSet('subfolder', SUBFOLDER);
    $view->offsetSet('theme', $c->misc->getConf('theme'));
    $view->offsetSet('Favicon', $c->misc->icon('Favicon'));
    $view->offsetSet('Introduction', $c->misc->icon('Introduction'));
    $view->offsetSet('lang', $c->lang);

    $view->offsetSet('applangdir', $c->lang['applangdir']);

    $view->offsetSet('appName', $c->get('settings')['appName']);

    $misc->setView($view);

    return $view;
};

$container['haltHandler'] = function ($c) {
    return function ($request, $response, $exits, $status = 500) use ($c) {
        $title = 'PHPPgAdmin Error';

        $html = '<p>The application could not run because of the following error:</p>';

        $output = sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            '<title>%s</title><style>' .
            'body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}' .
            'h3{margin:0;font-size:28px;font-weight:normal;line-height:30px;}' .
            'span{display:inline-block;font-size:16px;}' .
            '</style></head><body><h3>%s</h3><p>%s</p><span>%s</span></body></html>',
            $title,
            $title,
            $html,
            implode('<br>', $exits)
        );

        $body = $response->getBody(); //new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response
            ->withStatus($status)
            ->withHeader('Content-type', 'text/html')
            ->withBody($body);
    };
};

// Set the requestobj and responseobj properties of the container
// as the value of $request and $response, which already contain the route
$app->add(new \PHPPgAdmin\Middleware\PopulateRequestResponse($container));

$container['action'] = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if (!isset($msg)) {
    $msg = '';
}

$container['msg'] = $msg;
