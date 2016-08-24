<?php

/**
 * List indexes on a table
 *
 * $Id: indexes.php,v 1.46 2008/01/08 22:50:29 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$index_controller = new \PHPPgAdmin\Controller\IndexController($container);

$misc->printHeader($lang['strindexes'], "<script src=\"/js/indexes.js\" type=\"text/javascript\"></script>");

if ($action == 'create_index' || $action == 'save_create_index') {
	echo "<body onload=\"init();\">";
} else {
	$misc->printBody();
}

switch ($action) {
	case 'cluster_index':
		if (isset($_POST['cluster'])) {
			$index_controller->doClusterIndex(false);
		} else {
			$index_controller->doDefault();
		}

		break;
	case 'confirm_cluster_index':
		$index_controller->doClusterIndex(true);
		break;
	case 'reindex':
		$index_controller->doReindex();
		break;
	case 'save_create_index':
		if (isset($_POST['cancel'])) {
			$index_controller->doDefault();
		} else {
			$index_controller->doSaveCreateIndex();
		}

		break;
	case 'create_index':
		$index_controller->doCreateIndex();
		break;
	case 'drop_index':
		if (isset($_POST['drop'])) {
			$index_controller->doDropIndex(false);
		} else {
			$index_controller->doDefault();
		}

		break;
	case 'confirm_drop_index':
		$index_controller->doDropIndex(true);
		break;
	default:
		$index_controller->doDefault();
		break;
}

$misc->printFooter();
