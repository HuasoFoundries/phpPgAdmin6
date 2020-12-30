<?php

/**
 * PHPPgAdmin 6.1.3
 */

use PHPPgAdmin\ContainerUtils;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * PHPPgAdmin 6.1.3.
 */
function getAppInstance(): App
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
    $app = ContainerUtils::getAppInstance($conf);

    return $app;
}

function containerInstance(): ContainerUtils
{
    $app = getAppInstance();
    $container = $app->getContainer();

    if (!$container instanceof ContainerUtils) {
        \trigger_error('App Container must be an instance of \\Slim\\Container', \E_USER_ERROR);
    }

    return  $container;
}

function requestInstance(): Request
{
    return  \containerInstance()->request;
}

function responseInstance(): Response
{
    return \containerInstance()->response;
}
