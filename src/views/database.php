<?php

/**
 * Manage schemas within a database.
 *
 * $Id: database.php,v 1.104 2007/11/30 06:04:43 xzilla Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\DatabaseController($container);
if ($do_render) {
    $controller->render();
}
