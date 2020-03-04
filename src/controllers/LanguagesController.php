<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class LanguagesController extends BaseController
{
    public $controller_title = 'strlanguages';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
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
    public function doDefault($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('database');
        $this->printTabs('database', 'languages');
        $this->printMsg($msg);

        $languages = $data->getLanguages();

        $columns = [
            'language' => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('lanname'),
            ],
            'trusted' => [
                'title' => $this->lang['strtrusted'],
                'field' => Decorator::field('lanpltrusted'),
                'type' => 'yesno',
            ],
            'function' => [
                'title' => $this->lang['strfunction'],
                'field' => Decorator::field('lanplcallf'),
            ],
        ];

        $actions = [];

        echo $this->printTable($languages, $columns, $actions, 'languages-languages', $this->lang['strnolanguages']);
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $languages = $data->getLanguages();

        $attrs = [
            'text' => Decorator::field('lanname'),
            'icon' => 'Language',
        ];

        return $this->printTree($languages, $attrs, 'languages');
    }
}
