<?php

/**
 * PHPPgAdmin v6.0.0-beta.39
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class BrowserController extends BaseController
{
    public $controller_name = 'BrowserController';

    /**
     * Default method to render the controller according to the action parameter.
     *
     * @param null|mixed $action
     */
    public function render($action = null)
    {
        if ($action === null) {
            $action = $this->action;
        }

        switch ($action) {
            case 'tree':
                return $this->doTree();
                break;
            case 'jstree':
                return $this->jsTree();
                break;
            default:
                return $this->doDefault();
                break;
        }
    }

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function doDefault()
    {
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

        return $this->view->fetch('browser.twig', $viewVars);
    }

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function jsTree()
    {
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

        return $this->view->fetch('jstree.twig', $viewVars);
    }

    public function doTree()
    {
        $treedata = new \PHPPgAdmin\ArrayRecordSet([]);
        $reqvars  = [];

        $attrs = [
            'text'    => 'Servers',
            'icon'    => 'Servers',
            'is_root' => 'true',
            'action'  => Decorator::url('/src/views/servers'),
            'branch'  => Decorator::url('/src/views/servers', $reqvars, ['action' => 'tree']),
        ];

        return $this->printTree($treedata, $attrs, 'server');
    }
}
