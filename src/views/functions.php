<?php

/**
 * Manage functions in a database
 *
 * $Id: functions.php,v 1.78 2008/01/08 22:50:29 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$function_controller = new \PHPPgAdmin\Controller\FunctionController($container);

$misc->printHeader($lang['strfunctions']);
$misc->printBody();

switch ($action) {
	case 'save_create':
		if (isset($_POST['cancel'])) {
			$function_controller->doDefault();
		} else {
			$function_controller->doSaveCreate();
		}

		break;
	case 'create':
		$function_controller->doCreate();
		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$function_controller->doDrop(false);
		} else {
			$function_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$function_controller->doDrop(true);
		break;
	case 'save_edit':
		if (isset($_POST['cancel'])) {
			$function_controller->doDefault();
		} else {
			$function_controller->doSaveEdit();
		}

		break;
	case 'edit':
		$function_controller->doEdit();
		break;
	case 'properties':
		$function_controller->doProperties();
		break;
	default:
		$function_controller->doDefault();
		break;
}

$misc->printFooter();
