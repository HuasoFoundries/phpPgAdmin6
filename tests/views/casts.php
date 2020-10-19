<?php

/**
 * PHPPgAdmin 6.1.0
 */

// Include application functions
function castsFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\CastsController($container);
}
