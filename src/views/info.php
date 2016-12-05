<?php

/**
 * List extra information on a table
 *
 * $Id: info.php,v 1.14 2007/05/28 17:30:32 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$info_controller = new \PHPPgAdmin\Controller\InfoController($container);

$info_controller->render();
