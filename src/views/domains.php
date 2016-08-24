<?php

/**
 * Manage domains in a database
 *
 * $Id: domains.php,v 1.34 2007/09/13 13:41:01 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$domain_controller = new \PHPPgAdmin\Controller\DomainController($container);

$misc->printHeader($lang['strdomains']);
$misc->printBody();

switch ($action) {
	case 'add_check':
		$domain_controller->addCheck(true);
		break;
	case 'save_add_check':
		if (isset($_POST['cancel'])) {
			$domain_controller->doProperties();
		} else {
			$domain_controller->addCheck(false);
		}

		break;
	case 'drop_con':
		if (isset($_POST['drop'])) {
			$domain_controller->doDropConstraint(false);
		} else {
			$domain_controller->doProperties();
		}

		break;
	case 'confirm_drop_con':
		$domain_controller->doDropConstraint(true);
		break;
	case 'save_create':
		if (isset($_POST['cancel'])) {
			$domain_controller->doDefault();
		} else {
			$domain_controller->doSaveCreate();
		}

		break;
	case 'create':
		$domain_controller->doCreate();
		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$domain_controller->doDrop(false);
		} else {
			$domain_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$domain_controller->doDrop(true);
		break;
	case 'save_alter':
		if (isset($_POST['alter'])) {
			$domain_controller->doSaveAlter();
		} else {
			$domain_controller->doProperties();
		}

		break;
	case 'alter':
		$domain_controller->doAlter();
		break;
	case 'properties':
		$domain_controller->doProperties();
		break;
	default:
		$domain_controller->doDefault();
		break;
}

$misc->printFooter();
