<?php

/**
 * PHPPgAdmin v6.0.0-beta.39
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class TriggersController extends BaseController
{
    public $controller_name = 'TriggersController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang = $this->lang;

        $action = $this->action;
        if ('tree' == $action) {
            return $this->doTree();
        }

        $this->printHeader($lang['strtables'].' - '.$_REQUEST['table'].' - '.$lang['strtriggers']);
        $this->printBody();

        switch ($action) {
            case 'alter':
                if (isset($_POST['alter'])) {
                    $this->doSaveAlter();
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_alter':
                $this->doAlter();

                break;
            case 'confirm_enable':
                $this->doEnable(true);

                break;
            case 'confirm_disable':
                $this->doDisable(true);

                break;
            case 'save_create':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'create':
                $this->doCreate();

                break;
            case 'drop':
                if (isset($_POST['yes'])) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'enable':
                if (isset($_POST['yes'])) {
                    $this->doEnable(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'disable':
                if (isset($_POST['yes'])) {
                    $this->doDisable(false);
                } else {
                    $this->doDefault();
                }

                break;
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * List all the triggers on the table.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $tgPre = function (&$rowdata, $actions) use ($data) {
            // toggle enable/disable trigger per trigger
            if (!$data->phpBool($rowdata->fields['tgenabled'])) {
                unset($actions['disable']);
            } else {
                unset($actions['enable']);
            }

            return $actions;
        };

        $this->printTrail('table');
        $this->printTabs('table', 'triggers');
        $this->printMsg($msg);

        $triggers = $data->getTriggers($_REQUEST['table']);

        $columns = [
            'trigger'    => [
                'title' => $lang['strname'],
                'field' => Decorator::field('tgname'),
            ],
            'definition' => [
                'title' => $lang['strdefinition'],
                'field' => Decorator::field('tgdef'),
            ],
            'function'   => [
                'title' => $lang['strfunction'],
                'field' => Decorator::field('proproto'),
                'url'   => "functions?action=properties&amp;server={$_REQUEST['server']}&amp;database={$_REQUEST['database']}&amp;",
                'vars'  => [
                    'schema'       => 'pronamespace',
                    'function'     => 'proproto',
                    'function_oid' => 'prooid',
                ],
            ],
            'actions'    => [
                'title' => $lang['stractions'],
            ],
        ];

        $actions = [
            'alter' => [
                'content' => $lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'triggers',
                        'urlvars' => [
                            'action'  => 'confirm_alter',
                            'table'   => $_REQUEST['table'],
                            'trigger' => Decorator::field('tgname'),
                        ],
                    ],
                ],
            ],
            'drop'  => [
                'content' => $lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'triggers',
                        'urlvars' => [
                            'action'  => 'confirm_drop',
                            'table'   => $_REQUEST['table'],
                            'trigger' => Decorator::field('tgname'),
                        ],
                    ],
                ],
            ],
        ];
        if ($data->hasDisableTriggers()) {
            $actions['enable'] = [
                'content' => $lang['strenable'],
                'attr'    => [
                    'href' => [
                        'url'     => 'triggers',
                        'urlvars' => [
                            'action'  => 'confirm_enable',
                            'table'   => $_REQUEST['table'],
                            'trigger' => Decorator::field('tgname'),
                        ],
                    ],
                ],
            ];
            $actions['disable'] = [
                'content' => $lang['strdisable'],
                'attr'    => [
                    'href' => [
                        'url'     => 'triggers',
                        'urlvars' => [
                            'action'  => 'confirm_disable',
                            'table'   => $_REQUEST['table'],
                            'trigger' => Decorator::field('tgname'),
                        ],
                    ],
                ],
            ];
        }

        echo $this->printTable($triggers, $columns, $actions, 'triggers-triggers', $lang['strnotriggers'], $tgPre);

        $this->printNavLinks(['create' => [
            'attr'    => [
                'href' => [
                    'url'     => 'triggers',
                    'urlvars' => [
                        'action'   => 'create',
                        'server'   => $_REQUEST['server'],
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'table'    => $_REQUEST['table'],
                    ],
                ],
            ],
            'content' => $lang['strcreatetrigger'],
        ]], 'triggers-triggers', get_defined_vars());
    }

    public function doTree()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $triggers = $data->getTriggers($_REQUEST['table']);

        $reqvars = $this->misc->getRequestVars('table');

        $attrs = [
            'text' => Decorator::field('tgname'),
            'icon' => 'Trigger',
        ];

        return $this->printTree($triggers, $attrs, 'triggers');
    }

    /**
     * Function to save after altering a trigger.
     */
    public function doSaveAlter()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->alterTrigger($_POST['table'], $_POST['trigger'], $_POST['name']);
        if (0 == $status) {
            $this->doDefault($lang['strtriggeraltered']);
        } else {
            $this->doAlter($lang['strtriggeralteredbad']);
        }
    }

    /**
     * Function to allow altering of a trigger.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('trigger');
        $this->printTitle($lang['stralter'], 'pg.trigger.alter');
        $this->printMsg($msg);

        $triggerdata = $data->getTrigger($_REQUEST['table'], $_REQUEST['trigger']);

        if ($triggerdata->recordCount() > 0) {
            if (!isset($_POST['name'])) {
                $_POST['name'] = $triggerdata->fields['tgname'];
            }

            echo '<form action="'.\SUBFOLDER."/src/views/triggers\" method=\"post\">\n";
            echo "<table>\n";
            echo "<tr><th class=\"data\">{$lang['strname']}</th>\n";
            echo '<td class="data1">';
            echo "<input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['name']), "\" />\n";
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"alter\" />\n";
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
            echo '<input type="hidden" name="trigger" value="', htmlspecialchars($_REQUEST['trigger']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$lang['strok']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            echo "<p>{$lang['strnodata']}</p>\n";
        }
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('trigger');
            $this->printTitle($lang['strdrop'], 'pg.trigger.drop');

            echo '<p>', sprintf(
                $lang['strconfdroptrigger'],
                $this->misc->printVal($_REQUEST['trigger']),
                $this->misc->printVal($_REQUEST['table'])
            ), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/triggers\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
            echo '<input type="hidden" name="trigger" value="', htmlspecialchars($_REQUEST['trigger']), "\" />\n";
            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$lang['strcascade']}</label></p>\n";
            echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />\n";
            echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />\n";
            echo "</form>\n";
        } else {
            $status = $data->dropTrigger($_POST['trigger'], $_POST['table'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($lang['strtriggerdropped']);
            } else {
                $this->doDefault($lang['strtriggerdroppedbad']);
            }
        }
    }

    /**
     * Show confirmation of enable trigger and perform enabling the trigger.
     *
     * @param mixed $confirm
     */
    public function doEnable($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('trigger');
            $this->printTitle($lang['strenable'], 'pg.table.alter');

            echo '<p>', sprintf(
                $lang['strconfenabletrigger'],
                $this->misc->printVal($_REQUEST['trigger']),
                $this->misc->printVal($_REQUEST['table'])
            ), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/triggers\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"enable\" />\n";
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
            echo '<input type="hidden" name="trigger" value="', htmlspecialchars($_REQUEST['trigger']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />\n";
            echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />\n";
            echo "</form>\n";
        } else {
            $status = $data->enableTrigger($_POST['trigger'], $_POST['table']);
            if (0 == $status) {
                $this->doDefault($lang['strtriggerenabled']);
            } else {
                $this->doDefault($lang['strtriggerenabledbad']);
            }
        }
    }

    /**
     * Show confirmation of disable trigger and perform disabling the trigger.
     *
     * @param mixed $confirm
     */
    public function doDisable($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('trigger');
            $this->printTitle($lang['strdisable'], 'pg.table.alter');

            echo '<p>', sprintf(
                $lang['strconfdisabletrigger'],
                $this->misc->printVal($_REQUEST['trigger']),
                $this->misc->printVal($_REQUEST['table'])
            ), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/triggers\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"disable\" />\n";
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
            echo '<input type="hidden" name="trigger" value="', htmlspecialchars($_REQUEST['trigger']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />\n";
            echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />\n";
            echo "</form>\n";
        } else {
            $status = $data->disableTrigger($_POST['trigger'], $_POST['table']);
            if (0 == $status) {
                $this->doDefault($lang['strtriggerdisabled']);
            } else {
                $this->doDefault($lang['strtriggerdisabledbad']);
            }
        }
    }

    /**
     * Let them create s.th.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('table');
        $this->printTitle($lang['strcreatetrigger'], 'pg.trigger.create');
        $this->printMsg($msg);

        // Get all the functions that can be used in triggers
        $funcs = $data->getTriggerFunctions();
        if (0 == $funcs->recordCount()) {
            $this->doDefault($lang['strnofunctions']);

            return;
        }

        // Populate functions
        $sel0 = new \PHPPgAdmin\XHtml\XHtmlSelect('formFunction');
        while (!$funcs->EOF) {
            $sel0->add(new \PHPPgAdmin\XHtml\XHtmlOption($funcs->fields['proname']));
            $funcs->moveNext();
        }

        // Populate times
        $sel1 = new \PHPPgAdmin\XHtml\XHtmlSelect('formExecTime');
        $sel1->set_data($data->triggerExecTimes);

        // Populate events
        $sel2 = new \PHPPgAdmin\XHtml\XHtmlSelect('formEvent');
        $sel2->set_data($data->triggerEvents);

        // Populate occurences
        $sel3 = new \PHPPgAdmin\XHtml\XHtmlSelect('formFrequency');
        $sel3->set_data($data->triggerFrequency);

        echo '<form action="'.\SUBFOLDER."/src/views/triggers\" method=\"post\">\n";
        echo "<table>\n";
        echo "<tr>\n";
        echo "		<th class=\"data\">{$lang['strname']}</th>\n";
        echo "		<th class=\"data\">{$lang['strwhen']}</th>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "		<td class=\"data1\"> <input type=\"text\" name=\"formTriggerName\" size=\"32\" /></td>\n";
        echo '		<td class="data1"> ', $sel1->fetch(), "</td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "    <th class=\"data\">{$lang['strevent']}</th>\n";
        echo "    <th class=\"data\">{$lang['strforeach']}</th>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo '     <td class="data1"> ', $sel2->fetch(), "</td>\n";
        echo '     <td class="data1"> ', $sel3->fetch(), "</td>\n";
        echo "</tr>\n";
        echo "<tr><th class=\"data\"> {$lang['strfunction']}</th>\n";
        echo "<th class=\"data\"> {$lang['strarguments']}</th></tr>\n";
        echo '<tr><td class="data1">', $sel0->fetch(), "</td>\n";
        echo "<td class=\"data1\">(<input type=\"text\" name=\"formTriggerArgs\" size=\"32\" />)</td>\n";
        echo "</tr></table>\n";
        echo "<p><input type=\"submit\" value=\"{$lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
        echo "<input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
        echo $this->misc->form;
        echo "</form>\n";
    }

    /**
     * Actually creates the new trigger in the database.
     */
    public function doSaveCreate()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name and a definition

        if ('' == $_POST['formFunction']) {
            $this->doCreate($lang['strtriggerneedsfunc']);
        } elseif ('' == $_POST['formTriggerName']) {
            $this->doCreate($lang['strtriggerneedsname']);
        } elseif ('' == $_POST['formEvent']) {
            $this->doCreate();
        } else {
            $status = $data->createTrigger(
                $_POST['formTriggerName'],
                $_POST['table'],
                $_POST['formFunction'],
                $_POST['formExecTime'],
                $_POST['formEvent'],
                $_POST['formFrequency'],
                $_POST['formTriggerArgs']
            );
            if (0 == $status) {
                $this->doDefault($lang['strtriggercreated']);
            } else {
                $this->doCreate($lang['strtriggercreatedbad']);
            }
        }
    }
}
