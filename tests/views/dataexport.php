<?php

/**
 * PHPPgAdmin 6.0.1
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function dataexportFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\DataexportController($container);
}
