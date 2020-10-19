<?php

/**
 * PHPPgAdmin 6.1.0
 */

// Include application functions

function domainsFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\DomainsController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
