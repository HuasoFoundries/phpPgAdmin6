<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 * @package PHPPgAdmin
 */
class TablesController extends BaseController
{
    use AdminTrait;
    public $script          = 'tables.php';
    public $controller_name = 'TablesController';
    public $table_place     = 'tables-tables';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang   = $this->lang;
        $action = $this->action;

        if ('tree' == $action) {
            return $this->doTree();
        }
        if ('subtree' == $action) {
            return $this->doSubTree();
        }

        $header_template = 'header.twig';
        $footer_template = 'footer.twig';

        ob_start();

        switch ($action) {
            case 'create':

                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $header_template = 'header_select2.twig';
                    $this->doCreate();
                }

                break;
            case 'createlike':
                $header_template = 'header_select2.twig';
                $this->doCreateLike(false);

                break;
            case 'confcreatelike':
                if (isset($_POST['cancel'])) {
                    $header_template = 'header_datatables.twig';
                    $this->doDefault();
                } else {
                    //$header_template = 'header_select2.twig';
                    $this->doCreateLike(true);
                }

                break;
            case 'selectrows':
                if (!isset($_POST['cancel'])) {
                    $this->doSelectRows(false);
                } else {
                    $header_template = 'header_datatables.twig';
                    $this->doDefault();
                }

                break;
            case 'confselectrows':
                $this->doSelectRows(true);

                break;
            case 'insertrow':
                if (!isset($_POST['cancel'])) {
                    $this->doInsertRow(false);
                } else {
                    $header_template = 'header_datatables.twig';
                    $this->doDefault();
                }

                break;
            case 'confinsertrow':
                $this->doInsertRow(true);

                break;
            case 'empty':
                if (isset($_POST['empty'])) {
                    $this->doEmpty(false);
                } else {
                    $header_template = 'header_datatables.twig';
                    $this->doDefault();
                }

                break;
            case 'confirm_empty':
                $this->doEmpty(true);

                break;
            case 'drop':
                if (isset($_POST['drop'])) {
                    $this->doDrop(false);
                } else {
                    $header_template = 'header_datatables.twig';
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            default:
                if (false === $this->adminActions($action, 'table')) {
                    $header_template = 'header_datatables.twig';
                    $this->doDefault();
                }

                break;
        }

        $output = ob_get_clean();

        $this->printHeader($lang['strtables'], null, true, $header_template);
        $this->printBody();

        echo $output;

        return $this->printFooter();
    }

    /**
     * Show default list of tables in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'tables');
        $this->printMsg($msg);

        $tables = $data->getTables();

        $columns = [
            'table'      => [
                'title' => $lang['strtable'],
                'field' => Decorator::field('relname'),
                'url'   => \SUBFOLDER . "/redirect/table?{$this->misc->href}&amp;",
                'vars'  => ['table' => 'relname'],
            ],
            'owner'      => [
                'title' => $lang['strowner'],
                'field' => Decorator::field('relowner'),
            ],
            'tablespace' => [
                'title' => $lang['strtablespace'],
                'field' => Decorator::field('tablespace'),
            ],
            'tuples'     => [
                'title' => $lang['strestimatedrowcount'],
                'field' => Decorator::field('reltuples'),
                'type'  => 'numeric',
            ],
            'actions'    => [
                'title' => $lang['stractions'],
            ],
            'comment'    => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('relcomment'),
            ],
        ];

        $actions = [
            'multiactions' => [
                'keycols' => ['table' => 'relname'],
                'url'     => 'tables.php',
                'default' => 'analyze',
            ],
            'browse'       => [
                'content' => $lang['strbrowse'],
                'attr'    => [
                    'href' => [
                        'url'     => 'display.php',
                        'urlvars' => [
                            'subject' => 'table',
                            'return'  => 'table',
                            'table'   => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'select'       => [
                'content' => $lang['strselect'],
                'attr'    => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action' => 'confselectrows',
                            'table'  => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'insert'       => [
                'content' => $lang['strinsert'],
                'attr'    => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action' => 'confinsertrow',
                            'table'  => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'empty'        => [
                'multiaction' => 'confirm_empty',
                'content'     => $lang['strempty'],
                'attr'        => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action' => 'confirm_empty',
                            'table'  => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'alter'        => [
                'content' => $lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'tblproperties.php',
                        'urlvars' => [
                            'action' => 'confirm_alter',
                            'table'  => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'drop'         => [
                'multiaction' => 'confirm_drop',
                'content'     => $lang['strdrop'],
                'attr'        => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'table'  => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'vacuum'       => [
                'multiaction' => 'confirm_vacuum',
                'content'     => $lang['strvacuum'],
                'attr'        => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action' => 'confirm_vacuum',
                            'table'  => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'analyze'      => [
                'multiaction' => 'confirm_analyze',
                'content'     => $lang['stranalyze'],
                'attr'        => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action' => 'confirm_analyze',
                            'table'  => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'reindex'      => [
                'multiaction' => 'confirm_reindex',
                'content'     => $lang['strreindex'],
                'attr'        => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action' => 'confirm_reindex',
                            'table'  => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            //'cluster' TODO ?
        ];

        if (!$data->hasTablespaces()) {
            unset($columns['tablespace']);
        }

        //\Kint::dump($tables);

        echo $this->printTable($tables, $columns, $actions, $this->table_place, $lang['strnotables']);

        $navlinks = [
            'create' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action'   => 'create',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $lang['strcreatetable'],
            ],
        ];

        if (($tables->recordCount() > 0) && $data->hasCreateTableLike()) {
            $navlinks['createlike'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => [
                            'action'   => 'createlike',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $lang['strcreatetablelike'],
            ];
        }
        $this->printNavLinks($navlinks, 'tables-tables', get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        //\PC::debug($this->misc->getDatabase(), 'getDatabase');

        $tables = $data->getTables();

        $reqvars = $this->misc->getRequestVars('table');

        $attrs = [
            'text'       => Decorator::field('relname'),
            'icon'       => 'Table',
            'iconAction' => Decorator::url('display.php', $reqvars, ['table' => Decorator::field('relname')]),
            'toolTip'    => Decorator::field('relcomment'),
            'action'     => Decorator::redirecturl('redirect.php', $reqvars, ['table' => Decorator::field('relname')]),
            'branch'     => Decorator::url('tables.php', $reqvars, ['action' => 'subtree', 'table' => Decorator::field('relname')]),
        ];

        return $this->printTree($tables, $attrs, 'tables');
    }

    public function doSubTree()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $tabs    = $this->misc->getNavTabs('table');
        $items   = $this->adjustTabsForTree($tabs);
        $reqvars = $this->misc->getRequestVars('table');

        $attrs = [
            'text'   => Decorator::field('title'),
            'icon'   => Decorator::field('icon'),
            'action' => Decorator::actionurl(
                Decorator::field('url'),
                $reqvars,
                Decorator::field('urlvars'),
                ['table' => $_REQUEST['table']]
            ),
            'branch' => Decorator::ifempty(
                Decorator::field('branch'),
                '',
                Decorator::url(
                    Decorator::field('url'),
                    $reqvars,
                    [
                        'action' => 'tree',
                        'table'  => $_REQUEST['table'],
                    ]
                )
            ),
        ];

        return $this->printTree($items, $attrs, 'table');
    }

    /**
     * Displays a screen where they can enter a new table.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_REQUEST['stage'])) {
            $_REQUEST['stage'] = 1;
            $default_with_oids = $data->getDefaultWithOid();
            if ('off' == $default_with_oids) {
                $_REQUEST['withoutoids'] = 'on';
            }
        }

        if (!isset($_REQUEST['name'])) {
            $_REQUEST['name'] = '';
        }

        if (!isset($_REQUEST['fields'])) {
            $_REQUEST['fields'] = '';
        }

        if (!isset($_REQUEST['tblcomment'])) {
            $_REQUEST['tblcomment'] = '';
        }

        if (!isset($_REQUEST['spcname'])) {
            $_REQUEST['spcname'] = '';
        }

        switch ($_REQUEST['stage']) {
            case 1:
                // You are presented with a form in which you describe the table, pick the tablespace and state how many fields it will have
                // Fetch all tablespaces from the database
                if ($data->hasTablespaces()) {
                    $tablespaces = $data->getTablespaces();
                }

                $this->printTrail('schema');
                $this->printTitle($lang['strcreatetable'], 'pg.table.create');
                $this->printMsg($msg);

                echo '<form action="' . \SUBFOLDER . '/src/views/' . $this->script . '" method="post">';
                echo "\n";
                echo "<table>\n";
                echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strname']}</th>\n";
                echo "\t\t<td class=\"data\"><input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                htmlspecialchars($_REQUEST['name']), "\" /></td>\n\t</tr>\n";
                echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strnumcols']}</th>\n";
                echo "\t\t<td class=\"data\"><input name=\"fields\" size=\"5\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                htmlspecialchars($_REQUEST['fields']), "\" /></td>\n\t</tr>\n";
                echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['stroptions']}</th>\n";
                echo "\t\t<td class=\"data\"><label for=\"withoutoids\"><input type=\"checkbox\" id=\"withoutoids\" name=\"withoutoids\"", isset($_REQUEST['withoutoids']) ? ' checked="checked"' : '', " />WITHOUT OIDS</label></td>\n\t</tr>\n";

                // Tablespace (if there are any)
                if ($data->hasTablespaces() && $tablespaces->recordCount() > 0) {
                    echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strtablespace']}</th>\n";
                    echo "\t\t<td class=\"data1\">\n\t\t\t<select name=\"spcname\">\n";
                    // Always offer the default (empty) option
                    echo "\t\t\t\t<option value=\"\"",
                    ('' == $_REQUEST['spcname']) ? ' selected="selected"' : '', "></option>\n";
                    // Display all other tablespaces
                    while (!$tablespaces->EOF) {
                        $spcname = htmlspecialchars($tablespaces->fields['spcname']);
                        echo "\t\t\t\t<option value=\"{$spcname}\"",
                        ($tablespaces->fields['spcname'] == $_REQUEST['spcname']) ? ' selected="selected"' : '', ">{$spcname}</option>\n";
                        $tablespaces->moveNext();
                    }
                    echo "\t\t\t</select>\n\t\t</td>\n\t</tr>\n";
                }

                echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strcomment']}</th>\n";
                echo "\t\t<td><textarea name=\"tblcomment\" rows=\"3\" cols=\"32\">",
                htmlspecialchars($_REQUEST['tblcomment']), "</textarea></td>\n\t</tr>\n";

                echo "</table>\n";
                echo "<p><input type=\"hidden\" name=\"action\" value=\"create\" />\n";
                echo "<input type=\"hidden\" name=\"stage\" value=\"2\" />\n";
                echo $this->misc->form;
                echo "<input type=\"submit\" value=\"{$lang['strnext']}\" />\n";
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
                echo "</form>\n";

                break;
            case 2:

                // Check inputs
                $fields = trim($_REQUEST['fields']);
                if ('' == trim($_REQUEST['name'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreate($lang['strtableneedsname']);

                    return;
                }
                if ('' == $fields || !is_numeric($fields) || $fields != (int) $fields || $fields < 1) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreate($lang['strtableneedscols']);

                    return;
                }

                $types        = $data->getTypes(true, false, true);
                $types_for_js = [];

                $this->printTrail('schema');
                $this->printTitle($lang['strcreatetable'], 'pg.table.create');
                $this->printMsg($msg);

                echo '<script src="' . \SUBFOLDER . '/js/tables.js" type="text/javascript"></script>';
                echo '<form action="' . \SUBFOLDER . "/src/views/tables.php\" method=\"post\">\n";

                // Output table header
                echo "<table>\n";
                echo "\t<tr><th colspan=\"2\" class=\"data required\">{$lang['strcolumn']}</th><th colspan=\"2\" class=\"data required\">{$lang['strtype']}</th>";
                echo "<th class=\"data\">{$lang['strlength']}</th><th class=\"data\">{$lang['strnotnull']}</th>";
                echo "<th class=\"data\">{$lang['struniquekey']}</th><th class=\"data\">{$lang['strprimarykey']}</th>";
                echo "<th class=\"data\">{$lang['strdefault']}</th><th class=\"data\">{$lang['strcomment']}</th></tr>\n";

                for ($i = 0; $i < $_REQUEST['fields']; ++$i) {
                    if (!isset($_REQUEST['field'][$i])) {
                        $_REQUEST['field'][$i] = '';
                    }

                    if (!isset($_REQUEST['length'][$i])) {
                        $_REQUEST['length'][$i] = '';
                    }

                    if (!isset($_REQUEST['default'][$i])) {
                        $_REQUEST['default'][$i] = '';
                    }

                    if (!isset($_REQUEST['colcomment'][$i])) {
                        $_REQUEST['colcomment'][$i] = '';
                    }

                    echo "\t<tr>\n\t\t<td>", $i + 1, ".&nbsp;</td>\n";
                    echo "\t\t<td><input name=\"field[{$i}]\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                    htmlspecialchars($_REQUEST['field'][$i]), "\" /></td>\n";
                    echo "\t\t<td>\n\t\t\t<select name=\"type[{$i}]\" class=\"select2\" id=\"types{$i}\" onchange=\"checkLengths(this.options[this.selectedIndex].value,{$i});\">\n";
                    // Output any "magic" types
                    foreach ($data->extraTypes as $v) {
                        $types_for_js[strtolower($v)] = 1;
                        echo "\t\t\t\t<option value=\"", htmlspecialchars($v), '"',
                        (isset($_REQUEST['type'][$i]) && $_REQUEST['type'][$i] == $v) ? ' selected="selected"' : '', '>',
                        $this->misc->printVal($v), "</option>\n";
                    }
                    $types->moveFirst();
                    while (!$types->EOF) {
                        $typname                = $types->fields['typname'];
                        $types_for_js[$typname] = 1;
                        echo "\t\t\t\t<option value=\"", htmlspecialchars($typname), '"',
                        (isset($_REQUEST['type'][$i]) && $_REQUEST['type'][$i] == $typname) ? ' selected="selected"' : '', '>',
                        $this->misc->printVal($typname), "</option>\n";
                        $types->moveNext();
                    }
                    echo "\t\t\t</select>\n\t\t\n";
                    if (0 == $i) {
                        // only define js types array once
                        $predefined_size_types = array_intersect($data->predefined_size_types, array_keys($types_for_js));
                        $escaped_predef_types  = []; // the JS escaped array elements
                        foreach ($predefined_size_types as $value) {
                            $escaped_predef_types[] = "'{$value}'";
                        }
                        echo '<script type="text/javascript">predefined_lengths = new Array(' . implode(',', $escaped_predef_types) . ");</script>\n\t</td>";
                    }

                    // Output array type selector
                    echo "\t\t<td>\n\t\t\t<select name=\"array[{$i}]\">\n";
                    echo "\t\t\t\t<option value=\"\"", (isset($_REQUEST['array'][$i]) && $_REQUEST['array'][$i] == '') ? ' selected="selected"' : '', "></option>\n";
                    echo "\t\t\t\t<option value=\"[]\"", (isset($_REQUEST['array'][$i]) && $_REQUEST['array'][$i] == '[]') ? ' selected="selected"' : '', ">[ ]</option>\n";
                    echo "\t\t\t</select>\n\t\t</td>\n";

                    echo "\t\t<td><input name=\"length[{$i}]\" id=\"lengths{$i}\" size=\"10\" value=\"",
                    htmlspecialchars($_REQUEST['length'][$i]), "\" /></td>\n";
                    echo "\t\t<td><input type=\"checkbox\" name=\"notnull[{$i}]\"", (isset($_REQUEST['notnull'][$i])) ? ' checked="checked"' : '', " /></td>\n";
                    echo "\t\t<td style=\"text-align: center\"><input type=\"checkbox\" name=\"uniquekey[{$i}]\""
                        . (isset($_REQUEST['uniquekey'][$i]) ? ' checked="checked"' : '') . " /></td>\n";
                    echo "\t\t<td style=\"text-align: center\"><input type=\"checkbox\" name=\"primarykey[{$i}]\" "
                        . (isset($_REQUEST['primarykey'][$i]) ? ' checked="checked"' : '')
                        . " /></td>\n";
                    echo "\t\t<td><input name=\"default[{$i}]\" size=\"20\" value=\"",
                    htmlspecialchars($_REQUEST['default'][$i]), "\" /></td>\n";
                    echo "\t\t<td><input name=\"colcomment[{$i}]\" size=\"40\" value=\"",
                    htmlspecialchars($_REQUEST['colcomment'][$i]), "\" />
						<script type=\"text/javascript\">checkLengths(document.getElementById('types{$i}').value,{$i});</script>
						</td>\n\t</tr>\n";
                }
                echo "</table>\n";
                echo "<p><input type=\"hidden\" name=\"action\" value=\"create\" />\n";
                echo "<input type=\"hidden\" name=\"stage\" value=\"3\" />\n";
                echo $this->misc->form;
                echo '<input type="hidden" name="name" value="', htmlspecialchars($_REQUEST['name']), "\" />\n";
                echo '<input type="hidden" name="fields" value="', htmlspecialchars($_REQUEST['fields']), "\" />\n";
                if (isset($_REQUEST['withoutoids'])) {
                    echo "<input type=\"hidden\" name=\"withoutoids\" value=\"true\" />\n";
                }
                echo '<input type="hidden" name="tblcomment" value="', htmlspecialchars($_REQUEST['tblcomment']), "\" />\n";
                if (isset($_REQUEST['spcname'])) {
                    echo '<input type="hidden" name="spcname" value="', htmlspecialchars($_REQUEST['spcname']), "\" />\n";
                }
                echo "<input type=\"submit\" value=\"{$lang['strcreate']}\" />\n";
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
                echo "</form>\n";

                break;
            case 3:

                if (!isset($_REQUEST['notnull'])) {
                    $_REQUEST['notnull'] = [];
                }

                if (!isset($_REQUEST['uniquekey'])) {
                    $_REQUEST['uniquekey'] = [];
                }

                if (!isset($_REQUEST['primarykey'])) {
                    $_REQUEST['primarykey'] = [];
                }

                if (!isset($_REQUEST['length'])) {
                    $_REQUEST['length'] = [];
                }

                // Default tablespace to null if it isn't set
                if (!isset($_REQUEST['spcname'])) {
                    $_REQUEST['spcname'] = null;
                }

                // Check inputs
                $fields = trim($_REQUEST['fields']);
                if ('' == trim($_REQUEST['name'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreate($lang['strtableneedsname']);

                    return;
                }
                if ('' == $fields || !is_numeric($fields) || $fields != (int) $fields || $fields <= 0) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreate($lang['strtableneedscols']);

                    return;
                }

                $status = $data->createTable(
                    $_REQUEST['name'],
                    $_REQUEST['fields'],
                    $_REQUEST['field'],
                    $_REQUEST['type'],
                    $_REQUEST['array'],
                    $_REQUEST['length'],
                    $_REQUEST['notnull'],
                    $_REQUEST['default'],
                    isset($_REQUEST['withoutoids']),
                    $_REQUEST['colcomment'],
                    $_REQUEST['tblcomment'],
                    $_REQUEST['spcname'],
                    $_REQUEST['uniquekey'],
                    $_REQUEST['primarykey']
                );

                if (0 == $status) {
                    $this->misc->setReloadBrowser(true);

                    return $this->doDefault($lang['strtablecreated']);
                }
                if ($status == -1) {
                    $_REQUEST['stage'] = 2;
                    $this->doCreate($lang['strtableneedsfield']);

                    return;
                }
                $_REQUEST['stage'] = 2;
                $this->doCreate($lang['strtablecreatedbad']);

                return;
                break;
            default:
                echo "<p>{$lang['strinvalidparam']}</p>\n";
        }
    }

    /**
     * Dsiplay a screen where user can create a table from an existing one.
     * We don't have to check if pg supports schema cause create table like
     * is available under pg 7.4+ which has schema.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doCreateLike($confirm, $msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!$confirm) {
            if (!isset($_REQUEST['name'])) {
                $_REQUEST['name'] = '';
            }

            if (!isset($_REQUEST['like'])) {
                $_REQUEST['like'] = '';
            }

            if (!isset($_REQUEST['tablespace'])) {
                $_REQUEST['tablespace'] = '';
            }

            $this->printTrail('schema');
            $this->printTitle($lang['strcreatetable'], 'pg.table.create');
            $this->printMsg($msg);

            $tbltmp = $data->getTables(true);
            $tbltmp = $tbltmp->getArray();

            $tables = [];
            $tblsel = '';
            foreach ($tbltmp as $a) {
                $data->fieldClean($a['nspname']);
                $data->fieldClean($a['relname']);
                $tables["\"{$a['nspname']}\".\"{$a['relname']}\""] = serialize(['schema' => $a['nspname'], 'table' => $a['relname']]);
                if ($_REQUEST['like'] == $tables["\"{$a['nspname']}\".\"{$a['relname']}\""]) {
                    $tblsel = htmlspecialchars($tables["\"{$a['nspname']}\".\"{$a['relname']}\""]);
                }
            }

            unset($tbltmp);

            echo '<form action="' . \SUBFOLDER . "/src/views/tables.php\" method=\"post\">\n";
            echo "<table>\n\t<tr>\n\t\t<th class=\"data left required\">{$lang['strname']}</th>\n";
            echo "\t\t<td class=\"data\"><input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"", htmlspecialchars($_REQUEST['name']), "\" /></td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strcreatetablelikeparent']}</th>\n";
            echo "\t\t<td class=\"data\">";
            echo \PHPPgAdmin\XHtml\HTMLController::printCombo($tables, 'like', true, $tblsel, false);
            echo "</td>\n\t</tr>\n";
            if ($data->hasTablespaces()) {
                $tblsp_ = $data->getTablespaces();
                if ($tblsp_->recordCount() > 0) {
                    $tblsp_ = $tblsp_->getArray();
                    $tblsp  = [];
                    foreach ($tblsp_ as $a) {
                        $tblsp[$a['spcname']] = $a['spcname'];
                    }

                    echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strtablespace']}</th>\n";
                    echo "\t\t<td class=\"data\">";
                    echo \PHPPgAdmin\XHtml\HTMLController::printCombo($tblsp, 'tablespace', true, $_REQUEST['tablespace'], false);
                    echo "</td>\n\t</tr>\n";
                }
            }
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['stroptions']}</th>\n\t\t<td class=\"data\">";
            echo '<label for="withdefaults"><input type="checkbox" id="withdefaults" name="withdefaults"',
            isset($_REQUEST['withdefaults']) ? ' checked="checked"' : '',
                "/>{$lang['strcreatelikewithdefaults']}</label>";
            if ($data->hasCreateTableLikeWithConstraints()) {
                echo '<br /><label for="withconstraints"><input type="checkbox" id="withconstraints" name="withconstraints"',
                isset($_REQUEST['withconstraints']) ? ' checked="checked"' : '',
                    "/>{$lang['strcreatelikewithconstraints']}</label>";
            }
            if ($data->hasCreateTableLikeWithIndexes()) {
                echo '<br /><label for="withindexes"><input type="checkbox" id="withindexes" name="withindexes"',
                isset($_REQUEST['withindexes']) ? ' checked="checked"' : '',
                    "/>{$lang['strcreatelikewithindexes']}</label>";
            }
            echo "</td>\n\t</tr>\n";
            echo '</table>';

            echo "<input type=\"hidden\" name=\"action\" value=\"confcreatelike\" />\n";
            echo $this->misc->form;
            echo "<p><input type=\"submit\" value=\"{$lang['strcreate']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            if ('' == trim($_REQUEST['name'])) {
                $this->doCreateLike(false, $lang['strtableneedsname']);

                return;
            }
            if ('' == trim($_REQUEST['like'])) {
                $this->doCreateLike(false, $lang['strtablelikeneedslike']);

                return;
            }

            if (!isset($_REQUEST['tablespace'])) {
                $_REQUEST['tablespace'] = '';
            }

            $status = $data->createTableLike(
                $_REQUEST['name'],
                unserialize($_REQUEST['like']),
                isset($_REQUEST['withdefaults']),
                isset($_REQUEST['withconstraints']),
                isset($_REQUEST['withindexes']),
                $_REQUEST['tablespace']
            );

            if (0 == $status) {
                $this->misc->setReloadBrowser(true);

                return $this->doDefault($lang['strtablecreated']);
            }
            $this->doCreateLike(false, $lang['strtablecreatedbad']);

            return;
        }
    }

    /**
     * Ask for select parameters and perform select.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doSelectRows($confirm, $msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('table');
            $this->printTabs('table', 'select');
            $this->printMsg($msg);

            $attrs = $data->getTableAttributes($_REQUEST['table']);

            echo '<form action="' . \SUBFOLDER . "/src/views/tables.php\" method=\"post\" id=\"selectform\">\n";
            if ($attrs->recordCount() > 0) {
                // JavaScript for select all feature
                echo "<script type=\"text/javascript\">\n";
                echo "//<![CDATA[\n";
                echo "	function selectAll() {\n";
                echo "		for (var i=0; i<document.getElementById('selectform').elements.length; i++) {\n";
                echo "			var e = document.getElementById('selectform').elements[i];\n";
                echo "			if (e.name.indexOf('show') == 0) e.checked = document.getElementById('selectform').selectall.checked;\n";
                echo "		}\n";
                echo "	}\n";
                echo "//]]>\n";
                echo "</script>\n";

                echo "<table>\n";

                // Output table header
                echo "<tr><th class=\"data\">{$lang['strshow']}</th><th class=\"data\">{$lang['strcolumn']}</th>";
                echo "<th class=\"data\">{$lang['strtype']}</th><th class=\"data\">{$lang['stroperator']}</th>";
                echo "<th class=\"data\">{$lang['strvalue']}</th></tr>";

                $i = 0;
                while (!$attrs->EOF) {
                    $attrs->fields['attnotnull'] = $data->phpBool($attrs->fields['attnotnull']);
                    // Set up default value if there isn't one already
                    if (!isset($_REQUEST['values'][$attrs->fields['attname']])) {
                        $_REQUEST['values'][$attrs->fields['attname']] = null;
                    }

                    if (!isset($_REQUEST['ops'][$attrs->fields['attname']])) {
                        $_REQUEST['ops'][$attrs->fields['attname']] = null;
                    }

                    // Continue drawing row
                    $id = (0 == ($i % 2) ? '1' : '2');
                    echo "<tr class=\"data{$id}\">\n";
                    echo '<td style="white-space:nowrap;">';
                    echo '<input type="checkbox" name="show[', htmlspecialchars($attrs->fields['attname']), ']"',
                    isset($_REQUEST['show'][$attrs->fields['attname']]) ? ' checked="checked"' : '', ' /></td>';
                    echo '<td style="white-space:nowrap;">', $this->misc->printVal($attrs->fields['attname']), '</td>';
                    echo '<td style="white-space:nowrap;">', $this->misc->printVal($data->formatType($attrs->fields['type'], $attrs->fields['atttypmod'])), '</td>';
                    echo '<td style="white-space:nowrap;">';
                    echo "<select name=\"ops[{$attrs->fields['attname']}]\">\n";
                    foreach (array_keys($data->selectOps) as $v) {
                        echo '<option value="', htmlspecialchars($v), '"', ($_REQUEST['ops'][$attrs->fields['attname']] == $v) ? ' selected="selected"' : '',
                        '>', htmlspecialchars($v), "</option>\n";
                    }
                    echo "</select>\n</td>\n";
                    echo '<td style="white-space:nowrap;">', $data->printField(
                        "values[{$attrs->fields['attname']}]",
                        $_REQUEST['values'][$attrs->fields['attname']],
                        $attrs->fields['type']
                    ), '</td>';
                    echo "</tr>\n";
                    ++$i;
                    $attrs->moveNext();
                }
                // Select all checkbox
                echo "<tr><td colspan=\"5\"><input type=\"checkbox\" id=\"selectall\" name=\"selectall\" accesskey=\"a\" onclick=\"javascript:selectAll()\" /><label for=\"selectall\">{$lang['strselectallfields']}</label></td>";
                echo "</tr></table>\n";
            } else {
                echo "<p>{$lang['strinvalidparam']}</p>\n";
            }

            echo "<p><input type=\"hidden\" name=\"action\" value=\"selectrows\" />\n";
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
            echo "<input type=\"hidden\" name=\"subject\" value=\"table\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"select\" accesskey=\"r\" value=\"{$lang['strselect']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";

            return;
        }
        if (!isset($_POST['show'])) {
            $_POST['show'] = [];
        }

        if (!isset($_POST['values'])) {
            $_POST['values'] = [];
        }

        if (!isset($_POST['nulls'])) {
            $_POST['nulls'] = [];
        }

        // Verify that they haven't supplied a value for unary operators
        foreach ($_POST['ops'] as $k => $v) {
            if ('p' == $data->selectOps[$v] && $_POST['values'][$k] != '') {
                $this->doSelectRows(true, $lang['strselectunary']);

                return;
            }
        }

        if (0 == sizeof($_POST['show'])) {
            $this->doSelectRows(true, $lang['strselectneedscol']);
        } else {
            // Generate query SQL
            $query = $data->getSelectSQL(
                $_REQUEST['table'],
                array_keys($_POST['show']),
                $_POST['values'],
                $_POST['ops']
            );
            $_REQUEST['query']  = $query;
            $_REQUEST['return'] = 'selectrows';

            $this->setNoOutput(true);

            $display_controller = new DisplayController($this->getContainer());

            return $display_controller->render();
        }
    }

    /**
     * Ask for insert parameters and then actually insert row.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doInsertRow($confirm, $msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('table');
            $this->printTabs('table', 'insert');

            $this->printMsg($msg);

            $attrs = $data->getTableAttributes($_REQUEST['table']);

            if (('disable' != $this->conf['autocomplete'])) {
                $fksprops = $this->misc->getAutocompleteFKProperties($_REQUEST['table']);
                if (false !== $fksprops) {
                    echo $fksprops['code'];
                }
            } else {
                $fksprops = false;
            }

            echo '<form action="' . \SUBFOLDER . "/src/views/tables.php\" method=\"post\" id=\"ac_form\">\n";
            if ($attrs->recordCount() > 0) {
                echo "<table>\n";

                // Output table header
                echo "<tr><th class=\"data\">{$lang['strcolumn']}</th><th class=\"data\">{$lang['strtype']}</th>";
                echo "<th class=\"data\">{$lang['strformat']}</th>";
                echo "<th class=\"data\">{$lang['strnull']}</th><th class=\"data\">{$lang['strvalue']}</th></tr>";

                $i      = 0;
                $fields = [];
                while (!$attrs->EOF) {
                    $fields[$attrs->fields['attnum']] = $attrs->fields['attname'];
                    $attrs->fields['attnotnull']      = $data->phpBool($attrs->fields['attnotnull']);
                    // Set up default value if there isn't one already
                    if (!isset($_REQUEST['values'][$attrs->fields['attnum']])) {
                        $_REQUEST['values'][$attrs->fields['attnum']] = $attrs->fields['adsrc'];
                    }

                    // Default format to 'VALUE' if there is no default,
                    // otherwise default to 'EXPRESSION'
                    if (!isset($_REQUEST['format'][$attrs->fields['attnum']])) {
                        $_REQUEST['format'][$attrs->fields['attnum']] = (null === $attrs->fields['adsrc']) ? 'VALUE' : 'EXPRESSION';
                    }

                    // Continue drawing row
                    $id = (0 == ($i % 2) ? '1' : '2');
                    echo "<tr class=\"data{$id}\">\n";
                    echo '<td style="white-space:nowrap;">', $this->misc->printVal($attrs->fields['attname']), '</td>';
                    echo "<td style=\"white-space:nowrap;\">\n";
                    echo $this->misc->printVal($data->formatType($attrs->fields['type'], $attrs->fields['atttypmod']));
                    echo "<input type=\"hidden\" name=\"types[{$attrs->fields['attnum']}]\" value=\"",
                    htmlspecialchars($attrs->fields['type']), '" /></td>';
                    echo "<td style=\"white-space:nowrap;\">\n";
                    echo "<select name=\"format[{$attrs->fields['attnum']}]\">\n";
                    echo '<option value="VALUE"', ($_REQUEST['format'][$attrs->fields['attnum']] == 'VALUE') ? ' selected="selected"' : '', ">{$lang['strvalue']}</option>\n";
                    echo '<option value="EXPRESSION"', ($_REQUEST['format'][$attrs->fields['attnum']] == 'EXPRESSION') ? ' selected="selected"' : '', ">{$lang['strexpression']}</option>\n";
                    echo "</select>\n</td>\n";
                    echo '<td style="white-space:nowrap;">';
                    // Output null box if the column allows nulls (doesn't look at CHECKs or ASSERTIONS)
                    if (!$attrs->fields['attnotnull']) {
                        echo "<label><span><input type=\"checkbox\" name=\"nulls[{$attrs->fields['attnum']}]\"",
                        isset($_REQUEST['nulls'][$attrs->fields['attnum']]) ? ' checked="checked"' : '', ' /></span></label></td>';
                    } else {
                        echo '&nbsp;</td>';
                    }
                    echo "<td id=\"row_att_{$attrs->fields['attnum']}\" style=\"white-space:nowrap;\">";
                    if ((false !== $fksprops) && isset($fksprops['byfield'][$attrs->fields['attnum']])) {
                        echo $data->printField(
                            "values[{$attrs->fields['attnum']}]",
                            $_REQUEST['values'][$attrs->fields['attnum']],
                            'fktype' /*force FK*/,
                            [
                                'id'           => "attr_{$attrs->fields['attnum']}",
                                'autocomplete' => 'off',
                            ]
                        );
                    } else {
                        echo $data->printField("values[{$attrs->fields['attnum']}]", $_REQUEST['values'][$attrs->fields['attnum']], $attrs->fields['type']);
                    }
                    echo "</td>\n";
                    echo "</tr>\n";
                    ++$i;
                    $attrs->moveNext();
                }
                echo "</table>\n";

                if (!isset($_SESSION['counter'])) {
                    $_SESSION['counter'] = 0;
                }

                echo "<input type=\"hidden\" name=\"action\" value=\"insertrow\" />\n";
                echo '<input type="hidden" name="fields" value="', htmlentities(serialize($fields), ENT_QUOTES, 'UTF-8'), "\" />\n";
                echo '<input type="hidden" name="protection_counter" value="' . $_SESSION['counter'] . "\" />\n";
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
                echo "<p><input type=\"submit\" name=\"insert\" value=\"{$lang['strinsert']}\" />\n";
                echo "<input type=\"submit\" name=\"insertandrepeat\" accesskey=\"r\" value=\"{$lang['strinsertandrepeat']}\" />\n";
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";

                if (false !== $fksprops) {
                    if ('default off' != $this->conf['autocomplete']) {
                        echo "<input type=\"checkbox\" id=\"no_ac\" value=\"1\" checked=\"checked\" /><label for=\"no_ac\">{$lang['strac']}</label>\n";
                    } else {
                        echo "<input type=\"checkbox\" id=\"no_ac\" value=\"0\" /><label for=\"no_ac\">{$lang['strac']}</label>\n";
                    }
                }
                echo "</p>\n";
            } else {
                echo "<p>{$lang['strnofieldsforinsert']}</p>\n";
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            }
            echo $this->misc->form;
            echo "</form>\n";
        } else {
            if (!isset($_POST['values'])) {
                $_POST['values'] = [];
            }

            if (!isset($_POST['nulls'])) {
                $_POST['nulls'] = [];
            }

            $_POST['fields'] = unserialize(htmlspecialchars_decode($_POST['fields'], ENT_QUOTES));

            if ($_SESSION['counter']++ == $_POST['protection_counter']) {
                $status = $data->insertRow($_POST['table'], $_POST['fields'], $_POST['values'], $_POST['nulls'], $_POST['format'], $_POST['types']);
                if (0 == $status) {
                    if (isset($_POST['insert'])) {
                        return $this->doDefault($lang['strrowinserted']);
                    }
                    $_REQUEST['values'] = [];
                    $_REQUEST['nulls']  = [];
                    $this->doInsertRow(true, $lang['strrowinserted']);
                } else {
                    $this->doInsertRow(true, $lang['strrowinsertedbad']);
                }
            } else {
                $this->doInsertRow(true, $lang['strrowduplicate']);
            }
        }
    }

    /**
     * Show confirmation of empty and perform actual empty.
     *
     * @param mixed $confirm
     */
    public function doEmpty($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($lang['strspecifytabletoempty']);
        }

        if ($confirm) {
            if (isset($_REQUEST['ma'])) {
                $this->printTrail('schema');
                $this->printTitle($lang['strempty'], 'pg.table.empty');

                echo '<form action="' . \SUBFOLDER . "/src/views/tables.php\" method=\"post\">\n";
                foreach ($_REQUEST['ma'] as $v) {
                    $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                    echo '<p>' . sprintf($lang['strconfemptytable'], $this->misc->printVal($a['table']));

                    echo "</p>\n";
                    printf('<input type="hidden" name="table[]" value="%s" />', htmlspecialchars($a['table']));
                }
            } // END mutli empty
            else {
                $this->printTrail('table');
                $this->printTitle($lang['strempty'], 'pg.table.empty');

                echo '<p>', sprintf($lang['strconfemptytable'], $this->misc->printVal($_REQUEST['table'])), "</p>\n";

                echo '<form action="' . \SUBFOLDER . "/src/views/tables.php\" method=\"post\">\n";

                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
            } // END not mutli empty
            echo "<input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$lang['strcascade']}</label>";
            echo "<input type=\"hidden\" name=\"action\" value=\"empty\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"empty\" value=\"{$lang['strempty']}\" /> <input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            echo "</form>\n";
        } // END if confirm
        else {
            // Do Empty
            $msg = '';
            if (is_array($_REQUEST['table'])) {
                foreach ($_REQUEST['table'] as $t) {
                    list($status, $sql) = $data->emptyTable($t, isset($_POST['cascade']));
                    if (0 == $status) {
                        $msg .= sprintf('%s<br />', $sql);
                        $msg .= sprintf('%s: %s<br />', htmlentities($t, ENT_QUOTES, 'UTF-8'), $lang['strtableemptied']);
                    } else {
                        $this->doDefault(sprintf('%s%s: %s<br />', $msg, htmlentities($t, ENT_QUOTES, 'UTF-8'), $lang['strtableemptiedbad']));

                        return;
                    }
                }
                $this->doDefault($msg);
            } // END mutli empty
            else {
                list($status, $sql) = $data->emptyTable($_POST['table'], isset($_POST['cascade']));
                if (0 == $status) {
                    $msg .= sprintf('%s<br />', $sql);
                    $msg .= sprintf('%s: %s<br />', htmlentities($_POST['table'], ENT_QUOTES, 'UTF-8'), $lang['strtableemptied']);

                    return $this->doDefault($msg);
                }

                return $this->doDefault($lang['strtableemptiedbad']);
            } // END not mutli empty
        } // END do Empty
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

        if (empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($lang['strspecifytabletodrop']);
        }

        if ($confirm) {
            //If multi drop
            if (isset($_REQUEST['ma'])) {
                $this->printTrail('schema');
                $this->printTitle($lang['strdrop'], 'pg.table.drop');

                echo '<form action="' . \SUBFOLDER . "/src/views/tables.php\" method=\"post\">\n";
                foreach ($_REQUEST['ma'] as $v) {
                    $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                    echo '<p>', sprintf($lang['strconfdroptable'], $this->misc->printVal($a['table'])), "</p>\n";
                    printf('<input type="hidden" name="table[]" value="%s" />', htmlspecialchars($a['table']));
                }
            } else {
                $this->printTrail('table');
                $this->printTitle($lang['strdrop'], 'pg.table.drop');

                echo '<p>', sprintf($lang['strconfdroptable'], $this->misc->printVal($_REQUEST['table'])), "</p>\n";

                echo '<form action="' . \SUBFOLDER . "/src/views/tables.php\" method=\"post\">\n";
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
            } // END if multi drop

            echo "<input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$lang['strcascade']}</label></p>\n";
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            echo "</form>\n";
        } // END confirm
        else {
            //If multi drop
            if (is_array($_REQUEST['table'])) {
                $msg    = '';
                $status = $data->beginTransaction();
                if (0 == $status) {
                    foreach ($_REQUEST['table'] as $t) {
                        $status = $data->dropTable($t, isset($_POST['cascade']));
                        if (0 == $status) {
                            $msg .= sprintf('%s: %s<br />', htmlentities($t, ENT_QUOTES, 'UTF-8'), $lang['strtabledropped']);
                        } else {
                            $data->endTransaction();

                            return $this->doDefault(sprintf('%s%s: %s<br />', $msg, htmlentities($t, ENT_QUOTES, 'UTF-8'), $lang['strtabledroppedbad']));
                        }
                    }
                }
                if (0 == $data->endTransaction()) {
                    // Everything went fine, back to the Default page....
                    $this->misc->setReloadBrowser(true);

                    return $this->doDefault($msg);
                }

                return $this->doDefault($lang['strtabledroppedbad']);
            }
            $status = $data->dropTable($_POST['table'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);

                return $this->doDefault($lang['strtabledropped']);
            }

            return $this->doDefault($lang['strtabledroppedbad']);
        } // END DROP
    }

    // END Function
}
