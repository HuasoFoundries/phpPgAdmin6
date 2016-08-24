<?php

/**
 * Main access point to the app.
 *
 * $Id: index.php,v 1.13 2007/04/18 14:08:48 mr-russ Exp $
 */

// Include application functions
$_no_db_connection = true;
require_once './src/lib.inc.php';

$app->post('/redirect[/{subject}]', function ($request, $response, $args) use ($msg) {

	$body = $response->getBody();
	$misc = $this->misc;

	$pwdkey = 'loginPassword_' . md5($_POST['loginServer']);
	// If login action is set, then set session variables
	if (isset($_POST['loginServer']) && isset($_POST['loginUsername']) &&
		isset($_POST['loginPassword_' . md5($_POST['loginServer'])])) {

		$_server_info = $this->misc->getServerInfo($_POST['loginServer']);

		\PC::debug($_POST, '$_POST');

		$_server_info['username'] = $_POST['loginUsername'];
		$_server_info['password'] = $_POST['loginPassword_' . md5($_POST['loginServer'])];

		$this->misc->setServerInfo(null, $_server_info, $_POST['loginServer']);

		// Check for shared credentials
		if (isset($_POST['loginShared'])) {
			$_SESSION['sharedUsername'] = $_POST['loginUsername'];
			$_SESSION['sharedPassword'] = $_POST['loginPassword_' . md5($_POST['loginServer'])];
		}

		$data = $misc->getDatabaseAccessor();

		$all_db_controller = new \PHPPgAdmin\Controller\AllDBController($this);

		$misc->printHeader($this->lang['strdatabases']);
		$misc->printBody();

		return $all_db_controller->doDefault();

		$misc->setReloadBrowser(true);
		$misc->printFooter();

		//$body->write($this->misc->printFooter(false));

	} else {
		$_server_info = $this->misc->getServerInfo();

		if (!isset($_server_info['username'])) {

			include BASE_PATH . '/src/views/login.php';

			$body->write(doLoginForm($this, $msg));

		}
	}

	return $response;

});

$app->get('/redirect[/{subject}]', function ($request, $response, $args) use ($msg) {

	$subject = (isset($args['subject'])) ? $args['subject'] : 'root';

	if ($subject == 'root') {
		$this->misc->setNoDBConnection(true);
	}
	$_server_info = $this->misc->getServerInfo();

	$body = $response->getBody();
	if (!isset($_server_info['username'])) {
		include BASE_PATH . '/src/views/login.php';
		$body->write(doLoginForm($this, $msg));

		//\Kint::dump($request->getParams());
		return $response;
	} else {

		$url = $this->misc->getLastTabURL($subject);

		$include_file = $url['url'];

		// Load query vars into superglobal arrays
		if (isset($url['urlvars'])) {

			/*echo '<pre>';
				print_r($url['urlvars']);
			*/
			$urlvars = [];

			foreach ($url['urlvars'] as $key => $urlvar) {
				if (strpos($key, '?') !== FALSE) {
					$key = explode('?', $key)[1];
				}
				$urlvars[$key] = value($urlvar, $_REQUEST);
			}

			$_REQUEST = array_merge($_REQUEST, $urlvars);
			$_GET     = array_merge($_GET, $urlvars);
		}

		$actionurl = \PHPPgAdmin\Decorators\Decorator::actionurl($include_file, $_GET);

		//PC::debug($url['url'], 'redirect.php will include');
		//PC::debug($actionurl->value($_GET), '$actionurl');

		if (is_readable($include_file)) {
			include $include_file;
		} else {
			$destinationurl = str_replace("%2Fredirect%2F{$subject}%3F", '', $actionurl->value($_GET));

			return $response->withStatus(302)->withHeader('Location', $destinationurl);

		}
	}
});

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

$app->get('/tree/browser', function ($request, $response, $args) use ($msg) {

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

$app->get('/tree/{node}[/{action}]', function ($request, $response, $args) use ($msg) {

	$newResponse = $response
		->withHeader('Content-type', 'text/xml')
		->withHeader('Cache-Control', 'no-cache');

	$phpscript = './src/tree/' . $args['node'] . '.php';

	if (is_readable($phpscript)) {
		include $phpscript;
		if (isset($args['action']) && $args['action'] == 'subtree') {
			doSubTree($this);
		} else {
			doTree($this);
		}
	}

	return $newResponse;

});

$app->get('/src/views/servers[/{action}]', function ($request, $response, $args) use ($msg) {

	$action = (isset($args['action'])) ? $args['action'] : '';

	include './src/views/servers.php';

	$body = $response->getBody();

	$header_html = $this->misc->printHeader($this->lang['strservers'], null, false);
	$body->write($header_html);

	$body_html = $this->misc->printBody(false);
	$body->write($body_html);

	$trail_html = $this->misc->printTrail('root', false);
	$body->write($trail_html);

	switch ($action) {
		case 'logout':
			$body->write(doLogout($this));

			break;
		default:
			$body->write(doDefault($this, $msg));
			break;
	}

	$footer_html = $this->misc->printFooter(false);
	$body->write($footer_html);
	return $response;

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

$app->get('/src/views/intro', function ($request, $response, $args) use ($msg) {
	include './src/views/intro.php';

	$body = $response->getBody();
	$body->write(doDefault($this));

	return $response;

});

$app->get('/views/{script}', function ($request, $response, $args) use ($msg) {
	$body = $response->getBody();
	$body->write('Not found ' . $args['script']);
	\PC::debug($args, 'args');
	return $response;

});

// Run app
$app->run();
