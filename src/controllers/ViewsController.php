<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use PHPPgAdmin\Traits\ViewsMatviewsTrait;

/**
 * Base controller class.
 */
class ViewsController extends BaseController
{
    use ViewsMatviewsTrait;

    public $table_place = 'views-views';

    public $controller_title = 'strviews';

    // this member variable is view for views and matview for materialized views
    public $keystring = 'view';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        if ('subtree' === $this->action) {
            return $this->doSubTree();
        }

        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            case 'selectrows':
                if (!isset($_REQUEST['cancel'])) {
                    $this->doSelectRows(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confselectrows':
                $this->doSelectRows(true);

                break;
            case 'save_create_wiz':
                if (isset($_REQUEST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreateWiz(false);
                }

                break;
            case 'wiz_create':
                $this->doWizardCreate();

                break;
            case 'set_params_create':
                if (null !== $this->getPostParam('cancel')) {
                    $this->doDefault();
                } else {
                    $this->doSetParamsCreate();
                }

                break;
            case 'save_create':
                if (isset($_REQUEST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'create':
                $this->doCreate();

                break;
            case 'drop':
                if (null !== $this->getPostParam('drop')) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;

            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * Show default list of views in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'views');
        $this->printMsg($msg);

        $views = $data->getViews();

        $columns = [
            $this->keystring => [
                'title' => $this->lang['strview'],
                'field' => Decorator::field('relname'),
                'url' => \containerInstance()->getDestinationWithLastTab('view'),
                    '/redirect/view?%s&amp;',
                    $this->misc->href
                ),
                'vars' => [$this->keystring => 'relname'],
            ],
            'owner' => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('relowner'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('relcomment'),
            ],
        ];

        $actions = [
            'multiactions' => [
                'keycols' => [$this->keystring => 'relname'],
                'url' => 'views',
            ],
            'browse' => [
                'content' => $this->lang['strbrowse'],
                'attr' => [
                    'href' => [
                        'url' => 'display',
                        'urlvars' => [
                            'action' => 'confselectrows',
                            'subject' => $this->keystring,
                            'return' => 'schema',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'select' => [
                'content' => $this->lang['strselect'],
                'attr' => [
                    'href' => [
                        'url' => 'views',
                        'urlvars' => [
                            'action' => 'confselectrows',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],

            // Insert is possible if the relevant rule for the view has been created.
            //            'insert' => array(
            //                'title'    => $this->lang['strinsert'],
            //                'url'    => "views?action=confinsertrow&amp;{$this->misc->href}&amp;",
            //                'vars'    => array($this->keystring => 'relname'),
            //            ),

            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'viewproperties',
                        'urlvars' => [
                            'action' => 'confirm_alter',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'multiaction' => 'confirm_drop',
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'views',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($views, $columns, $actions, $this->table_place, $this->lang['strnoviews']);

        $navlinks = [
            'create' => [
                'attr' => [
                    'href' => [
                        'url' => 'views',
                        'urlvars' => [
                            'action' => 'create',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreateview'],
            ],
            'createwiz' => [
                'attr' => [
                    'href' => [
                        'url' => 'views',
                        'urlvars' => [
                            'action' => 'wiz_create',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreateviewwiz'],
            ],
        ];
        $this->printNavLinks($navlinks, $this->table_place, \get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     *
     * @return \Slim\Http\Response|string
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $views = $data->getViews();

        $reqvars = $this->misc->getRequestVars($this->keystring);

        $attrs = [
            'text' => Decorator::field('relname'),
            'icon' => 'View',
            'iconAction' => Decorator::url('display', $reqvars, [$this->keystring => Decorator::field('relname')]),
            'toolTip' => Decorator::field('relcomment'),
            'action' => Decorator::redirecturl('redirect', $reqvars, [$this->keystring => Decorator::field('relname')]),
            'branch' => Decorator::url('views', $reqvars, ['action' => 'subtree', $this->keystring => Decorator::field('relname')]),
        ];

        return $this->printTree($views, $attrs, 'views');
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['view']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($this->lang['strspecifyviewtodrop']);
        }

        if ($confirm) {
            $this->printTrail('view');
            $this->printTitle($this->lang['strdrop'], 'pg.view.drop');

            echo '<form action="' . \containerInstance()->subFolder . '/src/views/views" method="post">' . \PHP_EOL;

            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    echo '<p>', \sprintf(
                        $this->lang['strconfdropview'],
                        $this->misc->printVal($a['view'])
                    ), '</p>' . \PHP_EOL;
                    echo '<input type="hidden" name="view[]" value="', \htmlspecialchars($a['view']), '" />' . \PHP_EOL;
                }
            } else {
                echo '<p>', \sprintf(
                    $this->lang['strconfdropview'],
                    $this->misc->printVal($_REQUEST['view'])
                ), '</p>' . \PHP_EOL;
                echo '<input type="hidden" name="view" value="', \htmlspecialchars($_REQUEST['view']), '" />' . \PHP_EOL;
            }

            echo '<input type="hidden" name="action" value="drop" />' . \PHP_EOL;

            echo $this->view->form;
            echo \sprintf(
                '<p><input type="checkbox" id="cascade" name="cascade" /> <label for="cascade">%s</label></p>',
                $this->lang['strcascade']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="drop" value="%s" />',
                $this->lang['strdrop']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s" />',
                $this->lang['strcancel']
            ) . \PHP_EOL;
            echo '</form>' . \PHP_EOL;
        } else {
            if (\is_array($_POST['view'])) {
                $msg = '';
                $status = $data->beginTransaction();

                if (0 === $status) {
                    foreach ($_POST['view'] as $s) {
                        $status = $data->dropView($s, isset($_POST['cascade']));

                        if (0 === $status) {
                            $msg .= \sprintf(
                                '%s: %s<br />',
                                \htmlentities($s, \ENT_QUOTES, 'UTF-8'),
                                $this->lang['strviewdropped']
                            );
                        } else {
                            $data->endTransaction();
                            $this->doDefault(\sprintf(
                                '%s%s: %s<br />',
                                $msg,
                                \htmlentities($s, \ENT_QUOTES, 'UTF-8'),
                                $this->lang['strviewdroppedbad']
                            ));

                            return;
                        }
                    }
                }

                if (0 === $data->endTransaction()) {
                    // Everything went fine, back to the Default page....
                    $this->view->setReloadBrowser(true);
                    $this->doDefault($msg);
                } else {
                    $this->doDefault($this->lang['strviewdroppedbad']);
                }
            } else {
                $status = $data->dropView($_POST['view'], isset($_POST['cascade']));

                if (0 === $status) {
                    $this->view->setReloadBrowser(true);
                    $this->doDefault($this->lang['strviewdropped']);
                } else {
                    $this->doDefault($this->lang['strviewdroppedbad']);
                }
            }
        }
    }

    /**
     * Sets up choices for table linkage, and which fields to select for the view we're creating.
     *
     * @param mixed $msg
     */
    public function doSetParamsCreate($msg = '')
    {
        // Check that they've chosen tables for the view definition
        if (!isset($_POST['formTables'])) {
            return $this->doWizardCreate($this->lang['strviewneedsdef']);
        }
        // Initialise variables
        $this->coalesceArr($_REQUEST, 'formView', '');

        $this->coalesceArr($_REQUEST, 'formComment', '');

        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreateviewwiz'], 'pg.view.create');
        $this->printMsg($msg);

        $this->printParamsCreateForm();
    }

    /**
     * Display a wizard where they can enter a new view.
     *
     * @param mixed $msg
     */
    public function doWizardCreate($msg = ''): void
    {
        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreateviewwiz'], 'pg.view.create');
        $this->printMsg($msg);

        $this->printWizardCreateForm();
    }

    /**
     * Displays a screen where they can enter a new view.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'formView', '');

        if (!isset($_REQUEST['formDefinition'])) {
            if (isset($_SESSION['sqlquery'])) {
                $_REQUEST['formDefinition'] = $_SESSION['sqlquery'];
            } else {
                $_REQUEST['formDefinition'] = 'SELECT ';
            }
        }
        $this->coalesceArr($_REQUEST, 'formComment', '');

        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreateview'], 'pg.view.create');
        $this->printMsg($msg);

        echo '<form action="' . \containerInstance()->subFolder . \sprintf(
            '/src/views/%s" method="post">',
            $this->view_name
        ) . \PHP_EOL;
        echo '<table style="width: 100%">' . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '	<td class="data1"><input name="formView" size="32" maxlength="%s" value="',
            $data->_maxNameLen
        ),
        \htmlspecialchars($_REQUEST['formView']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strdefinition']
        ) . \PHP_EOL;
        echo "\t<td class=\"data1\"><textarea style=\"width:100%;\" rows=\"10\" cols=\"50\" name=\"formDefinition\">",
        \htmlspecialchars($_REQUEST['formDefinition']), "</textarea></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strcomment']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
        \htmlspecialchars($_REQUEST['formComment']), "</textarea></td>\n\t</tr>" . \PHP_EOL;
        echo '</table>' . \PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create" />' . \PHP_EOL;
        echo $this->view->form;
        echo \sprintf(
            '<input type="submit" value="%s" />',
            $this->lang['strcreate']
        ) . \PHP_EOL;
        echo \sprintf(
            '<input type="submit" name="cancel" value="%s"  /></p>%s',
            $this->lang['strcancel'],
            \PHP_EOL
        );
        echo '</form>' . \PHP_EOL;
    }

    /**
     * Actually creates the new view in the database.
     */
    public function doSaveCreate(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name and a definition
        if ('' === $_POST['formView']) {
            $this->doCreate($this->lang['strviewneedsname']);
        } elseif ('' === $_POST['formDefinition']) {
            $this->doCreate($this->lang['strviewneedsdef']);
        } else {
            $status = $data->createView($_POST['formView'], $_POST['formDefinition'], false, $_POST['formComment']);

            if (0 === $status) {
                $this->view->setReloadBrowser(true);
                $this->doDefault($this->lang['strviewcreated']);
            } else {
                $this->doCreate($this->lang['strviewcreatedbad']);
            }
        }
    }
}
