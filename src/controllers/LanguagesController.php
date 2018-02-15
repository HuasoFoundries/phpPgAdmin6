<?php

namespace PHPPgAdmin\Controller;

use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class LanguagesController extends BaseController
{
    public $controller_name = 'LanguagesController';

    public function render()
    {
        $conf = $this->conf;

        $lang   = $this->lang;
        $action = $this->action;
        if ($action == 'tree') {
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
     * Show default list of languages in the database
     */
    public function doDefault($msg = '')
    {
        $conf = $this->conf;

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
            'trusted'  => [
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
        $conf = $this->conf;

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
