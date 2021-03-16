<?php

/**
 * PHPPgAdmin6
 */

// Include application functions
function tablespacesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\TablespacesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
