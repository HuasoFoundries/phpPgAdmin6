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
class CastsController extends BaseController
{
    public $controller_title = 'strcasts';

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

        $lang = $this->lang;
        $renderCastContext = static function ($val) use ($lang) {
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
            'function' => [
                'title' => $this->lang['strfunction'],
                'field' => Decorator::field('castfunc'),
                'params' => ['null' => $this->lang['strbinarycompat']],
            ],
            'implicit' => [
                'title' => $this->lang['strimplicit'],
                'field' => Decorator::field('castcontext'),
                'type' => 'callback',
                'params' => ['function' => $renderCastContext, 'align' => 'center'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('castcomment'),
            ],
        ];

        $actions = [];

        if (self::isRecordset($casts)) {
            echo $this->printTable($casts, $columns, $actions, 'casts-casts', $this->lang['strnocasts']);
        }

        return '';
    }

    /**
     * Generate XML for the browser tree.
     *
     * @return Response|string
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
