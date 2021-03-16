<?php

/**
 * PHPPgAdmin6
 */

// Include application functions
function castsFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\CastsController($container);
}
