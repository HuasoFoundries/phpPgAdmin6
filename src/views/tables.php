<?php

/**
 * List tables in a database
 *
 * $Id: tables.php,v 1.112 2008/06/16 22:38:46 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';

$misc->printHeader($lang['strtables']);
$misc->printBody();

$table_controller = new \PHPPgAdmin\Controller\TableController($app);

switch ($action) {
	case 'create':
		if (isset($_POST['cancel'])) {
			$table_controller->doDefault();
		} else {
			$table_controller->doCreate();
		}

		break;
	case 'createlike':
		$table_controller->doCreateLike(false);
		break;
	case 'confcreatelike':
		if (isset($_POST['cancel'])) {
			$table_controller->doDefault();
		} else {
			$table_controller->doCreateLike(true);
		}

		break;
	case 'selectrows':
		if (!isset($_POST['cancel'])) {
			$table_controller->doSelectRows(false);
		} else {
			$table_controller->doDefault();
		}

		break;
	case 'confselectrows':
		$table_controller->doSelectRows(true);
		break;
	case 'insertrow':
		if (!isset($_POST['cancel'])) {
			$table_controller->doInsertRow(false);
		} else {
			$table_controller->doDefault();
		}

		break;
	case 'confinsertrow':
		$table_controller->doInsertRow(true);
		break;
	case 'empty':
		if (isset($_POST['empty'])) {
			$table_controller->doEmpty(false);
		} else {
			$table_controller->doDefault();
		}

		break;
	case 'confirm_empty':
		$table_controller->doEmpty(true);
		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$table_controller->doDrop(false);
		} else {
			$table_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$table_controller->doDrop(true);
		break;
	default:
		if ($table_controller->adminActions($action, 'table') === false) {
			$table_controller->doDefault();
		}

		break;
}

$misc->printFooter();
