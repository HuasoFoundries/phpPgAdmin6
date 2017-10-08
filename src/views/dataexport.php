<?php

/**
 * Does an export to the screen or as a download.  This checks to
 * see if they have pg_dump set up, and will use it if possible.
 *
 * $Id: dataexport.php,v 1.26 2007/07/12 19:26:22 xzilla Exp $
 */
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\DataexportController($container);
if ($do_render) {
    $controller->render();
}
