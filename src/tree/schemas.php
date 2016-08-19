<?php
use \PHPPgAdmin\Decorators\Decorator;

/**
 * Manage schemas in a database
 *
 * $Id: schemas.php,v 1.22 2007/12/15 22:57:43 ioguix Exp $
 */

/**
 * Generate XML for the browser tree.
 */
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$schemas = $data->getSchemas();

	$reqvars = $misc->getRequestVars('schema');

	$attrs = [
		'text' => Decorator::field('nspname'),
		'icon' => 'Schema',
		'toolTip' => Decorator::field('nspcomment'),
		'action' => Decorator::redirecturl('redirect.php',
			$reqvars,
			[
				'subject' => 'schema',
				'schema' => Decorator::field('nspname'),
			]
		),
		'branch' => Decorator::branchurl('schemas.php',
			$reqvars,
			[
				'action' => 'subtree',
				'schema' => Decorator::field('nspname'),
			]
		),
	];

	return $misc->printTree($schemas, $attrs, 'schemas', false);

}

function doSubTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$tabs = $misc->getNavTabs('schema');

	$items = $misc->adjustTabsForTree($tabs);

	$reqvars = $misc->getRequestVars('schema');

	$attrs = [
		'text' => Decorator::field('title'),
		'icon' => Decorator::field('icon'),
		'action' => Decorator::actionurl(Decorator::field('url'),
			$reqvars,
			Decorator::field('urlvars', [])
		),
		'branch' => Decorator::branchurl(Decorator::field('url'),
			$reqvars,
			Decorator::field('urlvars'),
			['action' => 'tree']
		),
	];

	return $misc->printTree($items, $attrs, 'schema', false);

}
