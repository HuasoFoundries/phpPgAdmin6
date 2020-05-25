<?php

/**
 * PHPPgAdmin 6.0.0
 */

// Include application functions
function aggregatesFactory($container)
{
    $do_render = false;

    if (!\defined('BASE_PATH')) {
        require_once '../../src/lib.inc.php';
        $do_render = true;
    }
    $controller = new \PHPPgAdmin\Controller\AggregatesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
