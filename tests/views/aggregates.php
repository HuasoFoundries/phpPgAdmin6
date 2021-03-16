<?php

/**
 * PHPPgAdmin6
 */

// Include application functions
function aggregatesFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\AggregatesController($container);
}
