<?php

/**
 * Main access point to the app.
 *
 * $Id: index.php,v 1.13 2007/04/18 14:08:48 mr-russ Exp $
 */

// Include application functions
$_no_db_connection = true;
require_once './libraries/lib.inc.php';

\PC::debug('Soy el index.php');

$app->get('/sqledit', function ($request, $response, $args) {
	/*return $this->view->render($response, 'profile.html', [
		        'name' => $args['name']
	*/
	//PC::debug($request, 'sqledit');
	//Kint::dump($request);

	$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
	if (!isset($msg)) {
		$msg = '';
	}
	include './views/sqledit.php';
	switch ($action) {
	case 'find':
		doFind($this->misc);
		break;
	case 'sql':
	default:
		$viewVars = doDefault($this->misc);

		$this->view->render($response, 'sqledit_header.twig', $viewVars);

		$this->misc->printTabs($this->misc->getNavTabs('popup'), 'sql');

		_printConnection($this->misc, $action);

		return $this->view->render($response, 'sqledit.twig', $viewVars);

		break;
	}

// Set the name of the window
	$misc->setWindowName('sqledit');

	$misc->printFooter();

});

$app->get('/{folder}/{script}', function ($request, $response, $args) use ($misc, $conf, $lang) {
	/*return $this->view->render($response, 'profile.html', [
		        'name' => $args['name']
	*/
	\PC::debug($request);
	Kint::dump($args);

});

$app->get('/{folder}/{script}/{name}', function ($request, $response, $args) use ($misc, $conf, $lang) {
	/*return $this->view->render($response, 'profile.html', [
		        'name' => $args['name']
	*/

	\PC::debug($request);
	Kint::dump($args);

});

$app->get('/', function ($request, $response, $args) use ($misc, $conf, $lang) {
	/*return $this->view->render($response, 'profile.html', [
		        'name' => $args['name']
	*/
	\PC::debug($request);

	$misc->printHeader('', null, true);

	$rtl = (strcasecmp($lang['applangdir'], 'rtl') == 0);

	$cols = $rtl ? '*,' . $conf['left_width'] : $conf['left_width'] . ',*';
	$mainframe = '<frame src="views/intro.php" name="detail" id="detail" frameborder="0" />';

	echo '<frameset cols="' . $cols . '">';

	if ($rtl) {
		echo $mainframe;
	}

	echo '<frame src="views/browser.php" name="browser" id="browser" frameborder="0" />';

	if (!$rtl) {
		echo $mainframe;
	}

	echo '<noframes>';
	echo '<body>';
	echo $lang['strnoframes'];
	echo '<br />';
	echo '<a href="views/intro.php">' . $lang['strnoframeslink'] . '</a>';
	echo '</body>';
	echo '</noframes>';

	echo '</frameset>';

	$misc->printFooter(false);

})->setName('home');

// Run app
$app->run();
