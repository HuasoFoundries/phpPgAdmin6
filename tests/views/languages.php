<?php

/**
 * PHPPgAdmin 6.1.3
 */

// Include application functions
function languagesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\LanguagesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
