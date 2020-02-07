<?php

/**
 * PHPPgAdmin v6.0.0-RC7
 */

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../../src/lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\DbexportController($container);
if ($do_render) {
    $controller->render();
}
