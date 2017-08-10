<?php

/**
 * Process an arbitrary SQL query - tricky!  The main problem is that
 * unless we implement a full SQL parser, there's no way of knowing
 * how many SQL statements have been strung together with semi-colons
 *
 * @param $_SESSION ['sqlquery'] The SQL query string to execute
 *
 * $Id: sql.php,v 1.43 2008/01/10 20:19:27 xzilla Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$sqlquery_controller = new \PHPPgAdmin\Controller\SQLQueryController($container);

$sqlquery_controller->render();
