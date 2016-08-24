<?php
use \PHPPgAdmin\Decorators\Decorator;

function doTree($container) {

	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$lang = $container->get('lang');
	$data = $misc->getDatabaseAccessor();

	$databases = $data->getDatabases();

	$reqvars = $misc->getRequestVars('database');

	$attrs = [
		'text' => Decorator::field('datname'),
		'icon' => 'Database',
		'toolTip' => Decorator::field('datcomment'),
		'action' => Decorator::redirecturl('redirect.php', $reqvars, ['database' => Decorator::field('datname')]),
		'branch' => Decorator::branchurl('database.php', $reqvars, ['action' => 'tree', 'database' => Decorator::field('datname')]),
	];

	return $misc->printTree($databases, $attrs, 'databases', false);
}
