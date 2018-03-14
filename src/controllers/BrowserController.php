<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 * @package PHPPgAdmin
 */
class BrowserController extends BaseController
{
    public $controller_name = 'BrowserController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang = $this->lang;

        $this->misc->setNoDBConnection(true);

        $this->setNoBottomLink(true);

        $viewVars = ['icon' => [
            'blank'          => $this->misc->icon('blank'),
            'I'              => $this->misc->icon('I'),
            'L'              => $this->misc->icon('L'),
            'Lminus'         => $this->misc->icon('Lminus'),
            'Loading'        => $this->misc->icon('Loading'),
            'Lplus'          => $this->misc->icon('Lplus'),
            'ObjectNotFound' => $this->misc->icon('ObjectNotFound'),
            'Refresh'        => $this->misc->icon('Refresh'),
            'Servers'        => $this->misc->icon('Servers'),
            'T'              => $this->misc->icon('T'),
            'Tminus'         => $this->misc->icon('Tminus'),
            'Tplus'          => $this->misc->icon('Tplus'),
        ]];

        echo $this->view->fetch('browser.twig', $viewVars);
    }
}
