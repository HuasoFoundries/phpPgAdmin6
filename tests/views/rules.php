<?php

/**
 * PHPPgAdmin 6.0.1
 */

// Include application functions

function rulesFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\RulesController($container);
}
