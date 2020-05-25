<?php

/**
 * PHPPgAdmin 6.0.0
 */

/**
 * PHPPgAdmin v6.0.0-RC9.
 *
 * @param mixed $container
 */
function dbexportFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\DbexportController($container);
}
