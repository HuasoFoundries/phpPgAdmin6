<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class CastsController extends BaseController
{
    public $controller_name = 'CastsController';

    /**
     * Default method to render the controller according to the action parameter
     */
    public function render()
    {
        $conf = $this->conf;

        $lang   = $this->lang;
        $action = $this->action;
        if ('tree' == $action) {
            return $this->doTree();
        }
        $data = $this->misc->getDatabaseAccessor();

        $this->printHeader($lang['strcasts']);
        $this->printBody();

        switch ($action) {
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * Show default list of casts in the database
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $renderCastContext = function ($val) use ($lang) {
            switch ($val) {
                case 'e':return $lang['strno'];
                case 'a':return $lang['strinassignment'];
                default:return $lang['stryes'];
            }
        };

        $this->printTrail('database');
        $this->printTabs('database', 'casts');
        $this->printMsg($msg);

        $casts = $data->getCasts();

        $columns = [
            'source_type' => [
                'title' => $lang['strsourcetype'],
                'field' => Decorator::field('castsource'),
            ],
            'target_type' => [
                'title' => $lang['strtargettype'],
                'field' => Decorator::field('casttarget'),
            ],
            'function'    => [
                'title'  => $lang['strfunction'],
                'field'  => Decorator::field('castfunc'),
                'params' => ['null' => $lang['strbinarycompat']],
            ],
            'implicit'    => [
                'title'  => $lang['strimplicit'],
                'field'  => Decorator::field('castcontext'),
                'type'   => 'callback',
                'params' => ['function' => $renderCastContext, 'align' => 'center'],
            ],
            'comment'     => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('castcomment'),
            ],
        ];

        $actions = [];

        echo $this->printTable($casts, $columns, $actions, 'casts-casts', $lang['strnocasts']);
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $conf = $this->conf;

        $lang = $this->lang;
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
