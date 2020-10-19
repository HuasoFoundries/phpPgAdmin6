<?php

/**
 * PHPPgAdmin 6.1.2
 */

// Include application functions

function rulesFactory($container)
{
    $do_render = false;

    return new \PHPPgAdmin\Controller\RulesController($container);
}
