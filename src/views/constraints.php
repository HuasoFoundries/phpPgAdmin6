<?php

/**
 * List constraints on a table
 *
 * $Id: constraints.php,v 1.56 2007/12/31 16:46:07 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$constraint_controller = new \PHPPgAdmin\Controller\ConstraintController($container);
$constraint_controller->render();
