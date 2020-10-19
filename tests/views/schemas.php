<?php

/**
 * PHPPgAdmin 6.1.3
 */

// Include application functions
function schemasFactory($container)
{
    $do_render = false;

    $controller = new \PHPPgAdmin\Controller\SchemasController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
