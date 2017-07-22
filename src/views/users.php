<?php

    /**
     * Manage users in a database cluster
     *
     * $Id: users.php,v 1.40 2008/02/25 17:20:44 xzilla Exp $
     */

// Include application functions
    require_once '../lib.inc.php';

    $user_controller = new \PHPPgAdmin\Controller\UserController($container);

    $user_controller->render();
