<?php

/**
 * PHPPgAdmin 6.0.0
 */

// Include application functions

function tblpropertiesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\TblpropertiesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
