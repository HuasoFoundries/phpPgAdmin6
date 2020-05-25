<?php

/**
 * PHPPgAdmin 6.0.1
 */

// Include application functions

function indexesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\IndexesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
