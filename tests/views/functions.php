<?php

/**
 * PHPPgAdmin 6.1.0
 */

// Include application functions

function functionsFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\FunctionsController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
