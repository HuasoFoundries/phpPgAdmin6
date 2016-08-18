<?php

/**
 * Main access point to the app.
 *
 * $Id: index.php,v 1.13 2007/04/18 14:08:48 mr-russ Exp $
 */

// Include application functions
$_no_db_connection = true;
require_once './src/lib.inc.php';

if (!isset($msg)) {
	$msg = '';
}

$app->get('/sqledit[/{action}]', function ($request, $response, $args) use ($msg) {

	$action = (isset($args['action'])) ? $args['action'] : 'sql';

	include './src/views/sqledit.php';
	$body = $response->getBody();

	switch ($action) {
		case 'find':

			$header_html = $this->view->fetch('sqledit_header.twig', ['title' => $this->lang['strfind']]);
			$body->write($header_html);
			$body->write(doFind($this));

			break;
		case 'sql':
		default:

			$header_html = $this->view->fetch('sqledit_header.twig', ['title' => $this->lang['strsql']]);
			$body->write($header_html);
			$body->write(doDefault($this));

			break;
	}

	$footer_html = $this->view->fetch('sqledit.twig');
	$body->write($footer_html);

	$this->misc->setWindowName('sqledit');
	return $response;

});

$app->get('/views/browser', function ($request, $response, $args) use ($msg) {

	$viewVars            = $this->lang;
	$viewVars['appName'] = $this->get('settings')['appName'];
	$viewVars['icon']    = [
		'blank' => $this->misc->icon('blank'),
		'I' => $this->misc->icon('I'),
		'L' => $this->misc->icon('L'),
		'Lminus' => $this->misc->icon('Lminus'),
		'Loading' => $this->misc->icon('Loading'),
		'Lplus' => $this->misc->icon('Lplus'),
		'ObjectNotFound' => $this->misc->icon('ObjectNotFound'),
		'Refresh' => $this->misc->icon('Refresh'),
		'Servers' => $this->misc->icon('Servers'),
		'T' => $this->misc->icon('T'),
		'Tminus' => $this->misc->icon('Tminus'),
		'Tplus' => $this->misc->icon('Tplus'),

	];

	$viewVars['cols'] = $cols;
	$viewVars['rtl']  = $rtl;

	$this->view->render($response, 'browser.twig', $viewVars);

});

$app->get('/views/servers[/{action}]', function ($request, $response, $args) use ($msg) {

	$action = (isset($args['action'])) ? $args['action'] : '';

	include './src/views/servers.php';

	if ($action == 'tree') {
		$newResponse = $response
			->withHeader('Content-type', 'text/xml')
			->withHeader('Cache-Control', 'no-cache');

		doTree($this);
		return $newResponse;

	} else {
		$body = $response->getBody();

		$header_html = $this->misc->printHeader($this->lang['strservers'], null, false);
		$body->write($header_html);

		$body_html = $this->misc->printBody(false);
		$body->write($body_html);

		$trail_html = $this->misc->printTrail('root', false);
		$body->write($trail_html);

		switch ($action) {
			case 'logout':
				doLogout($this);
				break;
			default:
				$body->write(doDefault($this));
				break;
		}

		$footer_html = $this->misc->printFooter(false);
		$body->write($footer_html);
		return $response;

	}

});

$app->get('/', function ($request, $response, $args) use ($msg) {

	$rtl  = (strcasecmp($this->lang['applangdir'], 'rtl') == 0);
	$cols = $rtl ? '*,' . $this->conf['left_width'] : $this->conf['left_width'] . ',*';

	$viewVars            = $this->lang;
	$viewVars['appName'] = $this->get('settings')['appName'];
	$viewVars['cols']    = $cols;
	$viewVars['rtl']     = $rtl;

	return $this->view->render($response, 'home.twig', $viewVars);

});

// Run app
$app->run();
