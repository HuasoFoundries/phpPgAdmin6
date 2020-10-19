<?php

/**
 * PHPPgAdmin 6.1.3
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function dataexportFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\DataexportController($container);
}
