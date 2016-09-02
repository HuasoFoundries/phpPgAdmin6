<?php

/**
 * List indexes on a table
 *
 * $Id: indexes.php,v 1.46 2008/01/08 22:50:29 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$index_controller = new \PHPPgAdmin\Controller\IndexController($container);

$index_controller->render();
