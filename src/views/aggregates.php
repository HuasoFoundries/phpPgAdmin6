<?php

    /**
     * Manage aggregates in a database
     *
     * $Id: aggregates.php,v 1.27 2008/01/19 13:46:15 ioguix Exp $
     */

// Include application functions
    require_once '../lib.inc.php';

    $aggregate_controller = new \PHPPgAdmin\Controller\AggregateController($container);
    $aggregate_controller->render();
