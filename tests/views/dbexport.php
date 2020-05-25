<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

function dbexportFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\DbexportController($container);
}
