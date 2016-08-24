<?php

if (!defined('BASE_PATH')) {
	require_once '../lib.inc.php';
}

$intro_controller = new \PHPPgAdmin\Controller\IntroController($container);

$misc->printHeader($lang['strcasts']);
$misc->printBody();

switch ($action) {

	default:
		$intro_controller->doDefault();
		break;
}

$misc->printFooter();
