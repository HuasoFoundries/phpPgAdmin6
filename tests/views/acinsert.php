<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

function acinsertFactory($container) { $do_render = false ;
if (!defined('BASE_PATH')) {
    require_once '../../src/lib.inc.php';
    $do_render = true;
}
echo 'Will instance controller';
$controller = new \PHPPgAdmin\Controller\AcinsertController($container);
if ($do_render) {
    $controller->render();
}
return $controller; }
