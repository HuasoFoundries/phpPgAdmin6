<?php

/**
 * Manage sequences in a database
 *
 * $Id: sequences.php,v 1.49 2007/12/15 22:21:54 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$sequence_controller = new \PHPPgAdmin\Controller\SequenceController($container);

// Print header
$misc->printHeader($lang['strsequences']);
$misc->printBody();

switch ($action) {
	case 'create':
		$sequence_controller->doCreateSequence();
		break;
	case 'save_create_sequence':
		if (isset($_POST['create'])) {
			$sequence_controller->doSaveCreateSequence();
		} else {
			$sequence_controller->doDefault();
		}

		break;
	case 'properties':
		$sequence_controller->doProperties();
		break;
	case 'drop':
		if (isset($_POST['drop'])) {
			$sequence_controller->doDrop(false);
		} else {
			$sequence_controller->doDefault();
		}

		break;
	case 'confirm_drop':
		$sequence_controller->doDrop(true);
		break;
	case 'restart':
		$sequence_controller->doRestart();
		break;
	case 'reset':
		$sequence_controller->doReset();
		break;
	case 'nextval':
		$sequence_controller->doNextval();
		break;
	case 'setval':
		if (isset($_POST['setval'])) {
			$sequence_controller->doSaveSetval();
		} else {
			$sequence_controller->doDefault();
		}

		break;
	case 'confirm_setval':
		$sequence_controller->doSetval();
		break;
	case 'alter':
		if (isset($_POST['alter'])) {
			$sequence_controller->doSaveAlter();
		} else {
			$sequence_controller->doDefault();
		}

		break;
	case 'confirm_alter':
		$sequence_controller->doAlter();
		break;
	default:
		$sequence_controller->doDefault();
		break;
}

// Print footer
$misc->printFooter();
