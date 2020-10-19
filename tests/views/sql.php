<?php

/**
 * PHPPgAdmin 6.1.3
 */

// Include application functions

function sqlFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\SqlController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
