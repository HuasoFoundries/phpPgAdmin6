<?php

/**
 * PHPPgAdmin 6.1.3
 */

\defined('BASE_PATH') || \define('BASE_PATH', \dirname(__DIR__));

\defined('THEME_PATH') || \define('THEME_PATH', \dirname(__DIR__) . '/assets/themes');
// Enforce PHP environment
\ini_set('arg_separator.output', '&amp;');
\defined('ADODB_ERROR_HANDLER_TYPE') || \define('ADODB_ERROR_HANDLER_TYPE', \E_USER_ERROR);
\defined('ADODB_ERROR_HANDLER') || \define('ADODB_ERROR_HANDLER', '\PHPPgAdmin\ADOdbException::adodb_throw');

function getAppInstance(): \Slim\App
{
    $subfolder = '';
    // Check to see if the configuration file exists, if not, explain
    if (!\file_exists(\dirname(__DIR__) . '/config.inc.php')) {
        die('Configuration error: Copy config.inc.example.php to config.inc.php and edit appropriately.');
    }
    $conf = [];

    include_once \dirname(__DIR__) . '/config.inc.php';

    if (isset($conf['subfolder']) && \is_string($conf['subfolder'])) {
        $subfolder = $conf['subfolder'];
    } elseif (\PHP_SAPI === 'cli-server' || \PHP_SAPI === 'cli') {
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
