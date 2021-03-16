<?php

/**
 * PHPPgAdmin6
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function acinsertFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\AcinsertController($container);
}
