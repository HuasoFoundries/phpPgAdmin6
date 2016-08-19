<?php

/**
 * Manage casts in a database
 *
 * $Id: casts.php,v 1.16 2007/09/25 16:08:05 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
if (!isset($msg)) {
	$msg = '';
}

/**
 * Show default list of casts in the database
 */
function doDefault($msg = '') {
	global $data, $misc, $database;
	global $lang;

	function renderCastContext($val) {
		global $lang;
		switch ($val) {
			case 'e':return $lang['strno'];
			case 'a':return $lang['strinassignment'];
			default:return $lang['stryes'];
		}
	}

	$misc->printTrail('database');
	$misc->printTabs('database', 'casts');
	$misc->printMsg($msg);

	$casts = $data->getCasts();

	$columns = [
		'source_type' => [
			'title' => $lang['strsourcetype'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('castsource'),
		],
		'target_type' => [
			'title' => $lang['strtargettype'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('casttarget'),
		],
		'function' => [
			'title' => $lang['strfunction'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('castfunc'),
			'params' => ['null' => $lang['strbinarycompat']],
		],
		'implicit' => [
			'title' => $lang['strimplicit'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('castcontext'),
			'type' => 'callback',
			'params' => ['function' => 'renderCastContext', 'align' => 'center'],
		],
		'comment' => [
			'title' => $lang['strcomment'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('castcomment'),
		],
	];

	$actions = [];

	echo $misc->printTable($casts, $columns, $actions, 'casts-casts', $lang['strnocasts']);
}

$misc->printHeader($lang['strcasts']);
$misc->printBody();

switch ($action) {

	default:
		doDefault();
		break;
}

$misc->printFooter();
