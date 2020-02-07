<?php

/**
 * PHPPgAdmin v6.0.0-RC8.
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class RulesController extends BaseController
{
    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        // Different header if we're view rules or table rules
        $this->printHeader($_REQUEST[$_REQUEST['subject']].' - '.$this->lang['strrules']);
        $this->printBody();

        switch ($this->action) {
            case 'create_rule':
                $this->createRule(true);

                break;
            case 'save_create_rule':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->createRule(false);
                }

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
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * List all the rules on the table.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail($_REQUEST['subject']);
        $this->printTabs($_REQUEST['subject'], 'rules');
        $this->printMsg($msg);

        $rules = $data->getRules($_REQUEST[$_REQUEST['subject']]);

        $columns = [
            'rule'       => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('rulename'),
            ],
            'definition' => [
                'title' => $this->lang['strdefinition'],
                'field' => Decorator::field('definition'),
            ],
            'actions'    => [
                'title' => $this->lang['stractions'],
            ],
        ];

        $subject = urlencode($_REQUEST['subject']);
        $object = urlencode($_REQUEST[$_REQUEST['subject']]);

        $actions = [
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'rules',
                        'urlvars' => [
                            'action'  => 'confirm_drop',
                            'reltype' => $subject,
                            $subject  => $object,
                            'subject' => 'rule',
                            'rule'    => Decorator::field('rulename'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($rules, $columns, $actions, 'rules-rules', $this->lang['strnorules']);

        $this->printNavLinks(['create' => [
            'attr'    => [
                'href' => [
                    'url'     => 'rules',
                    'urlvars' => [
                        'action'   => 'create_rule',
                        'server'   => $_REQUEST['server'],
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        $subject   => $object,
                        'subject'  => $subject,
                    ],
                ],
            ],
            'content' => $this->lang['strcreaterule'],
        ]], 'rules-rules', get_defined_vars());
    }

    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $rules = $data->getRules($_REQUEST[$_REQUEST['subject']]);

        $attrs = [
            'text' => Decorator::field('rulename'),
            'icon' => 'Rule',
        ];

        return $this->printTree($rules, $attrs, 'rules');
    }

    /**
     * Confirm and then actually create a rule.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function createRule($confirm, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'name', '');

        $this->coalesceArr($_POST, 'event', '');

        $this->coalesceArr($_POST, 'where', '');

        $this->coalesceArr($_POST, 'type', 'SOMETHING');

        $this->coalesceArr($_POST, 'raction', '');

        if ($confirm) {
            $this->printTrail($_REQUEST['subject']);
            $this->printTitle($this->lang['strcreaterule'], 'pg.rule.create');
            $this->printMsg($msg);

            echo '<form action="'.\SUBFOLDER.'/src/views/rules" method="post">'.PHP_EOL;
            echo '<table>'.PHP_EOL;
            echo "<tr><th class=\"data left required\">{$this->lang['strname']}</th>".PHP_EOL;
            echo "<td class=\"data1\"><input name=\"name\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['name']), '" /></td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left required\">{$this->lang['strevent']}</th>".PHP_EOL;
            echo '<td class="data1"><select name="event">'.PHP_EOL;
            foreach ($data->rule_events as $v) {
                echo "<option value=\"{$v}\"", ($v == $_POST['event']) ? ' selected="selected"' : '',
                    ">{$v}</option>".PHP_EOL;
            }
            echo '</select></td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left\">{$this->lang['strwhere']}</th>".PHP_EOL;
            echo '<td class="data1"><input name="where" size="32" value="',
            htmlspecialchars($_POST['where']), '" /></td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left\"><label for=\"instead\">{$this->lang['strinstead']}</label></th>".PHP_EOL;
            echo '<td class="data1">';
            echo '<input type="checkbox" id="instead" name="instead" ', (isset($_POST['instead'])) ? ' checked="checked"' : '', ' />'.PHP_EOL;
            echo '</td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left required\">{$this->lang['straction']}</th>".PHP_EOL;
            echo '<td class="data1">';
            echo '<input type="radio" id="type1" name="type" value="NOTHING"', ('NOTHING' == $_POST['type']) ? ' checked="checked"' : '', ' /> <label for="type1">NOTHING</label><br />'.PHP_EOL;
            echo '<input type="radio" name="type" value="SOMETHING"', ('SOMETHING' == $_POST['type']) ? ' checked="checked"' : '', ' />'.PHP_EOL;
            echo '(<input name="raction" size="32" value="',
            htmlspecialchars($_POST['raction']), '" />)</td></tr>'.PHP_EOL;
            echo '</table>'.PHP_EOL;

            echo '<input type="hidden" name="action" value="save_create_rule" />'.PHP_EOL;
            echo '<input type="hidden" name="subject" value="', htmlspecialchars($_REQUEST['subject']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="', htmlspecialchars($_REQUEST['subject']),
            '" value="', htmlspecialchars($_REQUEST[$_REQUEST['subject']]), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<p><input type=\"submit\" name=\"ok\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            if ('' == trim($_POST['name'])) {
                $this->createRule(true, $this->lang['strruleneedsname']);
            } else {
                $status = $data->createRule(
                    $_POST['name'],
                    $_POST['event'],
                    $_POST[$_POST['subject']],
                    $_POST['where'],
                    isset($_POST['instead']),
                    $_POST['type'],
                    $_POST['raction']
                );
                if (0 == $status) {
                    $this->doDefault($this->lang['strrulecreated']);
                } else {
                    $this->createRule(true, $this->lang['strrulecreatedbad']);
                }
            }
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
            $this->printTrail($_REQUEST['subject']);
            $this->printTitle($this->lang['strdrop'], 'pg.rule.drop');

            echo '<p>', sprintf(
                $this->lang['strconfdroprule'],
                $this->misc->printVal($_REQUEST['rule']),
                $this->misc->printVal($_REQUEST[$_REQUEST['reltype']])
            ), '</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/rules" method="post">'.PHP_EOL;
            echo '<input type="hidden" name="action" value="drop" />'.PHP_EOL;
            echo '<input type="hidden" name="subject" value="', htmlspecialchars($_REQUEST['reltype']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="', htmlspecialchars($_REQUEST['reltype']),
            '" value="', htmlspecialchars($_REQUEST[$_REQUEST['reltype']]), '" />'.PHP_EOL;
            echo '<input type="hidden" name="rule" value="', htmlspecialchars($_REQUEST['rule']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>".PHP_EOL;
            echo "<input type=\"submit\" name=\"yes\" value=\"{$this->lang['stryes']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"no\" value=\"{$this->lang['strno']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->dropRule($_POST['rule'], $_POST[$_POST['subject']], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($this->lang['strruledropped']);
            } else {
                $this->doDefault($this->lang['strruledroppedbad']);
            }
        }
    }
}
