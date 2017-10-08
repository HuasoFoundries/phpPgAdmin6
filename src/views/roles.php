<?php

/**
 * Manage roles in a database cluster.
 *
 * $Id: roles.php,v 1.13 2008/03/21 15:32:57 xzilla Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\RolesController($container);
if ($do_render) {
    $controller->render();
}
