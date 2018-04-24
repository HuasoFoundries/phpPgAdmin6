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
class CastsController extends BaseController
{
    public $controller_name = 'CastsController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $this->printHeader($this->lang['strcasts']);
        $this->printBody();

        switch ($this->action) {
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * Show default list of casts in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $lang              = $this->lang;
        $renderCastContext = function ($val) use ($lang) {
            switch ($val) {
                case 'e':
                    return $lang['strno'];
                case 'a':
                    return $lang['strinassignment'];
                default:
                    return $lang['stryes'];
            }
        };

        $this->printTrail('database');
        $this->printTabs('database', 'casts');
        $this->printMsg($msg);

        $casts = $data->getCasts();

        $columns = [
            'source_type' => [
                'title' => $this->lang['strsourcetype'],
                'field' => Decorator::field('castsource'),
            ],
            'target_type' => [
                'title' => $this->lang['strtargettype'],
                'field' => Decorator::field('casttarget'),
            ],
            'function'    => [
                'title'  => $this->lang['strfunction'],
                'field'  => Decorator::field('castfunc'),
                'params' => ['null' => $this->lang['strbinarycompat']],
            ],
            'implicit'    => [
                'title'  => $this->lang['strimplicit'],
                'field'  => Decorator::field('castcontext'),
                'type'   => 'callback',
                'params' => ['function' => $renderCastContext, 'align' => 'center'],
            ],
            'comment'     => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('castcomment'),
            ],
        ];

        $actions = [];

        echo $this->printTable($casts, $columns, $actions, 'casts-casts', $this->lang['strnocasts']);
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $casts = $data->getCasts();

        $proto = Decorator::concat(Decorator::field('castsource'), ' AS ', Decorator::field('casttarget'));

        $attrs = [
            'text' => $proto,
            'icon' => 'Cast',
        ];

        return $this->printTree($casts, $attrs, 'casts');
    }
}
