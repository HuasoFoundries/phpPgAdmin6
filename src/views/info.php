<?php

/**
 * List extra information on a table
 *
 * $Id: info.php,v 1.14 2007/05/28 17:30:32 ioguix Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\InfoController($container);
if ($do_render) {
    $controller->render();
}
