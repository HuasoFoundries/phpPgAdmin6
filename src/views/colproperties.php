<?php

/**
 * List Columns properties in tables.
 *
 * $Id: colproperties.php
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\ColpropertiesController($container);
if ($do_render) {
    $controller->render();
}
