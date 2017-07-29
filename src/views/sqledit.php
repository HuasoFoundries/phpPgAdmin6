<?php

    /**
     * Alternative SQL editing window
     *
     * $Id: sqledit.php,v 1.40 2008/01/10 19:37:07 xzilla Exp $
     */

// Include application functions
//require_once '../lib.inc.php';
    require_once '../lib.inc.php';

    $sqledit_controller = new \PHPPgAdmin\Controller\SQLEditController($container);

    $sqledit_controller->render();
