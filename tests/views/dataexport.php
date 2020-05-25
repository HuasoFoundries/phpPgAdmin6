<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

function dataexportFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\DataexportController($container);
}
