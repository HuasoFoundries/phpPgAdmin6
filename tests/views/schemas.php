<?php

/**
 * PHPPgAdmin 6.0.0
 */

// Include application functions
function schemasFactory($container)
{
    $do_render = false;
    $do_render = false;
    $controller = new \PHPPgAdmin\Controller\SchemasController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
