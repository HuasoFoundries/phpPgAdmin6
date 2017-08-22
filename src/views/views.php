<?php

/**
 * Manage views in a database
 *
 * $Id: views.php,v 1.75 2007/12/15 22:57:43 ioguix Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\ViewsController($container);
if ($do_render) {
    $controller->render();
}
