<?php

/**
 * Main access point to the app.
 *
 * $Id: index.php,v 1.13 2007/04/18 14:08:48 mr-russ Exp $
 */

// Include application functions

require_once './src/lib.inc.php';

/*$app->post('/info', function ($request, $response, $args) use ($msg) {

var_dump($_SERVER);

return $response;
});*/

$app->post('/redirect[/{subject}]', function ($request, $response, $args) use ($msg) {

	$body = $response->getBody();
	$misc = $this->misc;

	$pwdkey = 'loginPassword_' . md5($_POST['loginServer']);
	// If login action is set, then set session variables
	if (isset($_POST['loginServer']) && isset($_POST['loginUsername']) &&
		isset($_POST['loginPassword_' . md5($_POST['loginServer'])])) {

		$_server_info = $this->misc->getServerInfo($_POST['loginServer']);

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

		$all_db_controller->doDefault();

		$misc->setReloadBrowser(true);
		$misc->printFooter(true);

		//$body->write($this->misc->printFooter(false));

	} else {
		$_server_info = $this->misc->getServerInfo();

		if (!isset($_server_info['username'])) {

			$login_controller = new \PHPPgAdmin\Controller\LoginController($this);
			$body->write($login_controller->doLoginForm($msg));

		}
	}

	return $response;

});

$app->get('/', function ($request, $response, $args) use ($msg) {

	//throw new \Slim\Exception\SlimException($request, $response);
	//throw new Exception("Error Processing Request", 1);

	$uri = $request->getUri();
	$base_and_qs = explode('?', $uri->getQuery());

	$query_string = '';
	if (count($base_and_qs) >= 2) {
		$query_string = '?' . $base_and_qs[1];
	}

	$viewVars = $this->lang;

	$viewVars['appName'] = $this->get('settings')['appName'];
	$subject = 'intro';
	$viewVars['rtl'] = (strcasecmp($this->lang['applangdir'], 'rtl') == 0);

	if ($viewVars['rtl']) {
		$viewVars['cols'] = '*,' . $this->conf['left_width'];
		$template = 'iframe_view_rtl.twig';
	} else {
		$viewVars['cols'] = $this->conf['left_width'] . ',*';
		$template = 'iframe_view.twig';
	}
	$viewVars['headertemplate'] = 'iframe_header.twig';
	$url = '/src/views/' . $subject . '.php' . $query_string;
	$viewVars['url'] = $url;

	return $this->view->render($response, $template, $viewVars);

});

$app->get('/redirect[/{subject}]', function ($request, $response, $args) use ($msg, $container) {

	$subject = (isset($args['subject'])) ? $args['subject'] : 'root';

	$viewVars = $this->lang;
	$viewVars['appName'] = $this->get('settings')['appName'];

	$viewVars['rtl'] = (strcasecmp($this->lang['applangdir'], 'rtl') == 0);

	if ($subject == 'root') {
		$this->misc->setNoDBConnection(true);
	}
	$_server_info = $this->misc->getServerInfo();

	$body = $response->getBody();
	\PC::debug($subject, 'subject');

	if (!isset($_server_info['username'])) {
		$this->misc->setNoDBConnection(true);

		$login_controller = new \PHPPgAdmin\Controller\LoginController($this);
		$login_html = $login_controller->doLoginForm($msg);

		$body->write($login_html);

		return $response;

		//return $response->withStatus(302)->withHeader('Location', '/login');
	} else {

		$url = $this->misc->getLastTabURL($subject);

		$include_file = $url['url'];

		\PC::debug($url, 'url');
		\PC::debug($subject, 'subject');

		// Load query vars into superglobal arrays
		if (isset($url['urlvars'])) {
			$urlvars = [];

			foreach ($url['urlvars'] as $key => $urlvar) {
				if (strpos($key, '?') !== FALSE) {
					$key = explode('?', $key)[1];
				}
				$urlvars[$key] = \PHPPgAdmin\Decorators\Decorator::get_sanitized_value($urlvar, $_REQUEST);
			}

			$_REQUEST = array_merge($_REQUEST, $urlvars);
			$_GET = array_merge($_GET, $urlvars);
		}

		$actionurl = \PHPPgAdmin\Decorators\Decorator::actionurl($include_file, $_GET);

		if (false && is_readable('./src/views/' . $include_file)) {
			require ('./src/views/' . $include_file);
		} else {
			$destinationurl = str_replace("%2Fredirect%2F{$subject}%3F", '', $actionurl->value($_GET));

			$viewVars['url'] = $destinationurl;

			\PC::debug($destinationurl, 'destinationurl');
			return $response->withStatus(302)->withHeader('Location', $destinationurl);
			//return $this->view->render($response, 'view.twig', $viewVars);

		}
	}
});

$app->get('/{subject}', function ($request, $response, $args) use ($msg, $container) {
	$subject = (isset($args['subject'])) ? $args['subject'] : 'root';
	if ($subject === 'server' || $subject === 'root') {
		$subject = 'login';
	}
	$uri = $request->getUri();
	$base_and_qs = explode('?', $uri->getQuery());

	$query_string = '';
	if (count($base_and_qs) >= 2) {
		$query_string = '?' . $base_and_qs[1];
	}

	$url = '/src/views/' . $subject . '.php' . $query_string;

	\PC::debug(['subject' => $subject, 'url' => $url], 'subject');
	$viewVars['rtl'] = (strcasecmp($this->lang['applangdir'], 'rtl') == 0);

	if ($viewVars['rtl']) {
		$viewVars['cols'] = '*,' . $this->conf['left_width'];
		$template = 'iframe_view_rtl.twig';
	} else {
		$viewVars['cols'] = $this->conf['left_width'] . ',*';
		$template = 'iframe_view.twig';
	}

	$viewVars = $this->lang;
	$viewVars['appName'] = $this->get('settings')['appName'];
	$viewVars['url'] = $url;

	return $this->view->render($response, $template, $viewVars);
});

// Run app
$app->run();
