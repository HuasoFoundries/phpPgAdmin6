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
class OperatorsController extends BaseController
{
    public $controller_name = 'OperatorsController';

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

        $this->printHeader($lang['stroperators']);
        $this->printBody();

        switch ($action) {
            case 'save_create':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'create':
                doCreate();

                break;
            case 'drop':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doDrop(false);
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'properties':
                $this->doProperties();

                break;
            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $operators = $data->getOperators();

        // Operator prototype: "type operator type"
        $proto = Decorator::concat(Decorator::field('oprleftname'), ' ', Decorator::field('oprname'), ' ', Decorator::field('oprrightname'));

        $reqvars = $this->misc->getRequestVars('operator');

        $attrs = [
            'text' => $proto,
            'icon' => 'Operator',
            'toolTip' => Decorator::field('oprcomment'),
            'action' => Decorator::actionurl(
                'operators.php',
                $reqvars,
                [
                    'action' => 'properties',
                    'operator' => $proto,
                    'operator_oid' => Decorator::field('oid'),
                ]
            ),
        ];

        return $this->printTree($operators, $attrs, 'operators');
    }

    /**
     * Show default list of operators in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'operators');
        $this->printMsg($msg);

        $operators = $data->getOperators();

        $columns = [
            'operator' => [
                'title' => $lang['stroperator'],
                'field' => Decorator::field('oprname'),
                'url' => "operators.php?action=properties&amp;{$this->misc->href}&amp;",
                'vars' => ['operator' => 'oprname', 'operator_oid' => 'oid'],
            ],
            'leftarg' => [
                'title' => $lang['strleftarg'],
                'field' => Decorator::field('oprleftname'),
            ],
            'rightarg' => [
                'title' => $lang['strrightarg'],
                'field' => Decorator::field('oprrightname'),
            ],
            'returns' => [
                'title' => $lang['strreturns'],
                'field' => Decorator::field('resultname'),
            ],
            'actions' => [
                'title' => $lang['stractions'],
            ],
            'comment' => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('oprcomment'),
            ],
        ];

        $actions = [
            'drop' => [
                // 'title' => $lang['strdrop'],
                // 'url'   => "operators.php?action=confirm_drop&amp;{$this->misc->href}&amp;",
                // 'vars'  => array('operator' => 'oprname', 'operator_oid' => 'oid'),
                'content' => $lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'operators.php',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'operator' => Decorator::field('oprname'),
                            'operator_oid' => Decorator::field('oid'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($operators, $columns, $actions, 'operators-operators', $lang['strnooperators']);

        //        TODO operators.php action=create $lang['strcreateoperator']
    }

    /**
     * Show read only properties for an operator.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('operator');
        $this->printTitle($lang['strproperties'], 'pg.operator');
        $this->printMsg($msg);

        $oprdata = $data->getOperator($_REQUEST['operator_oid']);
        $oprdata->fields['oprcanhash'] = $data->phpBool($oprdata->fields['oprcanhash']);

        if ($oprdata->recordCount() > 0) {
            echo "<table>\n";
            echo "<tr><th class=\"data left\">{$lang['strname']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprname']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strleftarg']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprleftname']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strrightarg']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprrightname']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strcommutator']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprcom']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strnegator']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprnegate']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strjoin']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprjoin']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strhashes']}</th>\n";
            echo '<td class="data1">', ($oprdata->fields['oprcanhash']) ? $lang['stryes'] : $lang['strno'], "</td></tr>\n";

            // these field only exists in 8.2 and before in pg_catalog
            if (isset($oprdata->fields['oprlsortop'])) {
                echo "<tr><th class=\"data left\">{$lang['strmerges']}</th>\n";
                echo '<td class="data1">', ('0' !== $oprdata->fields['oprlsortop'] && '0' !== $oprdata->fields['oprrsortop']) ? $lang['stryes'] : $lang['strno'], "</td></tr>\n";
                echo "<tr><th class=\"data left\">{$lang['strrestrict']}</th>\n";
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprrest']), "</td></tr>\n";
                echo "<tr><th class=\"data left\">{$lang['strleftsort']}</th>\n";
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprlsortop']), "</td></tr>\n";
                echo "<tr><th class=\"data left\">{$lang['strrightsort']}</th>\n";
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprrsortop']), "</td></tr>\n";
                echo "<tr><th class=\"data left\">{$lang['strlessthan']}</th>\n";
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprltcmpop']), "</td></tr>\n";
                echo "<tr><th class=\"data left\">{$lang['strgreaterthan']}</th>\n";
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprgtcmpop']), "</td></tr>\n";
            } else {
                echo "<tr><th class=\"data left\">{$lang['strmerges']}</th>\n";
                echo '<td class="data1">', $data->phpBool($oprdata->fields['oprcanmerge']) ? $lang['stryes'] : $lang['strno'], "</td></tr>\n";
            }
            echo "</table>\n";

            $this->printNavLinks(
                [
                    'showall' => [
                        'attr' => [
                            'href' => [
                                'url' => 'operators.php',
                                'urlvars' => [
                                    'server' => $_REQUEST['server'],
                                    'database' => $_REQUEST['database'],
                                    'schema' => $_REQUEST['schema'],
                                ],
                            ],
                        ],
                        'content' => $lang['strshowalloperators'],
                    ], ],
                'operators-properties',
                get_defined_vars()
            );
        } else {
            $this->doDefault($lang['strinvalidparam']);
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
            $this->printTrail('operator');
            $this->printTitle($lang['strdrop'], 'pg.operator.drop');

            echo '<p>', sprintf($lang['strconfdropoperator'], $this->misc->printVal($_REQUEST['operator'])), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/operators.php\" method=\"post\">\n";
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$lang['strcascade']}</label></p>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo '<input type="hidden" name="operator" value="', htmlspecialchars($_REQUEST['operator']), "\" />\n";
            echo '<input type="hidden" name="operator_oid" value="', htmlspecialchars($_REQUEST['operator_oid']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            $status = $data->dropOperator($_POST['operator_oid'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($lang['stroperatordropped']);
            } else {
                $this->doDefault($lang['stroperatordroppedbad']);
            }
        }
    }
}
