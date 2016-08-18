<?php
use \PHPPgAdmin\Decorators\Decorator;
/**
 * List tables in a database
 *
 * $Id: tables.php,v 1.112 2008/06/16 22:38:46 ioguix Exp $
 */
/**
 * Generate XML for the browser tree.
 */
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	\PC::debug($misc->getDatabase(), 'getDatabase');

	$tables = $data->getTables();

	$reqvars = $misc->getRequestVars('table');

	$attrs = [
		'text' => Decorator::field('relname'),
		'icon' => 'Table',
		'iconAction' => Decorator::url('display.php',
			$reqvars,
			['table' => Decorator::field('relname')]
		),
		'toolTip' => Decorator::field('relcomment'),
		'action' => Decorator::redirecturl('redirect.php',
			$reqvars,
			['table' => Decorator::field('relname')]
		),
		'branch' => Decorator::branchurl('tables.php',
			$reqvars,
			[
				'action' => 'subtree',
				'table' => Decorator::field('relname'),
			]
		),
	];

	return $misc->printTree($tables, $attrs, 'tables', false);
}

function doSubTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$tabs    = $misc->getNavTabs('table');
	$items   = $misc->adjustTabsForTree($tabs);
	$reqvars = $misc->getRequestVars('table');

	$attrs = [
		'text' => Decorator::field('title'),
		'icon' => Decorator::field('icon'),
		'action' => Decorator::actionurl(
			Decorator::field('url'),
			$reqvars,
			Decorator::field('urlvars'),
			['table' => $_REQUEST['table']]
		),
		'branch' => Decorator::ifempty(
			Decorator::field('branch'), '', Decorator::branchurl(Decorator::field('url'), $reqvars, [
				'action' => 'tree',
				'table' => $_REQUEST['table'],
			]
			)
		),
	];

	return $misc->printTree($items, $attrs, 'table', false);
}
