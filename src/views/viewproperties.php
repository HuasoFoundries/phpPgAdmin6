<?php

/**
 * List views in a database
 *
 * $Id: viewproperties.php,v 1.34 2007/12/11 14:17:17 ioguix Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\ViewpropertiesController($container);
if ($do_render) {
    $controller->render();
}
