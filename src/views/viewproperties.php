<?php

/**
 * List views in a database
 *
 * $Id: viewproperties.php,v 1.34 2007/12/11 14:17:17 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$viewproperty_controller = new \PHPPgAdmin\Controller\ViewPropertyController($app);

$misc->printHeader($lang['strviews'] . ' - ' . $_REQUEST['view']);
$misc->printBody();

switch ($action) {
	case 'save_edit':
		if (isset($_POST['cancel'])) {
			$viewproperty_controller->doDefinition();
		} else {
			$viewproperty_controller->doSaveEdit();
		}

		break;
	case 'edit':
		$viewproperty_controller->doEdit();
		break;
	case 'export':
		$viewproperty_controller->doExport();
		break;
	case 'definition':
		$viewproperty_controller->doDefinition();
		break;
	case 'properties':
		if (isset($_POST['cancel'])) {
			$viewproperty_controller->doDefault();
		} else {
			$viewproperty_controller->doProperties();
		}

		break;
	case 'alter':
		if (isset($_POST['alter'])) {
			$viewproperty_controller->doAlter(false);
		} else {
			$viewproperty_controller->doDefault();
		}

		break;
	case 'confirm_alter':
		doAlter(true);
		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$viewproperty_controller->doDrop(false);
		} else {
			$viewproperty_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$viewproperty_controller->doDrop(true);
		break;
	default:
		$viewproperty_controller->doDefault();
		break;
}

$misc->printFooter();
