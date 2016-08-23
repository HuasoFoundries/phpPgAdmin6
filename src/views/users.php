<?php

/**
 * Manage users in a database cluster
 *
 * $Id: users.php,v 1.40 2008/02/25 17:20:44 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$user_controller = new \PHPPgAdmin\Controller\UserController($app);

$misc->printHeader($lang['strusers']);
$misc->printBody();

switch ($action) {
	case 'changepassword':
		if (isset($_REQUEST['ok'])) {
			$user_controller->doChangePassword(false);
		} else {
			$user_controller->doAccount();
		}

		break;
	case 'confchangepassword':
		$user_controller->doChangePassword(true);
		break;
	case 'account':
		$user_controller->doAccount();
		break;
	case 'save_create':
		if (isset($_REQUEST['cancel'])) {
			$user_controller->doDefault();
		} else {
			$user_controller->doSaveCreate();
		}

		break;
	case 'create':
		$user_controller->doCreate();
		break;
	case 'drop':
		if (isset($_REQUEST['cancel'])) {
			$user_controller->doDefault();
		} else {
			$user_controller->doDrop(false);
		}

		break;
	case 'confirm_drop':
		$user_controller->doDrop(true);
		break;
	case 'save_edit':
		if (isset($_REQUEST['cancel'])) {
			$user_controller->doDefault();
		} else {
			$user_controller->doSaveEdit();
		}

		break;
	case 'edit':
		$user_controller->doEdit();
		break;
	default:
		$user_controller->doDefault();
		break;
}

$misc->printFooter();
