<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

function acinsertFactory($container)
{
    $do_render = false;
    $do_render = false;
    echo 'Will instance controller';
    $controller = new \PHPPgAdmin\Controller\AcinsertController($container);

    if ($do_render) {
        $controller->render();
    }

    return $controller;
}
