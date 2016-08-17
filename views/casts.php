<?php

/**
 * Manage casts in a database
 *
 * $Id: casts.php,v 1.16 2007/09/25 16:08:05 ioguix Exp $
 */

// Include application functions
require_once '../includes/lib.inc.php';

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
			'field' => field('castsource'),
		],
		'target_type' => [
			'title' => $lang['strtargettype'],
			'field' => field('casttarget'),
		],
		'function' => [
			'title' => $lang['strfunction'],
			'field' => field('castfunc'),
			'params' => ['null' => $lang['strbinarycompat']],
		],
		'implicit' => [
			'title' => $lang['strimplicit'],
			'field' => field('castcontext'),
			'type' => 'callback',
			'params' => ['function' => 'renderCastContext', 'align' => 'center'],
		],
		'comment' => [
			'title' => $lang['strcomment'],
			'field' => field('castcomment'),
		],
	];

	$actions = [];

	echo $misc->printTable($casts, $columns, $actions, 'casts-casts', $lang['strnocasts']);
}

/**
 * Generate XML for the browser tree.
 */
function doTree() {
	global $misc, $data;

	$casts = $data->getCasts();

	$proto = concat(field('castsource'), ' AS ', field('casttarget'));

	$attrs = [
		'text' => $proto,
		'icon' => 'Cast',
	];

	$misc->printTree($casts, $attrs, 'casts');
	exit;
}

if ($action == 'tree') {
	doTree();
}

$misc->printHeader($lang['strcasts']);
$misc->printBody();

switch ($action) {
	case 'tree':
		doTree();
		break;
	default:
		doDefault();
		break;
}

$misc->printFooter();
