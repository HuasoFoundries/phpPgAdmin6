<?php

/**
 * Help page redirection/browsing.
 *
 * $Id: help.php,v 1.3 2006/12/31 16:21:26 soranzo Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\HelpController($container);
if ($do_render) {
    $controller->render();
}
