<?php

/**
 * Manage sequences in a database.
 *
 * $Id: sequences.php,v 1.49 2007/12/15 22:21:54 ioguix Exp $
 */

// Include application functions
$do_render = false;
if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
    $do_render = true;
}
$controller = new \PHPPgAdmin\Controller\SequencesController($container);
if ($do_render) {
    $controller->render();
}
