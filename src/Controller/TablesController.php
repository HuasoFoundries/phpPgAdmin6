<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use PHPPgAdmin\Traits\AdminTrait;
use PHPPgAdmin\Traits\InsertEditRowTrait;
use PHPPgAdmin\XHtml\HTMLController;
use Slim\Http\Response;

/**
 * Base controller class.
 */
class TablesController extends BaseController
{
    use AdminTrait;
    use InsertEditRowTrait;

    public $table_place = 'tables-tables';

    public $controller_title = 'strtables';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        if ('subtree' === $this->action) {
            return $this->doSubTree();
        }

        if ('json' === $this->action) {
            return $this->displayJson();
        }

        $header_template = 'header.twig';

        \ob_start();

        switch ($this->action) {
            case 'create':
                if (null !== $this->getPostParam('cancel')) {
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
                if (null !== $this->getPostParam('cancel')) {
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
                    $this->doInsertRow();
                } else {
                    $header_template = 'header_datatables.twig';
                    $this->doDefault();
                }

                break;
            case 'confinsertrow':
                $this->formInsertRow();

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
                if (null !== $this->getPostParam('drop')) {
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
                if (false === $this->adminActions($this->action, 'table')) {
                    $header_template = 'header_datatables.twig';
                    $this->doDefault();
                }

                break;
        }

        $output = \ob_get_clean();

        $this->printHeader($this->headerTitle(), null, true, $header_template);
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
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'tables');
        $this->printMsg($msg);

        $tables = $data->getTables();

        $columns = $this->_getColumns();

        if (!(bool) ($this->conf['display_sizes']['tables'] ?? false)) {
            unset($columns['table_size']);
        }

        $actions = $this->_getActions();

        if (self::isRecordset($tables)) {
            echo $this->printTable($tables, $columns, $actions, $this->table_place, $this->lang['strnotables']);
        }
        $attr = [
            'href' => [
                'url' => 'tables',
                'urlvars' => [
                    'action' => 'createlike',
                    'server' => $this->getRequestParam('server'),
                    'database' => $this->getRequestParam('database'),
                    'schema' => $this->getRequestParam('schema'),
                ],
            ],
        ];
        $navlinks = [
            'create' => [
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'create',
                            'server' => $this->getRequestParam('server'),
                            'database' => $this->getRequestParam('database'),
                            'schema' => $this->getRequestParam('schema'),
                        ],
                    ],
                ],
                'content' => $this->lang['strcreatetable'],
            ],
        ];

        if ((0 < $tables->RecordCount()) && $data->hasCreateTableLike()) {
            $navlinks['createlike'] = [
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'createlike',
                            'server' => $this->getRequestParam('server'),
                            'database' => $this->getRequestParam('database'),
                            'schema' => $this->getRequestParam('schema'),
                        ],
                    ],
                ],
                'content' => $this->lang['strcreatetablelike'],
            ];
        }

        return $this->printNavLinks($navlinks, 'tables-tables', \get_defined_vars());
    }

    public function displayJson()
    {
        $data = $this->misc->getDatabaseAccessor();

        $tables = $data->getTables();

        $all_tables = $tables->getAll();

        return $this
            ->container
            ->response
            ->withStatus(200)
            ->withJson($all_tables);
    }

    /**
     * Generate XML for the browser tree.
     *
     * @return Response|string
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $tables = $data->getTables();

        $reqvars = $this->misc->getRequestVars('table');

        $attrs = [
            'text' => Decorator::field('relname'),
            'icon' => 'Table',
            'iconAction' => Decorator::url('display', $reqvars, ['table' => Decorator::field('relname')]),
            'toolTip' => Decorator::field('relcomment'),
            'action' => Decorator::redirecturl('redirect', $reqvars, ['table' => Decorator::field('relname')]),
            'branch' => Decorator::url('tables', $reqvars, ['action' => 'subtree', 'table' => Decorator::field('relname')]),
        ];

        return $this->printTree($tables, $attrs, 'tables');
    }

    /**
     * @return Response|string
     */
    public function doSubTree()
    {
        $tabs = $this->misc->getNavTabs('table');
        $items = $this->adjustTabsForTree($tabs);
        $reqvars = $this->misc->getRequestVars('table');

        $attrs = [
            'text' => Decorator::field('title'),
            'icon' => Decorator::field('icon'),
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
                        'table' => $_REQUEST['table'],
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
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_REQUEST['stage'])) {
            $_REQUEST['stage'] = 1;
            $default_with_oids = $data->getDefaultWithOid();

            if ('off' === $default_with_oids) {
                $_REQUEST['withoutoids'] = 'on';
            }
        }

        $this->coalesceArr($_REQUEST, 'name', '');

        $this->coalesceArr($_REQUEST, 'fields', '');

        $this->coalesceArr($_REQUEST, 'tblcomment', '');

        $this->coalesceArr($_REQUEST, 'spcname', '');
        $tablespaces = null;

        switch ($_REQUEST['stage']) {
            case 1:
                // You are presented with a form in which you describe the table, pick the tablespace and state how many fields it will have
                // Fetch all tablespaces from the database
                if ($data->hasTablespaces()) {
                    $tablespaces = $data->getTablespaces();
                }

                $this->printTrail('schema');
                $this->printTitle($this->lang['strcreatetable'], 'pg.table.create');
                $this->printMsg($msg);

                echo '<form action="' . $this->script . '" method="post">';
                echo \PHP_EOL;
                echo '<table>' . \PHP_EOL;
                echo \sprintf(
                    '	<tr>
		<th class="data left required">%s</th>',
                    $this->lang['strname']
                ) . \PHP_EOL;
                echo \sprintf(
                    '		<td class="data"><input name="name" size="32" maxlength="%s" value="',
                    $data->_maxNameLen
                ),
                \htmlspecialchars($_REQUEST['name']),
                "\" /></td>\n\t</tr>" . \PHP_EOL;
                echo \sprintf(
                    '	<tr>
		<th class="data left required">%s</th>',
                    $this->lang['strnumcols']
                ) . \PHP_EOL;
                echo \sprintf(
                    '		<td class="data"><input name="fields" size="5" maxlength="%s" value="',
                    $data->_maxNameLen
                ),
                \htmlspecialchars($_REQUEST['fields']),
                "\" /></td>\n\t</tr>" . \PHP_EOL;
                echo \sprintf(
                    '	<tr>
		<th class="data left">%s</th>',
                    $this->lang['stroptions']
                ) . \PHP_EOL;
                echo "\t\t<td class=\"data\"><label for=\"withoutoids\"><input type=\"checkbox\" id=\"withoutoids\" name=\"withoutoids\"", isset($_REQUEST['withoutoids']) ? ' checked="checked"' : '', " />WITHOUT OIDS</label></td>\n\t</tr>" . \PHP_EOL;

                // Tablespace (if there are any)
                if ($data->hasTablespaces() && 0 < $tablespaces->RecordCount()) {
                    echo \sprintf(
                        '	<tr>
		<th class="data left">%s</th>',
                        $this->lang['strtablespace']
                    ) . \PHP_EOL;
                    echo "\t\t<td class=\"data1\">\n\t\t\t<select name=\"spcname\">" . \PHP_EOL;
                    // Always offer the default (empty) option
                    echo "\t\t\t\t<option value=\"\"", ('' === $_REQUEST['spcname']) ? ' selected="selected"' : '',
                    '></option>' . \PHP_EOL;
                    // Display all other tablespaces
                    while (!$tablespaces->EOF) {
                        $spcname = \htmlspecialchars($tablespaces->fields['spcname']);
                        echo \sprintf(
                            '				<option value="%s"',
                            $spcname
                        ), ($tablespaces->fields['spcname'] === $_REQUEST['spcname']) ? ' selected="selected"' : '',
                        \sprintf(
                            '>%s</option>',
                            $spcname
                        ) . \PHP_EOL;
                        $tablespaces->MoveNext();
                    }
                    echo "\t\t\t</select>\n\t\t</td>\n\t</tr>" . \PHP_EOL;
                }

                echo \sprintf(
                    '	<tr>
		<th class="data left">%s</th>',
                    $this->lang['strcomment']
                ) . \PHP_EOL;
                echo "\t\t<td><textarea name=\"tblcomment\" rows=\"3\" cols=\"32\">",
                \htmlspecialchars($_REQUEST['tblcomment']),
                "</textarea></td>\n\t</tr>" . \PHP_EOL;

                echo '</table>' . \PHP_EOL;
                echo '<p><input type="hidden" name="action" value="create" />' . \PHP_EOL;
                echo '<input type="hidden" name="stage" value="2" />' . \PHP_EOL;
                echo $this->view->form;
                echo \sprintf(
                    '<input type="submit" value="%s" />',
                    $this->lang['strnext']
                ) . \PHP_EOL;
                echo \sprintf(
                    '<input type="submit" name="cancel" value="%s"  /></p>%s',
                    $this->lang['strcancel'],
                    \PHP_EOL
                );
                echo '</form>' . \PHP_EOL;

                break;
            case 2:
                // Check inputs
                $fields = (int) (\trim($_REQUEST['fields']));

                if ('' === \trim($_REQUEST['name'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreate($this->lang['strtableneedsname']);

                    return;
                }

                if ('' === $fields || !\is_numeric($fields) || (int) $fields !== $fields || 1 > $fields) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreate($this->lang['strtableneedscols']);

                    return;
                }

                $types = $data->getTypes(true, false, true);
                $types_for_js = [];

                $this->printTrail('schema');
                $this->printTitle($this->lang['strcreatetable'], 'pg.table.create');
                $this->printMsg($msg);

                echo '<script src="assets/js/tables.js" type="text/javascript"></script>';
                echo '<form action="tables" method="post">' . \PHP_EOL;

                // Output table header
                echo '<table>' . \PHP_EOL;
                echo \sprintf(
                    '	<tr><th colspan="2" class="data required">%s</th><th colspan="2" class="data required">%s</th>',
                    $this->lang['strcolumn'],
                    $this->lang['strtype']
                );
                echo \sprintf(
                    '<th class="data">%s</th><th class="data">%s</th>',
                    $this->lang['strlength'],
                    $this->lang['strnotnull']
                );
                echo \sprintf(
                    '<th class="data">%s</th><th class="data">%s</th>',
                    $this->lang['struniquekey'],
                    $this->lang['strprimarykey']
                );
                echo \sprintf(
                    '<th class="data">%s</th><th class="data">%s</th></tr>',
                    $this->lang['strdefault'],
                    $this->lang['strcomment']
                ) . \PHP_EOL;

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

                    echo "\t<tr>\n\t\t<td>", $i + 1, '.&nbsp;</td>' . \PHP_EOL;
                    echo \sprintf(
                        '		<td><input name="field[%s]" size="16" maxlength="%s" value="',
                        $i,
                        $data->_maxNameLen
                    ),
                    \htmlspecialchars($_REQUEST['field'][$i]),
                    '" /></td>' . \PHP_EOL;
                    echo \sprintf(
                        '		<td>
			<select name="type[%s]" class="select2" id="types%s" onchange="checkLengths(this.options[this.selectedIndex].value,%s);">',
                        $i,
                        $i,
                        $i
                    ) . \PHP_EOL;
                    // Output any "magic" types
                    foreach ($data->extraTypes as $v) {
                        $types_for_js[\mb_strtolower($v)] = 1;
                        echo "\t\t\t\t<option value=\"", \htmlspecialchars($v), '"', (isset($_REQUEST['type'][$i]) && $_REQUEST['type'][$i] === $v) ? ' selected="selected"' : '',
                        '>',
                        $this->misc->printVal($v),
                        '</option>' . \PHP_EOL;
                    }
                    $types->moveFirst();

                    while (!$types->EOF) {
                        $typname = $types->fields['typname'];
                        $types_for_js[$typname] = 1;
                        echo "\t\t\t\t<option value=\"", \htmlspecialchars($typname), '"', (isset($_REQUEST['type'][$i]) && $_REQUEST['type'][$i] === $typname) ? ' selected="selected"' : '',
                        '>',
                        $this->misc->printVal($typname),
                        '</option>' . \PHP_EOL;
                        $types->MoveNext();
                    }
                    echo "\t\t\t</select>\n\t\t\n";

                    if (0 === $i) {
                        // only define js types array once
                        $predefined_size_types = \array_intersect($data->predefined_size_types, \array_keys($types_for_js));
                        $escaped_predef_types = []; // the JS escaped array elements

                        foreach ($predefined_size_types as $value) {
                            $escaped_predef_types[] = \sprintf(
                                '\'%s\'',
                                $value
                            );
                        }
                        echo '<script type="text/javascript">predefined_lengths = new Array(' . \implode(',', $escaped_predef_types) . ");</script>\n\t</td>";
                    }

                    // Output array type selector
                    echo \sprintf(
                        '		<td>
			<select name="array[%s]">',
                        $i
                    ) . \PHP_EOL;
                    echo "\t\t\t\t<option value=\"\"", (isset($_REQUEST['array'][$i]) && '' === $_REQUEST['array'][$i]) ? ' selected="selected"' : '', '></option>' . \PHP_EOL;
                    echo "\t\t\t\t<option value=\"[]\"", (isset($_REQUEST['array'][$i]) && '[]' === $_REQUEST['array'][$i]) ? ' selected="selected"' : '', '>[ ]</option>' . \PHP_EOL;
                    echo "\t\t\t</select>\n\t\t</td>" . \PHP_EOL;

                    echo \sprintf(
                        '		<td><input name="length[%s]" id="lengths%s" size="10" value="',
                        $i,
                        $i
                    ),
                    \htmlspecialchars($_REQUEST['length'][$i]),
                    '" /></td>' . \PHP_EOL;
                    echo \sprintf(
                        '		<td><input type="checkbox" name="notnull[%s]"',
                        $i
                    ), (isset($_REQUEST['notnull'][$i])) ? ' checked="checked"' : '', ' /></td>' . \PHP_EOL;
                    echo \sprintf(
                        '		<td style="text-align: center"><input type="checkbox" name="uniquekey[%s]"',
                        $i
                    )
                        . (isset($_REQUEST['uniquekey'][$i]) ? ' checked="checked"' : '') . ' /></td>' . \PHP_EOL;
                    echo \sprintf(
                        '		<td style="text-align: center"><input type="checkbox" name="primarykey[%s]" ',
                        $i
                    )
                        . (isset($_REQUEST['primarykey'][$i]) ? ' checked="checked"' : '')
                        . ' /></td>' . \PHP_EOL;
                    echo \sprintf(
                        '		<td><input name="default[%s]" size="20" value="',
                        $i
                    ),
                    \htmlspecialchars($_REQUEST['default'][$i]),
                    '" /></td>' . \PHP_EOL;
                    echo \sprintf(
                        '		<td><input name="colcomment[%s]" size="40" value="',
                        $i
                    ),
                    \htmlspecialchars($_REQUEST['colcomment'][$i]),
                    \sprintf(
                        '" />
						<script type="text/javascript">checkLengths(document.getElementById(\'types%s\').value,%s);</script>
						</td>
	</tr>',
                        $i,
                        $i
                    ) . \PHP_EOL;
                }
                echo '</table>' . \PHP_EOL;
                echo '<p><input type="hidden" name="action" value="create" />' . \PHP_EOL;
                echo '<input type="hidden" name="stage" value="3" />' . \PHP_EOL;
                echo $this->view->form;
                echo '<input type="hidden" name="name" value="', \htmlspecialchars($_REQUEST['name']), '" />' . \PHP_EOL;
                echo '<input type="hidden" name="fields" value="', \htmlspecialchars($_REQUEST['fields']), '" />' . \PHP_EOL;

                if (isset($_REQUEST['withoutoids'])) {
                    echo '<input type="hidden" name="withoutoids" value="true" />' . \PHP_EOL;
                }
                echo '<input type="hidden" name="tblcomment" value="', \htmlspecialchars($_REQUEST['tblcomment']), '" />' . \PHP_EOL;

                if (isset($_REQUEST['spcname'])) {
                    echo '<input type="hidden" name="spcname" value="', \htmlspecialchars($_REQUEST['spcname']), '" />' . \PHP_EOL;
                }
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

                break;
            case 3:
                $this->coalesceArr($_REQUEST, 'notnull', []);

                $this->coalesceArr($_REQUEST, 'uniquekey', []);

                $this->coalesceArr($_REQUEST, 'primarykey', []);

                $this->coalesceArr($_REQUEST, 'length', []);

                // Default tablespace to null if it isn't set
                $this->coalesceArr($_REQUEST, 'spcname', null);

                // Check inputs
                $fields = (int) (\trim($_REQUEST['fields']));

                if ('' === \trim($_REQUEST['name'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreate($this->lang['strtableneedsname']);

                    return;
                }

                if ('' === $fields || !\is_numeric($fields) || (int) $fields !== $fields || 0 >= $fields) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreate($this->lang['strtableneedscols']);

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

                if (0 === $status) {
                    $this->view->setReloadBrowser(true);

                    $this->doDefault($this->lang['strtablecreated']);

                    return;
                }

                if (-1 === $status) {
                    $_REQUEST['stage'] = 2;
                    $this->doCreate($this->lang['strtableneedsfield']);

                    return;
                }
                $_REQUEST['stage'] = 2;
                $this->doCreate($this->lang['strtablecreatedbad']);

                return;

                break;

            default:
                echo \sprintf(
                    '<p>%s</p>',
                    $this->lang['strinvalidparam']
                ) . \PHP_EOL;
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
    public function doCreateLike($confirm, $msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if (!$confirm) {
            $this->coalesceArr($_REQUEST, 'name', '');

            $this->coalesceArr($_REQUEST, 'like', '');

            $this->coalesceArr($_REQUEST, 'tablespace', '');

            $this->printTrail('schema');
            $this->printTitle($this->lang['strcreatetable'], 'pg.table.create');
            $this->printMsg($msg);

            $tbltmp = $data->getAllTables();
            $tbltmp = $tbltmp->getArray();

            $tables = [];
            $tblsel = '';

            foreach ($tbltmp as $a) {
                $data->fieldClean($a['nspname']);
                $data->fieldClean($a['relname']);
                $tables[\sprintf(
                    '"%s"."%s"',
                    $a['nspname'],
                    $a['relname']
                )] = \serialize(['schema' => $a['nspname'], 'table' => $a['relname']]);

                if ($_REQUEST['like'] === $tables[\sprintf(
                    '"%s"."%s"',
                    $a['nspname'],
                    $a['relname']
                )]) {
                    $tblsel = \htmlspecialchars($tables[\sprintf(
                        '"%s"."%s"',
                        $a['nspname'],
                        $a['relname']
                    )]);
                }
            }

            unset($tbltmp);

            echo '<form action="tables" method="post">' . \PHP_EOL;
            echo \sprintf(
                '<table>
	<tr>
		<th class="data left required">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo \sprintf(
                '		<td class="data"><input name="name" size="32" maxlength="%s" value="',
                $data->_maxNameLen
            ), \htmlspecialchars($_REQUEST['name']), "\" /></td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left required">%s</th>',
                $this->lang['strcreatetablelikeparent']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data\">";
            echo HTMLController::printCombo($tables, 'like', true, $tblsel, false);
            echo "</td>\n\t</tr>" . \PHP_EOL;

            if ($data->hasTablespaces()) {
                $tblsp_ = $data->getTablespaces();

                if (0 < $tblsp_->RecordCount()) {
                    $tblsp_ = $tblsp_->getArray();
                    $tblsp = [];

                    foreach ($tblsp_ as $a) {
                        $tblsp[$a['spcname']] = $a['spcname'];
                    }

                    echo \sprintf(
                        '	<tr>
		<th class="data left">%s</th>',
                        $this->lang['strtablespace']
                    ) . \PHP_EOL;
                    echo "\t\t<td class=\"data\">";
                    echo HTMLController::printCombo($tblsp, 'tablespace', true, $_REQUEST['tablespace'], false);
                    echo "</td>\n\t</tr>" . \PHP_EOL;
                }
            }
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>
		<td class="data">',
                $this->lang['stroptions']
            );
            echo '<label for="withdefaults"><input type="checkbox" id="withdefaults" name="withdefaults"',
            isset($_REQUEST['withdefaults']) ? ' checked="checked"' : '',
            \sprintf(
                '/>%s</label>',
                $this->lang['strcreatelikewithdefaults']
            );

            if ($data->hasCreateTableLikeWithConstraints()) {
                echo '<br /><label for="withconstraints"><input type="checkbox" id="withconstraints" name="withconstraints"',
                isset($_REQUEST['withconstraints']) ? ' checked="checked"' : '',
                \sprintf(
                    '/>%s</label>',
                    $this->lang['strcreatelikewithconstraints']
                );
            }

            if ($data->hasCreateTableLikeWithIndexes()) {
                echo '<br /><label for="withindexes"><input type="checkbox" id="withindexes" name="withindexes"',
                isset($_REQUEST['withindexes']) ? ' checked="checked"' : '',
                \sprintf(
                    '/>%s</label>',
                    $this->lang['strcreatelikewithindexes']
                );
            }
            echo "</td>\n\t</tr>" . \PHP_EOL;
            echo '</table>';

            echo '<input type="hidden" name="action" value="confcreatelike" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<p><input type="submit" value="%s" />',
                $this->lang['strcreate']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s"  /></p>%s',
                $this->lang['strcancel'],
                \PHP_EOL
            );
            echo '</form>' . \PHP_EOL;
        } else {
            if ('' === \trim($_REQUEST['name'])) {
                $this->doCreateLike(false, $this->lang['strtableneedsname']);

                return;
            }

            if ('' === \trim($_REQUEST['like'])) {
                $this->doCreateLike(false, $this->lang['strtablelikeneedslike']);

                return;
            }

            $this->coalesceArr($_REQUEST, 'tablespace', '');

            $status = $data->createTableLike(
                $_REQUEST['name'],
                \unserialize($_REQUEST['like']),
                isset($_REQUEST['withdefaults']),
                isset($_REQUEST['withconstraints']),
                isset($_REQUEST['withindexes']),
                $_REQUEST['tablespace']
            );

            if (0 === $status) {
                $this->view->setReloadBrowser(true);

                $this->doDefault($this->lang['strtablecreated']);

                return;
            }
            $this->doCreateLike(false, $this->lang['strtablecreatedbad']);

            return;
        }
    }

    /**
     * Ask for select parameters and perform select.
     *
     * @param mixed $confirm
     * @param mixed $msg
     *
     * @return null|Response
     */
    public function doSelectRows($confirm, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('table');
            $this->printTabs('table', 'select');
            $this->printMsg($msg);

            $attrs = $data->getTableAttributes($_REQUEST['table']);

            echo '<form action="display" method="post" id="selectform">' . \PHP_EOL;

            if (0 < $attrs->RecordCount()) {
                // JavaScript for select all feature
                echo '<script type="text/javascript">' . \PHP_EOL;
                echo "//<![CDATA[\n";
                echo "	function selectAll() {\n";
                echo "		for (var i=0; i<document.getElementById('selectform').elements.length; i++) {\n";
                echo "			var e = document.getElementById('selectform').elements[i];\n";
                echo "			if (e.name.indexOf('show') == 0) e.checked = document.getElementById('selectform').selectall.checked;\n";
                echo "		}\n";
                echo "	}\n";
                echo '//]]>' . \PHP_EOL;
                echo '</script>' . \PHP_EOL;

                echo '<table>' . \PHP_EOL;

                // Output table header
                echo \sprintf(
                    '<tr><th class="data">%s</th><th class="data">%s</th>',
                    $this->lang['strshow'],
                    $this->lang['strcolumn']
                );
                echo \sprintf(
                    '<th class="data">%s</th><th class="data">%s</th>',
                    $this->lang['strtype'],
                    $this->lang['stroperator']
                );
                echo \sprintf(
                    '<th class="data">%s</th></tr>',
                    $this->lang['strvalue']
                );

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
                    $id = (0 === ($i % 2) ? '1' : '2');
                    echo \sprintf(
                        '<tr class="data%s">',
                        $id
                    ) . \PHP_EOL;
                    echo '<td style="white-space:nowrap;">';
                    echo '<input type="checkbox" name="show[', \htmlspecialchars($attrs->fields['attname']), ']"',
                    isset($_REQUEST['show'][$attrs->fields['attname']]) ? ' checked="checked"' : '',
                    ' /></td>';
                    echo '<td style="white-space:nowrap;">', $this->misc->printVal($attrs->fields['attname']), '</td>';
                    echo '<td style="white-space:nowrap;">', $this->misc->printVal($data->formatType($attrs->fields['type'], $attrs->fields['atttypmod'])), '</td>';
                    echo '<td style="white-space:nowrap;">';
                    echo \sprintf(
                        '<select name="ops[%s]">',
                        $attrs->fields['attname']
                    ) . \PHP_EOL;

                    foreach (\array_keys($data->selectOps) as $v) {
                        echo '<option value="', \htmlspecialchars($v), '"', ($_REQUEST['ops'][$attrs->fields['attname']] === $v) ? ' selected="selected"' : '',
                        '>',
                        \htmlspecialchars($v),
                        '</option>' . \PHP_EOL;
                    }
                    echo "</select>\n</td>" . \PHP_EOL;
                    echo '<td style="white-space:nowrap;">', $data->printField(
                        \sprintf(
                            'values[%s]',
                            $attrs->fields['attname']
                        ),
                        $_REQUEST['values'][$attrs->fields['attname']],
                        $attrs->fields['type']
                    ), '</td>';
                    echo '</tr>' . \PHP_EOL;
                    ++$i;
                    $attrs->MoveNext();
                }
                // Select all checkbox
                echo \sprintf(
                    '<tr><td colspan="5"><input type="checkbox" id="selectall" name="selectall" accesskey="a" onclick="javascript:selectAll()" /><label for="selectall">%s</label></td>',
                    $this->lang['strselectallfields']
                );
                echo '</tr></table>' . \PHP_EOL;
            } else {
                echo \sprintf(
                    '<p>%s</p>',
                    $this->lang['strinvalidparam']
                ) . \PHP_EOL;
            }

            echo '<p><input type="hidden" name="action" value="selectrows" />' . \PHP_EOL;
            echo \sprintf(
                '<input type="hidden" name="table" value="%s"  />%s',
                \htmlspecialchars($_REQUEST['table']),
                \PHP_EOL
            );
            echo '<input type="hidden" name="subject" value="table" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="select" accesskey="r" value="%s" />',
                $this->lang['strselect']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s"  /></p>%s',
                $this->lang['strcancel'],
                \PHP_EOL
            );
            echo '</form>' . \PHP_EOL;

            return;
        }
        $this->coalesceArr($_POST, 'show', []);

        $this->coalesceArr($_POST, 'values', []);

        $this->coalesceArr($_POST, 'nulls', []);

        // Verify that they haven't supplied a value for unary operators
        foreach ($_POST['ops'] as $k => $v) {
            if ('p' === $data->selectOps[$v] && '' !== $_POST['values'][$k]) {
                $this->doSelectRows(true, $this->lang['strselectunary']);

                return;
            }
        }

        if (0 === \count($_POST['show'])) {
            return $this->doSelectRows(true, $this->lang['strselectneedscol']);
        }
        // Generate query SQL
        $query = $data->getSelectSQL(
            $_REQUEST['table'],
            \array_keys($_POST['show']),
            $_POST['values'],
            $_POST['ops']
        );
        $_REQUEST['query'] = $query;
        $_REQUEST['return'] = 'selectrows';

        $this->setNoOutput(true);

        $display_controller = new DisplayController($this->getContainer());

        return $display_controller->render();
    }

    /**
     * Ask for insert parameters and then actually insert row.
     *
     * @param mixed $msg
     */
    public function formInsertRow($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('table');
        $this->printTabs('table', 'insert');

        $this->printMsg($msg);

        $attrs = $data->getTableAttributes($_REQUEST['table']);

        $fksprops = $this->_getFKProps();

        $this->coalesceArr($_REQUEST, 'values', []);
        $this->coalesceArr($_REQUEST, 'nulls', []);
        $this->coalesceArr($_REQUEST, 'format', []);

        echo '<form action="tables" method="post" id="ac_form">' . \PHP_EOL;

        if (0 < $attrs->RecordCount()) {
            echo '<table>' . \PHP_EOL;

            // Output table header
            echo \sprintf(
                '<tr><th class="data">%s</th><th class="data">%s</th>',
                $this->lang['strcolumn'],
                $this->lang['strtype']
            );
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strformat']
            );
            echo \sprintf(
                '<th class="data">%s</th><th class="data">%s</th></tr>',
                $this->lang['strnull'],
                $this->lang['strvalue']
            );

            $i = 0;
            $fields = [];

            while (!$attrs->EOF) {
                $fields[$attrs->fields['attnum']] = $attrs->fields['attname'];
                $attrs->fields['attnotnull'] = $data->phpBool($attrs->fields['attnotnull']);
                // Set up default value if there isn't one already
                if (!isset($_REQUEST['values'][$attrs->fields['attnum']])) {
                    $_REQUEST['values'][$attrs->fields['attnum']] = $attrs->fields['adsrc'];

                    if (null === $attrs->fields['adsrc'] && !$attrs->fields['attnotnull']) {
                        $_REQUEST['nulls'][$attrs->fields['attnum']] = true;
                    }
                }

                // Default format to 'VALUE' if there is no default,
                // otherwise default to 'EXPRESSION'
                if (!isset($_REQUEST['format'][$attrs->fields['attnum']])) {
                    $_REQUEST['format'][$attrs->fields['attnum']] = (null === $attrs->fields['adsrc']) ? 'VALUE' : 'EXPRESSION';
                }

                $requested_format = $_REQUEST['format'][$attrs->fields['attnum']];
                // Continue drawing row
                $id = (0 === ($i % 2) ? '1' : '2');
                echo \sprintf(
                    '<tr class="data%s">',
                    $id
                ) . \PHP_EOL;
                echo '<td style="white-space:nowrap;">', $this->misc->printVal($attrs->fields['attname']), '</td>';
                echo '<td style="white-space:nowrap;">' . \PHP_EOL;
                echo $this->misc->printVal(
                    $data->formatType(
                        $attrs->fields['type'],
                        $attrs->fields['atttypmod']
                    )
                );
                echo \sprintf(
                    '<input type="hidden" name="types[%s]" value="',
                    $attrs->fields['attnum']
                ),
                \htmlspecialchars($attrs->fields['type']),
                '" /></td>';
                echo '<td style="white-space:nowrap;">' . \PHP_EOL;

                echo \sprintf(
                    '<select name="format[%s]">',
                    $attrs->fields['attnum']
                ) . \PHP_EOL;
                echo \sprintf(
                    '<option value="VALUE" %s >%s</option> %s',
                    ('VALUE' === $requested_format) ? ' selected="selected" ' : '',
                    $this->lang['strvalue'],
                    \PHP_EOL
                );
                echo \sprintf(
                    '<option value="EXPRESSION" %s >%s</option> %s',
                    ('EXPRESSION' === $requested_format) ? ' selected="selected" ' : '',
                    $this->lang['strexpression'],
                    \PHP_EOL
                );

                echo "</select>\n</td>" . \PHP_EOL;
                echo '<td style="white-space:nowrap;">';
                // Output null box if the column allows nulls
                // Edit: if it can be null, then null it is.
                if (!$attrs->fields['attnotnull']) {
                    echo '<label><span>';
                    echo \sprintf(
                        '<input type="checkbox" class="nullcheckbox" name="nulls[%s]" %s />',
                        $attrs->fields['attnum'],
                        ' checked="checked"'
                    );
                    echo '</span></label>';
                }
                echo '</td>';

                echo \sprintf(
                    '<td id="row_att_%s" style="white-space:nowrap;">',
                    $attrs->fields['attnum']
                );

                if ((false !== $fksprops) && isset($fksprops['byfield'][$attrs->fields['attnum']])) {
                    echo $data->printField(
                        \sprintf(
                            'values[%s]',
                            $attrs->fields['attnum']
                        ),
                        $_REQUEST['values'][$attrs->fields['attnum']],
                        'fktype' /*force FK*/,
                        [
                            'id' => \sprintf(
                                'attr_%s',
                                $attrs->fields['attnum']
                            ),
                            'autocomplete' => 'off',
                            'class' => 'insert_row_input',
                        ]
                    );
                } else {
                    echo $data->printField(\sprintf(
                        'values[%s]',
                        $attrs->fields['attnum']
                    ), $_REQUEST['values'][$attrs->fields['attnum']], $attrs->fields['type'], ['class' => 'insert_row_input']);
                }
                echo '</td>' . \PHP_EOL;
                echo '</tr>' . \PHP_EOL;
                ++$i;
                $attrs->MoveNext();
            }
            echo '</table>' . \PHP_EOL;

            if (!isset($_SESSION['counter'])) {
                $_SESSION['counter'] = 0;
            }

            echo '<input type="hidden" name="action" value="insertrow" />' . \PHP_EOL;
            echo '<input type="hidden" name="fields" value="', \htmlentities(\serialize($fields), \ENT_QUOTES, 'UTF-8'), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="protection_counter" value="' . $_SESSION['counter'] . '" />' . \PHP_EOL;
            echo \sprintf(
                '<input type="hidden" name="table" value="%s"  />%s',
                \htmlspecialchars($_REQUEST['table']),
                \PHP_EOL
            );
            echo \sprintf(
                '<p><input type="submit" name="insert" value="%s" />',
                $this->lang['strinsert']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="insertandrepeat" accesskey="r" value="%s" />',
                $this->lang['strinsertandrepeat']
            ) . \PHP_EOL;

            if (false !== $fksprops) {
                if ('default off' !== $this->conf['autocomplete']) {
                    echo \sprintf(
                        '<input type="checkbox" id="no_ac" value="1" checked="checked" /><label for="no_ac">%s</label>',
                        $this->lang['strac']
                    ) . \PHP_EOL;
                } else {
                    echo \sprintf(
                        '<input type="checkbox" id="no_ac" value="0" /><label for="no_ac">%s</label>',
                        $this->lang['strac']
                    ) . \PHP_EOL;
                }
            }
            echo '</p>' . \PHP_EOL;
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnofieldsforinsert']
            ) . \PHP_EOL;
        }
        echo $this->view->form;
        echo '</form>' . \PHP_EOL;
        echo \sprintf(
            '<button class="btn btn-mini btn_back" style="float: right;        margin-right: 4em;        margin-top: -3em;">%s</button>%s',
            $this->lang['strcancel'],
            \PHP_EOL
        );
        echo '<script src="assets/js/insert_or_edit_row.js" type="text/javascript"></script>';
    }

    /**
     * Performs insertion of row according to request parameters.
     */
    public function doInsertRow()
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'values', []);

        $this->coalesceArr($_POST, 'nulls', []);

        $_POST['fields'] = \unserialize(\htmlspecialchars_decode($_POST['fields'], \ENT_QUOTES));

        if ($_SESSION['counter']++ === (int) ($_POST['protection_counter'])) {
            $status = $data->insertRow($_POST['table'], $_POST['fields'], $_POST['values'], $_POST['nulls'], $_POST['format'], $_POST['types']);

            if (0 === $status) {
                if (isset($_POST['insert'])) {
                    $this->doDefault($this->lang['strrowinserted']);

                    return;
                }
                $_REQUEST['values'] = [];
                $_REQUEST['nulls'] = [];

                return $this->formInsertRow($this->lang['strrowinserted']);
            }

            return $this->formInsertRow($this->lang['strrowinsertedbad']);
        }

        return $this->formInsertRow($this->lang['strrowduplicate']);
    }

    /**
     * Show confirmation of empty and perform actual empty.
     *
     * @param mixed $confirm
     */
    public function doEmpty($confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletoempty']);

            return;
        }

        if ($confirm) {
            if (isset($_REQUEST['ma'])) {
                $this->printTrail('schema');
                $this->printTitle($this->lang['strempty'], 'pg.table.empty');

                echo '<form action="tables" method="post">' . \PHP_EOL;

                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    echo '<p>' . \sprintf(
                        $this->lang['strconfemptytable'],
                        $this->misc->printVal($a['table'])
                    );

                    echo '</p>' . \PHP_EOL;
                    \printf('<input type="hidden" name="table[]" value="%s" />', \htmlspecialchars($a['table']));
                } //  END mutli empty
            } else {
                $this->printTrail('table');
                $this->printTitle($this->lang['strempty'], 'pg.table.empty');

                echo '<p>', \sprintf(
                    $this->lang['strconfemptytable'],
                    $this->misc->printVal($_REQUEST['table'])
                ), '</p>' . \PHP_EOL;

                echo '<form action="tables" method="post">' . \PHP_EOL;

                echo \sprintf(
                    '<input type="hidden" name="table" value="%s"  />%s',
                    \htmlspecialchars($_REQUEST['table']),
                    \PHP_EOL
                );
                // END not mutli empty
            }
            echo \sprintf(
                '<input type="checkbox" id="cascade" name="cascade" /> <label for="cascade">%s</label>',
                $this->lang['strcascade']
            );
            echo '<input type="hidden" name="action" value="empty" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="empty" value="%s" /> <input type="submit" name="cancel" value="%s" />',
                $this->lang['strempty'],
                $this->lang['strcancel']
            ) . \PHP_EOL;
            echo "</form>\n"; //  END if confirm
        } else {
            // Do Empty
            $msg = '';

            if (\is_array($_REQUEST['table'])) {
                foreach ($_REQUEST['table'] as $t) {
                    [$status, $sql] = $data->emptyTable($t, isset($_POST['cascade']));

                    if (0 === $status) {
                        $msg .= \sprintf(
                            '%s<br />',
                            $sql
                        );
                        $msg .= \sprintf(
                            '%s: %s<br />',
                            \htmlentities($t, \ENT_QUOTES, 'UTF-8'),
                            $this->lang['strtableemptied']
                        );
                    } else {
                        $this->doDefault(\sprintf(
                            '%s%s: %s<br />',
                            $msg,
                            \htmlentities($t, \ENT_QUOTES, 'UTF-8'),
                            $this->lang['strtableemptiedbad']
                        ));

                        return;
                    }
                }
                $this->doDefault($msg); //  END mutli empty
            } else {
                [$status, $sql] = $data->emptyTable($_POST['table'], isset($_POST['cascade']));

                if (0 === $status) {
                    $msg .= \sprintf(
                        '%s<br />',
                        $sql
                    );
                    $msg .= \sprintf(
                        '%s: %s<br />',
                        \htmlentities($_POST['table'], \ENT_QUOTES, 'UTF-8'),
                        $this->lang['strtableemptied']
                    );

                    $this->doDefault($msg);
                }

                $this->doDefault($sql . '<br>' . $this->lang['strtableemptiedbad']);
                // END not mutli empty
            }
            // END do Empty
        }
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletodrop']);

            return;
        }

        if ($confirm) {
            //If multi drop
            if (isset($_REQUEST['ma'])) {
                $this->printTrail('schema');
                $this->printTitle($this->lang['strdrop'], 'pg.table.drop');

                echo '<form action="tables" method="post">' . \PHP_EOL;

                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    echo '<p>', \sprintf(
                        $this->lang['strconfdroptable'],
                        $this->misc->printVal($a['table'])
                    ), '</p>' . \PHP_EOL;
                    \printf('<input type="hidden" name="table[]" value="%s" />', \htmlspecialchars($a['table']));
                }
            } else {
                $this->printTrail('table');
                $this->printTitle($this->lang['strdrop'], 'pg.table.drop');

                echo '<p>', \sprintf(
                    $this->lang['strconfdroptable'],
                    $this->misc->printVal($_REQUEST['table'])
                ), '</p>' . \PHP_EOL;

                echo '<form action="tables" method="post">' . \PHP_EOL;
                echo \sprintf(
                    '<input type="hidden" name="table" value="%s"  />%s',
                    \htmlspecialchars($_REQUEST['table']),
                    \PHP_EOL
                );
                // END if multi drop
            }

            echo '<input type="hidden" name="action" value="drop" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<p><input type="checkbox" id="cascade" name="cascade" /> <label for="cascade">%s</label></p>',
                $this->lang['strcascade']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="drop" value="%s" />',
                $this->lang['strdrop']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s" />',
                $this->lang['strcancel']
            ) . \PHP_EOL;
            echo "</form>\n"; //  END confirm
        } else {
            //If multi drop
            if (\is_array($_REQUEST['table'])) {
                $msg = '';
                $status = $data->beginTransaction();

                if (0 === $status) {
                    foreach ($_REQUEST['table'] as $t) {
                        $status = $data->dropTable($t, isset($_POST['cascade']));

                        if (0 === $status) {
                            $msg .= \sprintf(
                                '%s: %s<br />',
                                \htmlentities($t, \ENT_QUOTES, 'UTF-8'),
                                $this->lang['strtabledropped']
                            );
                        } else {
                            $data->endTransaction();

                            $this->doDefault(\sprintf(
                                '%s%s: %s<br />',
                                $msg,
                                \htmlentities($t, \ENT_QUOTES, 'UTF-8'),
                                $this->lang['strtabledroppedbad']
                            ));

                            return;
                        }
                    }
                }

                if (0 === $data->endTransaction()) {
                    // Everything went fine, back to the Default page....
                    $this->view->setReloadBrowser(true);

                    $this->doDefault($msg);

                    return;
                }

                $this->doDefault($this->lang['strtabledroppedbad']);

                return;
            }
            $status = $data->dropTable($_POST['table'], isset($_POST['cascade']));

            if (0 === $status) {
                $this->view->setReloadBrowser(true);

                $this->doDefault($this->lang['strtabledropped']);

                return;
            }

            $this->doDefault($this->lang['strtabledroppedbad']);

            return;
            // END DROP
        }
    }

    /**
     * @return (\PHPPgAdmin\Decorators\FieldDecorator|mixed|string|string[])[][]
     *
     * @psalm-return array{table: array{title: mixed, field: \PHPPgAdmin\Decorators\FieldDecorator, url: string, vars: array{table: string}}, owner: array{title: mixed, field: \PHPPgAdmin\Decorators\FieldDecorator}, tablespace: array{title: mixed, field: \PHPPgAdmin\Decorators\FieldDecorator}, tuples: array{title: mixed, field: \PHPPgAdmin\Decorators\FieldDecorator, type: string}, table_size: array{title: mixed, field: \PHPPgAdmin\Decorators\FieldDecorator}, actions: array{title: mixed}, comment: array{title: mixed, field: \PHPPgAdmin\Decorators\FieldDecorator}}
     */
    private function _getColumns()
    {
        return [
            'table' => [
                'title' => $this->lang['strtable'],
                'field' => Decorator::field('relname'),
                'url' => \containerInstance()->getDestinationWithLastTab('table') . '&',
                // '/redirect/table?%s&amp;',
                //$this->misc->href
                //  ),
                'vars' => ['table' => 'relname'],
            ],
            'owner' => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('relowner'),
            ],
            'tablespace' => [
                'title' => $this->lang['strtablespace'],
                'field' => Decorator::field('tablespace'),
            ],
            'tuples' => [
                'title' => $this->lang['strestimatedrowcount'],
                'field' => Decorator::field('reltuples'),
                'type' => 'numeric',
            ],
            'table_size' => [
                'title' => $this->lang['strsize'],
                'field' => Decorator::field('table_size'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('relcomment'),
            ],
        ];
    }

    /**
     * @return ((((\PHPPgAdmin\Decorators\FieldDecorator|string)[]|string)[]|string)[]|mixed|string)[][]
     *
     * @psalm-return array{multiactions: array{keycols: array{table: string}, url: string, default: string}, browse: array{content: mixed, attr: array{href: array{url: string, urlvars: array{subject: string, return: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}, select: array{content: mixed, attr: array{href: array{url: string, urlvars: array{action: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}, insert: array{content: mixed, attr: array{href: array{url: string, urlvars: array{action: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}, empty: array{multiaction: string, content: mixed, attr: array{href: array{url: string, urlvars: array{action: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}, alter: array{content: mixed, attr: array{href: array{url: string, urlvars: array{action: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}, drop: array{multiaction: string, content: mixed, attr: array{href: array{url: string, urlvars: array{action: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}, vacuum: array{multiaction: string, content: mixed, attr: array{href: array{url: string, urlvars: array{action: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}, analyze: array{multiaction: string, content: mixed, attr: array{href: array{url: string, urlvars: array{action: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}, reindex: array{multiaction: string, content: mixed, attr: array{href: array{url: string, urlvars: array{action: string, table: \PHPPgAdmin\Decorators\FieldDecorator}}}}}
     */
    private function _getActions()
    {
        return [
            'multiactions' => [
                'keycols' => ['table' => 'relname'],
                'url' => 'tables',
                'default' => 'analyze',
            ],
            'browse' => [
                'content' => $this->lang['strbrowse'],
                'attr' => [
                    'href' => [
                        'url' => 'display',
                        'urlvars' => [
                            'subject' => 'table',
                            'return' => 'table',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'select' => [
                'content' => $this->lang['strselect'],
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'confselectrows',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'insert' => [
                'content' => $this->lang['strinsert'],
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'confinsertrow',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'empty' => [
                'multiaction' => 'confirm_empty',
                'content' => $this->lang['strempty'],
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'confirm_empty',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'tblproperties',
                        'urlvars' => [
                            'action' => 'confirm_alter',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'multiaction' => 'confirm_drop',
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'vacuum' => [
                'multiaction' => 'confirm_vacuum',
                'content' => $this->lang['strvacuum'],
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'confirm_vacuum',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'analyze' => [
                'multiaction' => 'confirm_analyze',
                'content' => $this->lang['stranalyze'],
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'confirm_analyze',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'reindex' => [
                'multiaction' => 'confirm_reindex',
                'content' => $this->lang['strreindex'],
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => [
                            'action' => 'confirm_reindex',
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            //'cluster' TODO ?
        ];
    }

    // END Function
}
