<?php

/**
 * List triggers on a table
 *
 * $Id: triggers.php,v 1.37 2007/09/19 14:42:12 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$trigger_controller = new \PHPPgAdmin\Controller\TriggerController($app);

$misc->printHeader($lang['strtables'] . ' - ' . $_REQUEST['table'] . ' - ' . $lang['strtriggers']);
$misc->printBody();

switch ($action) {
	case 'alter':
		if (isset($_POST['alter'])) {
			$trigger_controller->doSaveAlter();
		} else {
			$trigger_controller->doDefault();
		}

		break;
	case 'confirm_alter':
		$trigger_controller->doAlter();
		break;
	case 'confirm_enable':
		$trigger_controller->doEnable(true);
		break;
	case 'confirm_disable':
		$trigger_controller->doDisable(true);
		break;
	case 'save_create':
		if (isset($_POST['cancel'])) {
			$trigger_controller->doDefault();
		} else {
			$trigger_controller->doSaveCreate();
		}

		break;
	case 'create':
		$trigger_controller->doCreate();
		break;
	case 'drop':
		if (isset($_POST['yes'])) {
			$trigger_controller->doDrop(false);
		} else {
			$trigger_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$trigger_controller->doDrop(true);
		break;
	case 'enable':
		if (isset($_POST['yes'])) {
			$trigger_controller->doEnable(false);
		} else {
			$trigger_controller->doDefault();
		}

		break;
	case 'disable':
		if (isset($_POST['yes'])) {
			$trigger_controller->doDisable(false);
		} else {
			$trigger_controller->doDefault();
		}

		break;
	default:
		$trigger_controller->doDefault();
		break;
}

$misc->printFooter();
