<?php

/**
 * Alternative SQL editing window
 *
 * $Id: history.php,v 1.3 2008/01/10 19:37:07 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$history_controller = new \PHPPgAdmin\Controller\HistoryController($container);

switch ($action) {
	case 'confdelhistory':
		$history_controller->doDelHistory($_REQUEST['queryid'], true);
		break;
	case 'delhistory':
		if (isset($_POST['yes'])) {
			$history_controller->doDelHistory($_REQUEST['queryid'], false);
		}

		$history_controller->doDefault();
		break;
	case 'confclearhistory':
		$history_controller->doClearHistory(true);
		break;
	case 'clearhistory':
		if (isset($_POST['yes'])) {
			$history_controller->doClearHistory(false);
		}

		$history_controller->doDefault();
		break;
	case 'download':
		$history_controller->doDownloadHistory();
		break;
	default:
		$history_controller->doDefault();
}

// Set the name of the window
$misc->setWindowName('history');
$misc->printFooter();
