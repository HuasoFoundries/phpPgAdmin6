<?php

/**
 * PHPPgAdmin 6.1.2
 */

// Include application functions

function displayFactory($container)
{
    $do_render = false;

    if (!\defined('BASE_PATH')) {
        require_once '../../src/lib.inc.php';
        $do_render = true;
    }
    $controller = new \PHPPgAdmin\Controller\DisplayController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
