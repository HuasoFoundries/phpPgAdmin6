<?php

/**
 * Manage conversions in a database
 *
 * $Id: conversions.php,v 1.15 2007/08/31 18:30:10 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
if (!isset($msg)) {
	$msg = '';
}

/**
 * Show default list of conversions in the database
 */
function doDefault($msg = '') {
	global $data, $conf, $misc, $database;
	global $lang;

	$misc->printTrail('schema');
	$misc->printTabs('schema', 'conversions');
	$misc->printMsg($msg);

	$conversions = $data->getconversions();

	$columns = [
		'conversion' => [
			'title' => $lang['strname'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('conname'),
		],
		'source_encoding' => [
			'title' => $lang['strsourceencoding'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('conforencoding'),
		],
		'target_encoding' => [
			'title' => $lang['strtargetencoding'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('contoencoding'),
		],
		'default' => [
			'title' => $lang['strdefault'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('condefault'),
			'type' => 'yesno',
		],
		'comment' => [
			'title' => $lang['strcomment'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('concomment'),
		],
	];

	$actions = [];

	echo $misc->printTable($conversions, $columns, $actions, 'conversions-conversions', $lang['strnoconversions']);
}

$misc->printHeader($lang['strconversions']);
$misc->printBody();

switch ($action) {
	default:
		doDefault();
		break;
}

$misc->printFooter();
