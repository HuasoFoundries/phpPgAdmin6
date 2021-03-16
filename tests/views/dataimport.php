<?php

/**
 * PHPPgAdmin6
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function dataimportFactory($container)
{
    return new \PHPPgAdmin\Controller\DataimportController($container);
}
