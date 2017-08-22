<?php

/**
 * Does an import to a particular table from a text file
 *
 * $Id: dataimport.php,v 1.11 2007/01/22 16:33:01 soranzo Exp $
 */

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\DataimportController($container);
if ($do_render) {
    $controller->render();
}
