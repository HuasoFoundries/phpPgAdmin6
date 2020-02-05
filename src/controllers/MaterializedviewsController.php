<?php

/**
 * PHPPgAdmin v6.0.0-RC6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class MaterializedviewsController extends BaseController
{
    use \PHPPgAdmin\Traits\ViewsMatviewsTrait;

    public $table_place      = 'matviews-matviews';
    public $controller_title = 'strviews';

    // this member variable is view for views and matview for materialized views
    public $keystring = 'matview';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }
        if ('subtree' == $this->action) {
            return $this->doSubTree();
        }

        $this->printHeader('M '.$this->lang['strviews']);
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
                    $this->doSaveCreateWiz(true);
                }

                break;
            case 'wiz_create':
                $this->doWizardCreate();

                break;
            case 'set_params_create':
                if (isset($_POST['cancel'])) {
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
                if (isset($_POST['drop'])) {
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

        $this->printFooter();
    }

    /**
     * Show default list of views in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'matviews');
        $this->printMsg($msg);

        $matviews = $data->getMaterializedViews();

        $columns = [
            $this->keystring => [
                'title' => 'M '.$this->lang['strview'],
                'field' => Decorator::field('relname'),
                'url'   => \SUBFOLDER."/redirect/matview?{$this->misc->href}&amp;",
                'vars'  => [$this->keystring => 'relname'],
            ],
            'owner'          => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('relowner'),
            ],
            'actions'        => [
                'title' => $this->lang['stractions'],
            ],
            'comment'        => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('relcomment'),
            ],
        ];

        $actions = [
            'multiactions' => [
                'keycols' => [$this->keystring => 'relname'],
                'url'     => 'materializedviews',
            ],
            'browse'       => [
                'content' => $this->lang['strbrowse'],
                'attr'    => [
                    'href' => [
                        'url'     => 'display',
                        'urlvars' => [
                            'action'         => 'confselectrows',
                            'subject'        => $this->keystring,
                            'return'         => 'schema',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'select'       => [
                'content' => $this->lang['strselect'],
                'attr'    => [
                    'href' => [
                        'url'     => 'materializedviews',
                        'urlvars' => [
                            'action'         => 'confselectrows',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],

            // Insert is possible if the relevant rule for the matview has been created.
            //            'insert' => array(
            //                'title'    => $this->lang['strinsert'],
            //                'url'    => "materializedviews?action=confinsertrow&amp;{$this->misc->href}&amp;",
            //                'vars'    => array('matview' => 'relname'),
            //            ),

            'alter'        => [
                'content' => $this->lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'materializedviewproperties',
                        'urlvars' => [
                            'action'         => 'confirm_alter',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'drop'         => [
                'multiaction' => 'confirm_drop',
                'content'     => $this->lang['strdrop'],
                'attr'        => [
                    'href' => [
                        'url'     => 'materializedviews',
                        'urlvars' => [
                            'action'         => 'confirm_drop',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($matviews, $columns, $actions, $this->table_place, $this->lang['strnoviews']);

        $navlinks = [
            'create'    => [
                'attr'    => [
                    'href' => [
                        'url'     => 'materializedviews',
                        'urlvars' => [
                            'action'   => 'create',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreateview'],
            ],
            'createwiz' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'materializedviews',
                        'urlvars' => [
                            'action'   => 'wiz_create',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreatematviewwiz'],
            ],
        ];
        $this->printNavLinks($navlinks, $this->table_place, get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $matviews = $data->getMaterializedViews();

        $reqvars = $this->misc->getRequestVars($this->keystring);

        $attrs = [
            'text'       => Decorator::field('relname'),
            'icon'       => 'MViews',
            'iconAction' => Decorator::url('display', $reqvars, [$this->keystring => Decorator::field('relname')]),
            'toolTip'    => Decorator::field('relcomment'),
            'action'     => Decorator::redirecturl('redirect', $reqvars, [$this->keystring => Decorator::field('relname')]),
            'branch'     => Decorator::url('materializedviews', $reqvars, ['action' => 'subtree', $this->keystring => Decorator::field('relname')]),
        ];

        return $this->printTree($matviews, $attrs, 'matviews');
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['matview']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($this->lang['strspecifyviewtodrop']);
        }

        if ($confirm) {
            $this->printTrail('getTrail');
            $this->printTitle($this->lang['strdrop'], 'pg.matview.drop');

            echo '<form action="'.\SUBFOLDER.'/src/views/materializedviews" method="post">'.PHP_EOL;

            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                    echo '<p>', sprintf($this->lang['strconfdropview'], $this->misc->printVal($a['view'])), '</p>'.PHP_EOL;
                    echo '<input type="hidden" name="view[]" value="', htmlspecialchars($a['view']), '" />'.PHP_EOL;
                }
            } else {
                echo '<p>', sprintf($this->lang['strconfdropview'], $this->misc->printVal($_REQUEST['matview'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="view" value="', htmlspecialchars($_REQUEST['matview']), '" />'.PHP_EOL;
            }

            echo '<input type="hidden" name="action" value="drop" />'.PHP_EOL;

            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>".PHP_EOL;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            if (is_array($_POST['view'])) {
                $msg    = '';
                $status = $data->beginTransaction();
                if (0 == $status) {
                    foreach ($_POST['view'] as $s) {
                        $status = $data->dropView($s, isset($_POST['cascade']));
                        if (0 == $status) {
                            $msg .= sprintf('%s: %s<br />', htmlentities($s, ENT_QUOTES, 'UTF-8'), $this->lang['strviewdropped']);
                        } else {
                            $data->endTransaction();
                            $this->doDefault(sprintf('%s%s: %s<br />', $msg, htmlentities($s, ENT_QUOTES, 'UTF-8'), $this->lang['strviewdroppedbad']));

                            return;
                        }
                    }
                }
                if (0 == $data->endTransaction()) {
                    // Everything went fine, back to the Default page....
                    $this->misc->setReloadBrowser(true);
                    $this->doDefault($msg);
                } else {
                    $this->doDefault($this->lang['strviewdroppedbad']);
                }
            } else {
                $status = $data->dropView($_POST['view'], isset($_POST['cascade']));
                if (0 == $status) {
                    $this->misc->setReloadBrowser(true);
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
        $this->printTitle($this->lang['strcreatematviewwiz'], 'pg.matview.create');
        $this->printMsg($msg);

        $this->printParamsCreateForm();
    }

    /**
     * Display a wizard where they can enter a new view.
     *
     * @param mixed $msg
     */
    public function doWizardCreate($msg = '')
    {
        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreatematviewwiz'], 'pg.matview.create');
        $this->printMsg($msg);

        $this->printWizardCreateForm();
    }

    /**
     * Displays a screen where they can enter a new view.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
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
        $this->printTitle($this->lang['strcreateview'], 'pg.matview.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/{$this->view_name}\" method=\"post\">".PHP_EOL;
        echo '<table style="width: 100%">'.PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>".PHP_EOL;
        echo "\t<td class=\"data1\"><input name=\"formView\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['formView']), "\" /></td>\n\t</tr>".PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strdefinition']}</th>".PHP_EOL;
        echo "\t<td class=\"data1\"><textarea style=\"width:100%;\" rows=\"10\" cols=\"50\" name=\"formDefinition\">",
        htmlspecialchars($_REQUEST['formDefinition']), "</textarea></td>\n\t</tr>".PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>".PHP_EOL;
        echo "\t\t<td class=\"data1\"><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
        htmlspecialchars($_REQUEST['formComment']), "</textarea></td>\n\t</tr>".PHP_EOL;
        echo '</table>'.PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create" />'.PHP_EOL;
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;
    }

    /**
     * Actually creates the new view in the database.
     */
    public function doSaveCreate()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name and a definition
        if ('' == $_POST['formView']) {
            $this->doCreate($this->lang['strviewneedsname']);
        } elseif ('' == $_POST['formDefinition']) {
            $this->doCreate($this->lang['strviewneedsdef']);
        } else {
            $status = $data->createView($_POST['formView'], $_POST['formDefinition'], false, $_POST['formComment'], true);
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doDefault($this->lang['strviewcreated']);
            } else {
                $this->doCreate($this->lang['strviewcreatedbad']);
            }
        }
    }
}
