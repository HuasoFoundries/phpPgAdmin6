<?php

/**
 * PHPPgAdmin 6.1.3
 */

// Include application functions

function historyFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\HistoryController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
