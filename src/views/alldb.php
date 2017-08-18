<?php

/**
 * Manage databases within a server
 *
 * $Id: alldb.php,v 1.59 2007/10/17 21:40:19 ioguix Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\AlldbController($container);
if ($do_render) {
    $controller->render();
}
