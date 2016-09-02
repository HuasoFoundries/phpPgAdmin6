<?php

/**
 * Manage types in a database
 *
 * $Id: types.php,v 1.42 2007/11/30 15:25:23 soranzo Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$type_controller = new \PHPPgAdmin\Controller\TypeController($container);
$type_controller->render();
