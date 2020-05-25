<?php

/**
 * PHPPgAdmin 6.0.0
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function browserFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\BrowserController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
