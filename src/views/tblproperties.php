<?php

/**
 * List tables in a database
 *
 * $Id: tblproperties.php,v 1.92 2008/01/19 13:46:15 ioguix Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\TblpropertiesController($container);
if ($do_render) {
    $controller->render();
}
