<?php

/**
 * Manage conversions in a database
 *
 * $Id: conversions.php,v 1.15 2007/08/31 18:30:10 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$conversion_controller = new \PHPPgAdmin\Controller\ConversionController($container);
$conversion_controller->render();
