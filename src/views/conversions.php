<?php

/**
 * Manage conversions in a database
 *
 * $Id: conversions.php,v 1.15 2007/08/31 18:30:10 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$misc->printHeader($lang['strconversions']);
$misc->printBody();

$conversion_controller = new \PHPPgAdmin\Controller\ConversionController($app);

switch ($action) {
	default:
		$conversion_controller->doDefault();
		break;
}

$misc->printFooter();
