<?php

/**
 * Manage opclasss in a database
 *
 * $Id: opclasses.php,v 1.10 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
if (!isset($msg)) {
	$msg = '';
}

/**
 * Show default list of opclasss in the database
 */
function doDefault($msg = '') {
	global $data, $conf, $misc;
	global $lang;

	$misc->printTrail('schema');
	$misc->printTabs('schema', 'opclasses');
	$misc->printMsg($msg);

	$opclasses = $data->getOpClasses();

	$columns = [
		'accessmethod' => [
			'title' => $lang['straccessmethod'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('amname'),
		],
		'opclass' => [
			'title' => $lang['strname'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('opcname'),
		],
		'type' => [
			'title' => $lang['strtype'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('opcintype'),
		],
		'default' => [
			'title' => $lang['strdefault'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('opcdefault'),
			'type' => 'yesno',
		],
		'comment' => [
			'title' => $lang['strcomment'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('opccomment'),
		],
	];

	$actions = [];

	echo $misc->printTable($opclasses, $columns, $actions, 'opclasses-opclasses', $lang['strnoopclasses']);
}

$misc->printHeader($lang['stropclasses']);
$misc->printBody();

switch ($action) {
	default:
		doDefault();
		break;
}

$misc->printFooter();
