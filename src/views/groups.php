<?php

/**
 * Manage groups in a database cluster
 *
 * $Id: groups.php,v 1.27 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$group_controller = new \PHPPgAdmin\Controller\GroupController($app);

$misc->printHeader($lang['strgroups']);
$misc->printBody();

switch ($action) {
	case 'add_member':
		$group_controller->doAddMember();
		break;
	case 'drop_member':
		if (isset($_REQUEST['drop'])) {
			$group_controller->doDropMember(false);
		} else {
			$group_controller->doProperties();
		}

		break;
	case 'confirm_drop_member':
		$group_controller->doDropMember(true);
		break;
	case 'save_create':
		if (isset($_REQUEST['cancel'])) {
			$group_controller->doDefault();
		} else {
			$group_controller->doSaveCreate();
		}

		break;
	case 'create':
		$group_controller->doCreate();
		break;
	case 'drop':
		if (isset($_REQUEST['drop'])) {
			$group_controller->doDrop(false);
		} else {
			$group_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$group_controller->doDrop(true);
		break;
	case 'save_edit':
		$group_controller->doSaveEdit();
		break;
	case 'edit':
		$group_controller->doEdit();
		break;
	case 'properties':
		$group_controller->doProperties();
		break;
	default:
		$group_controller->doDefault();
		break;
}

$misc->printFooter();
