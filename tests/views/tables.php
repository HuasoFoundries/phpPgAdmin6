<?php

/**
 * PHPPgAdmin 6.0.1
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function tablesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\TablesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
