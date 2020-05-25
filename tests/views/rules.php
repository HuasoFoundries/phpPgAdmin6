<?php

/**
 * PHPPgAdmin 6.0.0
 */

// Include application functions

function rulesFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\RulesController($container);
}
