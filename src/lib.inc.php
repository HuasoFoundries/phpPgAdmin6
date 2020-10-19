<?php

/**
 * PHPPgAdmin 6.1.0
 */

use Slim\App;

\defined('BASE_PATH') || \define('BASE_PATH', \dirname(__DIR__));

\defined('THEME_PATH') || \define('THEME_PATH', \dirname(__DIR__) . '/assets/themes');
// Enforce PHP environment
\ini_set('arg_separator.output', '&amp;');

if (!\is_writable(\dirname(__DIR__) . '/temp')) {
    die('Your temp folder must have write permissions (use chmod 777 temp -R on linux)');
}

require_once \dirname(__DIR__) . '/vendor/autoload.php';

$shouldSetSession = (\defined('PHP_SESSION_ACTIVE') ? \PHP_SESSION_ACTIVE !== \session_status() : !\session_id())
    && !\headers_sent()
    && !\ini_get('session.auto_start');

if ($shouldSetSession && \PHP_SAPI !== 'cli') {
    \session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'], isset($_SERVER['HTTPS']));
    \session_name('PPA_ID');
    \session_start();
}

\defined('ADODB_ERROR_HANDLER_TYPE') || \define('ADODB_ERROR_HANDLER_TYPE', \E_USER_ERROR);
\defined('ADODB_ERROR_HANDLER') || \define('ADODB_ERROR_HANDLER', '\PHPPgAdmin\ADOdbException::adodb_throw');

function getAppInstance(): \Slim\App
{
    $subfolder = '';
    // Check to see if the configuration file exists, if not, explain
    if (!\file_exists(\dirname(__DIR__) . '/config.inc.php')) {
        die('Configuration error: Copy config.inc.php-dist to config.inc.php and edit appropriately.');
    }
    $conf = [];

    include_once \dirname(__DIR__) . '/config.inc.php';

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

    $conf['subfolder'] = $subfolder;

    $conf['debugmode'] = (!isset($conf['debugmode'])) ? false : (bool) ($conf['debugmode']);

    if ($conf['debugmode']) {
        \ini_set('display_startup_errors', 'On');
        \ini_set('opcache.revalidate_freq', '0');
        \error_reporting(\E_ALL);

        if (\array_key_exists('register_debuggers', $conf) && \is_callable($conf['register_debuggers'])) {
            $conf['register_debuggers']();
        }
    }

    $conf['BASE_PATH'] = BASE_PATH;
    $conf['theme_path'] = BASE_PATH . '/assets/themes';
    \defined('IN_TEST') || \define('IN_TEST', false);
    $conf['IN_TEST'] = IN_TEST;
    \defined('ADODB_ASSOC_CASE') || \define('ADODB_ASSOC_CASE', ADODB_ASSOC_CASE_NATIVE);

    // Fetch App and DI Container
    $app = \PHPPgAdmin\ContainerUtils::getAppInstance($conf);

    return $app;
}

function containerInstance(): \PHPPgAdmin\ContainerUtils
{
    $app = getAppInstance();
    $container = $app->getContainer();

    if (!$container instanceof \PHPPgAdmin\ContainerUtils) {
        \trigger_error('App Container must be an instance of \\Slim\\Container', \E_USER_ERROR);
    }

    return  $container;
}

function requestInstance(): \Slim\Http\Request
{
    return  \containerInstance()->request;
}

function responseInstance(): \Slim\Http\Response
{
    return \containerInstance()->response;
}
