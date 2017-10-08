<?php

/**
 * Manage types in a database.
 *
 * $Id: types.php,v 1.42 2007/11/30 15:25:23 soranzo Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\TypesController($container);
if ($do_render) {
    $controller->render();
}
