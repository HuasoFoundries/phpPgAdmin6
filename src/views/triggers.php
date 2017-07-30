<?php

    /**
     * List triggers on a table
     *
     * $Id: triggers.php,v 1.37 2007/09/19 14:42:12 ioguix Exp $
     */

// Include application functions
    require_once '../lib.inc.php';

    $trigger_controller = new \PHPPgAdmin\Controller\TriggerController($container);
    $trigger_controller->render();
