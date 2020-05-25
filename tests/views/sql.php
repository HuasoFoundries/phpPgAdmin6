<?php

/**
 * PHPPgAdmin 6.0.0
 */

// Include application functions

function sqlFactory($container)
{
    $do_render = false;
    $do_render = false;
    $controller = new \PHPPgAdmin\Controller\SqlController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
