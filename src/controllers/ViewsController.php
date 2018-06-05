<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class ViewsController extends BaseController
{
    use \PHPPgAdmin\Traits\ViewsMatviewsTrait;

    public $table_place      = 'views-views';
    public $controller_title = 'strviews';

    // this member variable is view for views and matview for materialized views
    public $keystring = 'view';

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

        return $this->printFooter();
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
        $this->printTabs('schema', 'views');
        $this->printMsg($msg);

        $views = $data->getViews();

        $columns = [
            $this->keystring => [
                'title' => $this->lang['strview'],
                'field' => Decorator::field('relname'),
                'url'   => \SUBFOLDER."/redirect/view?{$this->misc->href}&amp;",
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
                'url'     => 'views',
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
                        'url'     => 'views',
                        'urlvars' => [
                            'action'         => 'confselectrows',
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

            'alter'        => [
                'content' => $this->lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'viewproperties',
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
                        'url'     => 'views',
                        'urlvars' => [
                            'action'         => 'confirm_drop',
                            $this->keystring => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($views, $columns, $actions, $this->table_place, $this->lang['strnoviews']);

        $navlinks = [
            'create'    => [
                'attr'    => [
                    'href' => [
                        'url'     => 'views',
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
                        'url'     => 'views',
                        'urlvars' => [
                            'action'   => 'wiz_create',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreateviewwiz'],
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

        $views = $data->getViews();

        $reqvars = $this->misc->getRequestVars($this->keystring);

        $attrs = [
            'text'       => Decorator::field('relname'),
            'icon'       => 'View',
            'iconAction' => Decorator::url('display', $reqvars, [$this->keystring => Decorator::field('relname')]),
            'toolTip'    => Decorator::field('relcomment'),
            'action'     => Decorator::redirecturl('redirect', $reqvars, [$this->keystring => Decorator::field('relname')]),
            'branch'     => Decorator::url('views', $reqvars, ['action' => 'subtree', $this->keystring => Decorator::field('relname')]),
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

            echo '<form action="'.\SUBFOLDER."/src/views/views\" method=\"post\">\n";

            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                    echo '<p>', sprintf($this->lang['strconfdropview'], $this->misc->printVal($a['view'])), "</p>\n";
                    echo '<input type="hidden" name="view[]" value="', htmlspecialchars($a['view']), "\" />\n";
                }
            } else {
                echo '<p>', sprintf($this->lang['strconfdropview'], $this->misc->printVal($_REQUEST['view'])), "</p>\n";
                echo '<input type="hidden" name="view" value="', htmlspecialchars($_REQUEST['view']), "\" />\n";
            }

            echo "<input type=\"hidden\" name=\"action\" value=\"drop\" />\n";

            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>\n";
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />\n";
            echo "</form>\n";
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
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've chosen tables for the view definition
        if (!isset($_POST['formTables'])) {
            $this->doWizardCreate($this->lang['strviewneedsdef']);
        } else {
            // Initialise variables
            $this->coalesceArr($_REQUEST, 'formView', '');

            $this->coalesceArr($_REQUEST, 'formComment', '');

            $this->printTrail('schema');
            $this->printTitle($this->lang['strcreateviewwiz'], 'pg.view.create');
            $this->printMsg($msg);

            $tblCount = sizeof($_POST['formTables']);
            //unserialize our schema/table information and store in arrSelTables
            for ($i = 0; $i < $tblCount; ++$i) {
                $arrSelTables[] = unserialize($_POST['formTables'][$i]);
            }

            $linkCount = $tblCount;

            //get linking keys
            $rsLinkKeys = $data->getLinkingKeys($arrSelTables);
            $linkCount  = $rsLinkKeys->recordCount() > $tblCount ? $rsLinkKeys->recordCount() : $tblCount;

            $arrFields = []; //array that will hold all our table/field names

            //if we have schemas we need to specify the correct schema for each table we're retrieiving
            //with getTableAttributes
            $curSchema = $data->_schema;
            for ($i = 0; $i < $tblCount; ++$i) {
                if ($arrSelTables[$i]['schemaname'] != $data->_schema) {
                    $data->setSchema($arrSelTables[$i]['schemaname']);
                }

                $attrs = $data->getTableAttributes($arrSelTables[$i]['tablename']);
                while (!$attrs->EOF) {
                    $arrFields["{$arrSelTables[$i]['schemaname']}.{$arrSelTables[$i]['tablename']}.{$attrs->fields['attname']}"] = serialize(
                        [
                            'schemaname' => $arrSelTables[$i]['schemaname'],
                            'tablename'  => $arrSelTables[$i]['tablename'],
                            'fieldname'  => $attrs->fields['attname'], ]
                    );
                    $attrs->moveNext();
                }

                $data->setSchema($curSchema);
            }
            asort($arrFields);

            echo '<form action="'.\SUBFOLDER."/src/views/views\" method=\"post\">\n";
            echo "<table>\n";
            echo "<tr><th class=\"data\">{$this->lang['strviewname']}</th></tr>";
            echo "<tr>\n<td class=\"data1\">\n";
            // View name
            echo '<input name="formView" value="', htmlspecialchars($_REQUEST['formView']), "\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" />\n";
            echo "</td>\n</tr>\n";
            echo "<tr><th class=\"data\">{$this->lang['strcomment']}</th></tr>";
            echo "<tr>\n<td class=\"data1\">\n";
            // View comments
            echo '<textarea name="formComment" rows="3" cols="32">',
            htmlspecialchars($_REQUEST['formComment']), "</textarea>\n";
            echo "</td>\n</tr>\n";
            echo "</table>\n";

            // Output selector for fields to be retrieved from view
            echo "<table>\n";
            echo "<tr><th class=\"data\">{$this->lang['strcolumns']}</th></tr>";
            echo "<tr>\n<td class=\"data1\">\n";
            echo \PHPPgAdmin\XHtml\HTMLController::printCombo($arrFields, 'formFields[]', false, '', true);
            echo "</td>\n</tr>";
            echo "<tr><td><input type=\"radio\" name=\"dblFldMeth\" id=\"dblFldMeth1\" value=\"rename\" /><label for=\"dblFldMeth1\">{$this->lang['strrenamedupfields']}</label>";
            echo "<br /><input type=\"radio\" name=\"dblFldMeth\" id=\"dblFldMeth2\" value=\"drop\" /><label for=\"dblFldMeth2\">{$this->lang['strdropdupfields']}</label>";
            echo "<br /><input type=\"radio\" name=\"dblFldMeth\" id=\"dblFldMeth3\" value=\"\" checked=\"checked\" /><label for=\"dblFldMeth3\">{$this->lang['strerrordupfields']}</label></td></tr></table><br />";

            // Output the Linking keys combo boxes
            echo "<table>\n";
            echo "<tr><th class=\"data\">{$this->lang['strviewlink']}</th></tr>";
            $rowClass = 'data1';
            for ($i = 0; $i < $linkCount; ++$i) {
                // Initialise variables
                if (!isset($formLink[$i]['operator'])) {
                    $formLink[$i]['operator'] = 'INNER JOIN';
                }

                echo "<tr>\n<td class=\"${rowClass}\">\n";

                if (!$rsLinkKeys->EOF) {
                    $curLeftLink  = htmlspecialchars(serialize(['schemaname' => $rsLinkKeys->fields['p_schema'], 'tablename' => $rsLinkKeys->fields['p_table'], 'fieldname' => $rsLinkKeys->fields['p_field']]));
                    $curRightLink = htmlspecialchars(serialize(['schemaname' => $rsLinkKeys->fields['f_schema'], 'tablename' => $rsLinkKeys->fields['f_table'], 'fieldname' => $rsLinkKeys->fields['f_field']]));
                    $rsLinkKeys->moveNext();
                } else {
                    $curLeftLink  = '';
                    $curRightLink = '';
                }

                echo \PHPPgAdmin\XHtml\HTMLController::printCombo($arrFields, "formLink[${i}][leftlink]", true, $curLeftLink, false);
                echo \PHPPgAdmin\XHtml\HTMLController::printCombo($data->joinOps, "formLink[${i}][operator]", true, $formLink[$i]['operator']);
                echo \PHPPgAdmin\XHtml\HTMLController::printCombo($arrFields, "formLink[${i}][rightlink]", true, $curRightLink, false);
                echo "</td>\n</tr>\n";
                $rowClass = 'data1' == $rowClass ? 'data2' : 'data1';
            }
            echo "</table>\n<br />\n";

            // Build list of available operators (infix only)
            $arrOperators = [];
            foreach ($data->selectOps as $k => $v) {
                if ('i' == $v) {
                    $arrOperators[$k] = $k;
                }
            }

            // Output additional conditions, note that this portion of the wizard treats the right hand side as literal values
            //(not as database objects) so field names will be treated as strings, use the above linking keys section to perform joins
            echo "<table>\n";
            echo "<tr><th class=\"data\">{$this->lang['strviewconditions']}</th></tr>";
            $rowClass = 'data1';
            for ($i = 0; $i < $linkCount; ++$i) {
                echo "<tr>\n<td class=\"${rowClass}\">\n";
                echo \PHPPgAdmin\XHtml\HTMLController::printCombo($arrFields, "formCondition[${i}][field]");
                echo \PHPPgAdmin\XHtml\HTMLController::printCombo($arrOperators, "formCondition[${i}][operator]", false, '', false);
                echo "<input type=\"text\" name=\"formCondition[${i}][txt]\" />\n";
                echo "</td>\n</tr>\n";
                $rowClass = 'data1' == $rowClass ? 'data2' : 'data1';
            }
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create_wiz\" />\n";

            foreach ($arrSelTables as $curTable) {
                echo '<input type="hidden" name="formTables[]" value="'.htmlspecialchars(serialize($curTable))."\" />\n";
            }

            echo $this->misc->form;
            echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        }
    }

    /**
     * Display a wizard where they can enter a new view.
     *
     * @param mixed $msg
     */
    public function doWizardCreate($msg = '')
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
        $this->printTitle($this->lang['strcreateview'], 'pg.view.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/{$this->view_name}\" method=\"post\">\n";
        echo "<table style=\"width: 100%\">\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>\n";
        echo "\t<td class=\"data1\"><input name=\"formView\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['formView']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strdefinition']}</th>\n";
        echo "\t<td class=\"data1\"><textarea style=\"width:100%;\" rows=\"10\" cols=\"50\" name=\"formDefinition\">",
        htmlspecialchars($_REQUEST['formDefinition']), "</textarea></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>\n";
        echo "\t\t<td class=\"data1\"><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
        htmlspecialchars($_REQUEST['formComment']), "</textarea></td>\n\t</tr>\n";
        echo "</table>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
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
            $status = $data->createView($_POST['formView'], $_POST['formDefinition'], false, $_POST['formComment']);
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doDefault($this->lang['strviewcreated']);
            } else {
                $this->doCreate($this->lang['strviewcreatedbad']);
            }
        }
    }
}
