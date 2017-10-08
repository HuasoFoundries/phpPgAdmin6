<?php

/**
 * Manage operators in a database.
 *
 * $Id: operators.php,v 1.29 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\OperatorsController($container);
if ($do_render) {
    $controller->render();
}
