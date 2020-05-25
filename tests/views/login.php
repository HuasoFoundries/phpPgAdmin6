<?php

/**
 * PHPPgAdmin 6.0.0
 */

/**
 * PHPPgAdmin v6.0.0-RC9.
 *
 * @param mixed $container
 */
function loginFactory($container)
{
    $do_render = false;
    $do_render = false;
    $controller = new \PHPPgAdmin\Controller\LoginController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
