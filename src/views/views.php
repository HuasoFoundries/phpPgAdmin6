<?php

/**
 * Manage views in a database
 *
 * $Id: views.php,v 1.75 2007/12/15 22:57:43 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$view_controller = new \PHPPgAdmin\Controller\ViewController($app);

$misc->printHeader($lang['strviews']);
$misc->printBody();

switch ($action) {
	case 'selectrows':
		if (!isset($_REQUEST['cancel'])) {
			$view_controller->doSelectRows(false);
		} else {
			$view_controller->doDefault();
		}

		break;
	case 'confselectrows':
		$view_controller->doSelectRows(true);
		break;
	case 'save_create_wiz':
		if (isset($_REQUEST['cancel'])) {
			$view_controller->doDefault();
		} else {
			$view_controller->doSaveCreateWiz();
		}

		break;
	case 'wiz_create':
		doWizardCreate();
		break;
	case 'set_params_create':
		if (isset($_POST['cancel'])) {
			$view_controller->doDefault();
		} else {
			$view_controller->doSetParamsCreate();
		}

		break;
	case 'save_create':
		if (isset($_REQUEST['cancel'])) {
			$view_controller->doDefault();
		} else {
			$view_controller->doSaveCreate();
		}

		break;
	case 'create':
		doCreate();
		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$view_controller->doDrop(false);
		} else {
			$view_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$view_controller->doDrop(true);
		break;
	default:
		$view_controller->doDefault();
		break;
}

$misc->printFooter();
