<?php

/**
 * Main access point to the app.
 *
 * $Id: index.php,v 1.13 2007/04/18 14:08:48 mr-russ Exp $
 */

// Include application functions

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

		$all_db_controller->doDefault();

		$misc->setReloadBrowser(true);
		$misc->printFooter();

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

$app->get('/redirect[/{subject}]', function ($request, $response, $args) use ($msg) {

	$subject = (isset($args['subject'])) ? $args['subject'] : 'root';

	if ($subject == 'root') {
		$this->misc->setNoDBConnection(true);
	}
	$_server_info = $this->misc->getServerInfo();

	$body = $response->getBody();
	if (!isset($_server_info['username'])) {
		$this->misc->setNoDBConnection(true);
		$login_controller = new \PHPPgAdmin\Controller\LoginController($this);

		$body->write($login_controller->doLoginForm($msg));

		return $response;
	} else {

		$url = $this->misc->getLastTabURL($subject);

		$include_file = $url['url'];

		\PC::debug($url, 'url');

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
			$_GET     = array_merge($_GET, $urlvars);
		}

		$actionurl = \PHPPgAdmin\Decorators\Decorator::actionurl($include_file, $_GET);

		if (is_readable($include_file)) {
			include $include_file;
		} else {
			$destinationurl = str_replace("%2Fredirect%2F{$subject}%3F", '', $actionurl->value($_GET));

			return $response->withStatus(302)->withHeader('Location', $destinationurl);

		}
	}
});

$app->get('/', function ($request, $response, $args) use ($msg) {

	$viewVars            = $this->lang;
	$viewVars['appName'] = $this->get('settings')['appName'];
	$viewVars['rtl']     = (strcasecmp($this->lang['applangdir'], 'rtl') == 0);

	if ($viewVars['rtl']) {
		$viewVars['cols'] = '*,' . $this->conf['left_width'];
		$template         = 'home_rtl.twig';
	} else {
		$viewVars['cols'] = $this->conf['left_width'] . ',*';
		$template         = 'home.twig';
	}

	return $this->view->render($response, $template, $viewVars);

});

// Run app
$app->run();
