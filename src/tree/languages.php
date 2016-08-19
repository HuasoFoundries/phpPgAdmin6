<?php
use \PHPPgAdmin\Decorators\Decorator;
/**
 * Manage languages in a database
 *
 * $Id: languages.php,v 1.13 2007/08/31 18:30:11 ioguix Exp $
 */

/**
 * Generate XML for the browser tree.
 */
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$languages = $data->getLanguages();

	$attrs = [
		'text' => Decorator::field('lanname'),
		'icon' => 'Language',
	];

	return $misc->printTree($languages, $attrs, 'languages', false);

}
