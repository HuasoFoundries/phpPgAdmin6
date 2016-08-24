<?php

/**
 * Manage operators in a database
 *
 * $Id: operators.php,v 1.29 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$operator_controller = new \PHPPgAdmin\Controller\OperatorController($container);

$misc->printHeader($lang['stroperators']);
$misc->printBody();

switch ($action) {
	case 'save_create':
		if (isset($_POST['cancel'])) {
			$operator_controller->doDefault();
		} else {
			$operator_controller->doSaveCreate();
		}

		break;
	case 'create':
		doCreate();
		break;
	case 'drop':
		if (isset($_POST['cancel'])) {
			$operator_controller->doDefault();
		} else {
			$operator_controller->doDrop(false);
		}

		break;
	case 'confirm_drop':
		$operator_controller->doDrop(true);
		break;
	case 'properties':
		$operator_controller->doProperties();
		break;
	default:
		$operator_controller->doDefault();
		break;
}

$misc->printFooter();
