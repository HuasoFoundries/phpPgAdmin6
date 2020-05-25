<?php

/**
 * PHPPgAdmin 6.0.0
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function dbexportFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\DbexportController($container);
}
