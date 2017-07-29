<?php

    if (!defined('BASE_PATH')) {
        require_once '../lib.inc.php';
    }

    $browser_controller = new \PHPPgAdmin\Controller\BrowserController($container);

    $browser_controller->render();
