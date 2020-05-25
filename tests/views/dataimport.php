<?php

/**
 * PHPPgAdmin 6.0.0
 */

/**
 * PHPPgAdmin v6.0.0-RC9.
 *
 * @param mixed $container
 */
function dataimportFactory($container)
{
    return new \PHPPgAdmin\Controller\DataimportController($container);
}
