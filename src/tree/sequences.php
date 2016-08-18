<?php
use \PHPPgAdmin\Decorators\Decorator;

/**
 * Manage sequences in a database
 *
 * $Id: sequences.php,v 1.49 2007/12/15 22:21:54 ioguix Exp $
 */

/**
 * Generate XML for the browser tree.
 */
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$sequences = $data->getSequences();

	$reqvars = $misc->getRequestVars('sequence');

	$attrs = [
		'text' => Decorator::field('seqname'),
		'icon' => 'Sequence',
		'toolTip' => Decorator::field('seqcomment'),
		'action' => Decorator::actionurl('sequences.php',
			$reqvars,
			[
				'action' => 'properties',
				'sequence' => Decorator::field('seqname'),
			]
		),
	];

	return $misc->printTree($sequences, $attrs, 'sequences', false);
}
