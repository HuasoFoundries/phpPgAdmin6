<?php

/**
 * Manage schemas in a database.
 *
 * $Id: schemas.php,v 1.22 2007/12/15 22:57:43 ioguix Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\SchemasController($container);
if ($do_render) {
    $controller->render();
}
