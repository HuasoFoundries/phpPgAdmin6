<?php

/**
 * PHPPgAdmin 6.1.0
 */

// Include application functions
function viewsFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\ViewsController($container);
}
