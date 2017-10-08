<?php

/**
 * Manage domains in a database.
 *
 * $Id: domains.php,v 1.34 2007/09/13 13:41:01 ioguix Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\DomainsController($container);
if ($do_render) {
    $controller->render();
}
