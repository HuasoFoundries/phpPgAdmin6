<?php

/**
 * Alternative SQL editing window
 *
 * $Id: history.php,v 1.3 2008/01/10 19:37:07 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$history_controller = new \PHPPgAdmin\Controller\HistoryController($container);

$history_controller->render();
