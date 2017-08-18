<?php

/**
 * List triggers on a table
 *
 * $Id: triggers.php,v 1.37 2007/09/19 14:42:12 ioguix Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\TriggersController($container);
if ($do_render) {
    $controller->render();
}
