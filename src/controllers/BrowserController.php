<?php

    namespace PHPPgAdmin\Controller;

    /**
     * Base controller class
     */
    class BrowserController extends BaseController
    {
        public $_name = 'BrowserController';

        /* Constructor */
        public function __construct(\Slim\Container $container)
        {
            $this->misc = $container->get('misc');

            $this->misc->setNoDBConnection(true);
            $this->misc->setNoBottomLink(true);

            parent::__construct($container);
        }

        public function render()
        {
            $conf = $this->conf;
            $misc = $this->misc;
            $lang = $this->lang;

            $viewVars            = $this->lang;
            $viewVars['appName'] = $this->misc->appName;
            $viewVars['icon']    = [
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

            ];

            echo $this->view->fetch('browser.twig', $viewVars);
        }
    }
