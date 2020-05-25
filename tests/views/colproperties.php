<?php

/**
 * PHPPgAdmin 6.0.1
 */

// Include application functions
function colpropertiesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\ColpropertiesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
