<?php

/**
 * List Columns properties in tables
 *
 * $Id: colproperties.php
 */

// Include application functions
require_once '../lib.inc.php';

if (isset($_REQUEST['table'])) {
	$tableName = &$_REQUEST['table'];
} elseif (isset($_REQUEST['view'])) {
	$tableName = &$_REQUEST['view'];
} else {
	die($lang['strnotableprovided']);
}

$misc->printHeader($lang['strtables'] . ' - ' . $tableName);
$misc->printBody();

$colproperty_controller = new \PHPPgAdmin\Controller\ColPropertyController($app);

if (isset($_REQUEST['view'])) {
	$colproperty_controller->doDefault(null, false);
} else {
	switch ($action) {
		case 'properties':
			if (isset($_POST['cancel'])) {
				$colproperty_controller->doDefault();
			} else {
				$colproperty_controller->doAlter();
			}

			break;
		default:
			$colproperty_controller->doDefault();
			break;
	}
}

$misc->printFooter();
