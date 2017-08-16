<?php

/**
 * Manage servers
 *
 * $Id: servers.php,v 1.12 2008/02/18 22:20:26 ioguix Exp $
 */

require_once '../lib.inc.php';

$server_controller = new \PHPPgAdmin\Controller\ServerController($container, true);

$server_controller->render();
