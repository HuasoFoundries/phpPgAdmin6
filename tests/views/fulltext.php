<?php

/**
 * PHPPgAdmin6
 */

// Include application functions

function fulltextFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\FulltextController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
