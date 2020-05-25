<?php

/**
 * PHPPgAdmin 6.0.1
 */

// Include application functions

function constraintsFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\ConstraintsController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
