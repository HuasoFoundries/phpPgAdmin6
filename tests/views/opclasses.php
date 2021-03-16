<?php

/**
 * PHPPgAdmin6
 */

// Include application functions
function opclassesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\OpclassesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
