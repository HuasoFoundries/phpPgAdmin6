<?php

/**
 * Manage opclasss in a database
 *
 * $Id: opclasses.php,v 1.10 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$opclasses_controller = new \PHPPgAdmin\Controller\OpClassesController($container);
$opclasses_controller->render();