<?php

/**
 * Manage servers
 *
 * $Id: servers.php,v 1.12 2008/02/18 22:20:26 ioguix Exp $
 */

function doLogout($container) {

	$lang           = $container->get('lang');
	$misc           = $container->get('misc');
	$plugin_manager = $container->get('plugin_manager');

	$plugin_manager->do_hook('logout', $_REQUEST['logoutServer']);

	$server_info = $misc->getServerInfo($_REQUEST['logoutServer']);
	$misc->setServerInfo(null, null, $_REQUEST['logoutServer']);

	unset($_SESSION['sharedUsername'], $_SESSION['sharedPassword']);

	$mist->setReloadBrowser(true);

	return doDefault($container, sprintf($lang['strlogoutmsg'], $server_info['desc']));

}

function doDefault($container, $msg = '') {

	$lang = $container->get('lang');
	$conf = $container->get('conf');
	$misc = $container->get('misc');
	$msg  = $container->msg;

	$default_html = '';

	$default_html .= $misc->printTabs('root', 'servers', false);
	$default_html .= $misc->printMsg($msg, false);

	$group = isset($_GET['group']) ? $_GET['group'] : false;

	$groups = $misc->getServersGroups(true, $group);

	$columns = [
		'group' => [
			'title' => $lang['strgroup'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('desc'),
			'url' => 'servers.php?',
			'vars' => ['group' => 'id'],
		],
	];
	$actions = [];

	if (($group !== false) and (isset($conf['srv_groups'][$group])) and ($groups->recordCount() > 0)) {
		$default_html .= $misc->printTitle(sprintf($lang['strgroupgroups'], htmlentities($conf['srv_groups'][$group]['desc'], ENT_QUOTES, 'UTF-8')), null, false);
	}

	$default_html .= $misc->printTable($groups, $columns, $actions, 'servers-servers');

	$servers = $misc->getServers(true, $group);

	function svPre(&$rowdata, $actions) {
		$actions['logout']['disable'] = empty($rowdata->fields['username']);
		return $actions;
	}

	$columns = [
		'server' => [
			'title' => $lang['strserver'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('desc'),
			'url' => "redirect.php?subject=server&amp;",
			'vars' => ['server' => 'id'],
		],
		'host' => [
			'title' => $lang['strhost'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('host'),
		],
		'port' => [
			'title' => $lang['strport'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('port'),
		],
		'username' => [
			'title' => $lang['strusername'],
			'field' => \PHPPgAdmin\Decorators\Decorator::field('username'),
		],
		'actions' => [
			'title' => $lang['stractions'],
		],
	];

	$actions = [
		'logout' => [
			'content' => $lang['strlogout'],
			'attr' => [
				'href' => [
					'url' => '/servers/logout',
					'urlvars' => [
						'logoutServer' => \PHPPgAdmin\Decorators\Decorator::field('id'),
					],
				],
			],
		],
	];

	if (($group !== false) and isset($conf['srv_groups'][$group])) {
		$default_html .= $misc->printTitle(sprintf($lang['strgroupservers'], htmlentities($conf['srv_groups'][$group]['desc'], ENT_QUOTES, 'UTF-8')), null, false);
		$actions['logout']['attr']['href']['urlvars']['group'] = $group;
	}

	$default_html .= $misc->printTable($servers, $columns, $actions, 'servers-servers', $lang['strnoobjects'], 'svPre');
	return $default_html;
}

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
		'text' => \PHPPgAdmin\Decorators\Decorator::field('desc'),

		// Show different icons for logged in/out
		'icon' => \PHPPgAdmin\Decorators\Decorator::field('icon'),

		'toolTip' => \PHPPgAdmin\Decorators\Decorator::field('id'),

		'action' => \PHPPgAdmin\Decorators\Decorator::field('action'),

		// Only create a branch url if the user has
		// logged into the server.
		'branch' => \PHPPgAdmin\Decorators\Decorator::field('branch'),
	];
	PC::debug($nodes, 'printTree');
	$misc->printTree($nodes, $attrs, 'servers');

}
