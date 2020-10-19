<?php

/**
 * PHPPgAdmin 6.1.3
 */

// Include application functions
function alldbFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\AlldbController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
