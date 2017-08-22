<?php

/**
 * Manage groups in a database cluster
 *
 * $Id: groups.php,v 1.27 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\GroupsController($container);
if ($do_render) {
    $controller->render();
}
