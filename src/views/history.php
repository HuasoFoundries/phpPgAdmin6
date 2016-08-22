<?php

/**
 * Alternative SQL editing window
 *
 * $Id: history.php,v 1.3 2008/01/10 19:37:07 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';

function doDefault() {
	global $misc, $lang;

	$onchange = "onchange=\"location.href='/views/history.php?server=' + encodeURI(server.options[server.selectedIndex].value) + '&amp;database=' + encodeURI(database.options[database.selectedIndex].value) + '&amp;'\"";

	$misc->printHeader($lang['strhistory']);

	// Bring to the front always
	echo "<body onload=\"window.focus();\">\n";

	echo "<form action=\"/src/views/history.php\" method=\"post\">\n";
	$misc->printConnection($onchange);
	echo "</form><br />";

	if (!isset($_REQUEST['database'])) {
		echo "<p>{$lang['strnodatabaseselected']}</p>\n";
		return;
	}

	if (isset($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']])) {

		$history = new \PHPPgAdmin\ArrayRecordSet($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']]);

		//Kint::dump($history);
		$columns = [
			'query' => [
				'title' => $lang['strsql'],
				'field' => \PHPPgAdmin\Decorators\Decorator::field('query'),
			],
			'paginate' => [
				'title' => $lang['strpaginate'],
				'field' => \PHPPgAdmin\Decorators\Decorator::field('paginate'),
				'type' => 'yesno',
			],
			'actions' => [
				'title' => $lang['stractions'],
			],
		];

		$actions = [
			'run' => [
				'content' => $lang['strexecute'],
				'attr' => [
					'href' => [
						'url' => 'sql.php',
						'urlvars' => [
							'subject' => 'history',
							'nohistory' => 't',
							'queryid' => \PHPPgAdmin\Decorators\Decorator::field('queryid'),
							'paginate' => \PHPPgAdmin\Decorators\Decorator::field('paginate'),
						],
					],
					'target' => 'detail',
				],
			],
			'remove' => [
				'content' => $lang['strdelete'],
				'attr' => [
					'href' => [
						'url' => 'history.php',
						'urlvars' => [
							'action' => 'confdelhistory',
							'queryid' => \PHPPgAdmin\Decorators\Decorator::field('queryid'),
						],
					],
				],
			],
		];

		echo $misc->printTable($history, $columns, $actions, 'history-history', $lang['strnohistory']);
	} else {
		echo "<p>{$lang['strnohistory']}</p>\n";
	}

	$navlinks = [
		'refresh' => [
			'attr' => [
				'href' => [
					'url' => 'history.php',
					'urlvars' => [
						'action' => 'history',
						'server' => $_REQUEST['server'],
						'database' => $_REQUEST['database'],
					],
				],
			],
			'content' => $lang['strrefresh'],
		],
	];

	if (isset($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']])
		&& count($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']])) {
		$navlinks['download'] = [
			'attr' => [
				'href' => [
					'url' => 'history.php',
					'urlvars' => [
						'action' => 'download',
						'server' => $_REQUEST['server'],
						'database' => $_REQUEST['database'],
					],
				],
			],
			'content' => $lang['strdownload'],
		];
		$navlinks['clear'] = [
			'attr' => [
				'href' => [
					'url' => 'history.php',
					'urlvars' => [
						'action' => 'confclearhistory',
						'server' => $_REQUEST['server'],
						'database' => $_REQUEST['database'],
					],
				],
			],
			'content' => $lang['strclearhistory'],
		];
	}

	$misc->printNavLinks($navlinks, 'history-history', get_defined_vars());
}

function doDelHistory($qid, $confirm) {
	global $misc, $lang;

	if ($confirm) {
		$misc->printHeader($lang['strhistory']);

		// Bring to the front always
		echo "<body onload=\"window.focus();\">\n";

		echo "<h3>{$lang['strdelhistory']}</h3>\n";
		echo "<p>{$lang['strconfdelhistory']}</p>\n";

		echo "<pre>", htmlentities($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']][$qid]['query'], ENT_QUOTES, 'UTF-8'), "</pre>";
		echo "<form action=\"/src/views/history.php\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"action\" value=\"delhistory\" />\n";
		echo "<input type=\"hidden\" name=\"queryid\" value=\"$qid\" />\n";
		echo $misc->form;
		echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />\n";
		echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />\n";
		echo "</form>\n";
	} else {
		unset($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']][$qid]);
	}

}

function doClearHistory($confirm) {
	global $misc, $lang;

	if ($confirm) {
		$misc->printHeader($lang['strhistory']);

		// Bring to the front always
		echo "<body onload=\"window.focus();\">\n";

		echo "<h3>{$lang['strclearhistory']}</h3>\n";
		echo "<p>{$lang['strconfclearhistory']}</p>\n";

		echo "<form action=\"/src/views/history.php\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"action\" value=\"clearhistory\" />\n";
		echo $misc->form;
		echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />\n";
		echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />\n";
		echo "</form>\n";
	} else {
		unset($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']]);
	}

}

function doDownloadHistory() {
	header('Content-Type: application/download');
	$datetime = date('YmdHis');
	header("Content-Disposition: attachment; filename=history{$datetime}.sql");

	foreach ($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']] as $queries) {
		$query = rtrim($queries['query']);
		echo $query;
		if (substr($query, -1) != ';') {
			echo ';';
		}

		echo "\n";
	}

	exit;
}

switch ($action) {
	case 'confdelhistory':
		doDelHistory($_REQUEST['queryid'], true);
		break;
	case 'delhistory':
		if (isset($_POST['yes'])) {
			doDelHistory($_REQUEST['queryid'], false);
		}

		doDefault();
		break;
	case 'confclearhistory':
		doClearHistory(true);
		break;
	case 'clearhistory':
		if (isset($_POST['yes'])) {
			doClearHistory(false);
		}

		doDefault();
		break;
	case 'download':
		doDownloadHistory();
		break;
	default:
		doDefault();
}

// Set the name of the window
$misc->setWindowName('history');
$misc->printFooter();
