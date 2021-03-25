<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use Slim\Http\Response;

/**
 * Base controller class.
 */
class OpclassesController extends BaseController
{
    public $controller_title = 'stropclasses';

    /**
     * Default method to render the controller according to the action parameter.
     *
     * @return null|Response|string
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        $this->printHeader();
        $this->printBody();
        $this->doDefault();

        $this->printFooter();
    }

    /**
     * Show default list of opclasss in the database.
     *
     * @param string $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'opclasses');
        $this->printMsg($msg);

        $opclasses = $data->getOpClasses();

        $columns = [
            'accessmethod' => [
                'title' => $this->lang['straccessmethod'],
                'field' => Decorator::field('amname'),
            ],
            'opclass' => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('opcname'),
            ],
            'type' => [
                'title' => $this->lang['strtype'],
                'field' => Decorator::field('opcintype'),
            ],
            'default' => [
                'title' => $this->lang['strdefault'],
                'field' => Decorator::field('opcdefault'),
                'type' => 'yesno',
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('opccomment'),
            ],
        ];

        $actions = [];

        if ($opclasses instanceof \PHPPgAdmin\Interfaces\RecordSet) {
            echo $this->printTable($opclasses, $columns, $actions, 'opclasses-opclasses', $this->lang['strnoopclasses']);
        }
    }

    /**
     * Generate XML for the browser tree.
     *
     * @return Response|string
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $opclasses = $data->getOpClasses();

        // OpClass prototype: "op_class/access_method"
        $proto = Decorator::concat(Decorator::field('opcname'), '/', Decorator::field('amname'));

        $attrs = [
            'text' => $proto,
            'icon' => 'OperatorClass',
            'toolTip' => Decorator::field('opccomment'),
        ];

        return $this->printTree($opclasses, $attrs, 'opclasses');
    }
}
