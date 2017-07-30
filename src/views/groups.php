<?php

    /**
     * Manage groups in a database cluster
     *
     * $Id: groups.php,v 1.27 2007/08/31 18:30:11 ioguix Exp $
     */

// Include application functions
    require_once '../lib.inc.php';

    $group_controller = new \PHPPgAdmin\Controller\GroupController($container);

    $group_controller->render();
