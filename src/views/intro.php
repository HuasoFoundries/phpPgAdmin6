<?php

if (!defined('BASE_PATH')) {
    require_once '../lib.inc.php';
}

$intro_controller = new \PHPPgAdmin\Controller\IntroController($container, true);

$intro_controller->render();
