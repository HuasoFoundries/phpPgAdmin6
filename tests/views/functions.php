<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

// Include application functions

function functionsFactory($container)
{
    $do_render = false;
    $do_render = false;
    $controller = new \PHPPgAdmin\Controller\FunctionsController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
