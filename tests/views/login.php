<?php

/**
 * PHPPgAdmin 6.1.3
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function loginFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\LoginController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
