<?php

/**
 * Manage databases within a server
 *
 * $Id: all_db.php,v 1.59 2007/10/17 21:40:19 ioguix Exp $
 */

// Include application functions
if (!defined('BASE_PATH')) {
	require_once '../lib.inc.php';
}

$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
if (!isset($msg)) {
	$msg = '';
}

$all_db_controller = new \PHPPgAdmin\Controller\AllDBController($app);

$misc->printHeader($lang['strdatabases']);
$misc->printBody();

switch ($action) {
	case 'export':
		$all_db_controller->doExport();
		break;
	case 'save_create':
		if (isset($_POST['cancel'])) {
			$all_db_controller->doDefault();
		} else {
			$all_db_controller->doSaveCreate();
		}

		break;
	case 'create':
		$all_db_controller->doCreate();
		break;
	case 'drop':
		if (isset($_REQUEST['drop'])) {
			$all_db_controller->doDrop(false);
		} else {
			$all_db_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		doDrop(true);
		break;
	case 'alter':
		if (isset($_POST['oldname']) && isset($_POST['newname']) && !isset($_POST['cancel'])) {
			$all_db_controller->doAlter(false);
		} else {
			$all_db_controller->doDefault();
		}

		break;
	case 'confirm_alter':
		$all_db_controller->doAlter(true);
		break;
	default:
		$all_db_controller->doDefault();

		break;
}

$misc->printFooter();
