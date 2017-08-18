<?php

/**
 * Manage functions in a database
 *
 * $Id: functions.php,v 1.78 2008/01/08 22:50:29 xzilla Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\FunctionsController($container);
if ($do_render) {
    $controller->render();
}
