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
class AggregatesController extends BaseController
{
    public $table_place = 'aggregates-aggregates';

    public $controller_title = 'straggregates';

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

        \ob_start();

        switch ($this->action) {
            case 'create':
                $this->doCreate();

                break;
            case 'save_create':
                if (null !== $this->getPostParam('cancel')) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'alter':
                $this->doAlter();

                break;
            case 'save_alter':
                if (null !== $this->getPostParam('alter')) {
                    $this->doSaveAlter();
                } else {
                    $this->doProperties();
                }

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
            case 'properties':
                $this->doProperties();

                break;
        }

        $output = \ob_get_clean();

        $this->printHeader($this->headerTitle());
        $this->printBody();
        echo $output;

        return $this->printFooter();
    }

    /**
     * Show default list of aggregate functions in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $this->printTrail('schema');
        $this->printTabs('schema', 'aggregates');
        $this->printMsg($msg);

        $aggregates = $this->data->getAggregates();
        $columns = [
            'aggrname' => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('proname'),
                'url' => \sprintf(
                    'aggregates?subject=aggregate&amp;action=properties&amp;%s&amp;',
                    $this->misc->href
                ),
                'vars' => ['aggrname' => 'proname', 'aggrtype' => 'proargtypes'],
            ],
            'aggrtype' => [
                'title' => $this->lang['strtype'],
                'field' => Decorator::field('proargtypes'),
            ],
            'aggrtransfn' => [
                'title' => $this->lang['straggrsfunc'],
                'field' => Decorator::field('aggtransfn'),
            ],
            'owner' => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('usename'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('aggrcomment'),
            ],
        ];

        $actions = [
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'aggregates.php',
                        'urlvars' => [
                            'action' => 'alter',
                            'aggrname' => Decorator::field('proname'),
                            'aggrtype' => Decorator::field('proargtypes'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'aggregates.php',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'aggrname' => Decorator::field('proname'),
                            'aggrtype' => Decorator::field('proargtypes'),
                        ],
                    ],
                ],
            ],
        ];

        if (!$this->data->hasAlterAggregate()) {
            unset($actions['alter']);
        }

        if (self::isRecordset($aggregates)) {
            echo $this->printTable($aggregates, $columns, $actions, $this->table_place, $this->lang['strnoaggregates']);
        }

        $navlinks = [
            'create' => [
                'attr' => [
                    'href' => [
                        'url' => 'aggregates.php',
                        'urlvars' => [
                            'action' => 'create',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreateaggregate'],
            ],
        ];

        return $this->printNavLinks($navlinks, $this->table_place, \get_defined_vars());
    }

    /**
     * @return Response|string
     */
    public function doTree()
    {
        $this->data = $this->misc->getDatabaseAccessor();

        $aggregates = $this->data->getAggregates();
        $proto = Decorator::concat(Decorator::field('proname'), ' (', Decorator::field('proargtypes'), ')');
        $reqvars = $this->misc->getRequestVars('aggregate');

        $attrs = [
            'text' => $proto,
            'icon' => 'Aggregate',
            'toolTip' => Decorator::field('aggcomment'),
            'action' => Decorator::redirecturl(
                'redirect',
                $reqvars,
                [
                    'action' => 'properties',
                    'aggrname' => Decorator::field('proname'),
                    'aggrtype' => Decorator::field('proargtypes'),
                ]
            ),
        ];

        return $this->printTree($aggregates, $attrs, 'aggregates');
    }

    /**
     * Actually creates the new aggregate in the database.
     */
    public function doSaveCreate()
    {
        $this->data = $this->misc->getDatabaseAccessor();
        // Check inputs
        if ('' === \trim($_REQUEST['name'])) {
            return $this->doCreate($this->lang['straggrneedsname']);
        }

        if ('' === \trim($_REQUEST['basetype'])) {
            return $this->doCreate($this->lang['straggrneedsbasetype']);
        }

        if ('' === \trim($_REQUEST['sfunc'])) {
            return $this->doCreate($this->lang['straggrneedssfunc']);
        }

        if ('' === \trim($_REQUEST['stype'])) {
            return $this->doCreate($this->lang['straggrneedsstype']);
        }

        $status = $this->data->createAggregate(
            $_REQUEST['name'],
            $_REQUEST['basetype'],
            $_REQUEST['sfunc'],
            $_REQUEST['stype'],
            $_REQUEST['ffunc'],
            $_REQUEST['initcond'],
            $_REQUEST['sortop'],
            $_REQUEST['aggrcomment']
        );

        if (0 === $status) {
            $this->view->setReloadBrowser(true);
            $this->doDefault($this->lang['straggrcreated']);
        } else {
            $this->doCreate($this->lang['straggrcreatedbad']);
        }
    }

    /**
     * Displays a screen for create a new aggregate function.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $this->data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'name', '');

        $this->coalesceArr($_REQUEST, 'basetype', '');

        $this->coalesceArr($_REQUEST, 'sfunc', '');

        $this->coalesceArr($_REQUEST, 'stype', '');

        $this->coalesceArr($_REQUEST, 'ffunc', '');

        $this->coalesceArr($_REQUEST, 'initcond', '');

        $this->coalesceArr($_REQUEST, 'sortop', '');

        $this->coalesceArr($_REQUEST, 'aggrcomment', '');

        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreateaggregate'], 'pg.aggregate.create');
        $this->printMsg($msg);

        echo '<form action="aggregates" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data"><input name="name" size="32" maxlength="%s" value="',
            $this->data->_maxNameLen
        ),
        \htmlspecialchars($_REQUEST['name']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['straggrbasetype']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data"><input name="basetype" size="32" maxlength="%s" value="',
            $this->data->_maxNameLen
        ),
        \htmlspecialchars($_REQUEST['basetype']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['straggrsfunc']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data"><input name="sfunc" size="32" maxlength="%s" value="',
            $this->data->_maxNameLen
        ),
        \htmlspecialchars($_REQUEST['sfunc']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['straggrstype']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data"><input name="stype" size="32" maxlength="%s" value="',
            $this->data->_maxNameLen
        ),
        \htmlspecialchars($_REQUEST['stype']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['straggrffunc']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data"><input name="ffunc" size="32" maxlength="%s" value="',
            $this->data->_maxNameLen
        ),
        \htmlspecialchars($_REQUEST['ffunc']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['straggrinitcond']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data"><input name="initcond" size="32" maxlength="%s" value="',
            $this->data->_maxNameLen
        ),
        \htmlspecialchars($_REQUEST['initcond']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['straggrsortop']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data"><input name="sortop" size="32" maxlength="%s" value="',
            $this->data->_maxNameLen
        ),
        \htmlspecialchars($_REQUEST['sortop']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strcomment']
        ) . \PHP_EOL;
        echo "\t\t<td><textarea name=\"aggrcomment\" rows=\"3\" cols=\"32\">",
        \htmlspecialchars($_REQUEST['aggrcomment']), "</textarea></td>\n\t</tr>" . \PHP_EOL;

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
     * Function to save after altering an aggregate.
     */
    public function doSaveAlter(): void
    {
        $this->data = $this->misc->getDatabaseAccessor();

        // Check inputs
        if ('' === \trim($_REQUEST['aggrname'])) {
            $this->doAlter($this->lang['straggrneedsname']);

            return;
        }

        $status = $this->data->alterAggregate(
            $_REQUEST['aggrname'],
            $_REQUEST['aggrtype'],
            $_REQUEST['aggrowner'],
            $_REQUEST['aggrschema'],
            $_REQUEST['aggrcomment'],
            $_REQUEST['newaggrname'],
            $_REQUEST['newaggrowner'],
            $_REQUEST['newaggrschema'],
            $_REQUEST['newaggrcomment']
        );

        if (0 === $status) {
            $this->doDefault($this->lang['straggraltered']);
        } else {
            $this->doAlter($this->lang['straggralteredbad']);

            return;
        }
    }

    /**
     * Function to allow editing an aggregate function.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $this->data = $this->misc->getDatabaseAccessor();

        $this->printTrail('aggregate');
        $this->printTitle($this->lang['stralter'], 'pg.aggregate.alter');
        $this->printMsg($msg);

        echo '<form action="aggregates" method="post">' . \PHP_EOL;
        $aggrdata = $this->data->getAggregate($_REQUEST['aggrname'], $_REQUEST['aggrtype']);

        if (0 < $aggrdata->RecordCount()) {
            // Output table header
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data required">%s</th>',
                $this->lang['strname']
            );
            echo \sprintf(
                '<th class="data required">%s</th>',
                $this->lang['strowner']
            );
            echo \sprintf(
                '<th class="data required">%s</th>
	</tr>',
                $this->lang['strschema']
            ) . \PHP_EOL;

            // Display aggregate's name, owner and schema
            echo "\t<tr>\n\t\t<td><input name=\"newaggrname\" size=\"32\" maxlength=\"32\" value=\"", \htmlspecialchars($_REQUEST['aggrname']), '" /></td>';
            echo '<td><input name="newaggrowner" size="32" maxlength="32" value="', \htmlspecialchars($aggrdata->fields['usename']), '" /></td>';
            echo '<td><input name="newaggrschema" size="32" maxlength="32" value="', \htmlspecialchars($_REQUEST['schema']), "\" /></td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strcomment']
            ) . \PHP_EOL;
            echo "\t\t<td><textarea name=\"newaggrcomment\" rows=\"3\" cols=\"32\">",
            \htmlspecialchars($aggrdata->fields['aggrcomment']), "</textarea></td>\n\t</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="save_alter" />' . \PHP_EOL;
            echo $this->view->form;
            echo '<input type="hidden" name="aggrname" value="', \htmlspecialchars($_REQUEST['aggrname']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="aggrtype" value="', \htmlspecialchars($_REQUEST['aggrtype']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="aggrowner" value="', \htmlspecialchars($aggrdata->fields['usename']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="aggrschema" value="', \htmlspecialchars($_REQUEST['schema']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="aggrcomment" value="', \htmlspecialchars($aggrdata->fields['aggrcomment']), '" />' . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="alter" value="%s" />',
                $this->lang['stralter']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s"  /></p>%s',
                $this->lang['strcancel'],
                \PHP_EOL
            );
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s" /></p>',
                $this->lang['strback']
            ) . \PHP_EOL;
        }
        echo '</form>' . \PHP_EOL;
    }

    /**
     * Show confirmation of drop and perform actual drop of the aggregate function selected.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm): void
    {
        $this->data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('aggregate');
            $this->printTitle($this->lang['strdrop'], 'pg.aggregate.drop');

            echo '<p>', \sprintf(
                $this->lang['strconfdropaggregate'],
                \htmlspecialchars($_REQUEST['aggrname'])
            ), '</p>' . \PHP_EOL;

            echo '<form action="aggregates" method="post">' . \PHP_EOL;
            echo \sprintf(
                '<p><input type="checkbox" id="cascade" name="cascade" /> <label for="cascade">%s</label></p>',
                $this->lang['strcascade']
            ) . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="drop" />' . \PHP_EOL;
            echo '<input type="hidden" name="aggrname" value="', \htmlspecialchars($_REQUEST['aggrname']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="aggrtype" value="', \htmlspecialchars($_REQUEST['aggrtype']), '" />' . \PHP_EOL;
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
            $status = $this->data->dropAggregate($_POST['aggrname'], $_POST['aggrtype'], isset($_POST['cascade']));

            if (0 === $status) {
                $this->view->setReloadBrowser(true);
                $this->doDefault($this->lang['straggregatedropped']);
            } else {
                $this->doDefault($this->lang['straggregatedroppedbad']);
            }
        }
    }

    /**
     * Show the properties of an aggregate.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $this->data = $this->misc->getDatabaseAccessor();

        $this->printTrail('aggregate');
        $this->printTitle($this->lang['strproperties'], 'pg.aggregate');
        $this->printMsg($msg);

        $aggrdata = $this->data->getAggregate($_REQUEST['aggrname'], $_REQUEST['aggrtype']);

        if (0 < $aggrdata->RecordCount()) {
            // Display aggregate's info
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '<tr>
	<th class="data left">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo "\t<td class=\"data1\">", \htmlspecialchars($_REQUEST['aggrname']), "</td>\n</tr>" . \PHP_EOL;
            echo \sprintf(
                '<tr>
	<th class="data left">%s</th>',
                $this->lang['straggrbasetype']
            ) . \PHP_EOL;
            echo "\t<td class=\"data1\">", \htmlspecialchars($_REQUEST['aggrtype']), "</td>\n</tr>" . \PHP_EOL;
            echo \sprintf(
                '<tr>
	<th class="data left">%s</th>',
                $this->lang['straggrsfunc']
            ) . \PHP_EOL;
            echo "\t<td class=\"data1\">", \htmlspecialchars($aggrdata->fields['aggtransfn']), "</td>\n</tr>" . \PHP_EOL;
            echo \sprintf(
                '<tr>
	<th class="data left">%s</th>',
                $this->lang['straggrstype']
            ) . \PHP_EOL;
            echo "\t<td class=\"data1\">", \htmlspecialchars($aggrdata->fields['aggstype']), "</td>\n</tr>" . \PHP_EOL;
            echo \sprintf(
                '<tr>
	<th class="data left">%s</th>',
                $this->lang['straggrffunc']
            ) . \PHP_EOL;
            echo "\t<td class=\"data1\">", \htmlspecialchars($aggrdata->fields['aggfinalfn']), "</td>\n</tr>" . \PHP_EOL;
            echo \sprintf(
                '<tr>
	<th class="data left">%s</th>',
                $this->lang['straggrinitcond']
            ) . \PHP_EOL;
            echo "\t<td class=\"data1\">", \htmlspecialchars($aggrdata->fields['agginitval']), "</td>\n</tr>" . \PHP_EOL;

            if ($this->data->hasAggregateSortOp()) {
                echo \sprintf(
                    '<tr>
	<th class="data left">%s</th>',
                    $this->lang['straggrsortop']
                ) . \PHP_EOL;
                echo "\t<td class=\"data1\">", \htmlspecialchars($aggrdata->fields['aggsortop']), "</td>\n</tr>" . \PHP_EOL;
            }
            echo \sprintf(
                '<tr>
	<th class="data left">%s</th>',
                $this->lang['strowner']
            ) . \PHP_EOL;
            echo "\t<td class=\"data1\">", \htmlspecialchars($aggrdata->fields['usename']), "</td>\n</tr>" . \PHP_EOL;
            echo \sprintf(
                '<tr>
	<th class="data left">%s</th>',
                $this->lang['strcomment']
            ) . \PHP_EOL;
            echo "\t<td class=\"data1\">", $this->misc->printVal($aggrdata->fields['aggrcomment']), "</td>\n</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;
        }

        $navlinks = [
            'showall' => [
                'attr' => [
                    'href' => [
                        'url' => 'aggregates',
                        'urlvars' => [
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['straggrshowall'],
            ],
        ];

        if ($this->data->hasAlterAggregate()) {
            $navlinks['alter'] = [
                'attr' => [
                    'href' => [
                        'url' => 'aggregates',
                        'urlvars' => [
                            'action' => 'alter',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            'aggrname' => $_REQUEST['aggrname'],
                            'aggrtype' => $_REQUEST['aggrtype'],
                        ],
                    ],
                ],
                'content' => $this->lang['stralter'],
            ];
        }

        $navlinks['drop'] = [
            'attr' => [
                'href' => [
                    'url' => 'aggregates',
                    'urlvars' => [
                        'action' => 'confirm_drop',
                        'server' => $_REQUEST['server'],
                        'database' => $_REQUEST['database'],
                        'schema' => $_REQUEST['schema'],
                        'aggrname' => $_REQUEST['aggrname'],
                        'aggrtype' => $_REQUEST['aggrtype'],
                    ],
                ],
            ],
            'content' => $this->lang['strdrop'],
        ];

        return $this->printNavLinks($navlinks, 'aggregates-properties', \get_defined_vars());
    }
}
