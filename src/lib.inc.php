<?php

/**
 * PHPPgAdmin 6.0.0
 */

\defined('BASE_PATH') || \define('BASE_PATH', \dirname(__DIR__));

\defined('THEME_PATH') || \define('THEME_PATH', \dirname(__DIR__) . '/assets/themes');
// Enforce PHP environment
\ini_set('arg_separator.output', '&amp;');

if (!\is_writable(\dirname(__DIR__) . '/temp')) {
    die('Your temp folder must have write permissions (use chmod 777 temp -R on linux)');
}

require_once \dirname(__DIR__) . '/vendor/autoload.php';
$subfolder = '';
// Check to see if the configuration file exists, if not, explain
if (!\file_exists(\dirname(__DIR__) . '/config.inc.php')) {
    die('Configuration error: Copy config.inc.php-dist to config.inc.php and edit appropriately.');
}
    $conf = [];

    include \dirname(__DIR__) . '/config.inc.php';

    if (isset($conf['subfolder']) && \is_string($conf['subfolder'])) {
        $subfolder = $conf['subfolder'];
    } elseif (\PHP_SAPI === 'cli-server') {
        $subfolder = '/index.php';
    } elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
        $subfolder = \str_replace(
            $_SERVER['DOCUMENT_ROOT'],
            '',
            \dirname(__DIR__)
        );
    }

\defined('PHPPGA_SUBFOLDER') || \define('PHPPGA_SUBFOLDER', $subfolder);
$shouldSetSession = (\defined('PHP_SESSION_ACTIVE') ? \PHP_SESSION_ACTIVE !== \session_status() : !\session_id())
&& !\headers_sent()
&& !\ini_get('session.auto_start');

if ($shouldSetSession && \PHP_SAPI !== 'cli') {
    \session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'], isset($_SERVER['HTTPS']));
    \session_name('PPA_ID');
    \session_start();
}

$debugmode = (!isset($conf['debugmode'])) ? false : (bool) ($conf['debugmode']);
\defined('DEBUGMODE') || \define('DEBUGMODE', $debugmode);

if (!\defined('ADODB_ERROR_HANDLER_TYPE')) {
    \define('ADODB_ERROR_HANDLER_TYPE', \E_USER_ERROR);
}

if (!\defined('ADODB_ERROR_HANDLER')) {
    \define('ADODB_ERROR_HANDLER', '\PHPPgAdmin\ADOdbException::adodb_throw');
}

if (DEBUGMODE) {
    \ini_set('display_errors', 'On');

    \ini_set('display_startup_errors', 'On');
    \ini_set('opcache.revalidate_freq', '0');
    \error_reporting(\E_ALL);

    if (\array_key_exists('register_debuggers', $conf) && \is_callable($conf['register_debuggers'])) {
        $conf['register_debuggers']();
    }
}

// Fetch App and DI Container
$app = \PHPPgAdmin\ContainerUtils::createApp($conf);
$container = $app->getContainer();

if (!$container instanceof \Slim\Container) {
    \trigger_error('App Container must be an instance of \\Slim\\Container', \E_USER_ERROR);
}

// This should be deprecated once we're sure no php scripts are required directly
$container->offsetSet('server', $_REQUEST['server'] ?? null);
$container->offsetSet('database', $_REQUEST['database'] ?? null);
$container->offsetSet('schema', $_REQUEST['schema'] ?? null);

$container['haltHandler'] = static function (\Slim\Container $c) {
    return static function ($request, $response, $exits, $status = 500) use ($c) {
        $title = 'PHPPgAdmin Error';

        $html = '<p>The application could not run because of the following error:</p>';

        $output = \sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            '<title>%s</title><style>' .
            'body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}' .
            'h3{margin:0;font-size:28px;font-weight:normal;line-height:30px;}' .
            'span{display:inline-block;font-size:16px;}' .
            '</style></head><body><h3>%s</h3><p>%s</p><span>%s</span></body></html>',
            $title,
            $title,
            $html,
            \implode('<br>', $exits)
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

$container['action'] = $_REQUEST['action'] ?? '';

if (!isset($msg)) {
    $msg = '';
}

$container['msg'] = $msg;
//ddd($container->misc);
