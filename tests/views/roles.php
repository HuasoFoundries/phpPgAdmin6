<?php

/**
 * PHPPgAdmin 6.0.1
 */

// Include application functions

function rolesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\RolesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
