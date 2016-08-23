<?php

/**
 * List constraints on a table
 *
 * $Id: constraints.php,v 1.56 2007/12/31 16:46:07 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$misc->printHeader($lang['strtables'] . ' - ' . $_REQUEST['table'] . ' - ' . $lang['strconstraints'],
	"<script src=\"/js/indexes.js\" type=\"text/javascript\"></script>");

if ($action == 'add_unique_key' || $action == 'save_add_unique_key'
	|| $action == 'add_primary_key' || $action == 'save_add_primary_key'
	|| $action == 'add_foreign_key' || $action == 'save_add_foreign_key') {
	echo "<body onload=\"init();\">";
} else {
	$misc->printBody();
}

$constraint_controller = new \PHPPgAdmin\Controller\ConstraintController($app);

switch ($action) {
	case 'add_foreign_key':
		$constraint_controller->addForeignKey(1);
		break;
	case 'save_add_foreign_key':
		if (isset($_POST['cancel'])) {
			$constraint_controller->doDefault();
		} else {
			$constraint_controller->addForeignKey($_REQUEST['stage']);
		}

		break;
	case 'add_unique_key':
		$constraint_controller->addPrimaryOrUniqueKey('unique', true);
		break;
	case 'save_add_unique_key':
		if (isset($_POST['cancel'])) {
			$constraint_controller->doDefault();
		} else {
			$constraint_controller->addPrimaryOrUniqueKey('unique', false);
		}

		break;
	case 'add_primary_key':
		$constraint_controller->addPrimaryOrUniqueKey('primary', true);
		break;
	case 'save_add_primary_key':
		if (isset($_POST['cancel'])) {
			$constraint_controller->doDefault();
		} else {
			$constraint_controller->addPrimaryOrUniqueKey('primary', false);
		}

		break;
	case 'add_check':
		$constraint_controller->addCheck(true);
		break;
	case 'save_add_check':
		if (isset($_POST['cancel'])) {
			$constraint_controller->doDefault();
		} else {
			$constraint_controller->addCheck(false);
		}

		break;
	case 'save_create':
		$constraint_controller->doSaveCreate();
		break;
	case 'create':
		$constraint_controller->doCreate();
		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$constraint_controller->doDrop(false);
		} else {
			$constraint_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$constraint_controller->doDrop(true);
		break;
	default:
		$constraint_controller->doDefault();
		break;
}

$misc->printFooter();
