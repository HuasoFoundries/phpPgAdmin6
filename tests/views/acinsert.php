<?php

/**
 * PHPPgAdmin 6.1.0
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function acinsertFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\AcinsertController($container);
}
