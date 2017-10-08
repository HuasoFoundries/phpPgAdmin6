<?php

/**
 * Alternative SQL editing window.
 *
 * $Id: sqledit.php,v 1.40 2008/01/10 19:37:07 xzilla Exp $
 */

// Include application functions
//require_once '../lib.inc.php';
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\SqleditController($container);
if ($do_render) {
    $controller->render();
}
