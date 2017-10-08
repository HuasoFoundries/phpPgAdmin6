<?php

/**
 * Alternative SQL editing window.
 *
 * $Id: history.php,v 1.3 2008/01/10 19:37:07 xzilla Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\HistoryController($container);
if ($do_render) {
    $controller->render();
}
