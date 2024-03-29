<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use ADORecordSet;
use PHPPgAdmin\Decorators\Decorator;
use Slim\Http\Response;

/**
 * Base controller class.
 */
class FunctionsController extends BaseController
{
    public $table_place = 'functions-functions';

    public $controller_title = 'strfunctions';

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

        $header_template = 'header_datatables.twig';
        $footer_template = 'footer.twig';
        \ob_start();

        switch ($this->action) {
            case 'save_create':
                if (null !== $this->getPostParam('cancel')) {
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
                if (null !== $this->getPostParam('drop')) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'save_edit':
                if (null !== $this->getPostParam('cancel')) {
                    $this->doDefault();
                } else {
                    $this->doSaveEdit();
                }

                break;
            case 'edit':
                $this->view->offsetSet('codemirror', true);

                $this->doEdit();

                break;
            case 'properties':
                $header_template = 'header_highlight.twig';
                $this->doProperties();

                break;
            case 'show':
                if (isset($_GET['function'], $_GET['function_oid'])) {
                    $header_template = 'header_highlight.twig';
                    $this->showDefinition();
                } else {
                    $this->doDefault();
                }

                break;

            default:
                $this->doDefault();

                break;
        }
        $output = \ob_get_clean();

        $this->printHeader($this->headerTitle(), null, true, $header_template);
        $this->printBody();
        echo $output;

        return $this->printFooter(true, $footer_template);
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
            'function' => [
                'title' => $this->lang['strfunction'],
                'field' => Decorator::field('proproto'),
                'url' => containerInstance()->getDestinationWithLastTab('function'),

                'vars' => ['function' => 'proproto', 'function_oid' => 'prooid'],
            ],
            'returns' => [
                'title' => $this->lang['strreturns'],
                'field' => Decorator::field('proreturns'),
            ],
            'owner' => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('proowner'),
            ],
            'proglanguage' => [
                'title' => $this->lang['strproglanguage'],
                'field' => Decorator::field('prolanguage'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('procomment'),
            ],
        ];
        $actions = [
            'multiactions' => [
                'keycols' => ['function' => 'proproto', 'function_oid' => 'prooid'],
                'url' => 'functions',
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'functions',
                        'urlvars' => [
                            'action' => 'edit',
                            'function' => Decorator::field('proproto'),
                            'function_oid' => Decorator::field('prooid'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'multiaction' => 'confirm_drop',
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'functions',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'function' => Decorator::field('proproto'),
                            'function_oid' => Decorator::field('prooid'),
                        ],
                    ],
                ],
            ],
            'privileges' => [
                'content' => $this->lang['strprivileges'],
                'attr' => [
                    'href' => [
                        'url' => 'privileges',
                        'urlvars' => [
                            'subject' => 'function',
                            'function' => Decorator::field('proproto'),
                            'function_oid' => Decorator::field('prooid'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($funcs)) {
            echo $this->printTable($funcs, $columns, $actions, $this->table_place, $this->lang['strnofunctions']);
        }

        return $this->_printNavLinks('functions-functions');
    }

    /**
     * Generate XML for the browser tree.
     *
     * @return Response|string
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $funcs = $data->getFunctions();

        $proto = Decorator::concat(Decorator::field('proname'), ' (', Decorator::field('proarguments'), ')');

        $reqvars = $this->misc->getRequestVars('function');

        $attrs = [
            'text' => $proto,
            'icon' => 'Function',
            'toolTip' => Decorator::field('procomment'),
            'action' => Decorator::redirecturl(
                'redirect',
                $reqvars,
                [
                    'action' => 'properties',
                    'function' => $proto,
                    'function_oid' => Decorator::field('prooid'),
                ]
            ),
        ];

        return $this->printTree($funcs, $attrs, 'functions');
    }

    /**
     * Function to save after editing a function.
     */
    public function doSaveEdit(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $fnlang = \mb_strtolower($_POST['original_lang']);

        if ('c' === $fnlang) {
            $def = [$_POST['formObjectFile'], $_POST['formLinkSymbol']];
        } elseif ('internal' === $fnlang) {
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
            $_POST['formCost'] ?? null,
            $_POST['formRows'] ?? 0,
            $_POST['formComment']
        );

        if (0 === $status) {
            // If function has had schema altered, need to change to the new schema
            // and reload the browser frame.
            if (!empty($_POST['formFuncSchema']) && ($_POST['formFuncSchema'] !== $_POST['original_schema'])) {
                // Jump them to the new function schema
                $this->misc->setCurrentSchema($_POST['formFuncSchema']);
                // Force a browser reload
                $this->view->setReloadBrowser(true);
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
        $this->printTabs('function', 'definition');
        $this->printTitle($this->lang['stralter'], 'pg.function.alter');
        $this->printMsg($msg);

        $fndata = $data->getFunction($_REQUEST['function_oid']);

        if (0 >= $fndata->RecordCount()) {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;

            return;
        }
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

        $args = $data->hasNamedParams() ? $this->_getNamedParamsArgs($data, $fndata) : $fndata->fields['proarguments'];

        echo '<form action="functions" method="post">' . \PHP_EOL;
        echo '<table style="width: 95%">' . \PHP_EOL;
        echo '<tr>' . \PHP_EOL;
        echo \sprintf(
            '<th class="data required">%s</th>',
            $this->lang['strschema']
        ) . \PHP_EOL;
        echo \sprintf(
            '<th class="data required">%s</th>',
            $this->lang['strfunction']
        ) . \PHP_EOL;
        echo \sprintf(
            '<th class="data">%s</th>',
            $this->lang['strarguments']
        ) . \PHP_EOL;
        echo \sprintf(
            '<th class="data required">%s</th>',
            $this->lang['strreturns']
        ) . \PHP_EOL;
        echo \sprintf(
            '<th class="data required">%s</th>',
            $this->lang['strproglanguage']
        ) . \PHP_EOL;
        echo '</tr>' . \PHP_EOL;

        echo '<tr>' . \PHP_EOL;
        echo '<td class="data1">';
        echo '<input type="hidden" name="original_schema" value="', \htmlspecialchars($fndata->fields['proschema']), '" />' . \PHP_EOL;

        if ($data->hasFunctionAlterSchema()) {
            $schemas = $data->getSchemas();
            echo '<select name="formFuncSchema">';

            while (!$schemas->EOF) {
                $schema = $schemas->fields['nspname'];
                echo '<option value="', \htmlspecialchars($schema), '"',
                ($schema === $_POST['formFuncSchema']) ? ' selected="selected"' : '', '>', \htmlspecialchars($schema), '</option>' . \PHP_EOL;
                $schemas->MoveNext();
            }
            echo '</select>' . \PHP_EOL;
        } else {
            echo $fndata->fields['proschema'];
        }

        echo '</td>' . \PHP_EOL;
        echo '<td class="data1">';
        echo '<input type="hidden" name="original_function" value="', \htmlspecialchars($fndata->fields['proname']), '" />' . \PHP_EOL;
        echo \sprintf(
            '<input name="formFunction" style="width: 100%; box-sizing: border-box;" maxlength="%s" value="',
            $data->_maxNameLen
        ), \htmlspecialchars($_POST['formFunction']), '" />';
        echo '</td>' . \PHP_EOL;

        echo '<td class="data1">', $this->misc->printVal($args), \PHP_EOL;
        echo '<input type="hidden" name="original_arguments" value="', \htmlspecialchars($args), '" />' . \PHP_EOL;
        echo '</td>' . \PHP_EOL;

        echo '<td class="data1">';

        if ($fndata->fields['proretset']) {
            echo 'setof ';
        }

        echo $this->misc->printVal($fndata->fields['proresult']), \PHP_EOL;
        echo '<input type="hidden" name="original_returns" value="', \htmlspecialchars($fndata->fields['proresult']), '" />' . \PHP_EOL;

        if ($fndata->fields['proretset']) {
            echo '<input type="hidden" name="original_setof" value="yes" />' . \PHP_EOL;
        }

        echo '</td>' . \PHP_EOL;

        echo '<td class="data1">', $this->misc->printVal($fndata->fields['prolanguage']), \PHP_EOL;
        echo '<input type="hidden" name="original_lang" value="', \htmlspecialchars($fndata->fields['prolanguage']), '" />' . \PHP_EOL;
        echo '</td>' . \PHP_EOL;
        echo '</tr>' . \PHP_EOL;

        $fnlang = \mb_strtolower($fndata->fields['prolanguage']);

        if ('c' === $fnlang) {
            echo \sprintf(
                '<tr><th class="data required" colspan="2">%s</th>',
                $this->lang['strobjectfile']
            ) . \PHP_EOL;
            echo \sprintf(
                '<th class="data" colspan="2">%s</th></tr>',
                $this->lang['strlinksymbol']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="2"><input type="text" name="formObjectFile" style="width:100%" value="',
            \htmlspecialchars($_POST['formObjectFile']), '" /></td>' . \PHP_EOL;
            echo '<td class="data1" colspan="2"><input type="text" name="formLinkSymbol" style="width:100%" value="',
            \htmlspecialchars($_POST['formLinkSymbol']), '" /></td></tr>' . \PHP_EOL;
        } elseif ('internal' === $fnlang) {
            echo \sprintf(
                '<tr><th class="data" colspan="5">%s</th></tr>',
                $this->lang['strlinksymbol']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="5"><input type="text" name="formLinkSymbol" style="width:100%" value="',
            \htmlspecialchars($_POST['formLinkSymbol']), '" /></td></tr>' . \PHP_EOL;
        } else {
            echo \sprintf(
                '<tr><th class="data required" colspan="5">%s</th></tr>',
                $this->lang['strdefinition']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="5">';
            $textarea_id = ('sql' === $fnlang || 'plpgsql' === $fnlang) ? 'query' : 'formDefinition';
            echo '<textarea style="width:100%;" rows="20" cols="50" id="' . $textarea_id . '" name="formDefinition">';
            echo \htmlspecialchars($_POST['formDefinition']);
            echo '</textarea></td></tr>' . \PHP_EOL;
        }

        // Display function comment
        echo \sprintf(
            '<tr><th class="data" colspan="5">%s</th></tr>',
            $this->lang['strcomment']
        ) . \PHP_EOL;
        echo '<tr><td class="data1" colspan="5">';
        echo '<textarea style="width:100%;" name="formComment" rows="3" cols="50">';
        echo \htmlspecialchars($_POST['formComment']);
        echo '</textarea></td></tr>' . \PHP_EOL;

        // Display function cost options
        if ($data->hasFunctionCosting()) {
            echo \sprintf(
                '<tr><th class="data required" colspan="5">%s</th></tr>',
                $this->lang['strfunctioncosting']
            ) . \PHP_EOL;
            echo \sprintf(
                '<td class="data1" colspan="2">%s: <input name="formCost" size="16" value="',
                $this->lang['strexecutioncost']
            ) .
            \htmlspecialchars($_POST['formCost']) . '" /></td>';
            echo \sprintf(
                '<td class="data1" colspan="2">%s: <input name="formRows" size="16" value="',
                $this->lang['strresultrows']
            ),
            \htmlspecialchars($_POST['formRows']), '"', ($fndata->fields['proretset']) ? '' : 'disabled', '/></td>';
        }

        // Display function properties
        if (\is_array($data->funcprops) && 0 < \count($data->funcprops)) {
            echo \sprintf(
                '<tr><th class="data" colspan="5">%s</th></tr>',
                $this->lang['strproperties']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="5">' . \PHP_EOL;
            $i = 0;

            foreach ($data->funcprops as $k => $v) {
                echo \sprintf(
                    '<select name="formProperties[%s]">',
                    $i
                ) . \PHP_EOL;

                foreach ($v as $p) {
                    echo '<option value="', \htmlspecialchars($p), '"',
                    ($_POST['formProperties'][$i] === $p) ? ' selected="selected"' : '',
                    '>', $this->misc->printVal($p), '</option>' . \PHP_EOL;
                }
                echo '</select><br />' . \PHP_EOL;
                ++$i;
            }
            echo '</td></tr>' . \PHP_EOL;
        }

        // function owner
        if ($data->hasFunctionAlterOwner()) {
            $users = $data->getUsers();
            echo \sprintf(
                '<tr><td class="data1" colspan="5">%s: <select name="formFuncOwn">',
                $this->lang['strowner']
            );

            while (!$users->EOF) {
                $uname = $users->fields['usename'];
                echo '<option value="', \htmlspecialchars($uname), '"',
                ($uname === $_POST['formFuncOwn']) ? ' selected="selected"' : '', '>', \htmlspecialchars($uname), '</option>' . \PHP_EOL;
                $users->MoveNext();
            }
            echo '</select>' . \PHP_EOL;
            echo '<input type="hidden" name="original_owner" value="', \htmlspecialchars($fndata->fields['proowner']), '" />' . \PHP_EOL;
            echo '</td></tr>' . \PHP_EOL;
        }
        echo '</table>' . \PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_edit" />' . \PHP_EOL;
        echo '<input type="hidden" name="function" value="', \htmlspecialchars($_REQUEST['function']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="function_oid" value="', \htmlspecialchars($_REQUEST['function_oid']), '" />' . \PHP_EOL;
        echo $this->view->form;
        echo \sprintf(
            '<input type="submit" value="%s" />',
            $this->lang['stralter']
        ) . \PHP_EOL;
        echo \sprintf(
            '<input type="submit" name="cancel" value="%s"  /></p>%s',
            $this->lang['strcancel'],
            \PHP_EOL
        );
        echo '</form>' . \PHP_EOL;
    }

    /**
     * Show the creation sentence for this function.
     *
     * @param string $fname        The function name
     * @param int    $function_oid The function oid
     */
    public function showDefinition($fname, $function_oid)
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('function');
        $this->printTabs('function', 'export');
        $this->printTitle($this->lang['strproperties'], 'pg.function');

        $fname = \str_replace(' ', '', $f);
        $funcdata = $data->getFunctionDef($function_oid);
        $func_full = '';

        if (0 >= $funcdata->RecordCount()) {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;

            return $this->_printNavLinks('functions-properties', $func_full);
        }

        echo '<table style="width: 95%">' . \PHP_EOL;

        $fnlang = \mb_strtolower($funcdata->fields['prolanguage']);
        echo '<tr><td class="data1" colspan="4">';
        echo \sprintf(
            '<pre><code class="sql hljs">%s',
            \PHP_EOL
        );

        echo \sprintf(
            '%s--%s',
            \PHP_EOL,
            \PHP_EOL
        );
        echo \sprintf(
            '-- Name: %s; Type: FUNCTION; Schema: %s; Owner: %s',
            $fname,
            $funcdata->fields['nspname'],
            $funcdata->fields['relowner']
        );
        echo \sprintf(
            '%s--%s%s',
            \PHP_EOL,
            \PHP_EOL,
            \PHP_EOL
        );

        echo \sprintf(
            '%s;',
            $funcdata->fields['pg_get_functiondef']
        );

        echo \sprintf(
            '%s%sALTER FUNCTION %s OWNER TO %s;%s',
            \PHP_EOL,
            \PHP_EOL,
            $fname,
            $funcdata->fields['relowner'],
            \PHP_EOL
        );

        // Show comment if any
        if (null !== $funcdata->fields['relcomment']) {
            echo \sprintf(
                '%s--%s',
                \PHP_EOL,
                \PHP_EOL
            );
            echo \sprintf(
                '-- Name: %s; Type: COMMENT; Schema: %s; Owner: %s',
                $fname,
                $funcdata->fields['nspname'],
                $funcdata->fields['relowner']
            );
            echo \sprintf(
                '%s--%s%s',
                \PHP_EOL,
                \PHP_EOL,
                \PHP_EOL
            );
            echo \sprintf("%sCOMMENT ON FUNCTION %s.%s IS '%s';%s", \PHP_EOL, $funcdata->fields['nspname'], $fname, $funcdata->fields['relcomment'], \PHP_EOL);
            //echo '<p class="comment">', $this->misc->printVal($funcdata->fields['relcomment']), '</p>' . PHP_EOL;
        }

        echo \sprintf(
            '%s</code></pre>',
            \PHP_EOL
        );

        echo '</td></tr>' . \PHP_EOL;

        echo '</table>' . \PHP_EOL;

        return $this->_printNavLinks('functions-properties', $func_full);
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
        $this->printTabs('function', 'definition');
        $this->printTitle($this->lang['strproperties'], 'pg.function');
        $this->printMsg($msg);

        $funcdata = $data->getFunction($_REQUEST['function_oid']);
        $func_full = '';

        if (0 >= $funcdata->RecordCount()) {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;

            return $this->_printNavLinks('functions-properties', $func_full);
        }
        // Deal with named parameters
        $args = $this->_getPropertiesArgs($funcdata);

        // Show comment if any
        if (null !== $funcdata->fields['procomment']) {
            echo '<p class="comment">', $this->misc->printVal($funcdata->fields['procomment']), '</p>' . \PHP_EOL;
        }

        $funcdata->fields['proretset'] = $data->phpBool($funcdata->fields['proretset']);
        $func_full = $funcdata->fields['proname'] . '(' . $funcdata->fields['proarguments'] . ')';

        echo '<table style="width: 95%">' . \PHP_EOL;

        echo \sprintf(
            '<tr><th class="data">%s</th>%s',
            $this->lang['strfunction'],
            \PHP_EOL
        );
        echo \sprintf(
            '<th class="data">%s</th>%s',
            $this->lang['strarguments'],
            \PHP_EOL
        );
        echo \sprintf(
            '<th class="data">%s</th>%s',
            $this->lang['strreturns'],
            \PHP_EOL
        );
        echo \sprintf(
            '<th class="data">%s</th></tr>%s',
            $this->lang['strproglanguage'],
            \PHP_EOL
        );

        echo '<tr><td class="data1">', $this->misc->printVal($funcdata->fields['proname']), '</td>' . \PHP_EOL;
        echo '<td class="data1">', $this->misc->printVal($args), '</td>' . \PHP_EOL;
        echo '<td class="data1">';

        if ($funcdata->fields['proretset']) {
            echo 'setof ';
        }

        echo $this->misc->printVal($funcdata->fields['proresult']), '</td>' . \PHP_EOL;
        echo '<td class="data1">', $this->misc->printVal($funcdata->fields['prolanguage']), '</td></tr>' . \PHP_EOL;

        $fnlang = \mb_strtolower($funcdata->fields['prolanguage']);

        if ('c' === $fnlang) {
            echo \sprintf(
                '<tr><th class="data" colspan="2">%s</th>',
                $this->lang['strobjectfile']
            ) . \PHP_EOL;
            echo \sprintf(
                '<th class="data" colspan="2">%s</th></tr>',
                $this->lang['strlinksymbol']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="2">', $this->misc->printVal($funcdata->fields['probin']), '</td>' . \PHP_EOL;
            echo '<td class="data1" colspan="2">', $this->misc->printVal($funcdata->fields['prosrc']), '</td></tr>' . \PHP_EOL;
        } elseif ('internal' === $fnlang) {
            echo \sprintf(
                '<tr><th class="data" colspan="4">%s</th></tr>',
                $this->lang['strlinksymbol']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="4">', $this->misc->printVal($funcdata->fields['prosrc']), '</td></tr>' . \PHP_EOL;
        } else {
            echo '<tr><td class="data1" colspan="4">';
            echo \sprintf(
                '<pre><code class="%s hljs">%s</code></pre>',
                $fnlang,
                $funcdata->fields['prosrc']
            );
            echo '</td></tr>' . \PHP_EOL;
        }

        // Display function cost options
        if ($data->hasFunctionCosting()) {
            echo \sprintf(
                '<tr><th class="data required" colspan="4">%s</th></tr>',
                $this->lang['strfunctioncosting']
            ) . \PHP_EOL;
            echo \sprintf(
                '<td class="data1" colspan="2">%s: ',
                $this->lang['strexecutioncost']
            ), $this->misc->printVal($funcdata->fields['procost']), ' </td>';
            echo \sprintf(
                '<td class="data1" colspan="2">%s: ',
                $this->lang['strresultrows']
            ), $this->misc->printVal($funcdata->fields['prorows']), ' </td>';
        }

        // Show flags
        if (\is_array($data->funcprops) && 0 < \count($data->funcprops)) {
            // Fetch an array of the function properties
            $funcprops = $data->getFunctionProperties($funcdata->fields);
            echo \sprintf(
                '<tr><th class="data" colspan="4">%s</th></tr>',
                $this->lang['strproperties']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="4">' . \PHP_EOL;

            foreach ($funcprops as $v) {
                echo $this->misc->printVal($v), '<br />' . \PHP_EOL;
            }
            echo '</td></tr>' . \PHP_EOL;
        }

        echo \sprintf(
            '<tr><td class="data1" colspan="5">%s: ',
            $this->lang['strowner']
        ), \htmlspecialchars($funcdata->fields['proowner']), \PHP_EOL;
        echo '</td></tr>' . \PHP_EOL;
        echo '</table>' . \PHP_EOL;

        return $this->_printNavLinks('functions-properties', $func_full);
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
            $this->printTrail('function');
            $this->printTabs('function', 'definition');
            $this->printTitle($this->lang['strdrop'], 'pg.function.drop');
            echo '<form action="functions" method="post">' . \PHP_EOL;
            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    echo '<p>', \sprintf(
                        $this->lang['strconfdropfunction'],
                        $this->misc->printVal($a['function'])
                    ), '</p>' . \PHP_EOL;
                    echo '<input type="hidden" name="function[]" value="', \htmlspecialchars($a['function']), '" />' . \PHP_EOL;
                    echo '<input type="hidden" name="function_oid[]" value="', \htmlspecialchars($a['function_oid']), '" />' . \PHP_EOL;
                }
            } else {
                echo '<p>', \sprintf(
                    $this->lang['strconfdropfunction'],
                    $this->misc->printVal($_REQUEST['function'])
                ), '</p>' . \PHP_EOL;
                echo '<input type="hidden" name="function" value="', \htmlspecialchars($_REQUEST['function']), '" />' . \PHP_EOL;
                echo '<input type="hidden" name="function_oid" value="', \htmlspecialchars($_REQUEST['function_oid']), '" />' . \PHP_EOL;
            }
            echo '<input type="hidden" name="action" value="drop" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<p><input type="checkbox" id="cascade" name="cascade" /><label for="cascade">%s</label></p>',
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
            echo '</form>' . \PHP_EOL;
        } elseif (\is_array($_POST['function_oid'])) {
            $msg = '';
            $status = $data->beginTransaction();

            if (0 === $status) {
                foreach ($_POST['function_oid'] as $k => $s) {
                    $status = $data->dropFunction($s, isset($_POST['cascade']));

                    if (0 === $status) {
                        $msg .= \sprintf(
                            '%s: %s<br />',
                            \htmlentities($_POST['function'][$k], \ENT_QUOTES, 'UTF-8'),
                            $this->lang['strfunctiondropped']
                        );
                    } else {
                        $data->endTransaction();
                        $this->doDefault(\sprintf(
                            '%s%s: %s<br />',
                            $msg,
                            \htmlentities($_POST['function'][$k], \ENT_QUOTES, 'UTF-8'),
                            $this->lang['strfunctiondroppedbad']
                        ));

                        return;
                    }
                }
            }

            if (0 === $data->endTransaction()) {
                // Everything went fine, back to the Default page....
                $this->view->setReloadBrowser(true);
                $this->doDefault($msg);
            } else {
                $this->doDefault($this->lang['strfunctiondroppedbad']);
            }
        } else {
            $status = $data->dropFunction($_POST['function_oid'], isset($_POST['cascade']));

            if (0 === $status) {
                $this->view->setReloadBrowser(true);
                $this->doDefault($this->lang['strfunctiondropped']);
            } else {
                $this->doDefault($this->lang['strfunctiondroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where they can enter a new function.
     *
     * @param string $msg  message to display
     * @param mixed  $szJS
     */
    public function doCreate($msg = '', $szJS = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $_POST['formFunction'] = $this->getPostParam('formFunction', '');

        $_POST['formArguments'] = $this->getPostParam('formArguments', '');

        $_POST['formReturns'] = $this->getPostParam('formReturns', '');

        $this->coalesceArr($_POST, 'formLanguage', $_REQUEST['language'] ?? 'sql');

        $_POST['formDefinition'] = $this->getPostParam('formDefinition', '');

        $_POST['formObjectFile'] = $this->getPostParam('formObjectFile', '');

        $_POST['formLinkSymbol'] = $this->getPostParam('formLinkSymbol', '');

        $_POST['formProperties'] = $this->getPostParam('formProperties', $data->defaultprops);

        $_POST['formSetOf'] = $this->getPostParam('formSetOf', '');

        $_POST['formArray'] = $this->getPostParam('formArray', '');

        $_POST['formCost'] = $this->getPostParam('formCost', '');

        $_POST['formRows'] = $this->getPostParam('formRows', '');

        $_POST['formComment'] = $this->getPostParam('formComment', '');

        $types = $data->getTypes(true, true, true);
        $langs = $data->getLanguages(true);
        $fnlang = \mb_strtolower($_POST['formLanguage']);

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

            if ($types->fields['typname'] === $_POST['formReturns']) {
                $szSelected = ' selected="selected"';
            }
            // this variable is include in the JS code bellow, so we need to ENT_QUOTES
            $szTypes .= '<option value="' . \htmlspecialchars($types->fields['typname'], \ENT_QUOTES) . \sprintf(
                '"%s>',
                $szSelected
            );
            $szTypes .= \htmlspecialchars($types->fields['typname'], \ENT_QUOTES) . '</option>';
            $types->MoveNext();
        }

        $szFunctionName = \sprintf(
            '<td class="data1"><input name="formFunction" size="16" maxlength="%s" value="',
            $data->_maxNameLen
        ) .
        \htmlspecialchars($_POST['formFunction']) . '" /></td>';

        $szArguments = '<td class="data1"><input name="formArguments" style="width:100%;" size="16" value="' .
        \htmlspecialchars($_POST['formArguments']) . '" /></td>';

        $szSetOfSelected = '';
        $szNotSetOfSelected = '';

        if ('' === $_POST['formSetOf']) {
            $szNotSetOfSelected = ' selected="selected"';
        } elseif ('SETOF' === $_POST['formSetOf']) {
            $szSetOfSelected = ' selected="selected"';
        }
        $szReturns = '<td class="data1" colspan="2">';
        $szReturns .= '<select name="formSetOf">';
        $szReturns .= \sprintf(
            '<option value=""%s></option>',
            $szNotSetOfSelected
        );
        $szReturns .= \sprintf(
            '<option value="SETOF"%s>SETOF</option>',
            $szSetOfSelected
        );
        $szReturns .= '</select>';

        $szReturns .= '<select class="select2" name="formReturns">' . $szTypes . '</select>';

        // Create string array type selector

        $szArraySelected = '';
        $szNotArraySelected = '';

        if ('' === $_POST['formArray']) {
            $szNotArraySelected = ' selected="selected"';
        } elseif ('[]' === $_POST['formArray']) {
            $szArraySelected = ' selected="selected"';
        }

        $szReturns .= '<select name="formArray">';
        $szReturns .= \sprintf(
            '<option value=""%s></option>',
            $szNotArraySelected
        );
        $szReturns .= \sprintf(
            '<option value="[]"%s>[ ]</option>',
            $szArraySelected
        );
        $szReturns .= "</select>\n</td>";

        // Create string for language
        $szLanguage = '<td class="data1">';

        if ('c' === $fnlang || 'internal' === $fnlang) {
            $szLanguage .= $_POST['formLanguage'] . \PHP_EOL;
            $szLanguage .= \sprintf(
                '<input type="hidden" name="formLanguage" value="%s" />',
                $_POST['formLanguage']
            ) . \PHP_EOL;
        } else {
            $szLanguage .= '<select name="formLanguage">' . \PHP_EOL;

            while (!$langs->EOF) {
                $szSelected = '';

                if ($langs->fields['lanname'] === $_POST['formLanguage']) {
                    $szSelected = ' selected="selected"';
                }

                if ('c' !== \mb_strtolower($langs->fields['lanname']) && 'internal' !== \mb_strtolower($langs->fields['lanname'])) {
                    $szLanguage .= '<option value="' . \htmlspecialchars($langs->fields['lanname']) . \sprintf(
                        '"%s>
',
                        $szSelected
                    ) .
                    $this->misc->printVal($langs->fields['lanname']) . '</option>';
                }

                $langs->MoveNext();
            }
            $szLanguage .= '</select>' . \PHP_EOL;
        }

        $szLanguage .= '</td>';
        $szJSArguments = \sprintf(
            '<tr><th class="data" colspan="7">%s</th></tr>',
            $this->lang['strarguments']
        );
        $arrayModes = ['IN', 'OUT', 'INOUT'];
        $szModes = '<select name="formArgModes[]" style="width:100%;">';

        foreach ($arrayModes as $pV) {
            $szModes .= \sprintf(
                '<option value="%s">%s</option>',
                $pV,
                $pV
            );
        }
        $szModes .= '</select>';
        $szArgReturns = '<select name="formArgArray[]">';
        $szArgReturns .= '<option value=""></option>';
        $szArgReturns .= '<option value="[]">[]</option>';
        $szArgReturns .= '</select>';
        $subfolder = \containerInstance()->subFolder;

        if (!empty($this->conf['theme'])) {
            $szImgPath = \containerInstance()->subFolder . \sprintf(
                '/assets/images/themes/%s',
                $this->conf['theme']
            );
        } else {
            $szImgPath = \containerInstance()->subFolder . '/assets/images/themes/default';
        }

        if (empty($msg)) {
            $szJSTRArg = \sprintf(
                '<script type="text/javascript" >addArg(\'%s\');</script>',
                $subfolder
            ) . \PHP_EOL;
        } else {
            $szJSTRArg = '';
        }
        $szJSAddTR = \sprintf(
            '<tr id="parent_add_tr" onclick="addArg(\'%s\');" onmouseover="this.style.cursor=\'pointer\'">',
            $subfolder
        ) . \PHP_EOL;
        $szJSAddTR .= '<td style="text-align: right" colspan="6" class="data3"><table><tr><td class="data3">';
        $szJSAddTR .= \sprintf(
            '<img src="%s/AddArguments.png" alt="Add Argument" /></td>',
            $szImgPath
        );
        $szJSAddTR .= \sprintf(
            '<td class="data3"><span style="font-size: 8pt">%s</span></td></tr></table></td>
</tr>',
            $this->lang['strargadd']
        ) . \PHP_EOL;

        echo '<script src="' . \containerInstance()->subFolder . \sprintf(
            '/assets/js/functions.js" type="text/javascript"></script>
		<script type="text/javascript">
			//<![CDATA[
			var g_types_select = \'<select class="select2" name="formArgType[]">%s</select>%s\';
			var g_modes_select = \'%s\';
			var g_name = \'\';
			var g_lang_strargremove = \'',
            $szTypes,
            $szArgReturns,
            $szModes
        ), \htmlspecialchars($this->lang['strargremove'], \ENT_QUOTES), "';
			var g_lang_strargnoargs = '", \htmlspecialchars($this->lang['strargnoargs'], \ENT_QUOTES), "';
			var g_lang_strargenableargs = '", \htmlspecialchars($this->lang['strargenableargs'], \ENT_QUOTES), "';
			var g_lang_strargnorowabove = '", \htmlspecialchars($this->lang['strargnorowabove'], \ENT_QUOTES), "';
			var g_lang_strargnorowbelow = '", \htmlspecialchars($this->lang['strargnorowbelow'], \ENT_QUOTES), "';
			var g_lang_strargremoveconfirm = '", \htmlspecialchars($this->lang['strargremoveconfirm'], \ENT_QUOTES), "';
			var g_lang_strargraise = '", \htmlspecialchars($this->lang['strargraise'], \ENT_QUOTES), "';
			var g_lang_strarglower = '", \htmlspecialchars($this->lang['strarglower'], \ENT_QUOTES), "';
			//]]>
		</script>
		";
        echo '<form action="functions" method="post">' . \PHP_EOL;
        echo '<table><tbody id="args_table">' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data required">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '<th class="data required" colspan="2">%s</th>',
            $this->lang['strreturns']
        ) . \PHP_EOL;
        echo \sprintf(
            '<th class="data required">%s</th></tr>',
            $this->lang['strproglanguage']
        ) . \PHP_EOL;
        echo '<tr>' . \PHP_EOL;
        echo $szFunctionName . \PHP_EOL;
        echo $szReturns . \PHP_EOL;
        echo $szLanguage . \PHP_EOL;
        echo '</tr>' . \PHP_EOL;
        echo $szJSArguments . \PHP_EOL;
        echo '<tr>' . \PHP_EOL;
        echo \sprintf(
            '<th class="data required">%s</th>',
            $this->lang['strargmode']
        ) . \PHP_EOL;
        echo \sprintf(
            '<th class="data required">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '<th class="data required" colspan="2">%s</th>',
            $this->lang['strargtype']
        ) . \PHP_EOL;
        echo '</tr>' . \PHP_EOL;
        echo $szJSAddTR . \PHP_EOL;

        if ('c' === $fnlang) {
            echo \sprintf(
                '<tr><th class="data required" colspan="2">%s</th>',
                $this->lang['strobjectfile']
            ) . \PHP_EOL;
            echo \sprintf(
                '<th class="data" colspan="2">%s</th></tr>',
                $this->lang['strlinksymbol']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="2"><input type="text" name="formObjectFile" style="width:100%" value="',
            \htmlspecialchars($_POST['formObjectFile']), '" /></td>' . \PHP_EOL;
            echo '<td class="data1" colspan="2"><input type="text" name="formLinkSymbol" style="width:100%" value="',
            \htmlspecialchars($_POST['formLinkSymbol']), '" /></td></tr>' . \PHP_EOL;
        } elseif ('internal' === $fnlang) {
            echo \sprintf(
                '<tr><th class="data" colspan="4">%s</th></tr>',
                $this->lang['strlinksymbol']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="4"><input type="text" name="formLinkSymbol" style="width:100%" value="',
            \htmlspecialchars($_POST['formLinkSymbol']), '" /></td></tr>' . \PHP_EOL;
        } else {
            echo \sprintf(
                '<tr><th class="data required" colspan="4">%s</th></tr>',
                $this->lang['strdefinition']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="4">';
            echo '<textarea style="width:100%;" rows="20" cols="50" name="formDefinition">';
            echo \htmlspecialchars($_POST['formDefinition']);
            echo '</textarea></td></tr>' . \PHP_EOL;
        }

        // Display function comment
        echo \sprintf(
            '<tr><th class="data" colspan="4">%s</th></tr>',
            $this->lang['strcomment']
        ) . \PHP_EOL;
        echo '<tr><td class="data1" colspan="4"><textarea style="width:100%;" name="formComment" rows="3" cols="50">',
        \htmlspecialchars($_POST['formComment']), '</textarea></td></tr>' . \PHP_EOL;

        // Display function cost options
        if ($data->hasFunctionCosting()) {
            echo \sprintf(
                '<tr><th class="data required" colspan="4">%s</th></tr>',
                $this->lang['strfunctioncosting']
            ) . \PHP_EOL;
            echo \sprintf(
                '<td class="data1" colspan="2">%s: <input name="formCost" size="16" value="',
                $this->lang['strexecutioncost']
            ) .
            \htmlspecialchars($_POST['formCost']) . '" /></td>';
            echo \sprintf(
                '<td class="data1" colspan="2">%s: <input name="formRows" size="16" value="',
                $this->lang['strresultrows']
            ) .
            \htmlspecialchars($_POST['formRows']) . '" /></td>';
        }

        // Display function properties
        if (\is_array($data->funcprops) && 0 < \count($data->funcprops)) {
            echo \sprintf(
                '<tr><th class="data required" colspan="4">%s</th></tr>',
                $this->lang['strproperties']
            ) . \PHP_EOL;
            echo '<tr><td class="data1" colspan="4">' . \PHP_EOL;
            $i = 0;

            foreach ($data->funcprops as $k => $v) {
                echo \sprintf(
                    '<select name="formProperties[%s]">',
                    $i
                ) . \PHP_EOL;

                foreach ($v as $p) {
                    echo '<option value="', \htmlspecialchars($p), '"',
                    ($_POST['formProperties'][$i] === $p) ? ' selected="selected"' : '',
                    '>', $this->misc->printVal($p), '</option>' . \PHP_EOL;
                }
                echo '</select><br />' . \PHP_EOL;
                ++$i;
            }
            echo '</td></tr>' . \PHP_EOL;
        }
        echo '</tbody></table>' . \PHP_EOL;
        echo $szJSTRArg;
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
        echo $szJS;
    }

    /**
     * Actually creates the new function in the database.
     */
    public function doSaveCreate(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $fnlang = \mb_strtolower($_POST['formLanguage']);

        if ('c' === $fnlang) {
            $def = [$_POST['formObjectFile'], $_POST['formLinkSymbol']];
        } elseif ('internal' === $fnlang) {
            $def = $_POST['formLinkSymbol'];
        } else {
            $def = $_POST['formDefinition'];
        }

        $szJS = '';

        echo '<script src="assets/js/functions.js" type="text/javascript"></script>';
        echo '<script type="text/javascript">' . $this->_buildJSData() . '</script>';

        if (!empty($_POST['formArgName'])) {
            $szJS = $this->_buildJSRows($this->_buildFunctionArguments($_POST));
        } else {
            $subfolder = \containerInstance()->subFolder;
            $szJS = '<script type="text/javascript" src="' . \containerInstance()->subFolder . '/assets/js/functions.js">noArgsRebuild(addArg("' . $subfolder . '"));</script>';
        }

        $cost = (isset($_POST['formCost'])) ? $_POST['formCost'] : null;

        if ('' === $cost || !\is_numeric($cost) || (int) $cost !== $cost || 0 > $cost) {
            $cost = null;
        }

        $rows = (isset($_POST['formRows'])) ? $_POST['formRows'] : null;

        if ('' === $rows || !\is_numeric($rows) || (int) $rows !== $rows) {
            $rows = null;
        }

        // Check that they've given a name and a definition
        if ('' === $_POST['formFunction']) {
            $this->doCreate($this->lang['strfunctionneedsname'], $szJS);
        } elseif ('internal' !== $fnlang && !$def) {
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
                'SETOF' === $_POST['formSetOf'],
                $cost,
                $rows,
                $_POST['formComment'],
                false
            );

            if (0 === $status) {
                $this->doDefault($this->lang['strfunctioncreated']);
            } else {
                $this->doCreate($this->lang['strfunctioncreatedbad'], $szJS);
            }
        }
    }

    private function _printNavLinks(string $place, string $func_full = '')
    {
        if ('functions-properties' === $place) {
            $navlinks = [
                'showall' => [
                    'attr' => [
                        'href' => [
                            'url' => 'functions',
                            'urlvars' => [
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strshowallfunctions'],
                ],
                'alter' => [
                    'attr' => [
                        'href' => [
                            'url' => 'functions',
                            'urlvars' => [
                                'action' => 'edit',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                                'function' => $_REQUEST['function'],
                                'function_oid' => $_REQUEST['function_oid'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['stralter'],
                ],
                'drop' => [
                    'attr' => [
                        'href' => [
                            'url' => 'functions',
                            'urlvars' => [
                                'action' => 'confirm_drop',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                                'function' => $func_full,
                                'function_oid' => $_REQUEST['function_oid'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strdrop'],
                ],
            ];
        } elseif ('functions-functions' === $place) {
            $navlinks = [
                'createpl' => [
                    'attr' => [
                        'href' => [
                            'url' => 'functions',
                            'urlvars' => [
                                'action' => 'create',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strcreateplfunction'],
                ],
                'createinternal' => [
                    'attr' => [
                        'href' => [
                            'url' => 'functions',
                            'urlvars' => [
                                'action' => 'create',
                                'language' => 'internal',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strcreateinternalfunction'],
                ],
                'createc' => [
                    'attr' => [
                        'href' => [
                            'url' => 'functions',
                            'urlvars' => [
                                'action' => 'create',
                                'language' => 'C',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strcreatecfunction'],
                ],
            ];
        } else {
            return '';
        }

        return $this->printNavLinks($navlinks, $place, \get_defined_vars());
    }

    /**
     * @param mixed $data
     * @param mixed $fndata
     *
     * @return string
     */
    private function _getNamedParamsArgs($data, $fndata)
    {
        if (isset($fndata->fields['proallarguments'])) {
            $args_arr = $data->phpArray($fndata->fields['proallarguments']);
        } else {
            $args_arr = \explode(', ', $fndata->fields['proarguments']);
        }
        $names_arr = $data->phpArray($fndata->fields['proargnames']);
        $modes_arr = $data->phpArray($fndata->fields['proargmodes']);
        $args = '';
        $args_arr_size = \count($args_arr);

        for ($i = 0; $i < $args_arr_size; ++$i) {
            if (0 !== $i) {
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

            if (isset($names_arr[$i]) && '' !== $names_arr[$i]) {
                $data->fieldClean($names_arr[$i]);
                $args .= '"' . $names_arr[$i] . '" ';
            }
            $args .= $args_arr[$i];
        }

        return $args;
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
                $arrayArgs[] = $arrayVars['formArgModes'][$pK] . ' ' . \trim($pV) . ' ' . \trim($arrayVars['formArgType'][$pK]) . $arrayVars['formArgArray'][$pK];
            }

            return \implode(',', $arrayArgs);
        }

        return '';
    }

    /**
     * Build out JS to re-create table rows for arguments.
     *
     * @param string $szArgs args to parse
     *
     * @return string
     */
    private function _buildJSRows($szArgs)
    {
        $arrayModes = ['IN', 'OUT', 'INOUT'];
        $arrayArgs = \explode(',', $szArgs);
        $arrayProperArgs = [];
        $nC = 0;
        $szReturn = '';
        $szMode = [];

        foreach ($arrayArgs as $pV) {
            $arrayWords = \explode(' ', $pV);

            if (\in_array($arrayWords[0], $arrayModes, true)) {
                $szMode = $arrayWords[0];
                \array_shift($arrayWords);
            }
            $szArgName = \array_shift($arrayWords);

            if (false === \mb_strpos($arrayWords[\count($arrayWords) - 1], '[]')) {
                $szArgType = \implode(' ', $arrayWords);
                $bArgIsArray = 'false';
            } else {
                $szArgType = \str_replace('[]', '', \implode(' ', $arrayWords));
                $bArgIsArray = 'true';
            }
            $arrayProperArgs[] = [$szMode, $szArgName, $szArgType, $bArgIsArray];
            $subfolder = \containerInstance()->subFolder;
            $szReturn .= '<script type="text/javascript">';
            $szReturn .= \sprintf(
                'RebuildArgTR(\'%s\',\'%s\',\'%s\',new Boolean(%s,%s));',
                $szMode,
                $szArgName,
                $szArgType,
                $bArgIsArray,
                $subfolder
            );
            $szReturn .= '</script>;';
            ++$nC;
        }

        return $szReturn;
    }

    /**
     * @return string
     */
    private function _buildJSData()
    {
        $data = $this->misc->getDatabaseAccessor();

        $arrayModes = ['IN', 'OUT', 'INOUT'];
        $arrayTypes = $data->getTypes(true, true, true);
        $arrayPTypes = [];
        $arrayPModes = [];

        while (!$arrayTypes->EOF) {
            $arrayPTypes[] = "'" . $arrayTypes->fields['typname'] . "'";
            $arrayTypes->MoveNext();
        }

        foreach ($arrayModes as $pV) {
            $arrayPModes[] = \sprintf(
                '\'%s\'',
                $pV
            );
        }

        $szTypes = 'g_main_types = new Array(' . \implode(',', $arrayPTypes) . ');';
        $szModes = 'g_main_modes = new Array(' . \implode(',', $arrayPModes) . ');';

        return $szTypes . $szModes;
    }

    /**
     * Get the concatenated arguments for a function.
     *
     * @param ADORecordSet $funcdata The funcdata record
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
                $args_arr = \explode(', ', $funcdata->fields['proarguments']);
            }
            $names_arr = $data->phpArray($funcdata->fields['proargnames']);
            $modes_arr = $data->phpArray($funcdata->fields['proargmodes']);
            $args = '';
            $args_arr_size = \count($args_arr);

            for ($i = 0; $i < $args_arr_size; ++$i) {
                if (0 !== $i) {
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

                if (isset($names_arr[$i]) && '' !== $names_arr[$i]) {
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
