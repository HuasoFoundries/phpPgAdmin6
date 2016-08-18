<?php
use \PHPPgAdmin\Decorators\Decorator;
/**
 * Manage domains in a database
 *
 * $Id: domains.php,v 1.34 2007/09/13 13:41:01 ioguix Exp $
 */

/**
 * Generate XML for the browser tree.
 */
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$domains = $data->getDomains();

	$reqvars = $misc->getRequestVars('domain');

	$attrs = [
		'text' => Decorator::field('domname'),
		'icon' => 'Domain',
		'toolTip' => Decorator::field('domcomment'),
		'action' => Decorator::actionurl('domains.php',
			$reqvars,
			[
				'action' => 'properties',
				'domain' => Decorator::field('domname'),
			]
		),
	];

	return $misc->printTree($domains, $attrs, 'domains', false);
}
