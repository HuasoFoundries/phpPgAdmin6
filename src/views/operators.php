<?php

/**
 * Manage operators in a database
 *
 * $Id: operators.php,v 1.29 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$operator_controller = new \PHPPgAdmin\Controller\OperatorController($container);
$operator_controller->render();