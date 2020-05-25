<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class ColpropertiesController extends BaseController
{
    public $tableName = '';

    public $table_place = 'colproperties-colproperties';

    public $controller_title = 'strtables';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render(): void
    {
        if (isset($_REQUEST['table']) && !empty($_REQUEST['table'])) {
            $this->tableName = &$_REQUEST['table'];
        } elseif (isset($_REQUEST['view'])) {
            $this->tableName = &$_REQUEST['view'];
        } elseif (isset($_REQUEST['matview'])) {
            $this->tableName = &$_REQUEST['matview'];
        } else {
            $this->halt($this->lang['strnotableprovided']);
        }

        $this->printHeader($this->headerTitle('', '', $this->tableName), null, true, 'header_select2.twig');
        $this->printBody();

        if (isset($_REQUEST['view']) || isset($_REQUEST['matview'])) {
            $this->doDefault(null, false);
        } else {
            switch ($this->action) {
                case 'properties':
                    if (isset($_POST['cancel'])) {
                        $this->doDefault();
                    } else {
                        $this->doAlter();
                    }

                    break;

                default:
                    $this->doDefault();

                    break;
            }
        }

        $this->printFooter();
    }

    /**
     * Show default list of columns in the table.
     *
     * @param string $msg     message to display
     * @param bool   $isTable tells if we're showing table properties
     */
    public function doDefault($msg = '', $isTable = true): void
    {
        if (!isset($_REQUEST['table']) || empty($_REQUEST['table'])) {
            $isTable = false;
        }
        $data = $this->misc->getDatabaseAccessor();

        $attPre = static function (&$rowdata) use ($data): void {
            $rowdata->fields['+type'] = $data->formatType($rowdata->fields['type'], $rowdata->fields['atttypmod']);
        };

        if (empty($_REQUEST['column'])) {
            $msg .= "<br/>{$this->lang['strnoobjects']}";
        }

        $this->printTrail('column');
        //$this->printTitle($this->lang['strcolprop']);
        $this->printTabs('column', 'properties');
        $this->printMsg($msg);

        if (!empty($_REQUEST['column'])) {
            // Get table
            $tdata = $data->getTable($this->tableName);
            //\Kint::dump($tdata);
            // Get columns
            $attrs = $data->getTableAttributes($this->tableName, $_REQUEST['column']);

            // Show comment if any
            if (null !== $attrs->fields['comment']) {
                echo '<p class="comment">', $this->misc->printVal($attrs->fields['comment']), '</p>' . \PHP_EOL;
            }

            $column = [
                'column' => [
                    'title' => $this->lang['strcolumn'],
                    'field' => Decorator::field('attname'),
                ],
                'type' => [
                    'title' => $this->lang['strtype'],
                    'field' => Decorator::field('+type'),
                ],
            ];

            if ($isTable) {
                $column['notnull'] = [
                    'title' => $this->lang['strnotnull'],
                    'field' => Decorator::field('attnotnull'),
                    'type' => 'bool',
                    'params' => ['true' => 'NOT NULL', 'false' => ''],
                ];
                $column['default'] = [
                    'title' => $this->lang['strdefault'],
                    'field' => Decorator::field('adsrc'),
                ];
            }

            $actions = [];
            echo $this->printTable($attrs, $column, $actions, $this->table_place, $this->lang['strnodata'], $attPre);

            echo '<br />' . \PHP_EOL;

            $f_attname = $_REQUEST['column'];
            $f_table = $this->tableName;
            $f_schema = $data->_schema;
            $data->fieldClean($f_attname);
            $data->fieldClean($f_table);
            $data->fieldClean($f_schema);

            if ($isTable) {
                $navlinks = [
                    'browse' => [
                        'attr' => [
                            'href' => [
                                'url' => 'display',
                                'method' => 'post',
                                'urlvars' => [
                                    'subject' => 'column',
                                    'server' => $_REQUEST['server'],
                                    'database' => $_REQUEST['database'],
                                    'schema' => $_REQUEST['schema'],
                                    'table' => $this->tableName,
                                    'column' => $_REQUEST['column'],
                                    'return' => 'column',
                                    'f_attname' => $f_attname,
                                    'f_table' => $f_table,
                                    'f_schema' => $f_schema,
                                ],
                            ],
                        ],
                        'content' => $this->lang['strbrowse'],
                    ],
                    'alter' => [
                        'attr' => [
                            'href' => [
                                'url' => 'colproperties',
                                'urlvars' => [
                                    'action' => 'properties',
                                    'server' => $_REQUEST['server'],
                                    'database' => $_REQUEST['database'],
                                    'schema' => $_REQUEST['schema'],
                                    'table' => $this->tableName,
                                    'column' => $_REQUEST['column'],
                                ],
                            ],
                        ],
                        'content' => $this->lang['stralter'],
                    ],
                    'drop' => [
                        'attr' => [
                            'href' => [
                                'url' => 'tblproperties',
                                'urlvars' => [
                                    'action' => 'confirm_drop',
                                    'server' => $_REQUEST['server'],
                                    'database' => $_REQUEST['database'],
                                    'schema' => $_REQUEST['schema'],
                                    'table' => $this->tableName,
                                    'column' => $_REQUEST['column'],
                                ],
                            ],
                        ],
                        'content' => $this->lang['strdrop'],
                    ],
                ];
            } else {
                // Browse link
                $navlinks = [
                    'browse' => [
                        'attr' => [
                            'href' => [
                                'url' => 'display',
                                'method' => 'post',
                                'urlvars' => [
                                    'subject' => 'column',
                                    'server' => $_REQUEST['server'],
                                    'database' => $_REQUEST['database'],
                                    'schema' => $_REQUEST['schema'],
                                    'view' => $this->tableName,
                                    'column' => $_REQUEST['column'],
                                    'return' => 'column',
                                    'f_attname' => $f_attname,
                                    'f_table' => $f_table,
                                    'f_schema' => $f_schema,
                                ],
                            ],
                        ],
                        'content' => $this->lang['strbrowse'],
                    ],
                ];
            }

            $this->printNavLinks($navlinks, $this->table_place, \get_defined_vars());
        }
    }

    /**
     * Displays a screen where they can alter a column.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'stage', 1);

        switch ($_REQUEST['stage']) {
            case 1:
                $this->printTrail('column');
                $this->printTitle($this->lang['stralter'], 'pg.column.alter');
                $this->printMsg($msg);

                echo '<script src="' . self::SUBFOLDER . '/assets/js/tables.js" type="text/javascript"></script>';
                echo '<form action="' . self::SUBFOLDER . '/src/views/colproperties" method="post">' . \PHP_EOL;

                // Output table header
                echo '<table>' . \PHP_EOL;
                echo "<tr><th class=\"data required\">{$this->lang['strname']}</th>" . \PHP_EOL;

                if ($data->hasAlterColumnType()) {
                    echo "<th class=\"data required\" colspan=\"2\">{$this->lang['strtype']}</th>" . \PHP_EOL;
                    echo "<th class=\"data\">{$this->lang['strlength']}</th>" . \PHP_EOL;
                } else {
                    echo "<th class=\"data required\">{$this->lang['strtype']}</th>" . \PHP_EOL;
                }
                echo "<th class=\"data\">{$this->lang['strnotnull']}</th>\n<th class=\"data\">{$this->lang['strdefault']}</th>\n<th class=\"data\">{$this->lang['strcomment']}</th></tr>" . \PHP_EOL;

                $column = $data->getTableAttributes($_REQUEST['table'], $_REQUEST['column']);
                $column->fields['attnotnull'] = $data->phpBool($column->fields['attnotnull']);

                // Upon first drawing the screen, load the existing column information
                // from the database.
                if (!isset($_REQUEST['default'])) {
                    $_REQUEST['field'] = $column->fields['attname'];
                    $_REQUEST['type'] = $column->fields['base_type'];
                    // Check to see if its' an array type...
                    // @todo this is pretty hacky!
                    if ('[]' === \mb_substr($column->fields['base_type'], \mb_strlen($column->fields['base_type']) - 2)) {
                        $_REQUEST['type'] = \mb_substr($column->fields['base_type'], 0, \mb_strlen($column->fields['base_type']) - 2);
                        $_REQUEST['array'] = '[]';
                    } else {
                        $_REQUEST['type'] = $column->fields['base_type'];
                        $_REQUEST['array'] = '';
                    }
                    // To figure out the length, look in the brackets :(
                    //  @todo this is pretty hacky
                    if ($column->fields['type'] !== $column->fields['base_type'] && \preg_match('/\\(([0-9, ]*)\\)/', $column->fields['type'], $bits)) {
                        $_REQUEST['length'] = $bits[1];
                    } else {
                        $_REQUEST['length'] = '';
                    }

                    $_REQUEST['default'] = $_REQUEST['olddefault'] = $column->fields['adsrc'];

                    if ($column->fields['attnotnull']) {
                        $_REQUEST['notnull'] = 'YES';
                    }

                    $_REQUEST['comment'] = $column->fields['comment'];
                }

                // Column name
                echo "<tr><td><input name=\"field\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                \htmlspecialchars($_REQUEST['field']), '" /></td>' . \PHP_EOL;

                // Column type
                $escaped_predef_types = []; // the JS escaped array elements
                if ($data->hasAlterColumnType()) {
                    // Fetch all available types
                    $types = $data->getTypes(true, false, true);
                    $types_for_js = [];

                    echo "<td><select name=\"type\" id=\"type\" class=\"select2\" onchange=\"checkLengths(document.getElementById('type').value,'');\">" . \PHP_EOL;

                    while (!$types->EOF) {
                        $typname = $types->fields['typname'];
                        $types_for_js[] = $typname;
                        echo "\t<option value=\"", \htmlspecialchars($typname), '"', ($typname === $_REQUEST['type']) ? ' selected="selected"' : '', '>',
                        $this->misc->printVal($typname), '</option>' . \PHP_EOL;
                        $types->moveNext();
                    }
                    echo '</select>' . \PHP_EOL;
                    echo '</td>' . \PHP_EOL;

                    // Output array type selector
                    echo '<td><select name="array">' . \PHP_EOL;
                    echo "\t<option value=\"\"", ('' === $_REQUEST['array']) ? ' selected="selected"' : '', '></option>' . \PHP_EOL;
                    echo "\t<option value=\"[]\"", ('[]' === $_REQUEST['array']) ? ' selected="selected"' : '', '>[ ]</option>' . \PHP_EOL;
                    echo '</select></td>' . \PHP_EOL;
                    $predefined_size_types = \array_intersect($data->predefined_size_types, $types_for_js);

                    foreach ($predefined_size_types as $value) {
                        $escaped_predef_types[] = "'{$value}'";
                    }

                    echo '<td><input name="length" id="lengths" size="8" value="',
                    \htmlspecialchars($_REQUEST['length']), '" /></td>' . \PHP_EOL;
                } else {
                    // Otherwise draw the read-only type name
                    echo '<td>', $this->misc->printVal($data->formatType($column->fields['type'], $column->fields['atttypmod'])), '</td>' . \PHP_EOL;
                }

                echo '<td><input type="checkbox" name="notnull"', (isset($_REQUEST['notnull'])) ? ' checked="checked"' : '', ' /></td>' . \PHP_EOL;
                echo '<td><input name="default" size="20" value="',
                \htmlspecialchars($_REQUEST['default']), '" /></td>' . \PHP_EOL;
                echo '<td><input name="comment" size="40" value="',
                \htmlspecialchars($_REQUEST['comment']), '" /></td></tr>' . \PHP_EOL;
                echo '</table>' . \PHP_EOL;
                echo '<p><input type="hidden" name="action" value="properties" />' . \PHP_EOL;
                echo '<input type="hidden" name="stage" value="2" />' . \PHP_EOL;
                echo $this->misc->form;
                echo '<input type="hidden" name="table" value="', \htmlspecialchars($_REQUEST['table']), '" />' . \PHP_EOL;
                echo '<input type="hidden" name="column" value="', \htmlspecialchars($_REQUEST['column']), '" />' . \PHP_EOL;
                echo '<input type="hidden" name="olddefault" value="', \htmlspecialchars($_REQUEST['olddefault']), '" />' . \PHP_EOL;

                if ($column->fields['attnotnull']) {
                    echo '<input type="hidden" name="oldnotnull" value="on" />' . \PHP_EOL;
                }

                echo '<input type="hidden" name="oldtype" value="', \htmlspecialchars($data->formatType($column->fields['type'], $column->fields['atttypmod'])), '" />' . \PHP_EOL;
                // Add hidden variables to suppress error notices if we don't support altering column type
                if (!$data->hasAlterColumnType()) {
                    echo '<input type="hidden" name="type" value="', \htmlspecialchars($_REQUEST['type']), '" />' . \PHP_EOL;
                    echo '<input type="hidden" name="length" value="', \htmlspecialchars($_REQUEST['length']), '" />' . \PHP_EOL;
                    echo '<input type="hidden" name="array" value="', \htmlspecialchars($_REQUEST['array']), '" />' . \PHP_EOL;
                }
                echo "<input type=\"submit\" value=\"{$this->lang['stralter']}\" />" . \PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>" . \PHP_EOL;
                echo '</form>' . \PHP_EOL;
                echo '<script type="text/javascript">predefined_lengths = new Array(' . \implode(',', $escaped_predef_types) . ");checkLengths(document.getElementById('type').value,'');</script>" . \PHP_EOL;

                break;
            case 2:
                // Check inputs
                if ('' === \trim($_REQUEST['field'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doAlter($this->lang['strcolneedsname']);

                    return;
                }
                $this->coalesceArr($_REQUEST, 'length', '');

                [$status, $sql] = $data->alterColumn(
                    $_REQUEST['table'],
                    $_REQUEST['column'],
                    $_REQUEST['field'],
                    isset($_REQUEST['notnull']),
                    isset($_REQUEST['oldnotnull']),
                    $_REQUEST['default'],
                    $_REQUEST['olddefault'],
                    $_REQUEST['type'],
                    $_REQUEST['length'],
                    $_REQUEST['array'],
                    $_REQUEST['oldtype'],
                    $_REQUEST['comment']
                );

                if (0 === $status) {
                    if ($_REQUEST['column'] !== $_REQUEST['field']) {
                        $_REQUEST['column'] = $_REQUEST['field'];
                        $this->misc->setReloadBrowser(true);
                    }
                    $this->doDefault($sql . "<br/>{$this->lang['strcolumnaltered']}");
                } else {
                    $_REQUEST['stage'] = 1;
                    $this->doAlter($sql . "<br/>{$this->lang['strcolumnalteredbad']}");

                    return;
                }

                break;

            default:
                echo "<p>{$this->lang['strinvalidparam']}</p>" . \PHP_EOL;
        }
    }
}
