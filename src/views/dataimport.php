<?php

/**
 * PHPPgAdmin v6.0.0-beta.39
 */
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\DataimportController($container);
if ($do_render) {
    $controller->render();
}
