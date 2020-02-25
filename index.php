<?php

/**
 * PHPPgAdmin v6.0.0-RC8
 */

// This section is made to be able to parse requests coming from PHP Builtin webserver
if (PHP_SAPI === 'cli-server') {
    $will_redirect = false;
    // @todo is PHP_SELF is not set, chances are REQUEST_URI won't either
    $req_uri = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI'];
    if (substr($req_uri, 0, 10) === '/index.php') {
        $will_redirect = true;
        $req_uri       = substr($req_uri, 10);
    }
    $filePath     = realpath(ltrim($req_uri, '/'));
    $new_location = 'Location: http://' . $_SERVER['HTTP_HOST'] . $req_uri;

    if ($filePath && // 1. check that filepath is set
        is_readable($filePath) && // 2. and references a readable file/folder
        strpos($filePath, BASE_PATH . DIRECTORY_SEPARATOR) === 0 && // 3. And is inside this folder
        $filePath != BASE_PATH . DIRECTORY_SEPARATOR . 'index.php' && // 4. discard circular references to index.php
        substr(basename($filePath), 0, 1) != '.' // 5. don't serve dotfiles
    ) {
        if (strtolower(substr($filePath, -4)) == '.php') {
            // php file; serve through interpreter
            include $filePath;

            return;
        }
        if ($will_redirect) {
            header($new_location, true, 301);

            return;
        }
        // asset file; serve from filesystem
        return false;
    }
}

require_once __DIR__ . '/src/router.php';

function getSubjectParams($subject)
{
    $vars          = [];
    $common_params = [];

    if (array_key_exists('server', $_REQUEST)) {
        $common_params['server'] = $_REQUEST['server'];
    }

    if (array_key_exists('database', $_REQUEST)) {
        $common_params['database'] = $_REQUEST['database'];
    }

    if (array_key_exists('schema', $_REQUEST)) {
        $common_params['schema'] = $_REQUEST['schema'];
    }

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
                'subject' => 'database',
                'subject' => 'server',
                'server'  => $_REQUEST['server'],

            ]];

            break;
        case 'role':
            $vars = ['params' => [
                'subject' => 'role',
                'server'  => $_REQUEST['server'], 'action' => 'properties', 'rolename' => $_REQUEST['rolename'],

            ]];

            break;
        case 'database':
            $vars = ['params' => array_merge($common_params, [
                'subject' => 'database',

            ])];

            break;
        case 'schema':
            $vars = ['params' => array_merge($common_params, [
                'subject' => 'schema',

            ])];

            break;
        case 'table':
            $vars = ['params' => array_merge($common_params, [
                'subject' => 'table',

                'table'   => $_REQUEST['table'],

            ])];

            break;
        case 'selectrows':
            $vars = [
                'url'    => 'tables',
                'params' => array_merge($common_params, [
                    'subject' => 'table',

                    'table'   => $_REQUEST['table'],
                    'action'  => 'confselectrows',

                ])];

            break;
        case 'view':
            $vars = ['params' => array_merge($common_params, [
                'subject' => 'view',

                'view'    => $_REQUEST['view'],

            ])];

            break;
        case 'matview':
            $vars = ['params' => array_merge($common_params, [
                'subject' => 'matview',

                'matview' => $_REQUEST['matview'],

            ])];

            break;
        case 'fulltext':
        case 'ftscfg':
            $vars = ['params' => array_merge($common_params, [
                'subject' => 'fulltext',

                'action'  => 'viewconfig',
                'ftscfg'  => $_REQUEST['ftscfg'],

            ])];

            break;
        case 'function':
            $vars = ['params' => array_merge($common_params, [
                'subject'      => 'function',

                'function'     => $_REQUEST['function'],
                'function_oid' => $_REQUEST['function_oid'],

            ])];

            break;
        case 'aggregate':
            $vars = ['params' => array_merge($common_params, [
                'subject'  => 'aggregate',

                'action'   => 'properties',
                'aggrname' => $_REQUEST['aggrname'],
                'aggrtype' => $_REQUEST['aggrtype'],

            ])];

            break;
        case 'column':
            if (isset($_REQUEST['table'])) {
                $vars = ['params' => array_merge($common_params, [
                    'subject' => 'column',

                    'table'   => $_REQUEST['table'],
                    'column'  => $_REQUEST['column'],

                ])];
            } elseif (isset($_REQUEST['view'])) {
                $vars = ['params' => array_merge($common_params, [
                    'subject' => 'column',

                    'view'    => $_REQUEST['view'],
                    'column'  => $_REQUEST['column'],

                ])];
            } elseif (isset($_REQUEST['matview'])) {
                $vars = ['params' => array_merge($common_params, [
                    'subject' => 'column',

                    'matview' => $_REQUEST['matview'],
                    'column'  => $_REQUEST['column'],

                ])];
            }

            break;

        default:
            return false;
    }

    if (!isset($vars['url'])) {
        $vars['url'] = SUBFOLDER . '/redirect';
    }
    if ($vars['url'] == SUBFOLDER . '/redirect' && isset($vars['params']['subject'])) {
        $vars['url'] = SUBFOLDER . '/redirect/' . $vars['params']['subject'];
        unset($vars['params']['subject']);
    }

    return $vars;
}
