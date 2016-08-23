<?php

/**
 * Manage casts in a database
 *
 * $Id: casts.php,v 1.16 2007/09/25 16:08:05 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$misc->printHeader($lang['strcasts']);
$misc->printBody();

$cast_controller = new \PHPPgAdmin\Controller\CastController($app);

switch ($action) {

	default:
		$cast_controller->doDefault();
		break;
}

$misc->printFooter();
