<?php

/**
 * Manage users in a database cluster.
 *
 * $Id: users.php,v 1.40 2008/02/25 17:20:44 xzilla Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\UsersController($container);
if ($do_render) {
    $controller->render();
}
