<?php

/**
 * Help page redirection/browsing.
 *
 * $Id: help.php,v 1.3 2006/12/31 16:21:26 soranzo Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$help_controller = new \PHPPgAdmin\Controller\HelpController($app);

switch ($action) {
	case 'browse':
		$help_controller->doBrowse();
		break;
	default:
		$help_controller->doDefault();
		break;
}
