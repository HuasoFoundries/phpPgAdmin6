<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../../src/lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\TblpropertiesController($container);
if ($do_render) {
    $controller->render();
}
