<?php

namespace PHPPgAdmin\Controller;

use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class OpClassesController extends BaseController
{
    public $_name = 'OpClassesController';

    public function render()
    {
        $conf = $this->conf;
        $misc = $this->misc;
        $lang = $this->lang;

        $action = $this->action;
        if ($action == 'tree') {
            return $this->doTree();
        }

        $this->printHeader($lang['stropclasses']);
        $this->printBody();

        switch ($action) {
            default:
                $this->doDefault();
                break;
        }

        $misc->printFooter();

    }

/**
 * Generate XML for the browser tree.
 */
    public function doTree()
    {

        $conf = $this->conf;
        $misc = $this->misc;
        $lang = $this->lang;
        $data = $misc->getDatabaseAccessor();

        $opclasses = $data->getOpClasses();

        // OpClass prototype: "op_class/access_method"
        $proto = Decorator::concat(Decorator::field('opcname'), '/', Decorator::field('amname'));

        $attrs = [
            'text'    => $proto,
            'icon'    => 'OperatorClass',
            'toolTip' => Decorator::field('opccomment'),
        ];

        return $this->printTree($opclasses, $attrs, 'opclasses');
    }

    /**
     * Show default list of opclasss in the database
     */
    public function doDefault($msg = '')
    {
        $conf = $this->conf;
        $misc = $this->misc;
        $lang = $this->lang;
        $data = $misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'opclasses');
        $misc->printMsg($msg);

        $opclasses = $data->getOpClasses();

        $columns = [
            'accessmethod' => [
                'title' => $lang['straccessmethod'],
                'field' => Decorator::field('amname'),
            ],
            'opclass'      => [
                'title' => $lang['strname'],
                'field' => Decorator::field('opcname'),
            ],
            'type'         => [
                'title' => $lang['strtype'],
                'field' => Decorator::field('opcintype'),
            ],
            'default'      => [
                'title' => $lang['strdefault'],
                'field' => Decorator::field('opcdefault'),
                'type'  => 'yesno',
            ],
            'comment'      => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('opccomment'),
            ],
        ];

        $actions = [];

        echo $this->printTable($opclasses, $columns, $actions, 'opclasses-opclasses', $lang['strnoopclasses']);
    }

}
