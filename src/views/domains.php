<?php

/**
 * Manage domains in a database
 *
 * $Id: domains.php,v 1.34 2007/09/13 13:41:01 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$domain_controller = new \PHPPgAdmin\Controller\DomainController($container);

$domain_controller->render();
