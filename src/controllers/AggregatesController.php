<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class AggregatesController extends BaseController
{
    public $controller_name = 'AggregatesController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $conf = $this->conf;

        $lang = $this->lang;

        $action = $this->action;
        if ('tree' == $action) {
            return $this->doTree();
        }

        $this->printHeader($lang['straggregates']);
        $this->printBody();

        switch ($action) {
            case 'create':
                $this->doCreate();

                break;
            case 'save_create':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'alter':
                $this->doAlter();

                break;
            case 'save_alter':
                if (isset($_POST['alter'])) {
                    $this->doSaveAlter();
                } else {
                    $this->doProperties();
                }

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
            case 'properties':
                $this->doProperties();

                break;
        }

        return $this->printFooter();
    }

    /**
     * Show default list of aggregate functions in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();
        $this->printTrail('schema');
        $this->printTabs('schema', 'aggregates');
        $this->printMsg($msg);

        $aggregates = $data->getAggregates();

        $columns = [
            'aggrname' => [
                'title' => $lang['strname'],
                'field' => Decorator::field('proname'),
                'url'   => \SUBFOLDER."/redirect/aggregate?action=properties&amp;{$this->misc->href}&amp;",
                'vars'  => ['aggrname' => 'proname', 'aggrtype' => 'proargtypes'],
            ],
            'aggrtype' => [
                'title' => $lang['strtype'],
                'field' => Decorator::field('proargtypes'),
            ],
            'aggrtransfn' => [
                'title' => $lang['straggrsfunc'],
                'field' => Decorator::field('aggtransfn'),
            ],
            'owner' => [
                'title' => $lang['strowner'],
                'field' => Decorator::field('usename'),
            ],
            'actions' => [
                'title' => $lang['stractions'],
            ],
            'comment' => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('aggrcomment'),
            ],
        ];

        $actions = [
            'alter' => [
                'content' => $lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'aggregates.php',
                        'urlvars' => [
                            'action'   => 'alter',
                            'aggrname' => Decorator::field('proname'),
                            'aggrtype' => Decorator::field('proargtypes'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'content' => $lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'aggregates.php',
                        'urlvars' => [
                            'action'   => 'confirm_drop',
                            'aggrname' => Decorator::field('proname'),
                            'aggrtype' => Decorator::field('proargtypes'),
                        ],
                    ],
                ],
            ],
        ];

        if (!$data->hasAlterAggregate()) {
            unset($actions['alter']);
        }

        echo $this->printTable($aggregates, $columns, $actions, 'aggregates-aggregates', $lang['strnoaggregates']);

        $navlinks = [
            'create' => [
                'attr' => [
                    'href' => [
                        'url'     => 'aggregates.php',
                        'urlvars' => [
                            'action'   => 'create',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $lang['strcreateaggregate'],
            ],
        ];
        $this->printNavLinks($navlinks, 'aggregates-aggregates', get_defined_vars());
    }

    public function doTree()
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $aggregates = $data->getAggregates();

        $proto   = Decorator::concat(Decorator::field('proname'), ' (', Decorator::field('proargtypes'), ')');
        $reqvars = $this->misc->getRequestVars('aggregate');

        $attrs = [
            'text'    => $proto,
            'icon'    => 'Aggregate',
            'toolTip' => Decorator::field('aggcomment'),
            'action'  => Decorator::redirecturl(
                'redirect.php',
                $reqvars,
                [
                    'action'   => 'properties',
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
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();
        // Check inputs
        if ('' == trim($_REQUEST['name'])) {
            $this->doCreate($lang['straggrneedsname']);

            return;
        }
        if ('' == trim($_REQUEST['basetype'])) {
            $this->doCreate($lang['straggrneedsbasetype']);

            return;
        }
        if ('' == trim($_REQUEST['sfunc'])) {
            $this->doCreate($lang['straggrneedssfunc']);

            return;
        }
        if ('' == trim($_REQUEST['stype'])) {
            $this->doCreate($lang['straggrneedsstype']);

            return;
        }

        $status = $data->createAggregate(
            $_REQUEST['name'],
            $_REQUEST['basetype'],
            $_REQUEST['sfunc'],
            $_REQUEST['stype'],
            $_REQUEST['ffunc'],
            $_REQUEST['initcond'],
            $_REQUEST['sortop'],
            $_REQUEST['aggrcomment']
        );

        if (0 == $status) {
            $this->misc->setReloadBrowser(true);
            $this->doDefault($lang['straggrcreated']);
        } else {
            $this->doCreate($lang['straggrcreatedbad']);
        }
    }

    /**
     * Displays a screen for create a new aggregate function.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_REQUEST['name'])) {
            $_REQUEST['name'] = '';
        }

        if (!isset($_REQUEST['basetype'])) {
            $_REQUEST['basetype'] = '';
        }

        if (!isset($_REQUEST['sfunc'])) {
            $_REQUEST['sfunc'] = '';
        }

        if (!isset($_REQUEST['stype'])) {
            $_REQUEST['stype'] = '';
        }

        if (!isset($_REQUEST['ffunc'])) {
            $_REQUEST['ffunc'] = '';
        }

        if (!isset($_REQUEST['initcond'])) {
            $_REQUEST['initcond'] = '';
        }

        if (!isset($_REQUEST['sortop'])) {
            $_REQUEST['sortop'] = '';
        }

        if (!isset($_REQUEST['aggrcomment'])) {
            $_REQUEST['aggrcomment'] = '';
        }

        $this->printTrail('schema');
        $this->printTitle($lang['strcreateaggregate'], 'pg.aggregate.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/aggregates.php\" method=\"post\">\n";
        echo "<table>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strname']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['name']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['straggrbasetype']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"basetype\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['basetype']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['straggrsfunc']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"sfunc\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['sfunc']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['straggrstype']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"stype\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['stype']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['straggrffunc']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"ffunc\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['ffunc']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['straggrinitcond']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"initcond\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['initcond']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['straggrsortop']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"sortop\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['sortop']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strcomment']}</th>\n";
        echo "\t\t<td><textarea name=\"aggrcomment\" rows=\"3\" cols=\"32\">",
        htmlspecialchars($_REQUEST['aggrcomment']), "</textarea></td>\n\t</tr>\n";

        echo "</table>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
    }

    /**
     * Function to save after altering an aggregate.
     */
    public function doSaveAlter()
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        // Check inputs
        if ('' == trim($_REQUEST['aggrname'])) {
            $this->doAlter($lang['straggrneedsname']);

            return;
        }

        $status = $data->alterAggregate(
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
        if (0 == $status) {
            $this->doDefault($lang['straggraltered']);
        } else {
            $this->doAlter($lang['straggralteredbad']);

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
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('aggregate');
        $this->printTitle($lang['stralter'], 'pg.aggregate.alter');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/aggregates.php\" method=\"post\">\n";
        $aggrdata = $data->getAggregate($_REQUEST['aggrname'], $_REQUEST['aggrtype']);
        if ($aggrdata->recordCount() > 0) {
            // Output table header
            echo "<table>\n";
            echo "\t<tr>\n\t\t<th class=\"data required\">{$lang['strname']}</th>";
            echo "<th class=\"data required\">{$lang['strowner']}</th>";
            echo "<th class=\"data required\">{$lang['strschema']}</th>\n\t</tr>\n";

            // Display aggregate's name, owner and schema
            echo "\t<tr>\n\t\t<td><input name=\"newaggrname\" size=\"32\" maxlength=\"32\" value=\"", htmlspecialchars($_REQUEST['aggrname']), '" /></td>';
            echo '<td><input name="newaggrowner" size="32" maxlength="32" value="', htmlspecialchars($aggrdata->fields['usename']), '" /></td>';
            echo '<td><input name="newaggrschema" size="32" maxlength="32" value="', htmlspecialchars($_REQUEST['schema']), "\" /></td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strcomment']}</th>\n";
            echo "\t\t<td><textarea name=\"newaggrcomment\" rows=\"3\" cols=\"32\">",
            htmlspecialchars($aggrdata->fields['aggrcomment']), "</textarea></td>\n\t</tr>\n";
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"save_alter\" />\n";
            echo $this->misc->form;
            echo '<input type="hidden" name="aggrname" value="', htmlspecialchars($_REQUEST['aggrname']), "\" />\n";
            echo '<input type="hidden" name="aggrtype" value="', htmlspecialchars($_REQUEST['aggrtype']), "\" />\n";
            echo '<input type="hidden" name="aggrowner" value="', htmlspecialchars($aggrdata->fields['usename']), "\" />\n";
            echo '<input type="hidden" name="aggrschema" value="', htmlspecialchars($_REQUEST['schema']), "\" />\n";
            echo '<input type="hidden" name="aggrcomment" value="', htmlspecialchars($aggrdata->fields['aggrcomment']), "\" />\n";
            echo "<input type=\"submit\" name=\"alter\" value=\"{$lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
        } else {
            echo "<p>{$lang['strnodata']}</p>\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strback']}\" /></p>\n";
        }
        echo "</form>\n";
    }

    /**
     * Show confirmation of drop and perform actual drop of the aggregate function selected.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('aggregate');
            $this->printTitle($lang['strdrop'], 'pg.aggregate.drop');

            echo '<p>', sprintf($lang['strconfdropaggregate'], htmlspecialchars($_REQUEST['aggrname'])), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/aggregates.php\" method=\"post\">\n";
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$lang['strcascade']}</label></p>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo '<input type="hidden" name="aggrname" value="', htmlspecialchars($_REQUEST['aggrname']), "\" />\n";
            echo '<input type="hidden" name="aggrtype" value="', htmlspecialchars($_REQUEST['aggrtype']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            $status = $data->dropAggregate($_POST['aggrname'], $_POST['aggrtype'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doDefault($lang['straggregatedropped']);
            } else {
                $this->doDefault($lang['straggregatedroppedbad']);
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
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('aggregate');
        $this->printTitle($lang['strproperties'], 'pg.aggregate');
        $this->printMsg($msg);

        $aggrdata = $data->getAggregate($_REQUEST['aggrname'], $_REQUEST['aggrtype']);

        if ($aggrdata->recordCount() > 0) {
            // Display aggregate's info
            echo "<table>\n";
            echo "<tr>\n\t<th class=\"data left\">{$lang['strname']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($_REQUEST['aggrname']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$lang['straggrbasetype']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($_REQUEST['aggrtype']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$lang['straggrsfunc']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['aggtransfn']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$lang['straggrstype']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['aggstype']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$lang['straggrffunc']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['aggfinalfn']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$lang['straggrinitcond']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['agginitval']), "</td>\n</tr>\n";
            if ($data->hasAggregateSortOp()) {
                echo "<tr>\n\t<th class=\"data left\">{$lang['straggrsortop']}</th>\n";
                echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['aggsortop']), "</td>\n</tr>\n";
            }
            echo "<tr>\n\t<th class=\"data left\">{$lang['strowner']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['usename']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$lang['strcomment']}</th>\n";
            echo "\t<td class=\"data1\">", $this->misc->printVal($aggrdata->fields['aggrcomment']), "</td>\n</tr>\n";
            echo "</table>\n";
        } else {
            echo "<p>{$lang['strnodata']}</p>\n";
        }

        $navlinks = [
            'showall' => [
                'attr' => [
                    'href' => [
                        'url'     => 'aggregates.php',
                        'urlvars' => [
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $lang['straggrshowall'],
            ],
        ];

        if ($data->hasAlterAggregate()) {
            $navlinks['alter'] = [
                'attr' => [
                    'href' => [
                        'url'     => 'aggregates.php',
                        'urlvars' => [
                            'action'   => 'alter',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'aggrname' => $_REQUEST['aggrname'],
                            'aggrtype' => $_REQUEST['aggrtype'],
                        ],
                    ],
                ],
                'content' => $lang['stralter'],
            ];
        }

        $navlinks['drop'] = [
            'attr' => [
                'href' => [
                    'url'     => 'aggregates.php',
                    'urlvars' => [
                        'action'   => 'confirm_drop',
                        'server'   => $_REQUEST['server'],
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'aggrname' => $_REQUEST['aggrname'],
                        'aggrtype' => $_REQUEST['aggrtype'],
                    ],
                ],
            ],
            'content' => $lang['strdrop'],
        ];

        $this->printNavLinks($navlinks, 'aggregates-properties', get_defined_vars());
    }
}
