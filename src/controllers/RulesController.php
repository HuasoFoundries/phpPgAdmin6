<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class RulesController extends BaseController
{
    public $controller_name = 'RulesController';

    /**
     * Default method to render the controller according to the action parameter
     */
    public function render()
    {
        $conf = $this->conf;

        $lang = $this->lang;

        $action = $this->action;
        if ('tree' == $action) {
            return $this->doTree();
        }

        // Different header if we're view rules or table rules
        $this->printHeader($_REQUEST[$_REQUEST['subject']] . ' - ' . $lang['strrules']);
        $this->printBody();

        switch ($action) {
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
     * List all the rules on the table
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail($_REQUEST['subject']);
        $this->printTabs($_REQUEST['subject'], 'rules');
        $this->printMsg($msg);

        $rules = $data->getRules($_REQUEST[$_REQUEST['subject']]);

        $columns = [
            'rule'       => [
                'title' => $lang['strname'],
                'field' => Decorator::field('rulename'),
            ],
            'definition' => [
                'title' => $lang['strdefinition'],
                'field' => Decorator::field('definition'),
            ],
            'actions'    => [
                'title' => $lang['stractions'],
            ],
        ];

        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$_REQUEST['subject']]);

        $actions = [
            'drop' => [
                'content' => $lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'rules.php',
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

        echo $this->printTable($rules, $columns, $actions, 'rules-rules', $lang['strnorules']);

        $this->printNavLinks(['create' => [
            'attr'    => [
                'href' => [
                    'url'     => 'rules.php',
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
            'content' => $lang['strcreaterule'],
        ]], 'rules-rules', get_defined_vars());
    }

    public function doTree()
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $rules = $data->getRules($_REQUEST[$_REQUEST['subject']]);

        $reqvars = $this->misc->getRequestVars($_REQUEST['subject']);

        $attrs = [
            'text' => Decorator::field('rulename'),
            'icon' => 'Rule',
        ];

        return $this->printTree($rules, $attrs, 'rules');
    }

    /**
     * Confirm and then actually create a rule
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function createRule($confirm, $msg = '')
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['name'])) {
            $_POST['name'] = '';
        }

        if (!isset($_POST['event'])) {
            $_POST['event'] = '';
        }

        if (!isset($_POST['where'])) {
            $_POST['where'] = '';
        }

        if (!isset($_POST['type'])) {
            $_POST['type'] = 'SOMETHING';
        }

        if (!isset($_POST['raction'])) {
            $_POST['raction'] = '';
        }

        if ($confirm) {
            $this->printTrail($_REQUEST['subject']);
            $this->printTitle($lang['strcreaterule'], 'pg.rule.create');
            $this->printMsg($msg);

            echo '<form action="' . SUBFOLDER . "/src/views/rules.php\" method=\"post\">\n";
            echo "<table>\n";
            echo "<tr><th class=\"data left required\">{$lang['strname']}</th>\n";
            echo "<td class=\"data1\"><input name=\"name\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['name']), "\" /></td></tr>\n";
            echo "<tr><th class=\"data left required\">{$lang['strevent']}</th>\n";
            echo "<td class=\"data1\"><select name=\"event\">\n";
            foreach ($data->rule_events as $v) {
                echo "<option value=\"{$v}\"", ($v == $_POST['event']) ? ' selected="selected"' : '',
                    ">{$v}</option>\n";
            }
            echo "</select></td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strwhere']}</th>\n";
            echo '<td class="data1"><input name="where" size="32" value="',
            htmlspecialchars($_POST['where']), "\" /></td></tr>\n";
            echo "<tr><th class=\"data left\"><label for=\"instead\">{$lang['strinstead']}</label></th>\n";
            echo '<td class="data1">';
            echo '<input type="checkbox" id="instead" name="instead" ', (isset($_POST['instead'])) ? ' checked="checked"' : '', " />\n";
            echo "</td></tr>\n";
            echo "<tr><th class=\"data left required\">{$lang['straction']}</th>\n";
            echo '<td class="data1">';
            echo '<input type="radio" id="type1" name="type" value="NOTHING"', ('NOTHING' == $_POST['type']) ? ' checked="checked"' : '', " /> <label for=\"type1\">NOTHING</label><br />\n";
            echo '<input type="radio" name="type" value="SOMETHING"', ('SOMETHING' == $_POST['type']) ? ' checked="checked"' : '', " />\n";
            echo '(<input name="raction" size="32" value="',
            htmlspecialchars($_POST['raction']), "\" />)</td></tr>\n";
            echo "</table>\n";

            echo "<input type=\"hidden\" name=\"action\" value=\"save_create_rule\" />\n";
            echo '<input type="hidden" name="subject" value="', htmlspecialchars($_REQUEST['subject']), "\" />\n";
            echo '<input type="hidden" name="', htmlspecialchars($_REQUEST['subject']),
            '" value="', htmlspecialchars($_REQUEST[$_REQUEST['subject']]), "\" />\n";
            echo $this->misc->form;
            echo "<p><input type=\"submit\" name=\"ok\" value=\"{$lang['strcreate']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            if ('' == trim($_POST['name'])) {
                $this->createRule(true, $lang['strruleneedsname']);
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
                    $this->doDefault($lang['strrulecreated']);
                } else {
                    $this->createRule(true, $lang['strrulecreatedbad']);
                }
            }
        }
    }

    /**
     * Show confirmation of drop and perform actual drop
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail($_REQUEST['subject']);
            $this->printTitle($lang['strdrop'], 'pg.rule.drop');

            echo '<p>', sprintf(
                $lang['strconfdroprule'],
                $this->misc->printVal($_REQUEST['rule']),
                $this->misc->printVal($_REQUEST[$_REQUEST['reltype']])
            ), "</p>\n";

            echo '<form action="' . SUBFOLDER . "/src/views/rules.php\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo '<input type="hidden" name="subject" value="', htmlspecialchars($_REQUEST['reltype']), "\" />\n";
            echo '<input type="hidden" name="', htmlspecialchars($_REQUEST['reltype']),
            '" value="', htmlspecialchars($_REQUEST[$_REQUEST['reltype']]), "\" />\n";
            echo '<input type="hidden" name="rule" value="', htmlspecialchars($_REQUEST['rule']), "\" />\n";
            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$lang['strcascade']}</label></p>\n";
            echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />\n";
            echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />\n";
            echo "</form>\n";
        } else {
            $status = $data->dropRule($_POST['rule'], $_POST[$_POST['subject']], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($lang['strruledropped']);
            } else {
                $this->doDefault($lang['strruledroppedbad']);
            }
        }
    }
}
