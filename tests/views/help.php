<?php

/**
 * PHPPgAdmin6
 */

// Include application functions

function helpFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\HelpController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
