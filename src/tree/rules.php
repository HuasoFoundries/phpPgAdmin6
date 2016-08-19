<?php
use \PHPPgAdmin\Decorators\Decorator;

/**
 * List rules on a table OR view
 *
 * $Id: rules.php,v 1.33 2007/08/31 18:30:11 ioguix Exp $
 */
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$rules = $data->getRules($_REQUEST[$_REQUEST['subject']]);

	$reqvars = $misc->getRequestVars($_REQUEST['subject']);

	$attrs = [
		'text' => Decorator::field('rulename'),
		'icon' => 'Rule',
	];

	return $misc->printTree($rules, $attrs, 'rules', false);
}
