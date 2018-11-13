<?php

/**
 * PHPPgAdmin v6.0.0-beta.50
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class TblpropertiesController extends BaseController
{
    use \PHPPgAdmin\Traits\ExportTrait;
    public $controller_title = 'strtables';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $header_template = 'header.twig';

        ob_start();
        switch ($this->action) {
            case 'alter':
                if (isset($_POST['alter'])) {
                    $this->doSaveAlter();
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_alter':
                $this->doAlter();

                break;
            case 'import':
                $this->doImport();

                break;
            case 'export':
                $this->doExport();

                break;
            case 'add_column':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $header_template = 'header_select2.twig';
                    $this->doAddColumn();
                }

                break;
                /*case 'properties':
                if (isset($_POST['cancel'])) {
                $this->doDefault();
                } else {
                $this->doProperties();
                }*/

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
        }

        $output = ob_get_clean();

        $this->printHeader($this->headerTitle('', '', $_REQUEST['table']), null, true, $header_template);
        $this->printBody();

        echo $output;

        return $this->printFooter();
    }

    /**
     * Show default list of columns in the table.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $misc = $this->misc;
        $data = $misc->getDatabaseAccessor();

        $attPre = function (&$rowdata, $actions) use ($data) {
            $rowdata->fields['+type'] = $data->formatType($rowdata->fields['type'], $rowdata->fields['atttypmod']);
            $attname                  = $rowdata->fields['attname'];
            $table                    = $_REQUEST['table'];
            $data->fieldClean($attname);
            $data->fieldClean($table);

            $actions['browse']['attr']['href']['urlvars']['query'] = "SELECT \"{$attname}\", count(*) AS \"count\"
                FROM \"{$table}\" GROUP BY \"{$attname}\" ORDER BY \"{$attname}\"";

            return $actions;
        };

        $cstrRender = function ($s, $p) use ($misc, $data) {
            $str = '';
            foreach ($p['keys'] as $k => $c) {
                if (is_null($p['keys'][$k]['consrc'])) {
                    $atts        = $data->getAttributeNames($_REQUEST['table'], explode(' ', $p['keys'][$k]['indkey']));
                    $c['consrc'] = ('u' == $c['contype'] ? 'UNIQUE (' : 'PRIMARY KEY (').join(',', $atts).')';
                }

                if ($c['p_field'] == $s) {
                    switch ($c['contype']) {
                        case 'p':
                            $str .= '<a href="constraints?'.$misc->href.'&amp;table='.urlencode($c['p_table']).'&amp;schema='.urlencode($c['p_schema']).'"><img src="'.
                            $misc->icon('PrimaryKey').'" alt="[pk]" title="'.htmlentities($c['consrc'], ENT_QUOTES, 'UTF-8').'" /></a>';

                            break;
                        case 'f':
                            $str .= '<a href="tblproperties?'.$misc->href.'&amp;table='.urlencode($c['f_table']).'&amp;schema='.urlencode($c['f_schema']).'"><img src="'.
                            $misc->icon('ForeignKey').'" alt="[fk]" title="'.htmlentities($c['consrc'], ENT_QUOTES, 'UTF-8').'" /></a>';

                            break;
                        case 'u':
                            $str .= '<a href="constraints?'.$misc->href.'&amp;table='.urlencode($c['p_table']).'&amp;schema='.urlencode($c['p_schema']).'"><img src="'.
                            $misc->icon('UniqueConstraint').'" alt="[uniq]" title="'.htmlentities($c['consrc'], ENT_QUOTES, 'UTF-8').'" /></a>';

                            break;
                        case 'c':
                            $str .= '<a href="constraints?'.$misc->href.'&amp;table='.urlencode($c['p_table']).'&amp;schema='.urlencode($c['p_schema']).'"><img src="'.
                            $misc->icon('CheckConstraint').'" alt="[check]" title="'.htmlentities($c['consrc'], ENT_QUOTES, 'UTF-8').'" /></a>';
                    }
                }
            }

            return $str;
        };

        $this->printTrail('table');
        $this->printTabs('table', 'columns');
        $this->printMsg($msg);

        // Get table
        $tdata = $data->getTable($_REQUEST['table']);
        // Get columns
        $attrs = $data->getTableAttributes($_REQUEST['table']);
        // Get constraints keys
        $ck = $data->getConstraintsWithFields($_REQUEST['table']);

        // Show comment if any
        if (null !== $tdata->fields['relcomment']) {
            echo '<p class="comment">', $misc->printVal($tdata->fields['relcomment']), '</p>'.PHP_EOL;
        }

        $columns = [
            'column'  => [
                'title' => $this->lang['strcolumn'],
                'field' => Decorator::field('attname'),
                'url'   => "colproperties?subject=column&amp;{$misc->href}&amp;table=".urlencode($_REQUEST['table']).'&amp;',
                'vars'  => ['column' => 'attname'],
            ],
            'type'    => [
                'title' => $this->lang['strtype'],
                'field' => Decorator::field('+type'),
            ],
            'notnull' => [
                'title'  => $this->lang['strnotnull'],
                'field'  => Decorator::field('attnotnull'),
                'type'   => 'bool',
                'params' => ['true' => 'NOT NULL', 'false' => ''],
            ],
            'default' => [
                'title' => $this->lang['strdefault'],
                'field' => Decorator::field('adsrc'),
            ],
            'keyprop' => [
                'title'  => $this->lang['strconstraints'],
                'class'  => 'constraint_cell',
                'field'  => Decorator::field('attname'),
                'type'   => 'callback',
                'params' => [
                    'function' => $cstrRender,
                    'keys'     => $ck->getArray(),
                ],
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('comment'),
            ],
        ];

        $actions = [
            'browse'     => [
                'content' => $this->lang['strbrowse'],
                'attr'    => [
                    'href' => [
                        'url'     => 'display',
                        'urlvars' => [
                            'table'   => $_REQUEST['table'],
                            'subject' => 'column',
                            'return'  => 'table',
                            'column'  => Decorator::field('attname'),
                        ],
                    ],
                ],
            ],
            'alter'      => [
                'content' => $this->lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'colproperties',
                        'urlvars' => [
                            'subject' => 'column',
                            'action'  => 'properties',
                            'table'   => $_REQUEST['table'],
                            'column'  => Decorator::field('attname'),
                        ],
                    ],
                ],
            ],
            'privileges' => [
                'content' => $this->lang['strprivileges'],
                'attr'    => [
                    'href' => [
                        'url'     => 'privileges',
                        'urlvars' => [
                            'subject' => 'column',
                            'table'   => $_REQUEST['table'],
                            'column'  => Decorator::field('attname'),
                        ],
                    ],
                ],
            ],
            'drop'       => [
                'content' => $this->lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'tblproperties',
                        'urlvars' => [
                            'subject' => 'column',
                            'action'  => 'confirm_drop',
                            'table'   => $_REQUEST['table'],
                            'column'  => Decorator::field('attname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($attrs, $columns, $actions, 'tblproperties-tblproperties', $this->lang['strnodata'], $attPre);

        $navlinks = [
            'browse'    => [
                'attr'    => [
                    'href' => [
                        'url'     => 'display',
                        'urlvars' => [
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'table'    => $_REQUEST['table'],
                            'subject'  => 'table',
                            'return'   => 'table',
                        ],
                    ],
                ],
                'content' => $this->lang['strbrowse'],
            ],
            'select'    => [
                'attr'    => [
                    'href' => [
                        'url'     => 'tables',
                        'urlvars' => [
                            'action'   => 'confselectrows',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'table'    => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['strselect'],
            ],
            'insert'    => [
                'attr'    => [
                    'href' => [
                        'url'     => 'tables',
                        'urlvars' => [
                            'action'   => 'confinsertrow',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'table'    => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['strinsert'],
            ],
            'empty'     => [
                'attr'    => [
                    'href' => [
                        'url'     => 'tables',
                        'urlvars' => [
                            'action'   => 'confirm_empty',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'table'    => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['strempty'],
            ],
            'drop'      => [
                'attr'    => [
                    'href' => [
                        'url'     => 'tables',
                        'urlvars' => [
                            'action'   => 'confirm_drop',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'table'    => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['strdrop'],
            ],
            'addcolumn' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'tblproperties',
                        'urlvars' => [
                            'action'   => 'add_column',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'table'    => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['straddcolumn'],
            ],
            'alter'     => [
                'attr'    => [
                    'href' => [
                        'url'     => 'tblproperties',
                        'urlvars' => [
                            'action'   => 'confirm_alter',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'table'    => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['stralter'],
            ],
        ];
        $this->printNavLinks($navlinks, 'tblproperties-tblproperties', get_defined_vars());
    }

    public function doTree()
    {
        $misc = $this->misc;
        $data = $misc->getDatabaseAccessor();

        $columns = $data->getTableAttributes($_REQUEST['table']);
        $reqvars = $misc->getRequestVars('column');

        $attrs = [
            'text'       => Decorator::field('attname'),
            'action'     => Decorator::actionurl(
                'colproperties',
                $reqvars,
                [
                    'table'  => $_REQUEST['table'],
                    'column' => Decorator::field('attname'),
                ]
            ),
            'icon'       => 'Column',
            'iconAction' => Decorator::url(
                'display',
                $reqvars,
                [
                    'table'  => $_REQUEST['table'],
                    'column' => Decorator::field('attname'),
                    'query'  => Decorator::replace(
                        'SELECT "%column%", count(*) AS "count" FROM "%table%" GROUP BY "%column%" ORDER BY "%column%"',
                        [
                            '%column%' => Decorator::field('attname'),
                            '%table%'  => $_REQUEST['table'],
                        ]
                    ),
                ]
            ),
            'toolTip'    => Decorator::field('comment'),
        ];

        return $this->printTree($columns, $attrs, 'tblcolumns');
    }

    public function doSaveAlter()
    {
        $misc = $this->misc;
        $data = $misc->getDatabaseAccessor();

        // For databases that don't allow owner change
        $this->coalesceArr($_POST, 'owner', '');

        // Default tablespace to null if it isn't set
        $this->coalesceArr($_POST, 'tablespace', null);

        $this->coalesceArr($_POST, 'newschema', null);

        $status = $data->alterTable($_POST['table'], $_POST['name'], $_POST['owner'], $_POST['newschema'], $_POST['comment'], $_POST['tablespace']);
        if (0 == $status) {
            // If table has been renamed, need to change to the new name and
            // reload the browser frame.
            if ($_POST['table'] != $_POST['name']) {
                // Jump them to the new table name
                $_REQUEST['table'] = $_POST['name'];
                // Force a browser reload
                $misc->setReloadBrowser(true);
            }
            // If schema has changed, need to change to the new schema and reload the browser
            if (!empty($_POST['newschema']) && ($_POST['newschema'] != $data->_schema)) {
                // Jump them to the new sequence schema
                $misc->setCurrentSchema($_POST['newschema']);
                $misc->setReloadBrowser(true);
            }
            $this->doDefault($this->lang['strtablealtered']);
        } else {
            $this->doAlter($this->lang['strtablealteredbad']);
        }
    }

    /**
     * Function to allow altering of a table.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $misc = $this->misc;
        $data = $misc->getDatabaseAccessor();

        $this->printTrail('table');
        $this->printTitle($this->lang['stralter'], 'pg.table.alter');
        $this->printMsg($msg);

        // Fetch table info
        $table = $data->getTable($_REQUEST['table']);
        // Fetch all users
        $users       = $data->getUsers();
        $tablespaces = null;
        // Fetch all tablespaces from the database
        if ($data->hasTablespaces()) {
            $tablespaces = $data->getTablespaces(true);
        }

        if ($table->recordCount() > 0) {
            $this->coalesceArr($_POST, 'name', $table->fields['relname']);

            $this->coalesceArr($_POST, 'owner', $table->fields['relowner']);

            $this->coalesceArr($_POST, 'newschema', $table->fields['nspname']);

            $this->coalesceArr($_POST, 'comment', $table->fields['relcomment']);

            if ($data->hasTablespaces() && !isset($_POST['tablespace'])) {
                $_POST['tablespace'] = $table->fields['tablespace'];
            }

            echo '<form action="'.\SUBFOLDER.'/src/views/tblproperties" method="post">'.PHP_EOL;
            echo '<table>'.PHP_EOL;
            echo "<tr><th class=\"data left required\">{$this->lang['strname']}</th>".PHP_EOL;
            echo '<td class="data1">';
            echo "<input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['name'], ENT_QUOTES), '" /></td></tr>'.PHP_EOL;

            if ($data->isSuperUser()) {
                echo "<tr><th class=\"data left required\">{$this->lang['strowner']}</th>".PHP_EOL;
                echo '<td class="data1"><select name="owner">';
                while (!$users->EOF) {
                    $uname = $users->fields['usename'];
                    echo '<option value="', htmlspecialchars($uname), '"',
                    ($uname == $_POST['owner']) ? ' selected="selected"' : '', '>', htmlspecialchars($uname), '</option>'.PHP_EOL;
                    $users->moveNext();
                }
                echo '</select></td></tr>'.PHP_EOL;
            }

            if ($data->hasAlterTableSchema()) {
                $schemas = $data->getSchemas();
                echo "<tr><th class=\"data left required\">{$this->lang['strschema']}</th>".PHP_EOL;
                echo '<td class="data1"><select name="newschema">';
                while (!$schemas->EOF) {
                    $schema = $schemas->fields['nspname'];
                    echo '<option value="', htmlspecialchars($schema), '"',
                    ($schema == $_POST['newschema']) ? ' selected="selected"' : '', '>', htmlspecialchars($schema), '</option>'.PHP_EOL;
                    $schemas->moveNext();
                }
                echo '</select></td></tr>'.PHP_EOL;
            }

            // Tablespace (if there are any)
            if ($data->hasTablespaces() && $tablespaces->recordCount() > 0) {
                echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strtablespace']}</th>".PHP_EOL;
                echo "\t\t<td class=\"data1\">\n\t\t\t<select name=\"tablespace\">".PHP_EOL;
                // Always offer the default (empty) option
                echo "\t\t\t\t<option value=\"\"",
                ('' == $_POST['tablespace']) ? ' selected="selected"' : '', '></option>'.PHP_EOL;
                // Display all other tablespaces
                while (!$tablespaces->EOF) {
                    $spcname = htmlspecialchars($tablespaces->fields['spcname']);
                    echo "\t\t\t\t<option value=\"{$spcname}\"",
                    ($spcname == $_POST['tablespace']) ? ' selected="selected"' : '', ">{$spcname}</option>".PHP_EOL;
                    $tablespaces->moveNext();
                }
                echo "\t\t\t</select>\n\t\t</td>\n\t</tr>".PHP_EOL;
            }

            echo "<tr><th class=\"data left\">{$this->lang['strcomment']}</th>".PHP_EOL;
            echo '<td class="data1">';
            echo '<textarea rows="3" cols="32" name="comment">',
            htmlspecialchars($_POST['comment']), '</textarea></td></tr>'.PHP_EOL;
            echo '</table>'.PHP_EOL;
            echo '<p><input type="hidden" name="action" value="alter" />'.PHP_EOL;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
            echo $misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            echo "<p>{$this->lang['strnodata']}</p>".PHP_EOL;
        }
    }

    public function doExport($msg = '')
    {
        $data    = $this->misc->getDatabaseAccessor();
        $subject = 'table';
        $object  = $_REQUEST['table'];
        // Determine whether or not the table has an object ID
        $hasID = $data->hasObjectID($object);
        $this->prtrace('$hasID', $hasID);
        $this->printTrail('table');
        $this->printTabs('table', 'export');
        $this->printMsg($msg);

        echo $this->formHeader();

        // Data only
        echo $this->dataOnly($hasID);

        // Structure only
        echo $this->structureOnly();
        // Structure and data
        echo $this->structureAndData($hasID);

        echo $this->displayOrDownload();

        echo $this->formFooter($subject, $object);
    }

    public function doImport($msg = '')
    {
        $misc = $this->misc;

        $this->printTrail('table');
        $this->printTabs('table', 'import');
        $this->printMsg($msg);

        // Check that file uploads are enabled
        if (!ini_get('file_uploads')) {
            echo "<p>{$this->lang['strnouploads']}</p>".PHP_EOL;

            return;
        }
        // Don't show upload option if max size of uploads is zero
        $max_size = $misc->inisizeToBytes(ini_get('upload_max_filesize'));
        if (is_double($max_size) && $max_size > 0) {
            echo '<form action="'.\SUBFOLDER.'/src/views/dataimport" method="post" enctype="multipart/form-data">'.PHP_EOL;
            echo '<table>'.PHP_EOL;
            echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strformat']}</th>".PHP_EOL;
            echo "\t\t<td><select name=\"format\">".PHP_EOL;
            echo "\t\t\t<option value=\"auto\">{$this->lang['strauto']}</option>".PHP_EOL;
            echo "\t\t\t<option value=\"csv\">CSV</option>".PHP_EOL;
            echo "\t\t\t<option value=\"tab\">{$this->lang['strtabbed']}</option>".PHP_EOL;
            if (function_exists('xml_parser_create')) {
                echo "\t\t\t<option value=\"xml\">XML</option>".PHP_EOL;
            }
            echo "\t\t</select></td>\n\t</tr>".PHP_EOL;
            echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strallowednulls']}</th>".PHP_EOL;
            echo "\t\t<td><label><input type=\"checkbox\" name=\"allowednulls[0]\" value=\"\\N\" checked=\"checked\" />{$this->lang['strbackslashn']}</label><br />".PHP_EOL;
            echo "\t\t<label><input type=\"checkbox\" name=\"allowednulls[1]\" value=\"NULL\" />NULL</label><br />".PHP_EOL;
            echo "\t\t<label><input type=\"checkbox\" name=\"allowednulls[2]\" value=\"\" />{$this->lang['stremptystring']}</label></td>\n\t</tr>".PHP_EOL;
            echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strfile']}</th>".PHP_EOL;
            echo "\t\t<td><input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"{$max_size}\" />";
            echo "<input type=\"file\" name=\"source\" /></td>\n\t</tr>".PHP_EOL;
            echo '</table>'.PHP_EOL;
            echo '<p><input type="hidden" name="action" value="import" />'.PHP_EOL;
            echo $misc->form;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
            echo "<input type=\"submit\" value=\"{$this->lang['strimport']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        }
    }

    /**
     * Displays a screen where they can add a column.
     *
     * @param mixed $msg
     */
    public function doAddColumn($msg = '')
    {
        $misc = $this->misc;
        $data = $misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'stage', 1);

        switch ($_REQUEST['stage']) {
            case 1:
                // Set variable defaults
                $this->coalesceArr($_POST, 'field', '');

                $this->coalesceArr($_POST, 'type', '');

                $this->coalesceArr($_POST, 'array', '');

                $this->coalesceArr($_POST, 'length', '');

                $this->coalesceArr($_POST, 'default', '');

                $this->coalesceArr($_POST, 'comment', '');

                // Fetch all available types
                $types        = $data->getTypes(true, false, true);
                $types_for_js = [];

                $this->printTrail('table');
                $this->printTitle($this->lang['straddcolumn'], 'pg.column.add');
                $this->printMsg($msg);

                echo '<script src="'.\SUBFOLDER.'/assets/js/tables.js" type="text/javascript"></script>';
                echo '<form action="'.\SUBFOLDER.'/src/views/tblproperties" method="post">'.PHP_EOL;

                // Output table header
                echo '<table>'.PHP_EOL;
                echo "<tr><th class=\"data required\">{$this->lang['strname']}</th>\n<th colspan=\"2\" class=\"data required\">{$this->lang['strtype']}</th>".PHP_EOL;
                echo "<th class=\"data\">{$this->lang['strlength']}</th>".PHP_EOL;
                if ($data->hasCreateFieldWithConstraints()) {
                    echo "<th class=\"data\">{$this->lang['strnotnull']}</th>\n<th class=\"data\">{$this->lang['strdefault']}</th>".PHP_EOL;
                }

                echo "<th class=\"data\">{$this->lang['strcomment']}</th></tr>".PHP_EOL;

                echo "<tr><td><input name=\"field\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                htmlspecialchars($_POST['field']), '" /></td>'.PHP_EOL;
                echo "<td><select  class=\"select2\" name=\"type\" id=\"type\" onchange=\"checkLengths(document.getElementById('type').value,'');\">".PHP_EOL;
                // Output any "magic" types.  This came in with the alter column type so we'll check that
                if ($data->hasMagicTypes()) {
                    foreach ($data->extraTypes as $v) {
                        $types_for_js[] = strtolower($v);
                        echo "\t<option value=\"", htmlspecialchars($v), '"',
                        ($v == $_POST['type']) ? ' selected="selected"' : '', '>',
                        $misc->printVal($v), '</option>'.PHP_EOL;
                    }
                }
                while (!$types->EOF) {
                    $typname        = $types->fields['typname'];
                    $types_for_js[] = $typname;
                    echo "\t<option value=\"", htmlspecialchars($typname), '"', ($typname == $_POST['type']) ? ' selected="selected"' : '', '>',
                    $misc->printVal($typname), '</option>'.PHP_EOL;
                    $types->moveNext();
                }
                echo '</select></td>'.PHP_EOL;

                // Output array type selector
                echo '<td><select name="array">'.PHP_EOL;
                echo "\t<option value=\"\"", ('' == $_POST['array']) ? ' selected="selected"' : '', '></option>'.PHP_EOL;
                echo "\t<option value=\"[]\"", ('[]' == $_POST['array']) ? ' selected="selected"' : '', '>[ ]</option>'.PHP_EOL;
                echo '</select></td>'.PHP_EOL;
                $predefined_size_types = array_intersect($data->predefined_size_types, $types_for_js);
                $escaped_predef_types  = []; // the JS escaped array elements
                foreach ($predefined_size_types as $value) {
                    $escaped_predef_types[] = "'{$value}'";
                }

                echo '<td><input name="length" id="lengths" size="8" value="',
                htmlspecialchars($_POST['length']), '" /></td>'.PHP_EOL;
                // Support for adding column with not null and default
                if ($data->hasCreateFieldWithConstraints()) {
                    echo '<td><input type="checkbox" name="notnull"',
                    (isset($_REQUEST['notnull'])) ? ' checked="checked"' : '', ' /></td>'.PHP_EOL;
                    echo '<td><input name="default" size="20" value="',
                    htmlspecialchars($_POST['default']), '" /></td>'.PHP_EOL;
                }
                echo '<td><input name="comment" size="40" value="',
                htmlspecialchars($_POST['comment']), '" /></td></tr>'.PHP_EOL;
                echo '</table>'.PHP_EOL;
                echo '<p><input type="hidden" name="action" value="add_column" />'.PHP_EOL;
                echo '<input type="hidden" name="stage" value="2" />'.PHP_EOL;
                echo $misc->form;
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
                if (!$data->hasCreateFieldWithConstraints()) {
                    echo '<input type="hidden" name="default" value="" />'.PHP_EOL;
                }
                echo "<input type=\"submit\" value=\"{$this->lang['stradd']}\" />".PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
                echo '</form>'.PHP_EOL;
                echo '<script type="text/javascript">predefined_lengths = new Array('.implode(',', $escaped_predef_types).");checkLengths(document.getElementById('type').value,'');</script>".PHP_EOL;

                break;
            case 2:
                // Check inputs
                if ('' == trim($_POST['field'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doAddColumn($this->lang['strcolneedsname']);

                    return;
                }
                $this->coalesceArr($_POST, 'length', '');

                list($status, $sql) = $data->addColumn(
                    $_POST['table'],
                    $_POST['field'],
                    $_POST['type'],
                    '' != $_POST['array'],
                    $_POST['length'],
                    isset($_POST['notnull']),
                    $_POST['default'],
                    $_POST['comment']
                );
                if (0 == $status) {
                    $misc->setReloadBrowser(true);
                    $this->doDefault(sprintf('%s %s %s', $sql, PHP_EOL, $this->lang['strcolumnadded']));
                } else {
                    $_REQUEST['stage'] = 1;
                    $this->doAddColumn($this->lang['strcolumnaddedbad']);

                    return;
                }

                break;
            default:
                echo "<p>{$this->lang['strinvalidparam']}</p>".PHP_EOL;
        }
    }

    /**
     * Show confirmation of drop column and perform actual drop.
     *
     * @param bool $confirm true to ask for confirmation, false to actually drop
     */
    public function doDrop($confirm = true)
    {
        $misc = $this->misc;
        $data = $misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('column');
            $this->printTitle($this->lang['strdrop'], 'pg.column.drop');

            echo '<p>'.sprintf($this->lang['strconfdropcolumn'], $misc->printVal($_REQUEST['column']), $misc->printVal($_REQUEST['table'])).'</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/tblproperties" method="post">'.PHP_EOL;
            echo '<input type="hidden" name="action" value="drop" />'.PHP_EOL;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="column" value="', htmlspecialchars($_REQUEST['column']), '" />'.PHP_EOL;
            echo $misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\"> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>".PHP_EOL;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            list($status, $sql) = $data->dropColumn($_POST['table'], $_POST['column'], isset($_POST['cascade']));
            if (0 == $status) {
                $misc->setReloadBrowser(true);
                $this->doDefault(sprintf('%s %s %s', $sql, PHP_EOL, $this->lang['strcolumndropped']));
            } else {
                $this->doDefault($this->lang['strcolumndroppedbad']);
            }
        }
    }
}
