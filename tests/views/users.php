<?php

/**
 * PHPPgAdmin 6.0.1
 */

// Include application functions
function usersFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\UsersController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
