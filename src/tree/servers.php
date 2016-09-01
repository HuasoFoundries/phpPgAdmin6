<?php
use \PHPPgAdmin\Decorators\Decorator;
function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');

	$nodes    = [];
	$group_id = isset($_GET['group']) ? $_GET['group'] : false;

	/* root with srv_groups */
	if (isset($conf['srv_groups']) and count($conf['srv_groups']) > 0
		and $group_id === false) {
		$nodes = $misc->getServersGroups(true);
	} else if (isset($conf['srv_groups']) and $group_id !== false) {
		/* group subtree */
		if ($group_id !== 'all') {
			$nodes = $misc->getServersGroups(false, $group_id);
		}

		$nodes = array_merge($nodes, $misc->getServers(false, $group_id));
		$nodes = new \PHPPgAdmin\ArrayRecordSet($nodes);
	} else {
		/* no srv_group */
		$nodes = $misc->getServers(true, false);
	}

	$reqvars = $misc->getRequestVars('server');

	$attrs = [
		'text' => Decorator::field('desc'),

		// Show different icons for logged in/out
		'icon' => Decorator::field('icon'),

		'toolTip' => Decorator::field('id'),

		'action' => Decorator::field('action'),

		// Only create a branch url if the user has
		// logged into the server.
		'branch' => Decorator::field('branch'),
	];
	//PC::debug($nodes, 'printTree');
	return $misc->printTree($nodes, $attrs, 'servers', false);

}
