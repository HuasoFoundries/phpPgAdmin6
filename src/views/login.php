<?php

/**
 * Login screen
 *
 * $Id: login.php,v 1.38 2007/09/04 19:39:48 ioguix Exp $
 */

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\LoginController($container, true);
if ($do_render) {
    $controller->render();
}
