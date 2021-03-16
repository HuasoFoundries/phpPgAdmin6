<?php

/**
 * PHPPgAdmin6
 */

// Include application functions
function sequencesFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\SequencesController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
