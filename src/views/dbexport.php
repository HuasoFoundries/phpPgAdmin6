<?php
/**
 * Does an export of a database, schema, or table (via pg_dump)
 * to the screen or as a download.
 *
 * $Id: dbexport.php,v 1.22 2007/03/25 03:15:09 xzilla Exp $
 */

$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\DbexportController($container);
if ($do_render) {
    $controller->render();
}
