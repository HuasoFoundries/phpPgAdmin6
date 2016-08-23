<?php

/**
 * List tables in a database
 *
 * $Id: tblproperties.php,v 1.92 2008/01/19 13:46:15 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$tableproperty_controller = new \PHPPgAdmin\Controller\TablePropertyController($container);

$misc->printHeader($lang['strtables'] . ' - ' . $_REQUEST['table']);
$misc->printBody();

switch ($action) {
	case 'alter':
		if (isset($_POST['alter'])) {
			$tableproperty_controller->doSaveAlter();
		} else {
			$tableproperty_controller->doDefault();
		}

		break;
	case 'confirm_alter':
		$tableproperty_controller->doAlter();
		break;
	case 'import':
		$tableproperty_controller->doImport();
		break;
	case 'export':
		$tableproperty_controller->doExport();
		break;
	case 'add_column':
		if (isset($_POST['cancel'])) {
			$tableproperty_controller->doDefault();
		} else {
			$tableproperty_controller->doAddColumn();
		}

		break;
	case 'properties':
		if (isset($_POST['cancel'])) {
			$tableproperty_controller->doDefault();
		} else {
			$tableproperty_controller->doProperties();
		}

		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$tableproperty_controller->doDrop(false);
		} else {
			$tableproperty_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$tableproperty_controller->doDrop(true);
		break;
	default:
		$tableproperty_controller->doDefault();
		break;
}

$misc->printFooter();
