<?php

/**
 * PHPPgAdmin6
 */

// Include application functions

function triggersFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\TriggersController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
