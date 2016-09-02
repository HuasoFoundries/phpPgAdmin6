<?php

/**
 * List rules on a table OR view
 *
 * $Id: rules.php,v 1.33 2007/08/31 18:30:11 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$rule_controller = new \PHPPgAdmin\Controller\RuleController($container);
$rule_controller->render();