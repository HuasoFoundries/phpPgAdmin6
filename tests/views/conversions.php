<?php

/**
 * PHPPgAdmin 6.1.0
 */

// Include application functions

function conversionsFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\ConversionsController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
