<?php

/**
 * PHPPgAdmin 6.1.3
 */

// Include application functions
function viewsFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\ViewsController($container);
}
