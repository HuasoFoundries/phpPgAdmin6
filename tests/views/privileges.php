<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

function privilegesFactory($container)
{
    $do_render = false;
    $do_render = false;
    $controller = new \PHPPgAdmin\Controller\PrivilegesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
