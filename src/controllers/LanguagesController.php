<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class LanguagesController extends BaseController
{
    public $controller_name = 'LanguagesController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang   = $this->lang;
        $action = $this->action;
        if ('tree' == $action) {
            return $this->doTree();
        }

        $this->printHeader($lang['strlanguages']);
        $this->printBody();

        switch ($action) {
            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * Show default list of languages in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('database');
        $this->printTabs('database', 'languages');
        $this->printMsg($msg);

        $languages = $data->getLanguages();

        $columns = [
            'language' => [
                'title' => $lang['strname'],
                'field' => Decorator::field('lanname'),
            ],
            'trusted' => [
                'title' => $lang['strtrusted'],
                'field' => Decorator::field('lanpltrusted'),
                'type'  => 'yesno',
            ],
            'function' => [
                'title' => $lang['strfunction'],
                'field' => Decorator::field('lanplcallf'),
            ],
        ];

        $actions = [];

        echo $this->printTable($languages, $columns, $actions, 'languages-languages', $lang['strnolanguages']);
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $languages = $data->getLanguages();

        $attrs = [
            'text' => Decorator::field('lanname'),
            'icon' => 'Language',
        ];

        return $this->printTree($languages, $attrs, 'languages');
    }
}
