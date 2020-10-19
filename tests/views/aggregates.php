<?php

/**
 * PHPPgAdmin 6.1.2
 */

// Include application functions
function aggregatesFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\AggregatesController($container);
}
