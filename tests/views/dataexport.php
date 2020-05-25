<?php

/**
 * PHPPgAdmin 6.0.0
 */

/**
 * PHPPgAdmin v6.0.0-RC9.
 *
 * @param mixed $container
 */
function dataexportFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\DataexportController($container);
}
