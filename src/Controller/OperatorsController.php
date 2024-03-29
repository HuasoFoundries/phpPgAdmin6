<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use Slim\Http\Response;

/**
 * Base controller class.
 */
class OperatorsController extends BaseController
{
    public $controller_title = 'stroperators';

    /**
     * Default method to render the controller according to the action parameter.
     *
     * @return null|Response|string
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            /*case 'save_create':
            if($this->getPostParam('cancel')!==null){
            $this->doDefault();
            } else {
            $this->doSaveCreate();
            }

            break;
            case 'create':
            $this->doCreate();

            break;*/
            case 'drop':
                if (null !== $this->getPostParam('cancel')) {
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

        return $this->printFooter();
    }

    /**
     * Generate XML for the browser tree.
     *
     * @return Response|string
     */
    public function doTree()
    {
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
                'operators',
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
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'operators');
        $this->printMsg($msg);

        $operators = $data->getOperators();

        $columns = [
            'operator' => [
                'title' => $this->lang['stroperator'],
                'field' => Decorator::field('oprname'),
                'url' => \sprintf(
                    'operators?action=properties&amp;%s&amp;',
                    $this->misc->href
                ),
                'vars' => ['operator' => 'oprname', 'operator_oid' => 'oid'],
            ],
            'leftarg' => [
                'title' => $this->lang['strleftarg'],
                'field' => Decorator::field('oprleftname'),
            ],
            'rightarg' => [
                'title' => $this->lang['strrightarg'],
                'field' => Decorator::field('oprrightname'),
            ],
            'returns' => [
                'title' => $this->lang['strreturns'],
                'field' => Decorator::field('resultname'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('oprcomment'),
            ],
        ];

        $actions = [
            'drop' => [
                // 'title' => $this->lang['strdrop'],
                // 'url'   => "operators?action=confirm_drop&amp;{$this->misc->href}&amp;",
                // 'vars'  => array('operator' => 'oprname', 'operator_oid' => 'oid'),
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'operators',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'operator' => Decorator::field('oprname'),
                            'operator_oid' => Decorator::field('oid'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($operators)) {
            echo $this->printTable($operators, $columns, $actions, 'operators-operators', $this->lang['strnooperators']);
        }

        return '';
        //        TODO operators action=create $this->lang['strcreateoperator']
    }

    /**
     * Show read only properties for an operator.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('operator');
        $this->printTitle($this->lang['strproperties'], 'pg.operator');
        $this->printMsg($msg);

        $oprdata = $data->getOperator($_REQUEST['operator_oid']);
        $oprdata->fields['oprcanhash'] = $data->phpBool($oprdata->fields['oprcanhash']);

        if (0 < $oprdata->RecordCount()) {
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprname']), '</td></tr>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strleftarg']
            ) . \PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprleftname']), '</td></tr>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strrightarg']
            ) . \PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprrightname']), '</td></tr>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strcommutator']
            ) . \PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprcom']), '</td></tr>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strnegator']
            ) . \PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprnegate']), '</td></tr>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strjoin']
            ) . \PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprjoin']), '</td></tr>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strhashes']
            ) . \PHP_EOL;
            echo '<td class="data1">', ($oprdata->fields['oprcanhash']) ? $this->lang['stryes'] : $this->lang['strno'], '</td></tr>' . \PHP_EOL;

            // these field only exists in 8.2 and before in pg_catalog
            if (isset($oprdata->fields['oprlsortop'])) {
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strmerges']
                ) . \PHP_EOL;
                echo '<td class="data1">', ('0' !== $oprdata->fields['oprlsortop'] && '0' !== $oprdata->fields['oprrsortop']) ? $this->lang['stryes'] : $this->lang['strno'], '</td></tr>' . \PHP_EOL;
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strrestrict']
                ) . \PHP_EOL;
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprrest']), '</td></tr>' . \PHP_EOL;
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strleftsort']
                ) . \PHP_EOL;
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprlsortop']), '</td></tr>' . \PHP_EOL;
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strrightsort']
                ) . \PHP_EOL;
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprrsortop']), '</td></tr>' . \PHP_EOL;
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strlessthan']
                ) . \PHP_EOL;
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprltcmpop']), '</td></tr>' . \PHP_EOL;
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strgreaterthan']
                ) . \PHP_EOL;
                echo '<td class="data1">', $this->misc->printVal($oprdata->fields['oprgtcmpop']), '</td></tr>' . \PHP_EOL;
            } else {
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strmerges']
                ) . \PHP_EOL;
                echo '<td class="data1">', $data->phpBool($oprdata->fields['oprcanmerge']) ? $this->lang['stryes'] : $this->lang['strno'], '</td></tr>' . \PHP_EOL;
            }
            echo '</table>' . \PHP_EOL;

            return $this->printNavLinks(
                [
                    'showall' => [
                        'attr' => [
                            'href' => [
                                'url' => 'operators',
                                'urlvars' => [
                                    'server' => $_REQUEST['server'],
                                    'database' => $_REQUEST['database'],
                                    'schema' => $_REQUEST['schema'],
                                ],
                            ],
                        ],
                        'content' => $this->lang['strshowalloperators'],
                    ], ],
                'operators-properties',
                \get_defined_vars()
            );
        }
        $this->doDefault($this->lang['strinvalidparam']);
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('operator');
            $this->printTitle($this->lang['strdrop'], 'pg.operator.drop');

            echo '<p>', \sprintf(
                $this->lang['strconfdropoperator'],
                $this->misc->printVal($_REQUEST['operator'])
            ), '</p>' . \PHP_EOL;

            echo '<form action="operators" method="post">' . \PHP_EOL;
            echo \sprintf(
                '<p><input type="checkbox" id="cascade" name="cascade" /> <label for="cascade">%s</label></p>',
                $this->lang['strcascade']
            ) . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="drop" />' . \PHP_EOL;
            echo '<input type="hidden" name="operator" value="', \htmlspecialchars($_REQUEST['operator']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="operator_oid" value="', \htmlspecialchars($_REQUEST['operator_oid']), '" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="drop" value="%s" />',
                $this->lang['strdrop']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s"  /></p>%s',
                $this->lang['strcancel'],
                \PHP_EOL
            );
            echo '</form>' . \PHP_EOL;
        } else {
            $status = $data->dropOperator($_POST['operator_oid'], isset($_POST['cascade']));

            if (0 === $status) {
                $this->doDefault($this->lang['stroperatordropped']);
            } else {
                $this->doDefault($this->lang['stroperatordroppedbad']);
            }
        }
    }
}
