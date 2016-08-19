<?php
use \PHPPgAdmin\Decorators\Decorator;
/**
 * Manage views in a database
 *
 * $Id: views.php,v 1.75 2007/12/15 22:57:43 ioguix Exp $
 */

/**
 * Generate XML for the browser tree.
 */
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$views = $data->getViews();

	$reqvars = $misc->getRequestVars('view');

	$attrs = [
		'text' => Decorator::field('relname'),
		'icon' => 'View',
		'iconAction' => Decorator::url('display.php', $reqvars, ['view' => Decorator::field('relname')]),
		'toolTip' => Decorator::field('relcomment'),
		'action' => Decorator::redirecturl('redirect.php', $reqvars, ['view' => Decorator::field('relname')]),
		'branch' => Decorator::branchurl('views/subtree', $reqvars,
			[
				'view' => Decorator::field('relname'),
			]
		),
	];

	return $misc->printTree($views, $attrs, 'views', false);
}

function doSubTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$tabs    = $misc->getNavTabs('view');
	$items   = $misc->adjustTabsForTree($tabs);
	$reqvars = $misc->getRequestVars('view');

	$attrs = [
		'text' => Decorator::field('title'),
		'icon' => Decorator::field('icon'),
		'action' => Decorator::actionurl(Decorator::field('url'), $reqvars, Decorator::field('urlvars'), ['view' => $_REQUEST['view']]),
		'branch' => Decorator::ifempty(
			Decorator::field('branch'), '', Decorator::branchurl(Decorator::field('url'), Decorator::field('urlvars'), $reqvars,
				[
					'action' => 'tree',
					'view' => $_REQUEST['view'],
				]
			)
		),
	];

	return $misc->printTree($items, $attrs, 'view', false);
}
