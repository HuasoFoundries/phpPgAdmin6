<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class TypesController extends BaseController
{
    public $controller_title = 'strtypes';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            case 'create_comp':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doCreateComposite();
                }

                break;
            case 'create_enum':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doCreateEnum();
                }

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

        return $this->printFooter();
    }

    /**
     * Show default list of types in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'types');
        $this->printMsg($msg);

        $types = $data->getTypes();

        $columns = [
            'type'    => [
                'title' => $this->lang['strtype'],
                'field' => Decorator::field('typname'),
                'url'   => "types?action=properties&amp;{$this->misc->href}&amp;",
                'vars'  => ['type' => 'basename'],
            ],
            'owner'   => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('typowner'),
            ],
            'flavour' => [
                'title'  => $this->lang['strflavor'],
                'field'  => Decorator::field('typtype'),
                'type'   => 'verbatim',
                'params' => [
                    'map'   => [
                        'b' => $this->lang['strbasetype'],
                        'c' => $this->lang['strcompositetype'],
                        'd' => $this->lang['strdomain'],
                        'p' => $this->lang['strpseudotype'],
                        'e' => $this->lang['strenum'],
                    ],
                    'align' => 'center',
                ],
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('typcomment'),
            ],
        ];

        if (!isset($types->fields['typtype'])) {
            unset($columns['flavour']);
        }

        $actions = [
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'types',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'type'   => Decorator::field('basename'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($types, $columns, $actions, 'types-types', $this->lang['strnotypes']);

        $navlinks = [
            'create'     => [
                'attr'    => [
                    'href' => [
                        'url'     => 'types',
                        'urlvars' => [
                            'action'   => 'create',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreatetype'],
            ],
            'createcomp' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'types',
                        'urlvars' => [
                            'action'   => 'create_comp',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreatecomptype'],
            ],
            'createenum' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'types',
                        'urlvars' => [
                            'action'   => 'create_enum',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreateenumtype'],
            ],
        ];

        if (!$data->hasEnumTypes()) {
            unset($navlinks['enum']);
        }

        $this->printNavLinks($navlinks, 'types-types', get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $types = $data->getTypes();

        $reqvars = $this->misc->getRequestVars('type');

        $attrs = [
            'text'    => Decorator::field('typname'),
            'icon'    => 'Type',
            'toolTip' => Decorator::field('typcomment'),
            'action'  => Decorator::actionurl(
                'types',
                $reqvars,
                [
                    'action' => 'properties',
                    'type'   => Decorator::field('basename'),
                ]
            ),
        ];

        return $this->printTree($types, $attrs, 'types');
    }

    /**
     * Show read only properties for a type.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();
        // Get type (using base name)
        $typedata = $data->getType($_REQUEST['type']);

        $this->printTrail('type');
        $this->printTitle($this->lang['strproperties'], 'pg.type');
        $this->printMsg($msg);

        $attPre = function (&$rowdata) use ($data) {
            $rowdata->fields['+type'] = $data->formatType($rowdata->fields['type'], $rowdata->fields['atttypmod']);
        };

        if ($typedata->recordCount() > 0) {
            $vals = false;
            switch ($typedata->fields['typtype']) {
                case 'c':
                    $attrs = $data->getTableAttributes($_REQUEST['type']);

                    $columns = [
                        'field'   => [
                            'title' => $this->lang['strfield'],
                            'field' => Decorator::field('attname'),
                        ],
                        'type'    => [
                            'title' => $this->lang['strtype'],
                            'field' => Decorator::field('+type'),
                        ],
                        'comment' => [
                            'title' => $this->lang['strcomment'],
                            'field' => Decorator::field('comment'),
                        ],
                    ];

                    $actions = [];

                    echo $this->printTable($attrs, $columns, $actions, 'types-properties', $this->lang['strnodata'], $attPre);

                    break;
                case 'e':
                    $vals = $data->getEnumValues($typedata->fields['typname']);
                // no break
                default:
                    $byval = $data->phpBool($typedata->fields['typbyval']);
                    echo '<table>'.PHP_EOL;
                    echo "<tr><th class=\"data left\">{$this->lang['strname']}</th>".PHP_EOL;
                    echo '<td class="data1">', $this->misc->printVal($typedata->fields['typname']), '</td></tr>'.PHP_EOL;
                    echo "<tr><th class=\"data left\">{$this->lang['strinputfn']}</th>".PHP_EOL;
                    echo '<td class="data1">', $this->misc->printVal($typedata->fields['typin']), '</td></tr>'.PHP_EOL;
                    echo "<tr><th class=\"data left\">{$this->lang['stroutputfn']}</th>".PHP_EOL;
                    echo '<td class="data1">', $this->misc->printVal($typedata->fields['typout']), '</td></tr>'.PHP_EOL;
                    echo "<tr><th class=\"data left\">{$this->lang['strlength']}</th>".PHP_EOL;
                    echo '<td class="data1">', $this->misc->printVal($typedata->fields['typlen']), '</td></tr>'.PHP_EOL;
                    echo "<tr><th class=\"data left\">{$this->lang['strpassbyval']}</th>".PHP_EOL;
                    echo '<td class="data1">', ($byval) ? $this->lang['stryes'] : $this->lang['strno'], '</td></tr>'.PHP_EOL;
                    echo "<tr><th class=\"data left\">{$this->lang['stralignment']}</th>".PHP_EOL;
                    echo '<td class="data1">', $this->misc->printVal($typedata->fields['typalign']), '</td></tr>'.PHP_EOL;
                    if ($data->hasEnumTypes() && $vals) {
                        $vals   = $vals->getArray();
                        $nbVals = count($vals);
                        echo "<tr>\n\t<th class=\"data left\" rowspan=\"${nbVals}\">{$this->lang['strenumvalues']}</th>".PHP_EOL;
                        echo "<td class=\"data2\">{$vals[0]['enumval']}</td></tr>".PHP_EOL;
                        for ($i = 1; $i < $nbVals; ++$i) {
                            echo '<td class="data', 2 - ($i % 2), "\">{$vals[$i]['enumval']}</td></tr>".PHP_EOL;
                        }
                    }
                    echo '</table>'.PHP_EOL;
            }

            $this->printNavLinks(['showall' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'types',
                        'urlvars' => [
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strshowalltypes'],
            ]], 'types-properties', get_defined_vars());
        } else {
            $this->doDefault($this->lang['strinvalidparam']);
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
            $this->printTrail('type');
            $this->printTitle($this->lang['strdrop'], 'pg.type.drop');

            echo '<p>', sprintf($this->lang['strconfdroptype'], $this->misc->printVal($_REQUEST['type'])), '</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/types" method="post">'.PHP_EOL;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>".PHP_EOL;
            echo '<p><input type="hidden" name="action" value="drop" />'.PHP_EOL;
            echo '<input type="hidden" name="type" value="', htmlspecialchars($_REQUEST['type']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->dropType($_POST['type'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($this->lang['strtypedropped']);
            } else {
                $this->doDefault($this->lang['strtypedroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where they can enter a new composite type.
     *
     * @param mixed $msg
     */
    public function doCreateComposite($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'stage', 1);

        $this->coalesceArr($_REQUEST, 'name', '');

        $this->coalesceArr($_REQUEST, 'fields', '');

        $this->coalesceArr($_REQUEST, 'typcomment', '');

        switch ($_REQUEST['stage']) {
            case 1:
                $this->printTrail('type');
                $this->printTitle($this->lang['strcreatecomptype'], 'pg.type.create');
                $this->printMsg($msg);

                echo '<form action="'.\SUBFOLDER.'/src/views/types" method="post">'.PHP_EOL;
                echo '<table>'.PHP_EOL;
                echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>".PHP_EOL;
                echo "\t\t<td class=\"data\"><input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                htmlspecialchars($_REQUEST['name']), "\" /></td>\n\t</tr>".PHP_EOL;
                echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strnumfields']}</th>".PHP_EOL;
                echo "\t\t<td class=\"data\"><input name=\"fields\" size=\"5\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                htmlspecialchars($_REQUEST['fields']), "\" /></td>\n\t</tr>".PHP_EOL;

                echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>".PHP_EOL;
                echo "\t\t<td><textarea name=\"typcomment\" rows=\"3\" cols=\"32\">",
                htmlspecialchars($_REQUEST['typcomment']), "</textarea></td>\n\t</tr>".PHP_EOL;

                echo '</table>'.PHP_EOL;
                echo '<p><input type="hidden" name="action" value="create_comp" />'.PHP_EOL;
                echo '<input type="hidden" name="stage" value="2" />'.PHP_EOL;
                echo $this->misc->form;
                echo "<input type=\"submit\" value=\"{$this->lang['strnext']}\" />".PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
                echo '</form>'.PHP_EOL;

                break;
            case 2:
                // Check inputs
                $fields = trim($_REQUEST['fields']);
                if ('' == trim($_REQUEST['name'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreateComposite($this->lang['strtypeneedsname']);

                    return;
                }
                if ('' == $fields || !is_numeric($fields) || $fields != (int) $fields || $fields < 1) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreateComposite($this->lang['strtypeneedscols']);

                    return;
                }

                $types = $data->getTypes(true, false, true);

                $this->printTrail('schema');
                $this->printTitle($this->lang['strcreatecomptype'], 'pg.type.create');
                $this->printMsg($msg);

                echo '<form action="'.\SUBFOLDER.'/src/views/types" method="post">'.PHP_EOL;

                // Output table header
                echo '<table>'.PHP_EOL;
                echo "\t<tr><th colspan=\"2\" class=\"data required\">{$this->lang['strfield']}</th><th colspan=\"2\" class=\"data required\">{$this->lang['strtype']}</th>";
                echo "<th class=\"data\">{$this->lang['strlength']}</th><th class=\"data\">{$this->lang['strcomment']}</th></tr>".PHP_EOL;

                for ($i = 0; $i < $_REQUEST['fields']; ++$i) {
                    if (!isset($_REQUEST['field'][$i])) {
                        $_REQUEST['field'][$i] = '';
                    }

                    if (!isset($_REQUEST['length'][$i])) {
                        $_REQUEST['length'][$i] = '';
                    }

                    if (!isset($_REQUEST['colcomment'][$i])) {
                        $_REQUEST['colcomment'][$i] = '';
                    }

                    echo "\t<tr>\n\t\t<td>", $i + 1, '.&nbsp;</td>'.PHP_EOL;
                    echo "\t\t<td><input name=\"field[{$i}]\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                    htmlspecialchars($_REQUEST['field'][$i]), '" /></td>'.PHP_EOL;
                    echo "\t\t<td>\n\t\t\t<select name=\"type[{$i}]\">".PHP_EOL;
                    $types->moveFirst();
                    while (!$types->EOF) {
                        $typname = $types->fields['typname'];
                        echo "\t\t\t\t<option value=\"", htmlspecialchars($typname), '"',
                        (isset($_REQUEST['type'][$i]) && $_REQUEST['type'][$i] == $typname) ? ' selected="selected"' : '', '>',
                        $this->misc->printVal($typname), '</option>'.PHP_EOL;
                        $types->moveNext();
                    }
                    echo "\t\t\t</select>\n\t\t</td>".PHP_EOL;

                    // Output array type selector
                    echo "\t\t<td>\n\t\t\t<select name=\"array[{$i}]\">".PHP_EOL;
                    echo "\t\t\t\t<option value=\"\"", (isset($_REQUEST['array'][$i]) && $_REQUEST['array'][$i] == '') ? ' selected="selected"' : '', '></option>'.PHP_EOL;
                    echo "\t\t\t\t<option value=\"[]\"", (isset($_REQUEST['array'][$i]) && $_REQUEST['array'][$i] == '[]') ? ' selected="selected"' : '', '>[ ]</option>'.PHP_EOL;
                    echo "\t\t\t</select>\n\t\t</td>".PHP_EOL;

                    echo "\t\t<td><input name=\"length[{$i}]\" size=\"10\" value=\"",
                    htmlspecialchars($_REQUEST['length'][$i]), '" /></td>'.PHP_EOL;
                    echo "\t\t<td><input name=\"colcomment[{$i}]\" size=\"40\" value=\"",
                    htmlspecialchars($_REQUEST['colcomment'][$i]), "\" /></td>\n\t</tr>".PHP_EOL;
                }
                echo '</table>'.PHP_EOL;
                echo '<p><input type="hidden" name="action" value="create_comp" />'.PHP_EOL;
                echo '<input type="hidden" name="stage" value="3" />'.PHP_EOL;
                echo $this->misc->form;
                echo '<input type="hidden" name="name" value="', htmlspecialchars($_REQUEST['name']), '" />'.PHP_EOL;
                echo '<input type="hidden" name="fields" value="', htmlspecialchars($_REQUEST['fields']), '" />'.PHP_EOL;
                echo '<input type="hidden" name="typcomment" value="', htmlspecialchars($_REQUEST['typcomment']), '" />'.PHP_EOL;
                echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
                echo '</form>'.PHP_EOL;

                break;
            case 3:
                // Check inputs
                $fields = trim($_REQUEST['fields']);
                if ('' == trim($_REQUEST['name'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreateComposite($this->lang['strtypeneedsname']);

                    return;
                }
                if ('' == $fields || !is_numeric($fields) || $fields != (int) $fields || $fields <= 0) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreateComposite($this->lang['strtypeneedscols']);

                    return;
                }

                $status = $data->createCompositeType(
                    $_REQUEST['name'],
                    $_REQUEST['fields'],
                    $_REQUEST['field'],
                    $_REQUEST['type'],
                    $_REQUEST['array'],
                    $_REQUEST['length'],
                    $_REQUEST['colcomment'],
                    $_REQUEST['typcomment']
                );

                if (0 == $status) {
                    $this->doDefault($this->lang['strtypecreated']);
                } elseif ($status == -1) {
                    $_REQUEST['stage'] = 2;
                    $this->doCreateComposite($this->lang['strtypeneedsfield']);

                    return;
                } else {
                    $_REQUEST['stage'] = 2;
                    $this->doCreateComposite($this->lang['strtypecreatedbad']);

                    return;
                }

                break;
            default:
                echo "<p>{$this->lang['strinvalidparam']}</p>".PHP_EOL;
        }
    }

    /**
     * Displays a screen where they can enter a new enum type.
     *
     * @param mixed $msg
     */
    public function doCreateEnum($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'stage', 1);

        $this->coalesceArr($_REQUEST, 'name', '');

        $this->coalesceArr($_REQUEST, 'values', '');

        $this->coalesceArr($_REQUEST, 'typcomment', '');

        switch ($_REQUEST['stage']) {
            case 1:
                $this->printTrail('type');
                $this->printTitle($this->lang['strcreateenumtype'], 'pg.type.create');
                $this->printMsg($msg);

                echo '<form action="'.\SUBFOLDER.'/src/views/types" method="post">'.PHP_EOL;
                echo '<table>'.PHP_EOL;
                echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>".PHP_EOL;
                echo "\t\t<td class=\"data\"><input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                htmlspecialchars($_REQUEST['name']), "\" /></td>\n\t</tr>".PHP_EOL;
                echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strnumvalues']}</th>".PHP_EOL;
                echo "\t\t<td class=\"data\"><input name=\"values\" size=\"5\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                htmlspecialchars($_REQUEST['values']), "\" /></td>\n\t</tr>".PHP_EOL;

                echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>".PHP_EOL;
                echo "\t\t<td><textarea name=\"typcomment\" rows=\"3\" cols=\"32\">",
                htmlspecialchars($_REQUEST['typcomment']), "</textarea></td>\n\t</tr>".PHP_EOL;

                echo '</table>'.PHP_EOL;
                echo '<p><input type="hidden" name="action" value="create_enum" />'.PHP_EOL;
                echo '<input type="hidden" name="stage" value="2" />'.PHP_EOL;
                echo $this->misc->form;
                echo "<input type=\"submit\" value=\"{$this->lang['strnext']}\" />".PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
                echo '</form>'.PHP_EOL;

                break;
            case 2:
                // Check inputs
                $values = trim($_REQUEST['values']);
                if ('' == trim($_REQUEST['name'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreateEnum($this->lang['strtypeneedsname']);

                    return;
                }
                if ('' == $values || !is_numeric($values) || $values != (int) $values || $values < 1) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreateEnum($this->lang['strtypeneedsvals']);

                    return;
                }

                $this->printTrail('schema');
                $this->printTitle($this->lang['strcreateenumtype'], 'pg.type.create');
                $this->printMsg($msg);

                echo '<form action="'.\SUBFOLDER.'/src/views/types" method="post">'.PHP_EOL;

                // Output table header
                echo '<table>'.PHP_EOL;
                echo "\t<tr><th colspan=\"2\" class=\"data required\">{$this->lang['strvalue']}</th></tr>".PHP_EOL;

                for ($i = 0; $i < $_REQUEST['values']; ++$i) {
                    if (!isset($_REQUEST['value'][$i])) {
                        $_REQUEST['value'][$i] = '';
                    }

                    echo "\t<tr>\n\t\t<td>", $i + 1, '.&nbsp;</td>'.PHP_EOL;
                    echo "\t\t<td><input name=\"value[{$i}]\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                    htmlspecialchars($_REQUEST['value'][$i]), "\" /></td>\n\t</tr>".PHP_EOL;
                }
                echo '</table>'.PHP_EOL;
                echo '<p><input type="hidden" name="action" value="create_enum" />'.PHP_EOL;
                echo '<input type="hidden" name="stage" value="3" />'.PHP_EOL;
                echo $this->misc->form;
                echo '<input type="hidden" name="name" value="', htmlspecialchars($_REQUEST['name']), '" />'.PHP_EOL;
                echo '<input type="hidden" name="values" value="', htmlspecialchars($_REQUEST['values']), '" />'.PHP_EOL;
                echo '<input type="hidden" name="typcomment" value="', htmlspecialchars($_REQUEST['typcomment']), '" />'.PHP_EOL;
                echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
                echo '</form>'.PHP_EOL;

                break;
            case 3:
                // Check inputs
                $values = trim($_REQUEST['values']);
                if ('' == trim($_REQUEST['name'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreateEnum($this->lang['strtypeneedsname']);

                    return;
                }
                if ('' == $values || !is_numeric($values) || $values != (int) $values || $values <= 0) {
                    $_REQUEST['stage'] = 1;
                    $this->doCreateEnum($this->lang['strtypeneedsvals']);

                    return;
                }

                $status = $data->createEnumType($_REQUEST['name'], $_REQUEST['value'], $_REQUEST['typcomment']);

                if (0 == $status) {
                    $this->doDefault($this->lang['strtypecreated']);
                } elseif ($status == -1) {
                    $_REQUEST['stage'] = 2;
                    $this->doCreateEnum($this->lang['strtypeneedsvalue']);

                    return;
                } else {
                    $_REQUEST['stage'] = 2;
                    $this->doCreateEnum($this->lang['strtypecreatedbad']);

                    return;
                }

                break;
            default:
                echo "<p>{$this->lang['strinvalidparam']}</p>".PHP_EOL;
        }
    }

    /**
     * Displays a screen where they can enter a new type.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'typname', '');

        $this->coalesceArr($_POST, 'typin', '');

        $this->coalesceArr($_POST, 'typout', '');

        $this->coalesceArr($_POST, 'typlen', '');

        $this->coalesceArr($_POST, 'typdef', '');

        $this->coalesceArr($_POST, 'typelem', '');

        $this->coalesceArr($_POST, 'typdelim', '');

        $this->coalesceArr($_POST, 'typalign', $data->typAlignDef);

        $this->coalesceArr($_POST, 'typstorage', $data->typStorageDef);

        // Retrieve all functions and types in the database
        $funcs = $data->getFunctions(true);
        $types = $data->getTypes(true);

        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreatetype'], 'pg.type.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER.'/src/views/types" method="post">'.PHP_EOL;
        echo '<table>'.PHP_EOL;
        echo "<tr><th class=\"data left required\">{$this->lang['strname']}</th>".PHP_EOL;
        echo "<td class=\"data1\"><input name=\"typname\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_POST['typname']), '" /></td></tr>'.PHP_EOL;
        echo "<tr><th class=\"data left required\">{$this->lang['strinputfn']}</th>".PHP_EOL;
        echo '<td class="data1"><select name="typin">';
        while (!$funcs->EOF) {
            $proname = htmlspecialchars($funcs->fields['proname']);
            echo "<option value=\"{$proname}\"",
            ($proname == $_POST['typin']) ? ' selected="selected"' : '', ">{$proname}</option>".PHP_EOL;
            $funcs->moveNext();
        }
        echo '</select></td></tr>'.PHP_EOL;
        echo "<tr><th class=\"data left required\">{$this->lang['stroutputfn']}</th>".PHP_EOL;
        echo '<td class="data1"><select name="typout">';
        $funcs->moveFirst();
        while (!$funcs->EOF) {
            $proname = htmlspecialchars($funcs->fields['proname']);
            echo "<option value=\"{$proname}\"",
            ($proname == $_POST['typout']) ? ' selected="selected"' : '', ">{$proname}</option>".PHP_EOL;
            $funcs->moveNext();
        }
        echo '</select></td></tr>'.PHP_EOL;
        echo '<tr><th class="data left'.(version_compare($data->major_version, '7.4', '<') ? ' required' : '')."\">{$this->lang['strlength']}</th>".PHP_EOL;
        echo '<td class="data1"><input name="typlen" size="8" value="',
        htmlspecialchars($_POST['typlen']), '" /></td></tr>';
        echo "<tr><th class=\"data left\">{$this->lang['strdefault']}</th>".PHP_EOL;
        echo '<td class="data1"><input name="typdef" size="8" value="',
        htmlspecialchars($_POST['typdef']), '" /></td></tr>';
        echo "<tr><th class=\"data left\">{$this->lang['strelement']}</th>".PHP_EOL;
        echo '<td class="data1"><select name="typelem">';
        echo '<option value=""></option>'.PHP_EOL;
        while (!$types->EOF) {
            $currname = htmlspecialchars($types->fields['typname']);
            echo "<option value=\"{$currname}\"",
            ($currname == $_POST['typelem']) ? ' selected="selected"' : '', ">{$currname}</option>".PHP_EOL;
            $types->moveNext();
        }
        echo '</select></td></tr>'.PHP_EOL;
        echo "<tr><th class=\"data left\">{$this->lang['strdelimiter']}</th>".PHP_EOL;
        echo '<td class="data1"><input name="typdelim" size="1" maxlength="1" value="',
        htmlspecialchars($_POST['typdelim']), '" /></td></tr>';
        echo "<tr><th class=\"data left\"><label for=\"typbyval\">{$this->lang['strpassbyval']}</label></th>".PHP_EOL;
        echo '<td class="data1"><input type="checkbox" id="typbyval" name="typbyval"',
        isset($_POST['typbyval']) ? ' checked="checked"' : '', ' /></td></tr>';
        echo "<tr><th class=\"data left\">{$this->lang['stralignment']}</th>".PHP_EOL;
        echo '<td class="data1"><select name="typalign">';
        foreach ($data->typAligns as $v) {
            echo "<option value=\"{$v}\"",
            ($v == $_POST['typalign']) ? ' selected="selected"' : '', ">{$v}</option>".PHP_EOL;
        }
        echo '</select></td></tr>'.PHP_EOL;
        echo "<tr><th class=\"data left\">{$this->lang['strstorage']}</th>".PHP_EOL;
        echo '<td class="data1"><select name="typstorage">';
        foreach ($data->typStorages as $v) {
            echo "<option value=\"{$v}\"",
            ($v == $_POST['typstorage']) ? ' selected="selected"' : '', ">{$v}</option>".PHP_EOL;
        }
        echo '</select></td></tr>'.PHP_EOL;
        echo '</table>'.PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create" />'.PHP_EOL;
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;
    }

    /**
     * Actually creates the new type in the database.
     */
    public function doSaveCreate()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name and a length.
        // Note: We're assuming they've given in and out functions here
        // which might be unwise...
        if ('' == $_POST['typname']) {
            $this->doCreate($this->lang['strtypeneedsname']);
        } elseif ('' == $_POST['typlen']) {
            $this->doCreate($this->lang['strtypeneedslen']);
        } else {
            $status = $data->createType(
                $_POST['typname'],
                $_POST['typin'],
                $_POST['typout'],
                $_POST['typlen'],
                $_POST['typdef'],
                $_POST['typelem'],
                $_POST['typdelim'],
                isset($_POST['typbyval']),
                $_POST['typalign'],
                $_POST['typstorage']
            );
            if (0 == $status) {
                $this->doDefault($this->lang['strtypecreated']);
            } else {
                $this->doCreate($this->lang['strtypecreatedbad']);
            }
        }
    }
}
