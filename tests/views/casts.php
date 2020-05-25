<?php

/**
 * PHPPgAdmin 6.0.1
 */

// Include application functions
function castsFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\CastsController($container);
}
