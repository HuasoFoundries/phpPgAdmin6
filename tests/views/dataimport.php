<?php

/**
 * PHPPgAdmin 6.1.0
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function dataimportFactory($container)
{
    return new \PHPPgAdmin\Controller\DataimportController($container);
}
