<?php

/**
 * PHPPgAdmin 6.0.0
 */

// Include application functions
function operatorsFactory($container)
{
    $do_render = false;

    if (!\defined('BASE_PATH')) {
        require_once '../../src/lib.inc.php';
        $do_render = true;
    }
    $controller = new \PHPPgAdmin\Controller\OperatorsController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
