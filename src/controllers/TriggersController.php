<?php

/**
 * PHPPgAdmin v6.0.0-RC1
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
    public $controller_title = 'strtables';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $this->printHeader($this->headerTitle('', '', $_REQUEST['table'].' - '.$this->lang['strtriggers']));
        $this->printBody();

        switch ($this->action) {
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
                'title' => $this->lang['strname'],
                'field' => Decorator::field('tgname'),
            ],
            'definition' => [
                'title' => $this->lang['strdefinition'],
                'field' => Decorator::field('tgdef'),
            ],
            'function'   => [
                'title' => $this->lang['strfunction'],
                'field' => Decorator::field('proproto'),
                'url'   => "functions?action=properties&amp;server={$_REQUEST['server']}&amp;database={$_REQUEST['database']}&amp;",
                'vars'  => [
                    'schema'       => 'pronamespace',
                    'function'     => 'proproto',
                    'function_oid' => 'prooid',
                ],
            ],
            'actions'    => [
                'title' => $this->lang['stractions'],
            ],
        ];

        $actions = [
            'alter' => [
                'content' => $this->lang['stralter'],
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
                'content' => $this->lang['strdrop'],
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
                'content' => $this->lang['strenable'],
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
                'content' => $this->lang['strdisable'],
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

        echo $this->printTable($triggers, $columns, $actions, 'triggers-triggers', $this->lang['strnotriggers'], $tgPre);

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
            'content' => $this->lang['strcreatetrigger'],
        ]], 'triggers-triggers', get_defined_vars());
    }

    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $triggers = $data->getTriggers($_REQUEST['table']);

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
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->alterTrigger($_POST['table'], $_POST['trigger'], $_POST['name']);
        if (0 == $status) {
            $this->doDefault($this->lang['strtriggeraltered']);
        } else {
            $this->doAlter($this->lang['strtriggeralteredbad']);
        }
    }

    /**
     * Function to allow altering of a trigger.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('trigger');
        $this->printTitle($this->lang['stralter'], 'pg.trigger.alter');
        $this->printMsg($msg);

        $triggerdata = $data->getTrigger($_REQUEST['table'], $_REQUEST['trigger']);

        if ($triggerdata->recordCount() > 0) {
            $this->coalesceArr($_POST, 'name', $triggerdata->fields['tgname']);

            echo '<form action="'.\SUBFOLDER.'/src/views/triggers" method="post">'.PHP_EOL;
            echo '<table>'.PHP_EOL;
            echo "<tr><th class=\"data\">{$this->lang['strname']}</th>".PHP_EOL;
            echo '<td class="data1">';
            echo "<input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['name']), '" />'.PHP_EOL;
            echo '</table>'.PHP_EOL;
            echo '<p><input type="hidden" name="action" value="alter" />'.PHP_EOL;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="trigger" value="', htmlspecialchars($_REQUEST['trigger']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['strok']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            echo "<p>{$this->lang['strnodata']}</p>".PHP_EOL;
        }
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('trigger');
            $this->printTitle($this->lang['strdrop'], 'pg.trigger.drop');

            echo '<p>', sprintf(
                $this->lang['strconfdroptrigger'],
                $this->misc->printVal($_REQUEST['trigger']),
                $this->misc->printVal($_REQUEST['table'])
            ), '</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/triggers" method="post">'.PHP_EOL;
            echo '<input type="hidden" name="action" value="drop" />'.PHP_EOL;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="trigger" value="', htmlspecialchars($_REQUEST['trigger']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>".PHP_EOL;
            echo "<input type=\"submit\" name=\"yes\" value=\"{$this->lang['stryes']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"no\" value=\"{$this->lang['strno']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->dropTrigger($_POST['trigger'], $_POST['table'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($this->lang['strtriggerdropped']);
            } else {
                $this->doDefault($this->lang['strtriggerdroppedbad']);
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
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('trigger');
            $this->printTitle($this->lang['strenable'], 'pg.table.alter');

            echo '<p>', sprintf(
                $this->lang['strconfenabletrigger'],
                $this->misc->printVal($_REQUEST['trigger']),
                $this->misc->printVal($_REQUEST['table'])
            ), '</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/triggers" method="post">'.PHP_EOL;
            echo '<input type="hidden" name="action" value="enable" />'.PHP_EOL;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="trigger" value="', htmlspecialchars($_REQUEST['trigger']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"yes\" value=\"{$this->lang['stryes']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"no\" value=\"{$this->lang['strno']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->enableTrigger($_POST['trigger'], $_POST['table']);
            if (0 == $status) {
                $this->doDefault($this->lang['strtriggerenabled']);
            } else {
                $this->doDefault($this->lang['strtriggerenabledbad']);
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
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('trigger');
            $this->printTitle($this->lang['strdisable'], 'pg.table.alter');

            echo '<p>', sprintf(
                $this->lang['strconfdisabletrigger'],
                $this->misc->printVal($_REQUEST['trigger']),
                $this->misc->printVal($_REQUEST['table'])
            ), '</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/triggers" method="post">'.PHP_EOL;
            echo '<input type="hidden" name="action" value="disable" />'.PHP_EOL;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="trigger" value="', htmlspecialchars($_REQUEST['trigger']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"yes\" value=\"{$this->lang['stryes']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"no\" value=\"{$this->lang['strno']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->disableTrigger($_POST['trigger'], $_POST['table']);
            if (0 == $status) {
                $this->doDefault($this->lang['strtriggerdisabled']);
            } else {
                $this->doDefault($this->lang['strtriggerdisabledbad']);
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
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('table');
        $this->printTitle($this->lang['strcreatetrigger'], 'pg.trigger.create');
        $this->printMsg($msg);

        // Get all the functions that can be used in triggers
        $funcs = $data->getTriggerFunctions();
        if (0 == $funcs->recordCount()) {
            $this->doDefault($this->lang['strnofunctions']);

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

        echo '<form action="'.\SUBFOLDER.'/src/views/triggers" method="post">'.PHP_EOL;
        echo '<table>'.PHP_EOL;
        echo '<tr>'.PHP_EOL;
        echo "		<th class=\"data\">{$this->lang['strname']}</th>".PHP_EOL;
        echo "		<th class=\"data\">{$this->lang['strwhen']}</th>".PHP_EOL;
        echo '</tr>'.PHP_EOL;
        echo '<tr>'.PHP_EOL;
        echo '		<td class="data1"> <input type="text" name="formTriggerName" size="32" /></td>'.PHP_EOL;
        echo '		<td class="data1"> ', $sel1->fetch(), '</td>'.PHP_EOL;
        echo '</tr>'.PHP_EOL;
        echo '<tr>'.PHP_EOL;
        echo "    <th class=\"data\">{$this->lang['strevent']}</th>".PHP_EOL;
        echo "    <th class=\"data\">{$this->lang['strforeach']}</th>".PHP_EOL;
        echo '</tr>'.PHP_EOL;
        echo '<tr>'.PHP_EOL;
        echo '     <td class="data1"> ', $sel2->fetch(), '</td>'.PHP_EOL;
        echo '     <td class="data1"> ', $sel3->fetch(), '</td>'.PHP_EOL;
        echo '</tr>'.PHP_EOL;
        echo "<tr><th class=\"data\"> {$this->lang['strfunction']}</th>".PHP_EOL;
        echo "<th class=\"data\"> {$this->lang['strarguments']}</th></tr>".PHP_EOL;
        echo '<tr><td class="data1">', $sel0->fetch(), '</td>'.PHP_EOL;
        echo '<td class="data1">(<input type="text" name="formTriggerArgs" size="32" />)</td>'.PHP_EOL;
        echo '</tr></table>'.PHP_EOL;
        echo "<p><input type=\"submit\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
        echo '<input type="hidden" name="action" value="save_create" />'.PHP_EOL;
        echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
        echo $this->misc->form;
        echo '</form>'.PHP_EOL;
    }

    /**
     * Actually creates the new trigger in the database.
     */
    public function doSaveCreate()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name and a definition

        if ('' == $_POST['formFunction']) {
            $this->doCreate($this->lang['strtriggerneedsfunc']);
        } elseif ('' == $_POST['formTriggerName']) {
            $this->doCreate($this->lang['strtriggerneedsname']);
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
                $this->doDefault($this->lang['strtriggercreated']);
            } else {
                $this->doCreate($this->lang['strtriggercreatedbad']);
            }
        }
    }
}
