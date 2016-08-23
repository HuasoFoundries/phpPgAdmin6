<?php

/**
 * Manage aggregates in a database
 *
 * $Id: aggregates.php,v 1.27 2008/01/19 13:46:15 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$misc->printHeader($lang['straggregates']);
$misc->printBody();

$aggregate_controller = new \PHPPgAdmin\Controller\AggregateController($app);

switch ($action) {
	case 'create':
		$aggregate_controller->doCreate();
		break;
	case 'save_create':
		if (isset($_POST['cancel'])) {
			$aggregate_controller->doDefault();
		} else {
			$aggregate_controller->doSaveCreate();
		}

		break;
	case 'alter':
		$aggregate_controller->doAlter();
		break;
	case 'save_alter':
		if (isset($_POST['alter'])) {
			$aggregate_controller->doSaveAlter();
		} else {
			$aggregate_controller->doProperties();
		}

		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$aggregate_controller->doDrop(false);
		} else {
			$aggregate_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$aggregate_controller->doDrop(true);
		break;
	default:
		$aggregate_controller->doDefault();
		break;
	case 'properties':
		$aggregate_controller->doProperties();
		break;
}

$misc->printFooter();
