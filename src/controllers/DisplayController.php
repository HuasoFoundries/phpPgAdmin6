<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 * @package PHPPgAdmin
 */
class DisplayController extends BaseController
{
    public $controller_name = 'DisplayController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $conf           = $this->conf;
        $this->misc     = $this->misc;
        $lang           = $this->lang;
        $plugin_manager = $this->plugin_manager;
        $action         = $this->action;

        if ('dobrowsefk' == $action) {
            return $this->doBrowseFK();
        }

        set_time_limit(0);

        $scripts = '<script src="' . \SUBFOLDER . '/js/display.js" type="text/javascript"></script>';

        $scripts .= '<script type="text/javascript">' . "\n";
        $scripts .= "var Display = {\n";
        $scripts .= "errmsg: '" . str_replace("'", "\\'", $lang['strconnectionfail']) . "'\n";
        $scripts .= "};\n";
        $scripts .= '</script>' . "\n";

        $footer_template = 'footer.twig';
        $header_template = 'header.twig';

        ob_start();
        switch ($action) {
            case 'editrow':
                $header_template = 'header_sqledit.twig';
                $footer_template = 'footer_sqledit.twig';
                if (isset($_POST['save'])) {
                    $this->doEditRow(false);
                } else {
                    $this->doBrowse();
                }

                break;
            case 'confeditrow':
                $this->doEditRow(true);

                break;
            case 'delrow':
                $header_template = 'header_sqledit.twig';
                $footer_template = 'footer_sqledit.twig';
                if (isset($_POST['yes'])) {
                    $this->doDelRow(false);
                } else {
                    $this->doBrowse();
                }

                break;
            case 'confdelrow':
                $this->doDelRow(true);

                break;
            default:
                $header_template = 'header_sqledit.twig';
                $footer_template = 'footer_sqledit.twig';
                $this->doBrowse();

                break;
        }
        $output = ob_get_clean();

        // Set the title based on the subject of the request
        if (isset($_REQUEST['subject'], $_REQUEST[$_REQUEST['subject']])) {
            if ('table' == $_REQUEST['subject']) {
                $this->printHeader($lang['strtables'] . ': ' . $_REQUEST[$_REQUEST['subject']], $scripts, true, $header_template);
            } elseif ('view' == $_REQUEST['subject']) {
                $this->printHeader($lang['strviews'] . ': ' . $_REQUEST[$_REQUEST['subject']], $scripts, true, $header_template);
            } elseif ('matview' == $_REQUEST['subject']) {
                $this->printHeader('M' . $lang['strviews'] . ': ' . $_REQUEST[$_REQUEST['subject']], $scripts, true, $header_template);
            } elseif ('column' == $_REQUEST['subject']) {
                $this->printHeader($lang['strcolumn'] . ': ' . $_REQUEST[$_REQUEST['subject']], $scripts, true, $header_template);
            }
        } else {
            $this->printHeader($lang['strqueryresults'], $scripts, true, $header_template);
        }

        $this->printBody();

        echo $output;

        $this->printFooter(true, $footer_template);
    }

    /**
     * Displays requested data.
     *
     * @param mixed $msg
     */
    public function doBrowse($msg = '')
    {
        $conf           = $this->conf;
        $this->misc     = $this->misc;
        $lang           = $this->lang;
        $plugin_manager = $this->plugin_manager;
        $data           = $this->misc->getDatabaseAccessor();

        $save_history = false;
        // If current page is not set, default to first page
        if (!isset($_REQUEST['page'])) {
            $_REQUEST['page'] = 1;
        }

        if (!isset($_REQUEST['nohistory'])) {
            $save_history = true;
        }

        if (isset($_REQUEST['subject'])) {
            $subject = $_REQUEST['subject'];
            if (isset($_REQUEST[$subject])) {
                $object = $_REQUEST[$subject];
            }
        } else {
            $subject = '';
        }

        $this->printTrail(isset($subject) ? $subject : 'database');
        $this->printTabs($subject, 'browse');

        // This code is used when browsing FK in pure-xHTML (without js)
        if (isset($_REQUEST['fkey'])) {
            $ops = [];
            foreach ($_REQUEST['fkey'] as $x => $y) {
                $ops[$x] = '=';
            }
            $query             = $data->getSelectSQL($_REQUEST['table'], [], $_REQUEST['fkey'], $ops);
            $_REQUEST['query'] = $query;
        }

        if (isset($object)) {
            if (isset($_REQUEST['query'])) {
                $_SESSION['sqlquery'] = $_REQUEST['query'];
                $this->printTitle($lang['strselect']);
                $type = 'SELECT';
            } else {
                $type = 'TABLE';
            }
        } else {
            $this->printTitle($lang['strqueryresults']);
            // we comes from sql.php, $_SESSION['sqlquery'] has been set there
            $type = 'QUERY';
        }

        $this->printMsg($msg);

        // If 'sortkey' is not set, default to ''
        if (!isset($_REQUEST['sortkey'])) {
            $_REQUEST['sortkey'] = '';
        }

        // If 'sortdir' is not set, default to ''
        if (!isset($_REQUEST['sortdir'])) {
            $_REQUEST['sortdir'] = '';
        }

        // If 'strings' is not set, default to collapsed
        if (!isset($_REQUEST['strings'])) {
            $_REQUEST['strings'] = 'collapsed';
        }

        // Fetch unique row identifier, if this is a table browse request.
        if (isset($object)) {
            $key = $data->getRowIdentifier($object);
        } else {
            $key = [];
        }

        // Set the schema search path
        if (isset($_REQUEST['search_path'])) {
            if (0 != $data->setSearchPath(array_map('trim', explode(',', $_REQUEST['search_path'])))) {
                return;
            }
        }

        try {
            // Retrieve page from query.  $max_pages is returned by reference.
            $resultset = $data->browseQuery(
                $type,
                isset($object) ? $object : null,
                isset($_SESSION['sqlquery']) ? $_SESSION['sqlquery'] : null,
                $_REQUEST['sortkey'],
                $_REQUEST['sortdir'],
                $_REQUEST['page'],
                $this->conf['max_rows'],
                $max_pages
            );
        } catch (\PHPPgAdmin\ADOdbException $e) {
            return;
        }

        $fkey_information = $this->getFKInfo();

        // Build strings for GETs in array
        $_gets = [
            'server'   => $_REQUEST['server'],
            'database' => $_REQUEST['database'],
        ];

        if (isset($_REQUEST['schema'])) {
            $_gets['schema'] = $_REQUEST['schema'];
        }

        if (isset($object)) {
            $_gets[$subject] = $object;
        }

        if (isset($subject)) {
            $_gets['subject'] = $subject;
        }

        if (isset($_REQUEST['query'])) {
            $_gets['query'] = $_REQUEST['query'];
        }

        if (isset($_REQUEST['count'])) {
            $_gets['count'] = $_REQUEST['count'];
        }

        if (isset($_REQUEST['return'])) {
            $_gets['return'] = $_REQUEST['return'];
        }

        if (isset($_REQUEST['search_path'])) {
            $_gets['search_path'] = $_REQUEST['search_path'];
        }

        if (isset($_REQUEST['table'])) {
            $_gets['table'] = $_REQUEST['table'];
        }

        if (isset($_REQUEST['sortkey'])) {
            $_gets['sortkey'] = $_REQUEST['sortkey'];
        }

        if (isset($_REQUEST['sortdir'])) {
            $_gets['sortdir'] = $_REQUEST['sortdir'];
        }

        if (isset($_REQUEST['nohistory'])) {
            $_gets['nohistory'] = $_REQUEST['nohistory'];
        }

        $_gets['strings'] = $_REQUEST['strings'];

        if ($save_history && is_object($resultset) && ('QUERY' == $type)) {
            //{
            $this->misc->saveScriptHistory($_REQUEST['query']);
        }

        if (isset($_REQUEST['query'])) {
            $query = $_REQUEST['query'];
        } else {
            $query = "SELECT * FROM {$_REQUEST['schema']}";
            if ('matview' == $_REQUEST['subject']) {
                $query = "{$query}.{$_REQUEST['matview']};";
            } elseif ('view' == $_REQUEST['subject']) {
                $query = "{$query}.{$_REQUEST['view']};";
            } else {
                $query = "{$query}.{$_REQUEST['table']};";
            }
        }
        //$query = isset($_REQUEST['query'])? $_REQUEST['query'] : "select * from {$_REQUEST['schema']}.{$_REQUEST['table']};";
        $this->prtrace($query);
        //die(htmlspecialchars($query));

        echo '<form method="post" id="sqlform" action="' . $_SERVER['REQUEST_URI'] . '">';
        echo '<textarea width="90%" name="query"  id="query" rows="5" cols="100" resizable="true">';

        echo htmlspecialchars($query);
        echo '</textarea><br><input type="submit"/></form>';

        if (is_object($resultset) && $resultset->recordCount() > 0) {
            // Show page navigation
            $this->misc->printPages($_REQUEST['page'], $max_pages, $_gets);

            echo "<table id=\"data\">\n<tr>";

            // Check that the key is actually in the result set.  This can occur for select
            // operations where the key fields aren't part of the select.  XXX:  We should
            // be able to support this, somehow.
            foreach ($key as $v) {
                // If a key column is not found in the record set, then we
                // can't use the key.
                if (!in_array($v, array_keys($resultset->fields), true)) {
                    $key = [];

                    break;
                }
            }

            $buttons = [
                'edit'   => [
                    'content' => $lang['stredit'],
                    'attr'    => [
                        'href' => [
                            'url'     => 'display.php',
                            'urlvars' => array_merge([
                                'action'  => 'confeditrow',
                                'strings' => $_REQUEST['strings'],
                                'page'    => $_REQUEST['page'],
                            ], $_gets),
                        ],
                    ],
                ],
                'delete' => [
                    'content' => $lang['strdelete'],
                    'attr'    => [
                        'href' => [
                            'url'     => 'display.php',
                            'urlvars' => array_merge([
                                'action'  => 'confdelrow',
                                'strings' => $_REQUEST['strings'],
                                'page'    => $_REQUEST['page'],
                            ], $_gets),
                        ],
                    ],
                ],
            ];
            $actions = [
                'actionbuttons' => &$buttons,
                'place'         => 'display-browse',
            ];
            $plugin_manager->do_hook('actionbuttons', $actions);

            foreach (array_keys($actions['actionbuttons']) as $action) {
                $actions['actionbuttons'][$action]['attr']['href']['urlvars'] = array_merge(
                    $actions['actionbuttons'][$action]['attr']['href']['urlvars'],
                    $_gets
                );
            }

            $edit_params = isset($actions['actionbuttons']['edit']) ?
            $actions['actionbuttons']['edit'] : [];
            $delete_params = isset($actions['actionbuttons']['delete']) ?
            $actions['actionbuttons']['delete'] : [];

            // Display edit and delete actions if we have a key
            $colspan = count($buttons);
            if ($colspan > 0 and count($key) > 0) {
                echo "<th colspan=\"{$colspan}\" class=\"data\">{$lang['stractions']}</th>" . "\n";
            }

            // we show OIDs only if we are in TABLE or SELECT type browsing
            $this->printTableHeaderCells($resultset, $_gets, isset($object));

            echo '</tr>' . "\n";

            $i = 0;
            reset($resultset->fields);
            while (!$resultset->EOF) {
                $id = (0 == ($i % 2) ? '1' : '2');
                echo "<tr class=\"data{$id}\">" . "\n";
                // Display edit and delete links if we have a key
                if ($colspan > 0 and count($key) > 0) {
                    $keys_array = [];
                    $has_nulls  = false;
                    foreach ($key as $v) {
                        if (null === $resultset->fields[$v]) {
                            $has_nulls = true;

                            break;
                        }
                        $keys_array["key[{$v}]"] = $resultset->fields[$v];
                    }
                    if ($has_nulls) {
                        echo "<td colspan=\"{$colspan}\">&nbsp;</td>" . "\n";
                    } else {
                        if (isset($actions['actionbuttons']['edit'])) {
                            $actions['actionbuttons']['edit']                            = $edit_params;
                            $actions['actionbuttons']['edit']['attr']['href']['urlvars'] = array_merge(
                                $actions['actionbuttons']['edit']['attr']['href']['urlvars'],
                                $keys_array
                            );
                        }

                        if (isset($actions['actionbuttons']['delete'])) {
                            $actions['actionbuttons']['delete']                            = $delete_params;
                            $actions['actionbuttons']['delete']['attr']['href']['urlvars'] = array_merge(
                                $actions['actionbuttons']['delete']['attr']['href']['urlvars'],
                                $keys_array
                            );
                        }

                        foreach ($actions['actionbuttons'] as $action) {
                            echo "<td class=\"opbutton{$id}\">";
                            $this->printLink($action, true, __METHOD__);
                            echo '</td>' . "\n";
                        }
                    }
                }

                $this->printTableRowCells($resultset, $fkey_information, isset($object));

                echo '</tr>' . "\n";
                $resultset->moveNext();
                ++$i;
            }
            echo '</table>' . "\n";

            echo '<p>', $resultset->recordCount(), " {$lang['strrows']}</p>" . "\n";
            // Show page navigation
            $this->misc->printPages($_REQUEST['page'], $max_pages, $_gets);
        } else {
            echo "<p>{$lang['strnodata']}</p>" . "\n";
        }

        // Navigation links
        $navlinks = [];

        $fields = [
            'server'   => $_REQUEST['server'],
            'database' => $_REQUEST['database'],
        ];

        if (isset($_REQUEST['schema'])) {
            $fields['schema'] = $_REQUEST['schema'];
        }

        // Return
        if (isset($_REQUEST['return'])) {
            $urlvars = $this->misc->getSubjectParams($_REQUEST['return']);

            $navlinks['back'] = [
                'attr'    => [
                    'href' => [
                        'url'     => $urlvars['url'],
                        'urlvars' => $urlvars['params'],
                    ],
                ],
                'content' => $lang['strback'],
            ];
        }

        // Edit SQL link
        if ('QUERY' == $type) {
            $navlinks['edit'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'database.php',
                        'urlvars' => array_merge($fields, [
                            'action'   => 'sql',
                            'paginate' => 'on',
                        ]),
                    ],
                ],
                'content' => $lang['streditsql'],
            ];
        }

        // Expand/Collapse
        if ('expanded' == $_REQUEST['strings']) {
            $navlinks['collapse'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'display.php',
                        'urlvars' => array_merge(
                            $_gets,
                            [
                                'strings' => 'collapsed',
                                'page'    => $_REQUEST['page'],
                            ]
                        ),
                    ],
                ],
                'content' => $lang['strcollapse'],
            ];
        } else {
            $navlinks['collapse'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'display.php',
                        'urlvars' => array_merge(
                            $_gets,
                            [
                                'strings' => 'expanded',
                                'page'    => $_REQUEST['page'],
                            ]
                        ),
                    ],
                ],
                'content' => $lang['strexpand'],
            ];
        }

        // Create view and download
        if (isset($_REQUEST['query'], $resultset) && is_object($resultset) && $resultset->recordCount() > 0) {
            // Report views don't set a schema, so we need to disable create view in that case
            if (isset($_REQUEST['schema'])) {
                $navlinks['createview'] = [
                    'attr'    => [
                        'href' => [
                            'url'     => 'views.php',
                            'urlvars' => array_merge($fields, [
                                'action'         => 'create',
                                'formDefinition' => $_REQUEST['query'],
                            ]),
                        ],
                    ],
                    'content' => $lang['strcreateview'],
                ];
            }

            $urlvars = [];
            if (isset($_REQUEST['search_path'])) {
                $urlvars['search_path'] = $_REQUEST['search_path'];
            }

            $navlinks['download'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'dataexport.php',
                        'urlvars' => array_merge($fields, $urlvars),
                    ],
                ],
                'content' => $lang['strdownload'],
            ];
        }

        // Insert
        if (isset($object) && (isset($subject) && 'table' == $subject)) {
            $navlinks['insert'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'tables.php',
                        'urlvars' => array_merge($fields, [
                            'action' => 'confinsertrow',
                            'table'  => $object,
                        ]),
                    ],
                ],
                'content' => $lang['strinsert'],
            ];
        }

        // Refresh
        $navlinks['refresh'] = [
            'attr'    => [
                'href' => [
                    'url'     => 'display.php',
                    'urlvars' => array_merge(
                        $_gets,
                        [
                            'strings' => $_REQUEST['strings'],
                            'page'    => $_REQUEST['page'],
                        ]
                    ),
                ],
            ],
            'content' => $lang['strrefresh'],
        ];

        $this->printNavLinks($navlinks, 'display-browse', get_defined_vars());
    }

    /**
     * Show confirmation of edit and perform actual update.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doEditRow($confirm, $msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (is_array($_REQUEST['key'])) {
            $key = $_REQUEST['key'];
        } else {
            $key = unserialize(urldecode($_REQUEST['key']));
        }

        if ($confirm) {
            $this->printTrail($_REQUEST['subject']);
            $this->printTitle($lang['streditrow']);
            $this->printMsg($msg);

            $attrs     = $data->getTableAttributes($_REQUEST['table']);
            $resultset = $data->browseRow($_REQUEST['table'], $key);

            if (('disable' != $this->conf['autocomplete'])) {
                $fksprops = $this->misc->getAutocompleteFKProperties($_REQUEST['table']);
                if (false !== $fksprops) {
                    echo $fksprops['code'];
                }
            } else {
                $fksprops = false;
            }

            echo '<form action="' . \SUBFOLDER . '/src/views/display.php" method="post" id="ac_form">' . "\n";

            /*echo '<p>';
            if (!$error) {
            echo "<input type=\"submit\" name=\"save\" accesskey=\"r\" value=\"{$lang['strsave']}\" />" . "\n";
            }

            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />" . "\n";

            echo '</p>' . "\n";*/

            $elements = 0;
            $error    = true;
            if (1 == $resultset->recordCount() && $attrs->recordCount() > 0) {
                echo '<table>' . "\n";

                // Output table header
                echo "<tr><th class=\"data\">{$lang['strcolumn']}</th><th class=\"data\">{$lang['strtype']}</th>";
                echo "<th class=\"data\">{$lang['strformat']}</th>" . "\n";
                echo "<th class=\"data\">{$lang['strnull']}</th><th class=\"data\">{$lang['strvalue']}</th></tr>";

                $i = 0;
                while (!$attrs->EOF) {
                    $attrs->fields['attnotnull'] = $data->phpBool($attrs->fields['attnotnull']);
                    $id                          = (0 == ($i % 2) ? '1' : '2');

                    // Initialise variables
                    if (!isset($_REQUEST['format'][$attrs->fields['attname']])) {
                        $_REQUEST['format'][$attrs->fields['attname']] = 'VALUE';
                    }

                    echo "<tr class=\"data{$id}\">" . "\n";
                    echo '<td style="white-space:nowrap;">', $this->misc->printVal($attrs->fields['attname']), '</td>';
                    echo '<td style="white-space:nowrap;">' . "\n";
                    echo $this->misc->printVal($data->formatType($attrs->fields['type'], $attrs->fields['atttypmod']));
                    echo '<input type="hidden" name="types[', htmlspecialchars($attrs->fields['attname']), ']" value="',
                    htmlspecialchars($attrs->fields['type']), '" /></td>';
                    ++$elements;
                    echo '<td style="white-space:nowrap;">' . "\n";
                    echo '<select name="format[' . htmlspecialchars($attrs->fields['attname']), ']">' . "\n";
                    echo '<option value="VALUE"', ($_REQUEST['format'][$attrs->fields['attname']] == 'VALUE') ? ' selected="selected"' : '', ">{$lang['strvalue']}</option>" . "\n";
                    $selected = ($_REQUEST['format'][$attrs->fields['attname']] == 'EXPRESSION') ? ' selected="selected"' : '';
                    echo '<option value="EXPRESSION"' . $selected . ">{$lang['strexpression']}</option>" . "\n";
                    echo "</select>\n</td>" . "\n";
                    ++$elements;
                    echo '<td style="white-space:nowrap;">';
                    // Output null box if the column allows nulls (doesn't look at CHECKs or ASSERTIONS)
                    if (!$attrs->fields['attnotnull']) {
                        // Set initial null values
                        if ('confeditrow' == $_REQUEST['action'] && null === $resultset->fields[$attrs->fields['attname']]) {
                            $_REQUEST['nulls'][$attrs->fields['attname']] = 'on';
                        }
                        echo "<label><span><input type=\"checkbox\" name=\"nulls[{$attrs->fields['attname']}]\"",
                        isset($_REQUEST['nulls'][$attrs->fields['attname']]) ? ' checked="checked"' : '', ' /></span></label></td>' . "\n";
                        ++$elements;
                    } else {
                        echo '&nbsp;</td>';
                    }

                    echo "<td id=\"row_att_{$attrs->fields['attnum']}\" style=\"white-space:nowrap;\">";

                    $extras = [];

                    // If the column allows nulls, then we put a JavaScript action on the data field to unset the
                    // NULL checkbox as soon as anything is entered in the field.  We use the $elements variable to
                    // keep track of which element offset we're up to.  We can't refer to the null checkbox by name
                    // as it contains '[' and ']' characters.
                    if (!$attrs->fields['attnotnull']) {
                        $extras['onChange'] = 'elements[' . ($elements - 1) . '].checked = false;';
                    }

                    if ((false !== $fksprops) && isset($fksprops['byfield'][$attrs->fields['attnum']])) {
                        $extras['id']           = "attr_{$attrs->fields['attnum']}";
                        $extras['autocomplete'] = 'off';
                    }

                    echo $data->printField("values[{$attrs->fields['attname']}]", $resultset->fields[$attrs->fields['attname']], $attrs->fields['type'], $extras);

                    echo '</td>';
                    ++$elements;
                    echo '</tr>' . "\n";
                    ++$i;
                    $attrs->moveNext();
                }
                echo '</table>' . "\n";

                $error = false;
            } elseif (1 != $resultset->recordCount()) {
                echo "<p>{$lang['strrownotunique']}</p>" . "\n";
            } else {
                echo "<p>{$lang['strinvalidparam']}</p>" . "\n";
            }

            echo '<input type="hidden" name="action" value="editrow" />' . "\n";
            echo $this->misc->form;
            if (isset($_REQUEST['table'])) {
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />' . "\n";
            }

            if (isset($_REQUEST['subject'])) {
                echo '<input type="hidden" name="subject" value="', htmlspecialchars($_REQUEST['subject']), '" />' . "\n";
            }

            if (isset($_REQUEST['query'])) {
                echo '<input type="hidden" name="query" value="', htmlspecialchars($_REQUEST['query']), '" />' . "\n";
            }

            if (isset($_REQUEST['count'])) {
                echo '<input type="hidden" name="count" value="', htmlspecialchars($_REQUEST['count']), '" />' . "\n";
            }

            if (isset($_REQUEST['return'])) {
                echo '<input type="hidden" name="return" value="', htmlspecialchars($_REQUEST['return']), '" />' . "\n";
            }

            echo '<input type="hidden" name="page" value="', htmlspecialchars($_REQUEST['page']), '" />' . "\n";
            echo '<input type="hidden" name="sortkey" value="', htmlspecialchars($_REQUEST['sortkey']), '" />' . "\n";
            echo '<input type="hidden" name="sortdir" value="', htmlspecialchars($_REQUEST['sortdir']), '" />' . "\n";
            echo '<input type="hidden" name="strings" value="', htmlspecialchars($_REQUEST['strings']), '" />' . "\n";
            echo '<input type="hidden" name="key" value="', htmlspecialchars(urlencode(serialize($key))), '" />' . "\n";
            echo '<p>';
            if (!$error) {
                echo "<input type=\"submit\" name=\"save\" accesskey=\"r\" value=\"{$lang['strsave']}\" />" . "\n";
            }

            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />" . "\n";

            if (false !== $fksprops) {
                if ('default off' != $this->conf['autocomplete']) {
                    echo "<input type=\"checkbox\" id=\"no_ac\" value=\"1\" checked=\"checked\" /><label for=\"no_ac\">{$lang['strac']}</label>" . "\n";
                } else {
                    echo "<input type=\"checkbox\" id=\"no_ac\" value=\"0\" /><label for=\"no_ac\">{$lang['strac']}</label>" . "\n";
                }
            }

            echo '</p>' . "\n";
            echo '</form>' . "\n";
        } else {
            if (!isset($_POST['values'])) {
                $_POST['values'] = [];
            }

            if (!isset($_POST['nulls'])) {
                $_POST['nulls'] = [];
            }

            $status = $data->editRow(
                $_POST['table'],
                $_POST['values'],
                $_POST['nulls'],
                $_POST['format'],
                $_POST['types'],
                $key
            );
            if (0 == $status) {
                $this->doBrowse($lang['strrowupdated']);
            } elseif ($status == -2) {
                $this->doEditRow(true, $lang['strrownotunique']);
            } else {
                $this->doEditRow(true, $lang['strrowupdatedbad']);
            }
        }
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDelRow($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail($_REQUEST['subject']);
            $this->printTitle($lang['strdeleterow']);

            $resultset = $data->browseRow($_REQUEST['table'], $_REQUEST['key']);

            echo '<form action="' . \SUBFOLDER . '/src/views/display.php" method="post">' . "\n";
            echo $this->misc->form;

            if (1 == $resultset->recordCount()) {
                echo "<p>{$lang['strconfdeleterow']}</p>" . "\n";

                $fkinfo = [];
                echo '<table><tr>';
                $this->printTableHeaderCells($resultset, false, true);
                echo '</tr>';
                echo '<tr class="data1">' . "\n";
                $this->printTableRowCells($resultset, $fkinfo, true);
                echo '</tr>' . "\n";
                echo '</table>' . "\n";
                echo '<br />' . "\n";

                echo '<input type="hidden" name="action" value="delrow" />' . "\n";
                echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />" . "\n";
                echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />" . "\n";
            } elseif (1 != $resultset->recordCount()) {
                echo "<p>{$lang['strrownotunique']}</p>" . "\n";
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />" . "\n";
            } else {
                echo "<p>{$lang['strinvalidparam']}</p>" . "\n";
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />" . "\n";
            }
            if (isset($_REQUEST['table'])) {
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />' . "\n";
            }

            if (isset($_REQUEST['subject'])) {
                echo '<input type="hidden" name="subject" value="', htmlspecialchars($_REQUEST['subject']), '" />' . "\n";
            }

            if (isset($_REQUEST['query'])) {
                echo '<input type="hidden" name="query" value="', htmlspecialchars($_REQUEST['query']), '" />' . "\n";
            }

            if (isset($_REQUEST['count'])) {
                echo '<input type="hidden" name="count" value="', htmlspecialchars($_REQUEST['count']), '" />' . "\n";
            }

            if (isset($_REQUEST['return'])) {
                echo '<input type="hidden" name="return" value="', htmlspecialchars($_REQUEST['return']), '" />' . "\n";
            }

            echo '<input type="hidden" name="page" value="', htmlspecialchars($_REQUEST['page']), '" />' . "\n";
            echo '<input type="hidden" name="sortkey" value="', htmlspecialchars($_REQUEST['sortkey']), '" />' . "\n";
            echo '<input type="hidden" name="sortdir" value="', htmlspecialchars($_REQUEST['sortdir']), '" />' . "\n";
            echo '<input type="hidden" name="strings" value="', htmlspecialchars($_REQUEST['strings']), '" />' . "\n";
            echo '<input type="hidden" name="key" value="', htmlspecialchars(urlencode(serialize($_REQUEST['key']))), '" />' . "\n";
            echo '</form>' . "\n";
        } else {
            $status = $data->deleteRow($_POST['table'], unserialize(urldecode($_POST['key'])));
            if (0 == $status) {
                $this->doBrowse($lang['strrowdeleted']);
            } elseif ($status == -2) {
                $this->doBrowse($lang['strrownotunique']);
            } else {
                $this->doBrowse($lang['strrowdeletedbad']);
            }
        }
    }

    /**
     * build & return the FK information data structure
     * used when deciding if a field should have a FK link or not.
     *
     * @return [type] [description]
     */
    public function &getFKInfo()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        // Get the foreign key(s) information from the current table
        $fkey_information = ['byconstr' => [], 'byfield' => []];

        if (isset($_REQUEST['table'])) {
            $constraints = $data->getConstraintsWithFields($_REQUEST['table']);
            if ($constraints->recordCount() > 0) {
                $fkey_information['common_url'] = $this->misc->getHREF('schema') . '&amp;subject=table';

                // build the FK constraints data structure
                while (!$constraints->EOF) {
                    $constr = &$constraints->fields;
                    if ('f' == $constr['contype']) {
                        if (!isset($fkey_information['byconstr'][$constr['conid']])) {
                            $fkey_information['byconstr'][$constr['conid']] = [
                                'url_data' => 'table=' . urlencode($constr['f_table']) . '&amp;schema=' . urlencode($constr['f_schema']),
                                'fkeys'    => [],
                                'consrc'   => $constr['consrc'],
                            ];
                        }

                        $fkey_information['byconstr'][$constr['conid']]['fkeys'][$constr['p_field']] = $constr['f_field'];

                        if (!isset($fkey_information['byfield'][$constr['p_field']])) {
                            $fkey_information['byfield'][$constr['p_field']] = [];
                        }

                        $fkey_information['byfield'][$constr['p_field']][] = $constr['conid'];
                    }
                    $constraints->moveNext();
                }
            }
        }

        return $fkey_information;
    }

    /**
     * Print table header cells.
     *
     * @param $args - associative array for sort link parameters
     * @param mixed $withOid
     */
    public function printTableHeaderCells(&$resultset, $args, $withOid)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();
        $j    = 0;

        foreach ($resultset->fields as $k => $v) {
            if (($k === $data->id) && (!($withOid && $this->conf['show_oids']))) {
                ++$j;

                continue;
            }
            $finfo = $resultset->fetchField($j);

            if (false === $args) {
                echo '<th class="data">', $this->misc->printVal($finfo->name), '</th>' . "\n";
            } else {
                $args['page']    = $_REQUEST['page'];
                $args['sortkey'] = $j + 1;
                // Sort direction opposite to current direction, unless it's currently ''
                $args['sortdir'] = (
                    'asc' == $_REQUEST['sortdir']
                    and $_REQUEST['sortkey'] == ($j + 1)
                ) ? 'desc' : 'asc';

                $sortLink = http_build_query($args);

                echo "<th class=\"data\"><a href=\"?{$sortLink}\">"
                , $this->misc->printVal($finfo->name);
                if ($_REQUEST['sortkey'] == ($j + 1)) {
                    if ('asc' == $_REQUEST['sortdir']) {
                        echo '<img src="' . $this->misc->icon('RaiseArgument') . '" alt="asc">';
                    } else {
                        echo '<img src="' . $this->misc->icon('LowerArgument') . '" alt="desc">';
                    }
                }
                echo '</a></th>' . "\n";
            }
            ++$j;
        }

        reset($resultset->fields);
    }

    // Print data-row cells
    public function printTableRowCells(&$resultset, &$fkey_information, $withOid)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();
        $j    = 0;

        if (!isset($_REQUEST['strings'])) {
            $_REQUEST['strings'] = 'collapsed';
        }

        foreach ($resultset->fields as $k => $v) {
            $finfo = $resultset->fetchField($j++);

            if (($k === $data->id) && (!($withOid && $this->conf['show_oids']))) {
                continue;
            }
            if (null !== $v && '' == $v) {
                echo '<td>&nbsp;</td>';
            } else {
                echo '<td style="white-space:nowrap;">';

                if ((null !== $v) && isset($fkey_information['byfield'][$k])) {
                    foreach ($fkey_information['byfield'][$k] as $conid) {
                        $query_params = $fkey_information['byconstr'][$conid]['url_data'];

                        foreach ($fkey_information['byconstr'][$conid]['fkeys'] as $p_field => $f_field) {
                            $query_params .= '&amp;' . urlencode("fkey[{$f_field}]") . '=' . urlencode($resultset->fields[$p_field]);
                        }

                        // $fkey_information['common_url'] is already urlencoded
                        $query_params .= '&amp;' . $fkey_information['common_url'];
                        echo '<div style="display:inline-block;">';
                        echo '<a class="fk fk_' . htmlentities($conid, ENT_QUOTES, 'UTF-8') . "\" href=\"display.php?{$query_params}\">";
                        echo '<img src="' . $this->misc->icon('ForeignKey') . '" style="vertical-align:middle;" alt="[fk]" title="'
                        . htmlentities($fkey_information['byconstr'][$conid]['consrc'], ENT_QUOTES, 'UTF-8')
                            . '" />';
                        echo '</a>';
                        echo '</div>';
                    }
                    echo $this->misc->printVal($v, $finfo->type, ['null' => true, 'clip' => ('collapsed' == $_REQUEST['strings']), 'class' => 'fk_value']);
                } else {
                    echo $this->misc->printVal($v, $finfo->type, ['null' => true, 'clip' => ('collapsed' == $_REQUEST['strings'])]);
                }
                echo '</td>';
            }
        }
    }

    // Print the FK row, used in ajax requests
    public function doBrowseFK()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $ops = [];
        foreach ($_REQUEST['fkey'] as $x => $y) {
            $ops[$x] = '=';
        }
        $query             = $data->getSelectSQL($_REQUEST['table'], [], $_REQUEST['fkey'], $ops);
        $_REQUEST['query'] = $query;

        $fkinfo = $this->getFKInfo();

        $max_pages = 1;
        // Retrieve page from query.  $max_pages is returned by reference.
        $resultset = $data->browseQuery(
            'SELECT',
            $_REQUEST['table'],
            $_REQUEST['query'],
            null,
            null,
            1,
            1,
            $max_pages
        );

        echo '<a href="javascript:void(0);" style="display:table-cell;" class="fk_delete"><img alt="[delete]" src="' . $this->misc->icon('Delete') . '" /></a>' . "\n";
        echo '<div style="display:table-cell;">';

        if (is_object($resultset) && $resultset->recordCount() > 0) {
            /* we are browsing a referenced table here
             * we should show OID if show_oids is true
             * so we give true to withOid in functions bellow
             */
            echo '<table><tr>';
            $this->printTableHeaderCells($resultset, false, true);
            echo '</tr>';
            echo '<tr class="data1">' . "\n";
            $this->printTableRowCells($resultset, $fkinfo, true);
            echo '</tr>' . "\n";
            echo '</table>' . "\n";
        } else {
            echo $lang['strnodata'];
        }
        echo '</div>';
    }
}
