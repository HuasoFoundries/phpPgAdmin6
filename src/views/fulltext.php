<?php

    /**
     * Manage fulltext configurations, dictionaries and mappings
     *
     * $Id: fulltext.php,v 1.6 2008/03/17 21:35:48 ioguix Exp $
     */

// Include application functions
    require_once '../lib.inc.php';

    $fulltext_controller = new \PHPPgAdmin\Controller\FulltextController($container);
    $fulltext_controller->render();
