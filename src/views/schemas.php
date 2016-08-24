<?php

/**
 * Manage schemas in a database
 *
 * $Id: schemas.php,v 1.22 2007/12/15 22:57:43 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$schema_controller = new \PHPPgAdmin\Controller\SchemaController($container);

$misc->printHeader($lang['strschemas']);
$misc->printBody();

if (isset($_POST['cancel'])) {
	$action = '';
}

switch ($action) {
	case 'create':
		if (isset($_POST['create'])) {
			$schema_controller->doSaveCreate();
		} else {
			$schema_controller->doCreate();
		}

		break;
	case 'alter':
		if (isset($_POST['alter'])) {
			$schema_controller->doSaveAlter();
		} else {
			$schema_controller->doAlter();
		}

		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$schema_controller->doDrop(false);
		} else {
			$schema_controller->doDrop(true);
		}

		break;
	case 'export':
		$schema_controller->doExport();
		break;
	default:
		$schema_controller->doDefault();
		break;
}

$misc->printFooter();
