<?php

/**
 * PHPPgAdmin6
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function privilegesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\PrivilegesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
