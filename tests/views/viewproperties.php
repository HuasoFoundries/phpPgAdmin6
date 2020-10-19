<?php

/**
 * PHPPgAdmin 6.1.0
 */

// Include application functions
function viewpropertiesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\ViewpropertiesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
