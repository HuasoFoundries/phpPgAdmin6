<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class ConversionsController extends BaseController
{
    public $controller_title = 'strconversions';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * Show default list of conversions in the database.
     *
     * @param string $msg
     *
     * @return string|void
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'conversions');
        $this->printMsg($msg);

        $conversions = $data->getconversions();

        $columns = [
            'conversion'      => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('conname'),
            ],
            'source_encoding' => [
                'title' => $this->lang['strsourceencoding'],
                'field' => Decorator::field('conforencoding'),
            ],
            'target_encoding' => [
                'title' => $this->lang['strtargetencoding'],
                'field' => Decorator::field('contoencoding'),
            ],
            'default'         => [
                'title' => $this->lang['strdefault'],
                'field' => Decorator::field('condefault'),
                'type'  => 'yesno',
            ],
            'comment'         => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('concomment'),
            ],
        ];

        $actions = [];

        echo $this->printTable($conversions, $columns, $actions, 'conversions-conversions', $this->lang['strnoconversions']);
    }

    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $constraints = $data->getConstraints($_REQUEST['table']);

        $getIcon = function ($f) {
            switch ($f['contype']) {
                case 'u':
                    return 'UniqueConstraint';
                case 'c':
                    return 'CheckConstraint';
                case 'f':
                    return 'ForeignKey';
                case 'p':
                    return 'PrimaryKey';
            }
        };

        $attrs = [
            'text' => Decorator::field('conname'),
            'icon' => Decorator::callback($getIcon),
        ];

        return $this->printTree($constraints, $attrs, 'constraints');
    }
}
