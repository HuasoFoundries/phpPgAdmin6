<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class FunctionsController extends BaseController
{
    public $table_place      = 'functions-functions';
    public $controller_title = 'strfunctions';
    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $header_template = 'header_datatables.twig';
        $footer_template = 'footer.twig';
        ob_start();
        switch ($this->action) {
            case 'save_create':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'create':
                $header_template = 'header_select2.twig';
                $this->doCreate();

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
            case 'save_edit':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveEdit();
                }

                break;
            case 'edit':
                $header_template = 'header_sqledit.twig';
                $footer_template = 'footer_sqledit.twig';
                $this->doEdit();

                break;
            case 'properties':
                $header_template = 'header_highlight.twig';
                $this->doProperties();

                break;
            default:
                $this->doDefault();

                break;
        }
        $output = ob_get_clean();

        $this->printHeader($this->headerTitle(), null, true, $header_template);
        $this->printBody();
        echo $output;
        $this->printFooter(true, $footer_template);
    }

    /**
     * Show default list of functions in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'functions');
        $this->printMsg($msg);

        $funcs = $data->getFunctions();

        $columns = [
            'function'     => [
                'title' => $this->lang['strfunction'],
                'field' => Decorator::field('proproto'),
                'url'   => \SUBFOLDER . "/redirect/function?action=properties&amp;{$this->misc->href}&amp;",
                'vars'  => ['function' => 'proproto', 'function_oid' => 'prooid'],
            ],
            'returns'      => [
                'title' => $this->lang['strreturns'],
                'field' => Decorator::field('proreturns'),
            ],
            'owner'        => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('proowner'),
            ],
            'proglanguage' => [
                'title' => $this->lang['strproglanguage'],
                'field' => Decorator::field('prolanguage'),
            ],
            'actions'      => [
                'title' => $this->lang['stractions'],
            ],
            'comment'      => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('procomment'),
            ],
        ];

        $actions = [
            'multiactions' => [
                'keycols' => ['function' => 'proproto', 'function_oid' => 'prooid'],
                'url'     => 'functions',
            ],
            'alter'        => [
                'content' => $this->lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'functions',
                        'urlvars' => [
                            'action'       => 'edit',
                            'function'     => Decorator::field('proproto'),
                            'function_oid' => Decorator::field('prooid'),
                        ],
                    ],
                ],
            ],
            'drop'         => [
                'multiaction' => 'confirm_drop',
                'content'     => $this->lang['strdrop'],
                'attr'        => [
                    'href' => [
                        'url'     => 'functions',
                        'urlvars' => [
                            'action'       => 'confirm_drop',
                            'function'     => Decorator::field('proproto'),
                            'function_oid' => Decorator::field('prooid'),
                        ],
                    ],
                ],
            ],
            'privileges'   => [
                'content' => $this->lang['strprivileges'],
                'attr'    => [
                    'href' => [
                        'url'     => 'privileges',
                        'urlvars' => [
                            'subject'      => 'function',
                            'function'     => Decorator::field('proproto'),
                            'function_oid' => Decorator::field('prooid'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($funcs, $columns, $actions, $this->table_place, $this->lang['strnofunctions']);

        $this->_printNavLinks('functions-functions');
    }

    private function _printNavLinks($place, $func_full = '')
    {
        if ($place === 'functions-properties') {
            $navlinks = [
                'showall' => [
                    'attr'    => [
                        'href' => [
                            'url'     => 'functions',
                            'urlvars' => [
                                'server'   => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema'   => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strshowallfunctions'],
                ],
                'alter'   => [
                    'attr'    => [
                        'href' => [
                            'url'     => 'functions',
                            'urlvars' => [
                                'action'       => 'edit',
                                'server'       => $_REQUEST['server'],
                                'database'     => $_REQUEST['database'],
                                'schema'       => $_REQUEST['schema'],
                                'function'     => $_REQUEST['function'],
                                'function_oid' => $_REQUEST['function_oid'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['stralter'],
                ],
                'drop'    => [
                    'attr'    => [
                        'href' => [
                            'url'     => 'functions',
                            'urlvars' => [
                                'action'       => 'confirm_drop',
                                'server'       => $_REQUEST['server'],
                                'database'     => $_REQUEST['database'],
                                'schema'       => $_REQUEST['schema'],
                                'function'     => $func_full,
                                'function_oid' => $_REQUEST['function_oid'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strdrop'],
                ],
            ];
        } elseif ($place === 'functions-functions') {
            $navlinks = [
                'createpl'       => [
                    'attr'    => [
                        'href' => [
                            'url'     => 'functions',
                            'urlvars' => [
                                'action'   => 'create',
                                'server'   => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema'   => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strcreateplfunction'],
                ],
                'createinternal' => [
                    'attr'    => [
                        'href' => [
                            'url'     => 'functions',
                            'urlvars' => [
                                'action'   => 'create',
                                'language' => 'internal',
                                'server'   => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema'   => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strcreateinternalfunction'],
                ],
                'createc'        => [
                    'attr'    => [
                        'href' => [
                            'url'     => 'functions',
                            'urlvars' => [
                                'action'   => 'create',
                                'language' => 'C',
                                'server'   => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema'   => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strcreatecfunction'],
                ],
            ];
        } else {
            return;
        }

        $this->printNavLinks($navlinks, $place, get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $funcs = $data->getFunctions();

        $proto = Decorator::concat(Decorator::field('proname'), ' (', Decorator::field('proarguments'), ')');

        $reqvars = $this->misc->getRequestVars('function');

        $attrs = [
            'text'    => $proto,
            'icon'    => 'Function',
            'toolTip' => Decorator::field('procomment'),
            'action'  => Decorator::redirecturl(
                'redirect',
                $reqvars,
                [
                    'action'       => 'properties',
                    'function'     => $proto,
                    'function_oid' => Decorator::field('prooid'),
                ]
            ),
        ];

        return $this->printTree($funcs, $attrs, 'functions');
    }

    /**
     * Function to save after editing a function.
     */
    public function doSaveEdit()
    {
        $data = $this->misc->getDatabaseAccessor();

        $fnlang = strtolower($_POST['original_lang']);

        if ('c' == $fnlang) {
            $def = [$_POST['formObjectFile'], $_POST['formLinkSymbol']];
        } elseif ('internal' == $fnlang) {
            $def = $_POST['formLinkSymbol'];
        } else {
            $def = $_POST['formDefinition'];
        }
        if (!$data->hasFunctionAlterSchema()) {
            $_POST['formFuncSchema'] = '';
        }

        $status = $data->setFunction(
            $_POST['original_function'],
            $_POST['formFunction'],
            $_POST['original_arguments'],
            $_POST['original_returns'],
            $def,
            $_POST['original_lang'],
            $_POST['formProperties'],
            isset($_POST['original_setof']),
            $_POST['original_owner'],
            $_POST['formFuncOwn'],
            $_POST['original_schema'],
            $_POST['formFuncSchema'],
            isset($_POST['formCost']) ? $_POST['formCost'] : null,
            isset($_POST['formRows']) ? $_POST['formRows'] : 0,
            $_POST['formComment']
        );

        if (0 == $status) {
            // If function has had schema altered, need to change to the new schema
            // and reload the browser frame.
            if (!empty($_POST['formFuncSchema']) && ($_POST['formFuncSchema'] != $_POST['original_schema'])) {
                // Jump them to the new function schema
                $this->misc->setCurrentSchema($_POST['formFuncSchema']);
                // Force a browser reload
                $this->misc->setReloadBrowser(true);
            }
            $this->doProperties($this->lang['strfunctionupdated']);
        } else {
            $this->doEdit($this->lang['strfunctionupdatedbad']);
        }
    }

    /**
     * Function to allow editing of a Function.
     *
     * @param mixed $msg
     */
    public function doEdit($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('function');
        $this->printTitle($this->lang['stralter'], 'pg.function.alter');
        $this->printMsg($msg);

        $fndata = $data->getFunction($_REQUEST['function_oid']);

        if ($fndata->recordCount() > 0) {
            $fndata->fields['proretset'] = $data->phpBool($fndata->fields['proretset']);

            // Initialise variables
            $_POST['formDefinition'] = $this->getPostParam('formDefinition', $fndata->fields['prosrc']);

            $_POST['formProperties'] = $this->getPostParam('formProperties', $data->getFunctionProperties($fndata->fields));

            $_POST['formFunction'] = $this->getPostParam('formFunction', $fndata->fields['proname']);

            $_POST['formComment'] = $this->getPostParam('formComment', $fndata->fields['procomment']);

            $_POST['formObjectFile'] = $this->getPostParam('formObjectFile', $fndata->fields['probin']);

            $_POST['formLinkSymbol'] = $this->getPostParam('formLinkSymbol', $fndata->fields['prosrc']);

            $_POST['formFuncOwn'] = $this->getPostParam('formFuncOwn', $fndata->fields['proowner']);

            $_POST['formFuncSchema'] = $this->getPostParam('formFuncSchema', $fndata->fields['proschema']);

            if ($data->hasFunctionCosting()) {
                $_POST['formCost'] = $this->getPostParam('formCost', $fndata->fields['procost']);

                $_POST['formRows'] = $this->getPostParam('formRows', $fndata->fields['prorows']);
            }

            // Deal with named parameters
            if ($data->hasNamedParams()) {
                if (isset($fndata->fields['proallarguments'])) {
                    $args_arr = $data->phpArray($fndata->fields['proallarguments']);
                } else {
                    $args_arr = explode(', ', $fndata->fields['proarguments']);
                }
                $names_arr     = $data->phpArray($fndata->fields['proargnames']);
                $modes_arr     = $data->phpArray($fndata->fields['proargmodes']);
                $args          = '';
                $args_arr_size = sizeof($args_arr);
                for ($i = 0; $i < $args_arr_size; ++$i) {
                    if (0 != $i) {
                        $args .= ', ';
                    }

                    if (isset($modes_arr[$i])) {
                        switch ($modes_arr[$i]) {
                            case 'i':
                                $args .= ' IN ';

                                break;
                            case 'o':
                                $args .= ' OUT ';

                                break;
                            case 'b':
                                $args .= ' INOUT ';

                                break;
                            case 'v':
                                $args .= ' VARIADIC ';

                                break;
                            case 't':
                                $args .= ' TABLE ';

                                break;
                        }
                    }
                    if (isset($names_arr[$i]) && '' != $names_arr[$i]) {
                        $data->fieldClean($names_arr[$i]);
                        $args .= '"' . $names_arr[$i] . '" ';
                    }
                    $args .= $args_arr[$i];
                }
            } else {
                $args = $fndata->fields['proarguments'];
            }

            echo '<form action="' . \SUBFOLDER . "/src/views/functions\" method=\"post\">\n";
            echo "<table style=\"width: 90%\">\n";
            echo "<tr>\n";
            echo "<th class=\"data required\">{$this->lang['strschema']}</th>\n";
            echo "<th class=\"data required\">{$this->lang['strfunction']}</th>\n";
            echo "<th class=\"data\">{$this->lang['strarguments']}</th>\n";
            echo "<th class=\"data required\">{$this->lang['strreturns']}</th>\n";
            echo "<th class=\"data required\">{$this->lang['strproglanguage']}</th>\n";
            echo "</tr>\n";

            echo "<tr>\n";
            echo '<td class="data1">';
            echo '<input type="hidden" name="original_schema" value="', htmlspecialchars($fndata->fields['proschema']), "\" />\n";
            if ($data->hasFunctionAlterSchema()) {
                $schemas = $data->getSchemas();
                echo '<select name="formFuncSchema">';
                while (!$schemas->EOF) {
                    $schema = $schemas->fields['nspname'];
                    echo '<option value="', htmlspecialchars($schema), '"',
                    ($schema == $_POST['formFuncSchema']) ? ' selected="selected"' : '', '>', htmlspecialchars($schema), "</option>\n";
                    $schemas->moveNext();
                }
                echo "</select>\n";
            } else {
                echo $fndata->fields['proschema'];
            }

            echo "</td>\n";
            echo '<td class="data1">';
            echo '<input type="hidden" name="original_function" value="', htmlspecialchars($fndata->fields['proname']), "\" />\n";
            echo "<input name=\"formFunction\" style=\"width: 100%\" maxlength=\"{$data->_maxNameLen}\" value=\"", htmlspecialchars($_POST['formFunction']), '" />';
            echo "</td>\n";

            echo '<td class="data1">', $this->misc->printVal($args), "\n";
            echo '<input type="hidden" name="original_arguments" value="', htmlspecialchars($args), "\" />\n";
            echo "</td>\n";

            echo '<td class="data1">';
            if ($fndata->fields['proretset']) {
                echo 'setof ';
            }

            echo $this->misc->printVal($fndata->fields['proresult']), "\n";
            echo '<input type="hidden" name="original_returns" value="', htmlspecialchars($fndata->fields['proresult']), "\" />\n";
            if ($fndata->fields['proretset']) {
                echo "<input type=\"hidden\" name=\"original_setof\" value=\"yes\" />\n";
            }

            echo "</td>\n";

            echo '<td class="data1">', $this->misc->printVal($fndata->fields['prolanguage']), "\n";
            echo '<input type="hidden" name="original_lang" value="', htmlspecialchars($fndata->fields['prolanguage']), "\" />\n";
            echo "</td>\n";
            echo "</tr>\n";

            $fnlang = strtolower($fndata->fields['prolanguage']);
            if ('c' == $fnlang) {
                echo "<tr><th class=\"data required\" colspan=\"2\">{$this->lang['strobjectfile']}</th>\n";
                echo "<th class=\"data\" colspan=\"2\">{$this->lang['strlinksymbol']}</th></tr>\n";
                echo '<tr><td class="data1" colspan="2"><input type="text" name="formObjectFile" style="width:100%" value="',
                htmlspecialchars($_POST['formObjectFile']), "\" /></td>\n";
                echo '<td class="data1" colspan="2"><input type="text" name="formLinkSymbol" style="width:100%" value="',
                htmlspecialchars($_POST['formLinkSymbol']), "\" /></td></tr>\n";
            } elseif ('internal' == $fnlang) {
                echo "<tr><th class=\"data\" colspan=\"5\">{$this->lang['strlinksymbol']}</th></tr>\n";
                echo '<tr><td class="data1" colspan="5"><input type="text" name="formLinkSymbol" style="width:100%" value="',
                htmlspecialchars($_POST['formLinkSymbol']), "\" /></td></tr>\n";
            } else {
                echo "<tr><th class=\"data required\" colspan=\"5\">{$this->lang['strdefinition']}</th></tr>\n";
                echo '<tr><td class="data1" colspan="5">';
                $textarea_id = ($fnlang === 'sql' || $fnlang === 'plpgsql') ? 'query' : 'formDefinition';
                echo '<textarea style="width:100%;" rows="20" cols="50" id="' . $textarea_id . '" name="formDefinition">';
                echo htmlspecialchars($_POST['formDefinition']);
                echo "</textarea></td></tr>\n";
            }

            // Display function comment
            echo "<tr><th class=\"data\" colspan=\"5\">{$this->lang['strcomment']}</th></tr>\n";
            echo '<tr><td class="data1" colspan="5">';
            echo '<textarea style="width:100%;" name="formComment" rows="3" cols="50">';
            echo htmlspecialchars($_POST['formComment']);
            echo "</textarea></td></tr>\n";

            // Display function cost options
            if ($data->hasFunctionCosting()) {
                echo "<tr><th class=\"data required\" colspan=\"5\">{$this->lang['strfunctioncosting']}</th></tr>\n";
                echo "<td class=\"data1\" colspan=\"2\">{$this->lang['strexecutioncost']}: <input name=\"formCost\" size=\"16\" value=\"" .
                htmlspecialchars($_POST['formCost']) . '" /></td>';
                echo "<td class=\"data1\" colspan=\"2\">{$this->lang['strresultrows']}: <input name=\"formRows\" size=\"16\" value=\"",
                htmlspecialchars($_POST['formRows']), '"', (!$fndata->fields['proretset']) ? 'disabled' : '', '/></td>';
            }

            // Display function properties
            if (is_array($data->funcprops) && sizeof($data->funcprops) > 0) {
                echo "<tr><th class=\"data\" colspan=\"5\">{$this->lang['strproperties']}</th></tr>\n";
                echo "<tr><td class=\"data1\" colspan=\"5\">\n";
                $i = 0;
                foreach ($data->funcprops as $k => $v) {
                    echo "<select name=\"formProperties[{$i}]\">\n";
                    foreach ($v as $p) {
                        echo '<option value="', htmlspecialchars($p), '"',
                        ($_POST['formProperties'][$i] == $p) ? ' selected="selected"' : '',
                        '>', $this->misc->printVal($p), "</option>\n";
                    }
                    echo "</select><br />\n";
                    ++$i;
                }
                echo "</td></tr>\n";
            }

            // function owner
            if ($data->hasFunctionAlterOwner()) {
                $users = $data->getUsers();
                echo "<tr><td class=\"data1\" colspan=\"5\">{$this->lang['strowner']}: <select name=\"formFuncOwn\">";
                while (!$users->EOF) {
                    $uname = $users->fields['usename'];
                    echo '<option value="', htmlspecialchars($uname), '"',
                    ($uname == $_POST['formFuncOwn']) ? ' selected="selected"' : '', '>', htmlspecialchars($uname), "</option>\n";
                    $users->moveNext();
                }
                echo "</select>\n";
                echo '<input type="hidden" name="original_owner" value="', htmlspecialchars($fndata->fields['proowner']), "\" />\n";
                echo "</td></tr>\n";
            }
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"save_edit\" />\n";
            echo '<input type="hidden" name="function" value="', htmlspecialchars($_REQUEST['function']), "\" />\n";
            echo '<input type="hidden" name="function_oid" value="', htmlspecialchars($_REQUEST['function_oid']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" value=\"{$this->lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            echo "<p>{$this->lang['strnodata']}</p>\n";
        }
    }

    /**
     * Show read only properties of a function.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('function');
        $this->printTitle($this->lang['strproperties'], 'pg.function');
        $this->printMsg($msg);

        $funcdata  = $data->getFunction($_REQUEST['function_oid']);
        $func_full = '';
        if ($funcdata->recordCount() > 0) {
            // Deal with named parameters
            $args = $this->_getPropertiesArgs($funcdata);

            // Show comment if any
            if (null !== $funcdata->fields['procomment']) {
                echo '<p class="comment">', $this->misc->printVal($funcdata->fields['procomment']), "</p>\n";
            }

            $funcdata->fields['proretset'] = $data->phpBool($funcdata->fields['proretset']);
            $func_full                     = $funcdata->fields['proname'] . '(' . $funcdata->fields['proarguments'] . ')';
            echo "<table style=\"width: 90%\">\n";
            echo sprintf('<tr><th class="data">%s</th>%s', $this->lang['strfunction'], "\n");
            echo sprintf('<th class="data">%s</th>%s', $this->lang['strarguments'], "\n");
            echo sprintf('<th class="data">%s</th>%s', $this->lang['strreturns'], "\n");
            echo sprintf('<th class="data">%s</th></tr>%s', $this->lang['strproglanguage'], "\n");
            echo '<tr><td class="data1">', $this->misc->printVal($funcdata->fields['proname']), "</td>\n";
            echo '<td class="data1">', $this->misc->printVal($args), "</td>\n";
            echo '<td class="data1">';
            if ($funcdata->fields['proretset']) {
                echo 'setof ';
            }

            echo $this->misc->printVal($funcdata->fields['proresult']), "</td>\n";
            echo '<td class="data1">', $this->misc->printVal($funcdata->fields['prolanguage']), "</td></tr>\n";

            $fnlang = strtolower($funcdata->fields['prolanguage']);
            if ('c' == $fnlang) {
                echo "<tr><th class=\"data\" colspan=\"2\">{$this->lang['strobjectfile']}</th>\n";
                echo "<th class=\"data\" colspan=\"2\">{$this->lang['strlinksymbol']}</th></tr>\n";
                echo '<tr><td class="data1" colspan="2">', $this->misc->printVal($funcdata->fields['probin']), "</td>\n";
                echo '<td class="data1" colspan="2">', $this->misc->printVal($funcdata->fields['prosrc']), "</td></tr>\n";
            } elseif ('internal' == $fnlang) {
                echo "<tr><th class=\"data\" colspan=\"4\">{$this->lang['strlinksymbol']}</th></tr>\n";
                echo '<tr><td class="data1" colspan="4">', $this->misc->printVal($funcdata->fields['prosrc']), "</td></tr>\n";
            } else {
                echo '<tr><td class="data1" colspan="4">';
                echo sprintf('<pre><code class="%s hljs">%s</code></pre>', $fnlang, $funcdata->fields['prosrc']);
                echo "</td></tr>\n";
            }

            // Display function cost options
            if ($data->hasFunctionCosting()) {
                echo "<tr><th class=\"data required\" colspan=\"4\">{$this->lang['strfunctioncosting']}</th></tr>\n";
                echo "<td class=\"data1\" colspan=\"2\">{$this->lang['strexecutioncost']}: ", $this->misc->printVal($funcdata->fields['procost']), ' </td>';
                echo "<td class=\"data1\" colspan=\"2\">{$this->lang['strresultrows']}: ", $this->misc->printVal($funcdata->fields['prorows']), ' </td>';
            }

            // Show flags
            if (is_array($data->funcprops) && sizeof($data->funcprops) > 0) {
                // Fetch an array of the function properties
                $funcprops = $data->getFunctionProperties($funcdata->fields);
                echo "<tr><th class=\"data\" colspan=\"4\">{$this->lang['strproperties']}</th></tr>\n";
                echo "<tr><td class=\"data1\" colspan=\"4\">\n";
                foreach ($funcprops as $v) {
                    echo $this->misc->printVal($v), "<br />\n";
                }
                echo "</td></tr>\n";
            }

            echo "<tr><td class=\"data1\" colspan=\"5\">{$this->lang['strowner']}: ", htmlspecialchars($funcdata->fields['proowner']), "\n";
            echo "</td></tr>\n";
            echo "</table>\n";
        } else {
            echo "<p>{$this->lang['strnodata']}</p>\n";
        }

        $this->_printNavLinks('functions-properties', $func_full);
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['function']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($this->lang['strspecifyfunctiontodrop']);
        }

        if ($confirm) {
            $this->printTrail('schema');
            $this->printTitle($this->lang['strdrop'], 'pg.function.drop');

            echo '<form action="' . \SUBFOLDER . "/src/views/functions\" method=\"post\">\n";

            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                    echo '<p>', sprintf($this->lang['strconfdropfunction'], $this->misc->printVal($a['function'])), "</p>\n";
                    echo '<input type="hidden" name="function[]" value="', htmlspecialchars($a['function']), "\" />\n";
                    echo '<input type="hidden" name="function_oid[]" value="', htmlspecialchars($a['function_oid']), "\" />\n";
                }
            } else {
                echo '<p>', sprintf($this->lang['strconfdropfunction'], $this->misc->printVal($_REQUEST['function'])), "</p>\n";
                echo '<input type="hidden" name="function" value="', htmlspecialchars($_REQUEST['function']), "\" />\n";
                echo '<input type="hidden" name="function_oid" value="', htmlspecialchars($_REQUEST['function_oid']), "\" />\n";
            }

            echo "<input type=\"hidden\" name=\"action\" value=\"drop\" />\n";

            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /><label for=\"cascade\">{$this->lang['strcascade']}</label></p>\n";
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />\n";
            echo "</form>\n";
        } else {
            if (is_array($_POST['function_oid'])) {
                $msg    = '';
                $status = $data->beginTransaction();
                if (0 == $status) {
                    foreach ($_POST['function_oid'] as $k => $s) {
                        $status = $data->dropFunction($s, isset($_POST['cascade']));
                        if (0 == $status) {
                            $msg .= sprintf('%s: %s<br />', htmlentities($_POST['function'][$k], ENT_QUOTES, 'UTF-8'), $this->lang['strfunctiondropped']);
                        } else {
                            $data->endTransaction();
                            $this->doDefault(sprintf('%s%s: %s<br />', $msg, htmlentities($_POST['function'][$k], ENT_QUOTES, 'UTF-8'), $this->lang['strfunctiondroppedbad']));

                            return;
                        }
                    }
                }
                if (0 == $data->endTransaction()) {
                    // Everything went fine, back to the Default page....
                    $this->misc->setReloadBrowser(true);
                    $this->doDefault($msg);
                } else {
                    $this->doDefault($this->lang['strfunctiondroppedbad']);
                }
            } else {
                $status = $data->dropFunction($_POST['function_oid'], isset($_POST['cascade']));
                if (0 == $status) {
                    $this->misc->setReloadBrowser(true);
                    $this->doDefault($this->lang['strfunctiondropped']);
                } else {
                    $this->doDefault($this->lang['strfunctiondroppedbad']);
                }
            }
        }
    }

    /**
     * Displays a screen where they can enter a new function.
     *
     * @param string $msg  message to display
     * @param mixed  $szJS
     */
    public function doCreate($msg = '', $szJS = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $_POST['formFunction'] = $this->getPostParam('formFunction', '');

        $_POST['formArguments'] = $this->getPostParam('formArguments', '');

        $_POST['formReturns'] = $this->getPostParam('formReturns', '');

        if (!isset($_POST['formLanguage'])) {
            $_POST['formLanguage'] = isset($_REQUEST['language']) ? $_REQUEST['language'] : 'sql';
        }

        $_POST['formDefinition'] = $this->getPostParam('formDefinition', '');

        $_POST['formObjectFile'] = $this->getPostParam('formObjectFile', '');

        $_POST['formLinkSymbol'] = $this->getPostParam('formLinkSymbol', '');

        $_POST['formProperties'] = $this->getPostParam('formProperties', $data->defaultprops);

        $_POST['formSetOf'] = $this->getPostParam('formSetOf', '');

        $_POST['formArray'] = $this->getPostParam('formArray', '');

        $_POST['formCost'] = $this->getPostParam('formCost', '');

        $_POST['formRows'] = $this->getPostParam('formRows', '');

        $_POST['formComment'] = $this->getPostParam('formComment', '');

        $types  = $data->getTypes(true, true, true);
        $langs  = $data->getLanguages(true);
        $fnlang = strtolower($_POST['formLanguage']);

        switch ($fnlang) {
            case 'c':
                $this->printTitle($this->lang['strcreatecfunction'], 'pg.function.create.c');

                break;
            case 'internal':
                $this->printTitle($this->lang['strcreateinternalfunction'], 'pg.function.create.internal');

                break;
            default:
                $this->printTitle($this->lang['strcreateplfunction'], 'pg.function.create.pl');

                break;
        }
        $this->printMsg($msg);

        // Create string for return type list
        $szTypes = '';
        while (!$types->EOF) {
            $szSelected = '';
            if ($types->fields['typname'] == $_POST['formReturns']) {
                $szSelected = ' selected="selected"';
            }
            // this variable is include in the JS code bellow, so we need to ENT_QUOTES
            $szTypes .= '<option value="' . htmlspecialchars($types->fields['typname'], ENT_QUOTES) . "\"{$szSelected}>";
            $szTypes .= htmlspecialchars($types->fields['typname'], ENT_QUOTES) . '</option>';
            $types->moveNext();
        }

        $szFunctionName = "<td class=\"data1\"><input name=\"formFunction\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"" .
        htmlspecialchars($_POST['formFunction']) . '" /></td>';

        $szArguments = '<td class="data1"><input name="formArguments" style="width:100%;" size="16" value="' .
        htmlspecialchars($_POST['formArguments']) . '" /></td>';

        $szSetOfSelected    = '';
        $szNotSetOfSelected = '';
        if ('' == $_POST['formSetOf']) {
            $szNotSetOfSelected = ' selected="selected"';
        } elseif ('SETOF' == $_POST['formSetOf']) {
            $szSetOfSelected = ' selected="selected"';
        }
        $szReturns = '<td class="data1" colspan="2">';
        $szReturns .= '<select name="formSetOf">';
        $szReturns .= "<option value=\"\"{$szNotSetOfSelected}></option>";
        $szReturns .= "<option value=\"SETOF\"{$szSetOfSelected}>SETOF</option>";
        $szReturns .= '</select>';

        $szReturns .= '<select class="select2" name="formReturns">' . $szTypes . '</select>';

        // Create string array type selector

        $szArraySelected    = '';
        $szNotArraySelected = '';
        if ('' == $_POST['formArray']) {
            $szNotArraySelected = ' selected="selected"';
        } elseif ('[]' == $_POST['formArray']) {
            $szArraySelected = ' selected="selected"';
        }

        $szReturns .= '<select name="formArray">';
        $szReturns .= "<option value=\"\"{$szNotArraySelected}></option>";
        $szReturns .= "<option value=\"[]\"{$szArraySelected}>[ ]</option>";
        $szReturns .= "</select>\n</td>";

        // Create string for language
        $szLanguage = '<td class="data1">';
        if ('c' == $fnlang || 'internal' == $fnlang) {
            $szLanguage .= $_POST['formLanguage'] . "\n";
            $szLanguage .= "<input type=\"hidden\" name=\"formLanguage\" value=\"{$_POST['formLanguage']}\" />\n";
        } else {
            $szLanguage .= "<select name=\"formLanguage\">\n";
            while (!$langs->EOF) {
                $szSelected = '';
                if ($langs->fields['lanname'] == $_POST['formLanguage']) {
                    $szSelected = ' selected="selected"';
                }
                if ('c' != strtolower($langs->fields['lanname']) && 'internal' != strtolower($langs->fields['lanname'])) {
                    $szLanguage .= '<option value="' . htmlspecialchars($langs->fields['lanname']) . "\"{$szSelected}>\n" .
                    $this->misc->printVal($langs->fields['lanname']) . '</option>';
                }

                $langs->moveNext();
            }
            $szLanguage .= "</select>\n";
        }

        $szLanguage .= '</td>';
        $szJSArguments = "<tr><th class=\"data\" colspan=\"7\">{$this->lang['strarguments']}</th></tr>";
        $arrayModes    = ['IN', 'OUT', 'INOUT'];
        $szModes       = '<select name="formArgModes[]" style="width:100%;">';
        foreach ($arrayModes as $pV) {
            $szModes .= "<option value=\"{$pV}\">{$pV}</option>";
        }
        $szModes .= '</select>';
        $szArgReturns = '<select name="formArgArray[]">';
        $szArgReturns .= '<option value=""></option>';
        $szArgReturns .= '<option value="[]">[]</option>';
        $szArgReturns .= '</select>';
        $subfolder = \SUBFOLDER;
        if (!empty($this->conf['theme'])) {
            $szImgPath = \SUBFOLDER . "/assets/images/themes/{$this->conf['theme']}";
        } else {
            $szImgPath = \SUBFOLDER . '/assets/images/themes/default';
        }
        if (empty($msg)) {
            // $this->prtrace($subfolder);
            $szJSTRArg = "<script type=\"text/javascript\" >addArg('{$subfolder}');</script>\n";
        } else {
            $szJSTRArg = '';
        }
        $szJSAddTR = "<tr id=\"parent_add_tr\" onclick=\"addArg('{$subfolder}');\" onmouseover=\"this.style.cursor='pointer'\">\n";
        $szJSAddTR .= '<td style="text-align: right" colspan="6" class="data3"><table><tr><td class="data3">';
        $szJSAddTR .= "<img src=\"{$szImgPath}/AddArguments.png\" alt=\"Add Argument\" /></td>";
        $szJSAddTR .= "<td class=\"data3\"><span style=\"font-size: 8pt\">{$this->lang['strargadd']}</span></td></tr></table></td>\n</tr>\n";

        echo '<script src="' . \SUBFOLDER . "/assets/js/functions.js\" type=\"text/javascript\"></script>
		<script type=\"text/javascript\">
			//<![CDATA[
			var g_types_select = '<select class=\"select2\" name=\"formArgType[]\">{$szTypes}</select>{$szArgReturns}';
			var g_modes_select = '{$szModes}';
			var g_name = '';
			var g_lang_strargremove = '", htmlspecialchars($this->lang['strargremove'], ENT_QUOTES), "';
			var g_lang_strargnoargs = '", htmlspecialchars($this->lang['strargnoargs'], ENT_QUOTES), "';
			var g_lang_strargenableargs = '", htmlspecialchars($this->lang['strargenableargs'], ENT_QUOTES), "';
			var g_lang_strargnorowabove = '", htmlspecialchars($this->lang['strargnorowabove'], ENT_QUOTES), "';
			var g_lang_strargnorowbelow = '", htmlspecialchars($this->lang['strargnorowbelow'], ENT_QUOTES), "';
			var g_lang_strargremoveconfirm = '", htmlspecialchars($this->lang['strargremoveconfirm'], ENT_QUOTES), "';
			var g_lang_strargraise = '", htmlspecialchars($this->lang['strargraise'], ENT_QUOTES), "';
			var g_lang_strarglower = '", htmlspecialchars($this->lang['strarglower'], ENT_QUOTES), "';
			//]]>
		</script>
		";
        echo '<form action="' . \SUBFOLDER . "/src/views/functions\" method=\"post\">\n";
        echo "<table><tbody id=\"args_table\">\n";
        echo "<tr><th class=\"data required\">{$this->lang['strname']}</th>\n";
        echo "<th class=\"data required\" colspan=\"2\">{$this->lang['strreturns']}</th>\n";
        echo "<th class=\"data required\">{$this->lang['strproglanguage']}</th></tr>\n";
        echo "<tr>\n";
        echo "{$szFunctionName}\n";
        echo "{$szReturns}\n";
        echo "{$szLanguage}\n";
        echo "</tr>\n";
        echo "{$szJSArguments}\n";
        echo "<tr>\n";
        echo "<th class=\"data required\">{$this->lang['strargmode']}</th>\n";
        echo "<th class=\"data required\">{$this->lang['strname']}</th>\n";
        echo "<th class=\"data required\" colspan=\"2\">{$this->lang['strargtype']}</th>\n";
        echo "</tr>\n";
        echo "{$szJSAddTR}\n";

        if ('c' == $fnlang) {
            echo "<tr><th class=\"data required\" colspan=\"2\">{$this->lang['strobjectfile']}</th>\n";
            echo "<th class=\"data\" colspan=\"2\">{$this->lang['strlinksymbol']}</th></tr>\n";
            echo '<tr><td class="data1" colspan="2"><input type="text" name="formObjectFile" style="width:100%" value="',
            htmlspecialchars($_POST['formObjectFile']), "\" /></td>\n";
            echo '<td class="data1" colspan="2"><input type="text" name="formLinkSymbol" style="width:100%" value="',
            htmlspecialchars($_POST['formLinkSymbol']), "\" /></td></tr>\n";
        } elseif ('internal' == $fnlang) {
            echo "<tr><th class=\"data\" colspan=\"4\">{$this->lang['strlinksymbol']}</th></tr>\n";
            echo '<tr><td class="data1" colspan="4"><input type="text" name="formLinkSymbol" style="width:100%" value="',
            htmlspecialchars($_POST['formLinkSymbol']), "\" /></td></tr>\n";
        } else {
            echo "<tr><th class=\"data required\" colspan=\"4\">{$this->lang['strdefinition']}</th></tr>\n";
            echo '<tr><td class="data1" colspan="4">';
            echo '<textarea style="width:100%;" rows="20" cols="50" name="formDefinition">';
            echo htmlspecialchars($_POST['formDefinition']);
            echo "</textarea></td></tr>\n";
        }

        // Display function comment
        echo "<tr><th class=\"data\" colspan=\"4\">{$this->lang['strcomment']}</th></tr>\n";
        echo '<tr><td class="data1" colspan="4"><textarea style="width:100%;" name="formComment" rows="3" cols="50">',
        htmlspecialchars($_POST['formComment']), "</textarea></td></tr>\n";

        // Display function cost options
        if ($data->hasFunctionCosting()) {
            echo "<tr><th class=\"data required\" colspan=\"4\">{$this->lang['strfunctioncosting']}</th></tr>\n";
            echo "<td class=\"data1\" colspan=\"2\">{$this->lang['strexecutioncost']}: <input name=\"formCost\" size=\"16\" value=\"" .
            htmlspecialchars($_POST['formCost']) . '" /></td>';
            echo "<td class=\"data1\" colspan=\"2\">{$this->lang['strresultrows']}: <input name=\"formRows\" size=\"16\" value=\"" .
            htmlspecialchars($_POST['formRows']) . '" /></td>';
        }

        // Display function properties
        if (is_array($data->funcprops) && sizeof($data->funcprops) > 0) {
            echo "<tr><th class=\"data required\" colspan=\"4\">{$this->lang['strproperties']}</th></tr>\n";
            echo "<tr><td class=\"data1\" colspan=\"4\">\n";
            $i = 0;
            foreach ($data->funcprops as $k => $v) {
                echo "<select name=\"formProperties[{$i}]\">\n";
                foreach ($v as $p) {
                    echo '<option value="', htmlspecialchars($p), '"',
                    ($_POST['formProperties'][$i] == $p) ? ' selected="selected"' : '',
                    '>', $this->misc->printVal($p), "</option>\n";
                }
                echo "</select><br />\n";
                ++$i;
            }
            echo "</td></tr>\n";
        }
        echo "</tbody></table>\n";
        echo $szJSTRArg;
        echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
        echo $szJS;
    }

    /**
     * Actually creates the new function in the database.
     */
    public function doSaveCreate()
    {
        $data = $this->misc->getDatabaseAccessor();

        $fnlang = strtolower($_POST['formLanguage']);

        if ('c' == $fnlang) {
            $def = [$_POST['formObjectFile'], $_POST['formLinkSymbol']];
        } elseif ('internal' == $fnlang) {
            $def = $_POST['formLinkSymbol'];
        } else {
            $def = $_POST['formDefinition'];
        }

        $szJS = '';

        echo '<script src="' . \SUBFOLDER . '/assets/js/functions.js" type="text/javascript"></script>';
        echo '<script type="text/javascript">' . $this->_buildJSData() . '</script>';
        if (!empty($_POST['formArgName'])) {
            $szJS = $this->_buildJSRows($this->_buildFunctionArguments($_POST));
        } else {
            $subfolder = \SUBFOLDER;
            // $this->prtrace($subfolder);
            $szJS = '<script type="text/javascript" src="' . \SUBFOLDER . '/assets/js/functions.js">noArgsRebuild(addArg("' . $subfolder . '"));</script>';
        }

        $cost = (isset($_POST['formCost'])) ? $_POST['formCost'] : null;
        if ('' == $cost || !is_numeric($cost) || $cost != (int) $cost || $cost < 0) {
            $cost = null;
        }

        $rows = (isset($_POST['formRows'])) ? $_POST['formRows'] : null;
        if ('' == $rows || !is_numeric($rows) || $rows != (int) $rows) {
            $rows = null;
        }

        // Check that they've given a name and a definition
        if ('' == $_POST['formFunction']) {
            $this->doCreate($this->lang['strfunctionneedsname'], $szJS);
        } elseif ('internal' != $fnlang && !$def) {
            $this->doCreate($this->lang['strfunctionneedsdef'], $szJS);
        } else {
            // Append array symbol to type if chosen
            $status = $data->createFunction(
                $_POST['formFunction'],
                empty($_POST['nojs']) ? $this->_buildFunctionArguments($_POST) : $_POST['formArguments'],
                $_POST['formReturns'] . $_POST['formArray'],
                $def,
                $_POST['formLanguage'],
                $_POST['formProperties'],
                'SETOF' == $_POST['formSetOf'],
                $cost,
                $rows,
                $_POST['formComment'],
                false
            );
            if (0 == $status) {
                $this->doDefault($this->lang['strfunctioncreated']);
            } else {
                $this->doCreate($this->lang['strfunctioncreatedbad'], $szJS);
            }
        }
    }

    /**
     * Build out the function arguments string.
     *
     * @param array $arrayVars
     *
     * @return string the imploded array vars
     */
    private function _buildFunctionArguments($arrayVars)
    {
        if (isset($_POST['formArgName'])) {
            $arrayArgs = [];
            foreach ($arrayVars['formArgName'] as $pK => $pV) {
                $arrayArgs[] = $arrayVars['formArgModes'][$pK] . ' ' . trim($pV) . ' ' . trim($arrayVars['formArgType'][$pK]) . $arrayVars['formArgArray'][$pK];
            }

            return implode(',', $arrayArgs);
        }

        return '';
    }

    /**
     * Build out JS to re-create table rows for arguments.
     *
     * @param string $szArgs args to parse
     */
    private function _buildJSRows($szArgs)
    {
        $arrayModes      = ['IN', 'OUT', 'INOUT'];
        $arrayArgs       = explode(',', $szArgs);
        $arrayProperArgs = [];
        $nC              = 0;
        $szReturn        = '';
        $szMode          = [];
        foreach ($arrayArgs as $pV) {
            $arrayWords = explode(' ', $pV);
            if (true === in_array($arrayWords[0], $arrayModes, true)) {
                $szMode = $arrayWords[0];
                array_shift($arrayWords);
            }
            $szArgName = array_shift($arrayWords);
            if (false === strpos($arrayWords[count($arrayWords) - 1], '[]')) {
                $szArgType   = implode(' ', $arrayWords);
                $bArgIsArray = 'false';
            } else {
                $szArgType   = str_replace('[]', '', implode(' ', $arrayWords));
                $bArgIsArray = 'true';
            }
            $arrayProperArgs[] = [$szMode, $szArgName, $szArgType, $bArgIsArray];
            $subfolder         = \SUBFOLDER;
            // $this->prtrace($subfolder);
            $szReturn .= '<script type="text/javascript">';
            $szReturn .= "RebuildArgTR('{$szMode}','{$szArgName}','{$szArgType}',new Boolean({$bArgIsArray},{$subfolder}));";
            $szReturn .= '</script>;';
            ++$nC;
        }

        return $szReturn;
    }

    private function _buildJSData()
    {
        $data = $this->misc->getDatabaseAccessor();

        $arrayModes  = ['IN', 'OUT', 'INOUT'];
        $arrayTypes  = $data->getTypes(true, true, true);
        $arrayPTypes = [];
        $arrayPModes = [];

        while (!$arrayTypes->EOF) {
            $arrayPTypes[] = "'" . $arrayTypes->fields['typname'] . "'";
            $arrayTypes->moveNext();
        }

        foreach ($arrayModes as $pV) {
            $arrayPModes[] = "'{$pV}'";
        }

        $szTypes = 'g_main_types = new Array(' . implode(',', $arrayPTypes) . ');';
        $szModes = 'g_main_modes = new Array(' . implode(',', $arrayPModes) . ');';

        return $szTypes . $szModes;
    }

    /**
     * Get the concatenated arguments for a function.
     *
     * @param \PHPPgAdmin\ADORecordSet $funcdata The funcdata record
     *
     * @return string The arguments of the function
     */
    private function _getPropertiesArgs($funcdata)
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($data->hasNamedParams()) {
            if (isset($funcdata->fields['proallarguments'])) {
                $args_arr = $data->phpArray($funcdata->fields['proallarguments']);
            } else {
                $args_arr = explode(', ', $funcdata->fields['proarguments']);
            }
            $names_arr     = $data->phpArray($funcdata->fields['proargnames']);
            $modes_arr     = $data->phpArray($funcdata->fields['proargmodes']);
            $args          = '';
            $args_arr_size = sizeof($args_arr);
            for ($i = 0; $i < $args_arr_size; ++$i) {
                if (0 != $i) {
                    $args .= ', ';
                }

                if (isset($modes_arr[$i])) {
                    switch ($modes_arr[$i]) {
                        case 'i':
                            $args .= ' IN ';

                            break;
                        case 'o':
                            $args .= ' OUT ';

                            break;
                        case 'b':
                            $args .= ' INOUT ';

                            break;
                        case 'v':
                            $args .= ' VARIADIC ';

                            break;
                        case 't':
                            $args .= ' TABLE ';

                            break;
                    }
                }
                if (isset($names_arr[$i]) && '' != $names_arr[$i]) {
                    $data->fieldClean($names_arr[$i]);
                    $args .= '"' . $names_arr[$i] . '" ';
                }
                $args .= $args_arr[$i];
            }
        } else {
            $args = $funcdata->fields['proarguments'];
        }

        return $args;
    }
}
