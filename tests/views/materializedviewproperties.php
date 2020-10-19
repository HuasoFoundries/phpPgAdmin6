<?php

/**
 * PHPPgAdmin 6.1.2
 */

// Include application functions
function materializedviewpropertiesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\MaterializedviewpropertiesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
