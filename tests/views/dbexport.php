<?php

/**
 * PHPPgAdmin 6.1.3
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function dbexportFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\DbexportController($container);
}
