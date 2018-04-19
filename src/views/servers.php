<?php

/**
 * PHPPgAdmin v6.0.0-beta.39
 */
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\ServersController($container, true);
if ($do_render) {
    $controller->render();

    //$new_location = str_replace('.php', '', $container->environment->get('REQUEST_URI'));
    //header('HTTP/1.1 301 Moved Permanently');
    //header("Location: $new_location");
}
