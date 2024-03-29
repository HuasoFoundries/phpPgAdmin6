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
class ConversionsController extends BaseController
{
    public $controller_title = 'strconversions';

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
     * Show default list of conversions in the database.
     *
     * @param string $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'conversions');
        $this->printMsg($msg);

        $conversions = $data->getconversions();

        $columns = [
            'conversion' => [
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
            'default' => [
                'title' => $this->lang['strdefault'],
                'field' => Decorator::field('condefault'),
                'type' => 'yesno',
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('concomment'),
            ],
        ];

        $actions = [];

        if (self::isRecordset($conversions)) {
            echo $this->printTable($conversions, $columns, $actions, 'conversions-conversions', $this->lang['strnoconversions']);
        }

        return '';
    }

    /**
     * @return Response|string
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $constraints = $data->getConstraints($_REQUEST['table']);

        $getIcon = /**
         * @param mixed $f
         *
         * @return null|string
         */
        static function ($f) {
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
