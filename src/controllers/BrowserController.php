<?php

/**
 * PHPPgAdmin v6.0.0-RC5
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
    protected $no_db_connection = true;

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
            default:
                return $this->doDefault();

                break;
        }
    }

    /**
     * Default method to render the browser iframe using jstree.
     *
     * @return string rendered html of the jstree template
     */
    public function doDefault()
    {
        $this->misc->setNoDBConnection(true);

        $this->setNoBottomLink(true);

        $viewVars = ['icon' => [
            'Refresh' => $this->misc->icon('Refresh'),
            'Servers' => $this->misc->icon('Servers'),
        ]];

        return $this->view->fetch('browser.twig', $viewVars);
    }

    /**
     * Renders the root element of the jstree.
     *
     * @return string json representation of the root element of the jstree
     */
    public function doTree()
    {
        $treedata = new \PHPPgAdmin\ArrayRecordSet([]);
        $reqvars  = [];
        $action   = Decorator::url('/src/views/servers');
        $branch   = Decorator::url('/src/views/servers', $reqvars, ['action' => 'tree']);
        // $this->dump($branch);
        $attrs = [
            'text'    => 'Servers',
            'icon'    => 'Servers',
            'is_root' => 'true',
            'action'  => $action,
            'branch'  => $branch,
        ];

        return $this->printTree($treedata, $attrs, 'server');
    }
}
