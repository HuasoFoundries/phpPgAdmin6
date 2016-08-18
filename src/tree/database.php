<?php
use \PHPPgAdmin\Decorators\Decorator;
/**
 * Manage schemas within a database
 *
 * $Id: database.php,v 1.104 2007/11/30 06:04:43 xzilla Exp $
 */
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$reqvars = $misc->getRequestVars('database');

	$tabs = $misc->getNavTabs('database');

	$items = $misc->adjustTabsForTree($tabs);
	\PC::debug($reqvars, 'reqvars');
	$attrs = [
		'text' => Decorator::field('title'),
		'icon' => Decorator::field('icon'),
		'action' => Decorator::actionurl(Decorator::field('url'), $reqvars, Decorator::field('urlvars', [])),
		'branch' => Decorator::branchurl(Decorator::field('url'), $reqvars, Decorator::field('urlvars'), ['action' => 'tree']),
	];

	return $misc->printTree($items, $attrs, 'database', false);

}
