<?php

/**
 * PHPPgAdmin v6.0.0-beta.41
 */
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\PrivilegesController($container);
if ($do_render) {
    $controller->render();
}
