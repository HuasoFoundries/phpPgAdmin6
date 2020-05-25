<?php

/**
 * PHPPgAdmin 6.0.0
 */

// Include application functions
//require_once '../../src/lib.inc.php';
function sqleditFactory($container)
{
    $do_render = false;
    $do_render = false;
    $controller = new \PHPPgAdmin\Controller\SqleditController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
