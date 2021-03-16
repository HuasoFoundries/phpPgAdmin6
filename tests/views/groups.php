<?php

/**
 * PHPPgAdmin6
 */

// Include application functions

function groupsFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\GroupsController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
