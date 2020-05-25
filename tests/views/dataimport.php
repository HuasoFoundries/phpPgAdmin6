<?php

/**
 * PHPPgAdmin 6.0.1
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function dataimportFactory($container)
{
    return new \PHPPgAdmin\Controller\DataimportController($container);
}
