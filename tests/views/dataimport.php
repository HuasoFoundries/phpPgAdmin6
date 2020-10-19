<?php

/**
 * PHPPgAdmin 6.1.3
 */

/**
 * @param Psr\Container\ContainerInterface $container
 */
function dataimportFactory($container)
{
    return new \PHPPgAdmin\Controller\DataimportController($container);
}
