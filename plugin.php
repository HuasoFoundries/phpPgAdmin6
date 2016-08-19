<?php
require_once './includes/lib.inc.php';

$plugin_manager->do_action($_REQUEST['plugin'], $_REQUEST['action']);
