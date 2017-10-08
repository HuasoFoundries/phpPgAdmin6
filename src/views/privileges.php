<?php

/**
 * Manage privileges in a database.
 *
 * $Id: privileges.php,v 1.45 2007/09/13 13:41:01 ioguix Exp $
 */
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\PrivilegesController($container);
if ($do_render) {
    $controller->render();
}
