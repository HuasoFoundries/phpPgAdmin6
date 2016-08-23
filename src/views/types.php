<?php

/**
 * Manage types in a database
 *
 * $Id: types.php,v 1.42 2007/11/30 15:25:23 soranzo Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$type_controller = new \PHPPgAdmin\Controller\TypeController($container);

$misc->printHeader($lang['strtypes']);
$misc->printBody();

switch ($action) {
	case 'create_comp':
		if (isset($_POST['cancel'])) {
			$type_controller->doDefault();
		} else {
			$type_controller->doCreateComposite();
		}

		break;
	case 'create_enum':
		if (isset($_POST['cancel'])) {
			$type_controller->doDefault();
		} else {
			$type_controller->doCreateEnum();
		}

		break;
	case 'save_create':
		if (isset($_POST['cancel'])) {
			$type_controller->doDefault();
		} else {
			$type_controller->doSaveCreate();
		}

		break;
	case 'create':
		$type_controller->doCreate();
		break;
	case 'drop':
		if (isset($_POST['cancel'])) {
			$type_controller->doDefault();
		} else {
			$type_controller->doDrop(false);
		}

		break;
	case 'confirm_drop':
		$type_controller->doDrop(true);
		break;
	case 'properties':
		$type_controller->doProperties();
		break;
	default:
		$type_controller->doDefault();
		break;
}

$misc->printFooter();
