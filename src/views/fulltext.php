<?php

/**
 * Manage fulltext configurations, dictionaries and mappings.
 *
 * $Id: fulltext.php,v 1.6 2008/03/17 21:35:48 ioguix Exp $
 */

// Include application functions

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\FulltextController($container);
if ($do_render) {
    $controller->render();
}
