<?php

/**
 * List constraints on a table
 *
 * $Id: constraints.php,v 1.56 2007/12/31 16:46:07 xzilla Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\ConstraintsController($container);
if ($do_render) {
    $controller->render();
}
