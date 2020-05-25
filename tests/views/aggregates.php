<?php

/**
 * PHPPgAdmin 6.0.0
 */

// Include application functions
function aggregatesFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\AggregatesController($container);
}
