<?php

    /**
     * Manage sequences in a database
     *
     * $Id: sequences.php,v 1.49 2007/12/15 22:21:54 ioguix Exp $
     */

// Include application functions
    require_once '../lib.inc.php';

    $sequence_controller = new \PHPPgAdmin\Controller\SequenceController($container);
    $sequence_controller->render();