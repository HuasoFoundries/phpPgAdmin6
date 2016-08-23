<?php

/**
 * Manage schemas within a database
 *
 * $Id: database.php,v 1.104 2007/11/30 06:04:43 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$database_controller = new \PHPPgAdmin\Controller\DatabaseController($container);

if ($action == 'refresh_locks') {
	$database_controller->currentLocks(true);
}

if ($action == 'refresh_processes') {
	$database_controller->currentProcesses(true);
}

/* normal flow */
if ($action == 'locks' or $action == 'processes') {
	$scripts .= "<script src=\"/js/database.js\" type=\"text/javascript\"></script>";

	$refreshTime = $conf['ajax_refresh'] * 1000;

	$scripts .= "<script type=\"text/javascript\">\n";
	$scripts .= "var Database = {\n";
	$scripts .= "ajax_time_refresh: {$refreshTime},\n";
	$scripts .= "str_start: {text:'{$lang['strstart']}',icon: '" . $misc->icon('Execute') . "'},\n";
	$scripts .= "str_stop: {text:'{$lang['strstop']}',icon: '" . $misc->icon('Stop') . "'},\n";
	$scripts .= "load_icon: '" . $misc->icon('Loading') . "',\n";
	$scripts .= "server:'{$_REQUEST['server']}',\n";
	$scripts .= "dbname:'{$_REQUEST['database']}',\n";
	$scripts .= "action:'refresh_{$action}',\n";
	$scripts .= "errmsg: '" . str_replace("'", "\'", $lang['strconnectionfail']) . "'\n";
	$scripts .= "};\n";
	$scripts .= "</script>\n";
}

$misc->printHeader($lang['strdatabase'], $scripts);
$misc->printBody();

switch ($action) {
	case 'find':
		if (isset($_REQUEST['term'])) {
			$database_controller->doFind(false);
		} else {
			$database_controller->doFind(true);
		}

		break;
	case 'sql':
		$database_controller->doSQL();
		break;
	case 'variables':
		$database_controller->doVariables();
		break;
	case 'processes':
		$database_controller->doProcesses();
		break;
	case 'locks':
		$database_controller->doLocks();
		break;
	case 'export':
		$database_controller->doExport();
		break;
	case 'signal':
		$database_controller->doSignal();
		break;
	default:
		if (adminActions($action, 'database') === false) {
			$database_controller->doSQL();
		}

		break;
}

$misc->printFooter();
