<?php

    /**
     * List views in a database
     *
     * $Id: viewproperties.php,v 1.34 2007/12/11 14:17:17 ioguix Exp $
     */

// Include application functions
    require_once '../lib.inc.php';

    $viewproperty_controller = new \PHPPgAdmin\Controller\ViewPropertyController($container);
    $viewproperty_controller->render();
