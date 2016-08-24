<?php

/**
 * List rules on a table OR view
 *
 * $Id: rules.php,v 1.33 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$rule_controller = new \PHPPgAdmin\Controller\RuleController($container);

// Different header if we're view rules or table rules
$misc->printHeader($_REQUEST[$_REQUEST['subject']] . ' - ' . $lang['strrules']);
$misc->printBody();

switch ($action) {
	case 'create_rule':
		$rule_controller->createRule(true);
		break;
	case 'save_create_rule':
		if (isset($_POST['cancel'])) {
			$rule_controller->doDefault();
		} else {
			$rule_controller->createRule(false);
		}

		break;
	case 'drop':
		if (isset($_POST['yes'])) {
			$rule_controller->doDrop(false);
		} else {
			$rule_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$rule_controller->doDrop(true);
		break;
	default:
		$rule_controller->doDefault();
		break;
}

$misc->printFooter();
