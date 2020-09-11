<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class DisplayController extends BaseController
{
    use \PHPPgAdmin\Traits\InsertEditRowTrait;

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->misc = $this->misc;

        if ('dobrowsefk' === $this->action) {
            return $this->doBrowseFK();
        }

        \set_time_limit(0);

        $scripts = '<script src="' . self::SUBFOLDER . '/assets/js/display.js" type="text/javascript"></script>';

        $scripts .= '<script type="text/javascript">' . \PHP_EOL;
        $scripts .= "var Display = {\n";
        $scripts .= "errmsg: '" . \str_replace("'", "\\'", $this->lang['strconnectionfail']) . "'\n";
        $scripts .= "};\n";
        $scripts .= '</script>' . \PHP_EOL;

        $footer_template = 'footer.twig';
        $header_template = 'header.twig';

        \ob_start();

        switch ($this->action) {
            case 'editrow':
                $header_template = 'header_sqledit.twig';
                $footer_template = 'footer_sqledit.twig';

                if (isset($_POST['save'])) {
                    $this->doEditRow();
                } else {
                    $this->doBrowse();
                }

                break;
            case 'confeditrow':
                $this->formEditRow();

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
        $output = \ob_get_clean();

        $subject = $this->coalesceArr($_REQUEST, 'subject', 'table')['subject'];

        $object = null;
        $object = $this->setIfIsset($object, $_REQUEST[$subject]);

        // Set the title based on the subject of the request
        if ('table' === $subject) {
            $title = $this->headerTitle('strtables', '', $object);
        } elseif ('view' === $subject) {
            $title = $this->headerTitle('strviews', '', $object);
        } elseif ('matview' === $subject) {
            $title = $this->headerTitle('strviews', 'M', $object);
        } elseif ('column' === $subject) {
            $title = $this->headerTitle('strcolumn', '', $object);
        } else {
            $title = $this->headerTitle('strqueryresults');
        }

        $this->printHeader($title, $scripts, true, $header_template);

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
        $this->misc = $this->misc;
        $data = $this->misc->getDatabaseAccessor();

        // If current page is not set, default to first page
        $page = $this->coalesceArr($_REQUEST, 'page', 1)['page'];

        $save_history = !isset($_REQUEST['nohistory']);

        $subject = $this->coalesceArr($_REQUEST, 'subject', 'table')['subject'];

        $object = $this->coalesceArr($_REQUEST, $subject)[$subject];

        if ('column' === $subject && $object && isset($_REQUEST['f_schema'], $_REQUEST['f_table'])) {
            $f_schema = $_REQUEST['f_schema'];
            $f_table = $_REQUEST['f_table'];

            $_REQUEST['query'] = "SELECT \"{$object}\",
            count(*) AS \"count\"
            FROM \"{$f_schema}\".\"{$f_table}\"
            GROUP BY \"{$object}\" ORDER BY \"{$object}\"";
        } elseif ('table' === $subject && !isset($_REQUEST['query'])) {
            $show = $this->getPostParam('show', []);
            $values = $this->getPostParam('values', []);
            $ops = $this->getPostParam('ops', []);
            $query = $data->getSelectSQL(
                $_REQUEST['table'],
                \array_keys($show),
                $values,
                $ops
            );
            $_REQUEST['query'] = $query;
            $_REQUEST['return'] = 'selectrows';
        }

        //$object = $this->setIfIsset($object, $_REQUEST[$subject]);

        $this->printTrail($subject);

        $tabsPosition = 'browse';

        if ('database' === $subject) {
            $tabsPosition = 'sql';
        } elseif ('column' === $subject) {
            $tabsPosition = 'colproperties';
        }

        $this->printTabs($subject, $tabsPosition);

        [$query, $title, $type] = $this->getQueryTitleAndType($data, $object);

        $this->printTitle($this->lang[$title]);

        $this->printMsg($msg);

        // If 'sortkey' is not set, default to ''
        $sortkey = $this->coalesceArr($_REQUEST, 'sortkey', '')['sortkey'];

        // If 'sortdir' is not set, default to ''
        $sortdir = $this->coalesceArr($_REQUEST, 'sortdir', '')['sortdir'];

        // If 'strings' is not set, default to collapsed
        $strings = $this->coalesceArr($_REQUEST, 'strings', 'collapsed')['strings'];

        $this->coalesceArr($_REQUEST, 'schema')['schema'];
        $search_path = $this->coalesceArr($_REQUEST, 'search_path')['search_path'];

        // Set the schema search path
        if (isset($search_path) && (0 !== $data->setSearchPath(\array_map('trim', \explode(',', $search_path))))) {
            return;
        }

        try {
            $max_pages = 0;
            // Retrieve page from query.  $max_pages is returned by reference.
            $resultset = $data->browseQuery(
                $type,
                $object,
                $query,
                $sortkey,
                $sortdir,
                $page,
                $this->conf['max_rows'],
                $max_pages
            );
        } catch (\PHPPgAdmin\ADOdbException $e) {
            return $this->halt($e->getMessage());
        }

        // Build strings for GETs in array
        $_gets = [
            'server' => $_REQUEST['server'],
            'database' => $_REQUEST['database'],
            'schema' => $_REQUEST['schema'] ?? null,
            'query' => $_REQUEST['query'] ?? null,
            'count' => $_REQUEST['count'] ?? null,
            'return' => $_REQUEST['return'] ?? null,
            'search_path' => $_REQUEST['search_path'] ?? null,
            'table' => $_REQUEST['table'] ?? null,
            'nohistory' => $_REQUEST['nohistory'] ?? null,
        ];

        $this->coalesceArr($_REQUEST, 'query');
        $this->coalesceArr($_REQUEST, 'count');
        $this->coalesceArr($_REQUEST, 'return');
        $this->coalesceArr($_REQUEST, 'table');
        $this->coalesceArr($_REQUEST, 'nohistory');

        $this->setIfIsset($_gets[$subject], $object, null, false);
        $this->setIfIsset($_gets['subject'], $subject, null, false);

        $_gets['sortkey'] = $sortkey;
        $_gets['sortdir'] = $sortdir;
        $_gets['strings'] = $strings;

        if ($save_history && \is_object($resultset) && ('QUERY' === $type)) {
            //{
            $this->misc->saveScriptHistory($_REQUEST['query']);
        }

        $query = $query ? $query : \sprintf('SELECT * FROM %s.%s', $_REQUEST['schema'], $object);

        //$query = isset($_REQUEST['query'])? $_REQUEST['query'] : "select * from {$_REQUEST['schema']}.{$_REQUEST['table']};";

        //die(htmlspecialchars($query));

        echo '<form method="post" id="sqlform" action="' . $_SERVER['REQUEST_URI'] . '">';
        echo $this->view->form;

        if ($object) {
            echo '<input type="hidden" name="' . $subject . '" value="', \htmlspecialchars($object), '" />' . \PHP_EOL;
        }
        echo '<textarea width="90%" name="query"  id="query" rows="5" cols="100" resizable="true">';
        echo \htmlspecialchars($query);
        echo '</textarea><br><input type="submit"/>';

        echo '</form>';

        $this->printResultsTable($resultset, $page, $max_pages, $_gets, $object);
        // Navigation links

        $navlinks = $this->getBrowseNavLinks($type, $_gets, $page, $subject, $object, $resultset);
        $this->printNavLinks($navlinks, 'display-browse', \get_defined_vars());
    }

    public function getQueryTitleAndType($data, $object)
    {
        $fkey = $this->coalesceArr($_REQUEST, 'fkey')['fkey'];

        $query = $this->coalesceArr($_REQUEST, 'query')['query'];
        // This code is used when browsing FK in pure-xHTML (without js)
        if ($fkey) {
            $ops = [];

            foreach (\array_keys($fkey) as $x) {
                $ops[$x] = '=';
            }
            $query = $data->getSelectSQL($_REQUEST['table'], [], $fkey, $ops);
            $_REQUEST['query'] = $query;
        }

        $title = 'strqueryresults';
        $type = 'QUERY';

        if ($object && $query) {
            $_SESSION['sqlquery'] = $query;
            $title = 'strselect';
            $type = 'SELECT';
        } elseif ($object) {
            $title = 'strselect';
            $type = 'TABLE';
        } elseif (isset($_SESSION['sqlquery'])) {
            $query = $_SESSION['sqlquery'];
        }

        return [$query, $title, $type];
    }

    /**
     * @param array  $_gets
     * @param mixed  $type
     * @param mixed  $page
     * @param string $subject
     * @param mixed  $object
     * @param mixed  $resultset
     */
    public function getBrowseNavLinks($type, array $_gets, $page, string $subject, $object, $resultset)
    {
        $fields = [
            'server' => $_REQUEST['server'],
            'database' => $_REQUEST['database'],
        ];

        $this->setIfIsset($fields['schema'], $_REQUEST['schema'], null, false);

        $navlinks = [];
        $strings = $_gets['strings'];
        // Return
        if (isset($_REQUEST['return'])) {
            $urlvars = $this->misc->getSubjectParams($_REQUEST['return']);

            $navlinks['back'] = [
                'attr' => [
                    'href' => [
                        'url' => $urlvars['url'],
                        'urlvars' => $urlvars['params'],
                    ],
                ],
                'content' => $this->lang['strback'],
            ];
        }

        // Edit SQL link
        if ('QUERY' === $type) {
            $navlinks['edit'] = [
                'attr' => [
                    'href' => [
                        'url' => 'database',
                        'urlvars' => \array_merge(
                            $fields,
                            [
                                'action' => 'sql',
                                'paginate' => 'on',
                            ]
                        ),
                    ],
                ],
                'content' => $this->lang['streditsql'],
            ];
        }

        $navlinks['collapse'] = [
            'attr' => [
                'href' => [
                    'url' => 'display',
                    'urlvars' => \array_merge(
                        $_gets,
                        [
                            'strings' => 'expanded',
                            'page' => $page,
                        ]
                    ),
                ],
            ],
            'content' => $this->lang['strexpand'],
        ];
        // Expand/Collapse
        if ('expanded' === $strings) {
            $navlinks['collapse'] = [
                'attr' => [
                    'href' => [
                        'url' => 'display',
                        'urlvars' => \array_merge(
                            $_gets,
                            [
                                'strings' => 'collapsed',
                                'page' => $page,
                            ]
                        ),
                    ],
                ],
                'content' => $this->lang['strcollapse'],
            ];
        }

        // Create view and download
        if (isset($_REQUEST['query'], $resultset) && \is_object($resultset) && 0 < $resultset->recordCount()) {
            // Report views don't set a schema, so we need to disable create view in that case
            if (isset($_REQUEST['schema'])) {
                $navlinks['createview'] = [
                    'attr' => [
                        'href' => [
                            'url' => 'views',
                            'urlvars' => \array_merge(
                                $fields,
                                [
                                    'action' => 'create',
                                    'formDefinition' => $_REQUEST['query'],
                                ]
                            ),
                        ],
                    ],
                    'content' => $this->lang['strcreateview'],
                ];
            }

            $urlvars = [];

            $this->setIfIsset($urlvars['search_path'], $_REQUEST['search_path'], null, false);

            $navlinks['download'] = [
                'attr' => [
                    'href' => [
                        'url' => 'dataexport',
                        'urlvars' => \array_merge($fields, $urlvars),
                    ],
                ],
                'content' => $this->lang['strdownload'],
            ];
        }

        // Insert
        if (isset($object) && (isset($subject) && 'table' === $subject)) {
            $navlinks['insert'] = [
                'attr' => [
                    'href' => [
                        'url' => 'tables',
                        'urlvars' => \array_merge(
                            $fields,
                            [
                                'action' => 'confinsertrow',
                                'table' => $object,
                            ]
                        ),
                    ],
                ],
                'content' => $this->lang['strinsert'],
            ];
        }

        // Refresh
        $navlinks['refresh'] = [
            'attr' => [
                'href' => [
                    'url' => 'display',
                    'urlvars' => \array_merge(
                        $_gets,
                        [
                            'strings' => $strings,
                            'page' => $page,
                        ]
                    ),
                ],
            ],
            'content' => $this->lang['strrefresh'],
        ];

        return $navlinks;
    }

    /**
     * @param array $_gets
     * @param mixed $resultset
     * @param mixed $page
     * @param mixed $max_pages
     * @param mixed $object
     */
    public function printResultsTable($resultset, $page, $max_pages, array $_gets, $object): void
    {
        if (!\is_object($resultset) || 0 >= $resultset->recordCount()) {
            echo "<p>{$this->lang['strnodata']}</p>" . \PHP_EOL;

            return;
        }

        $data = $this->misc->getDatabaseAccessor();

        [$actions, $key] = $this->_getKeyAndActions($resultset, $object, $data, $page, $_gets);

        $fkey_information = $this->getFKInfo();
        // Show page navigation
        $paginator = $this->_printPages($page, $max_pages, $_gets);

        echo $paginator;
        echo '<table id="data">' . \PHP_EOL;
        echo '<tr>';

        try {
            // Display edit and delete actions if we have a key
            $display_action_column = (0 < \count($actions['actionbuttons']) && 0 < \count($key));
        } catch (\Exception $e) {
            $display_action_column = false;
        }

        echo $display_action_column ? "<th class=\"data\">{$this->lang['stractions']}</th>" . \PHP_EOL : '';

        // we show OIDs only if we are in TABLE or SELECT type browsing
        $this->printTableHeaderCells($resultset, $_gets, isset($object));

        echo '</tr>' . \PHP_EOL;

        \reset($resultset->fields);

        $trclass = 'data2';
        $buttonclass = 'opbutton2';

        while (!$resultset->EOF) {
            $trclass = ('data2' === $trclass) ? 'data1' : 'data2';
            $buttonclass = ('opbutton2' === $buttonclass) ? 'opbutton1' : 'opbutton2';

            echo \sprintf('<tr class="%s">', $trclass) . \PHP_EOL;

            $this->_printResultsTableActionButtons($resultset, $key, $actions, $display_action_column, $buttonclass);

            $this->printTableRowCells($resultset, $fkey_information, isset($object));

            echo '</tr>' . \PHP_EOL;
            $resultset->moveNext();
        }
        echo '</table>' . \PHP_EOL;

        echo '<p>', $resultset->recordCount(), " {$this->lang['strrows']}</p>" . \PHP_EOL;
        // Show page navigation
        echo $paginator;
    }

    /**
     * Print table header cells.
     *
     * @param \ADORecordSet $resultset set of results from getRow operation
     * @param array|bool    $args      - associative array for sort link parameters, or false if there isn't any
     * @param bool          $withOid   either to display OIDs or not
     */
    public function printTableHeaderCells(&$resultset, $args, $withOid): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if (!\is_object($resultset) || 0 >= $resultset->recordCount()) {
            return;
        }

        foreach (\array_keys($resultset->fields) as $index => $key) {
            if (($key === $data->id) && (!($withOid && $this->conf['show_oids']))) {
                continue;
            }
            $finfo = $resultset->fetchField($index);

            if (false === $args) {
                echo '<th class="data">', $this->misc->printVal($finfo->name), '</th>' . \PHP_EOL;

                continue;
            }
            $args['page'] = $_REQUEST['page'];
            $args['sortkey'] = $index + 1;
            // Sort direction opposite to current direction, unless it's currently ''
            $args['sortdir'] = ('asc' === $_REQUEST['sortdir'] && ($index + 1) === $_REQUEST['sortkey']) ? 'desc' : 'asc';

            $sortLink = \http_build_query($args);

            echo "<th class=\"data\"><a href=\"?{$sortLink}\">";
            echo $this->misc->printVal($finfo->name);

            if (($index + 1) === $_REQUEST['sortkey']) {
                $icon = ('asc' === $_REQUEST['sortdir']) ? $this->view->icon('RaiseArgument') : $this->view->icon('LowerArgument');
                echo \sprintf('<img src="%s" alt="%s">', $icon, $_REQUEST['sortdir']);
            }
            echo '</a></th>' . \PHP_EOL;
        }

        \reset($resultset->fields);
    }

    /**
     * Print table rows.
     *
     * @param \ADORecordSet $resultset        The resultset
     * @param array         $fkey_information The fkey information
     * @param bool          $withOid          either to display OIDs or not
     */
    public function printTableRowCells(&$resultset, &$fkey_information, $withOid): void
    {
        $data = $this->misc->getDatabaseAccessor();
        $j = 0;

        $this->coalesceArr($_REQUEST, 'strings', 'collapsed');

        foreach ($resultset->fields as $k => $v) {
            $finfo = $resultset->fetchField($j++);

            if (($k === $data->id) && (!($withOid && $this->conf['show_oids']))) {
                continue;
            }
            $printvalOpts = ['null' => true, 'clip' => ('collapsed' === $_REQUEST['strings'])];

            if (null !== $v && '' === $v) {
                echo '<td>&nbsp;</td>';
            } else {
                echo '<td style="white-space:nowrap;">';

                $this->_printFKLinks($resultset, $fkey_information, $k, $v, $printvalOpts);

                $val = $this->misc->printVal($v, $finfo->type, $printvalOpts);

                echo $val;
                echo '</td>';
            }
        }
    }

    /**
     * Show form to edit row.
     *
     * @param string $msg message to display on top of the form or after performing edition
     */
    public function formEditRow($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $key = $this->_unserializeIfNotArray($_REQUEST, 'key');

        $this->printTrail($_REQUEST['subject']);
        $this->printTitle($this->lang['streditrow']);
        $this->printMsg($msg);

        $attrs = $data->getTableAttributes($_REQUEST['table']);
        $resultset = $data->browseRow($_REQUEST['table'], $key);

        $fksprops = $this->_getFKProps();

        echo '<form action="' . self::SUBFOLDER . '/src/views/display" method="post" id="ac_form">' . \PHP_EOL;

        $elements = 0;
        $error = true;

        if (1 === $resultset->recordCount() && 0 < $attrs->recordCount()) {
            echo '<table>' . \PHP_EOL;

            // Output table header
            echo "<tr><th class=\"data\">{$this->lang['strcolumn']}</th><th class=\"data\">{$this->lang['strtype']}</th>";
            echo "<th class=\"data\">{$this->lang['strformat']}</th>" . \PHP_EOL;
            echo "<th class=\"data\">{$this->lang['strnull']}</th><th class=\"data\">{$this->lang['strvalue']}</th></tr>";

            $i = 0;

            while (!$attrs->EOF) {
                $attrs->fields['attnotnull'] = $data->phpBool($attrs->fields['attnotnull']);
                $id = (0 === ($i % 2) ? '1' : '2');

                // Initialise variables
                if (!isset($_REQUEST['format'][$attrs->fields['attname']])) {
                    $_REQUEST['format'][$attrs->fields['attname']] = 'VALUE';
                }

                echo "<tr class=\"data{$id}\">" . \PHP_EOL;
                echo '<td style="white-space:nowrap;">', $this->misc->printVal($attrs->fields['attname']), '</td>';
                echo '<td style="white-space:nowrap;">' . \PHP_EOL;
                echo $this->misc->printVal($data->formatType($attrs->fields['type'], $attrs->fields['atttypmod']));
                echo '<input type="hidden" name="types[', \htmlspecialchars($attrs->fields['attname']), ']" value="',
                \htmlspecialchars($attrs->fields['type']), '" /></td>';
                ++$elements;
                echo '<td style="white-space:nowrap;">' . \PHP_EOL;
                echo '<select name="format[' . \htmlspecialchars($attrs->fields['attname']), ']">' . \PHP_EOL;
                echo '<option value="VALUE"', ($_REQUEST['format'][$attrs->fields['attname']] === 'VALUE') ? ' selected="selected"' : '', ">{$this->lang['strvalue']}</option>" . \PHP_EOL;
                $selected = ($_REQUEST['format'][$attrs->fields['attname']] === 'EXPRESSION') ? ' selected="selected"' : '';
                echo '<option value="EXPRESSION"' . $selected . ">{$this->lang['strexpression']}</option>" . \PHP_EOL;
                echo "</select>\n</td>" . \PHP_EOL;
                ++$elements;
                echo '<td style="white-space:nowrap;">';
                // Output null box if the column allows nulls (doesn't look at CHECKs or ASSERTIONS)
                if (!$attrs->fields['attnotnull']) {
                    // Set initial null values
                    if ('confeditrow' === $_REQUEST['action']
                        && null === $resultset->fields[$attrs->fields['attname']]
                    ) {
                        $_REQUEST['nulls'][$attrs->fields['attname']] = 'on';
                    }
                    echo "<label><span><input type=\"checkbox\" class=\"nullcheckbox\" name=\"nulls[{$attrs->fields['attname']}]\"",
                    isset($_REQUEST['nulls'][$attrs->fields['attname']]) ? ' checked="checked"' : '', ' /></span></label></td>' . \PHP_EOL;
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
                    $extras['class'] = 'insert_row_input';
                }

                if ((false !== $fksprops) && isset($fksprops['byfield'][$attrs->fields['attnum']])) {
                    $extras['id'] = "attr_{$attrs->fields['attnum']}";
                    $extras['autocomplete'] = 'off';
                }

                echo $data->printField("values[{$attrs->fields['attname']}]", $resultset->fields[$attrs->fields['attname']], $attrs->fields['type'], $extras);

                echo '</td>';
                ++$elements;
                echo '</tr>' . \PHP_EOL;
                ++$i;
                $attrs->moveNext();
            }
            echo '</table>' . \PHP_EOL;

            $error = false;
        } elseif (1 !== $resultset->recordCount()) {
            echo "<p>{$this->lang['strrownotunique']}</p>" . \PHP_EOL;
        } else {
            echo "<p>{$this->lang['strinvalidparam']}</p>" . \PHP_EOL;
        }

        echo '<input type="hidden" name="action" value="editrow" />' . \PHP_EOL;
        echo $this->view->form;
        echo isset($_REQUEST['table']) ? \sprintf('<input type="hidden" name="table" value="%s" />%s', \htmlspecialchars($_REQUEST['table']), \PHP_EOL) : '';

        echo isset($_REQUEST['subject']) ? \sprintf('<input type="hidden" name="subject" value="%s" />%s', \htmlspecialchars($_REQUEST['subject']), \PHP_EOL) : '';

        echo isset($_REQUEST['query']) ? \sprintf('<input type="hidden" name="query" value="%s" />%s', \htmlspecialchars($_REQUEST['query']), \PHP_EOL) : '';

        echo isset($_REQUEST['count']) ? \sprintf('<input type="hidden" name="count" value="%s" />%s', \htmlspecialchars($_REQUEST['count']), \PHP_EOL) : '';

        echo isset($_REQUEST['return']) ? \sprintf('<input type="hidden" name="return" value="%s" />%s', \htmlspecialchars($_REQUEST['return']), \PHP_EOL) : '';

        echo '<input type="hidden" name="page" value="', \htmlspecialchars($_REQUEST['page']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="sortkey" value="', \htmlspecialchars($_REQUEST['sortkey']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="sortdir" value="', \htmlspecialchars($_REQUEST['sortdir']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="strings" value="', \htmlspecialchars($_REQUEST['strings']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="key" value="', \htmlspecialchars(\urlencode(\serialize($key))), '" />' . \PHP_EOL;
        echo '<p>';

        if (!$error) {
            echo "<input type=\"submit\" name=\"save\" accesskey=\"r\" value=\"{$this->lang['strsave']}\" />" . \PHP_EOL;
        }

        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />" . \PHP_EOL;

        if (false !== $fksprops) {
            $autocomplete_string = "<input type=\"checkbox\" id=\"no_ac\" value=\"0\" /><label for=\"no_ac\">{$this->lang['strac']}</label>";

            if ('default off' !== $this->conf['autocomplete']) {
                $autocomplete_string = "<input type=\"checkbox\" id=\"no_ac\" value=\"1\" checked=\"checked\" /><label for=\"no_ac\">{$this->lang['strac']}</label>";
            }
            echo $autocomplete_string . \PHP_EOL;
        }

        echo '</p>' . \PHP_EOL;
        echo '</form>' . \PHP_EOL;
        echo '<script src="' . self::SUBFOLDER . '/assets/js/insert_or_edit_row.js" type="text/javascript"></script>';
    }

    /**
     * Performs actual edition of row.
     */
    public function doEditRow()
    {
        $data = $this->misc->getDatabaseAccessor();

        $key = $this->_unserializeIfNotArray($_REQUEST, 'key');

        $this->coalesceArr($_POST, 'values', []);

        $this->coalesceArr($_POST, 'nulls', []);

        $status = $data->editRow(
            $_POST['table'],
            $_POST['values'],
            $_POST['nulls'],
            $_POST['format'],
            $_POST['types'],
            $key
        );

        if (0 === $status) {
            return $this->doBrowse($this->lang['strrowupdated']);
        }

        if (-2 === $status) {
            return $this->formEditRow($this->lang['strrownotunique']);
        }

        return $this->formEditRow($this->lang['strrowupdatedbad']);
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDelRow($confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail($_REQUEST['subject']);
            $this->printTitle($this->lang['strdeleterow']);

            $resultset = $data->browseRow($_REQUEST['table'], $_REQUEST['key']);

            echo '<form action="' . self::SUBFOLDER . '/src/views/display" method="post">' . \PHP_EOL;
            echo $this->view->form;

            if (1 === $resultset->recordCount()) {
                echo "<p>{$this->lang['strconfdeleterow']}</p>" . \PHP_EOL;

                $fkinfo = [];
                echo '<table><tr>';
                $this->printTableHeaderCells($resultset, false, true);
                echo '</tr>';
                echo '<tr class="data1">' . \PHP_EOL;
                $this->printTableRowCells($resultset, $fkinfo, true);
                echo '</tr>' . \PHP_EOL;
                echo '</table>' . \PHP_EOL;
                echo '<br />' . \PHP_EOL;

                echo '<input type="hidden" name="action" value="delrow" />' . \PHP_EOL;
                echo "<input type=\"submit\" name=\"yes\" value=\"{$this->lang['stryes']}\" />" . \PHP_EOL;
                echo "<input type=\"submit\" name=\"no\" value=\"{$this->lang['strno']}\" />" . \PHP_EOL;
            } elseif (1 !== $resultset->recordCount()) {
                echo "<p>{$this->lang['strrownotunique']}</p>" . \PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />" . \PHP_EOL;
            } else {
                echo "<p>{$this->lang['strinvalidparam']}</p>" . \PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />" . \PHP_EOL;
            }

            if (isset($_REQUEST['table'])) {
                echo \sprintf('<input type="hidden" name="table" value="%s"  />%s', \htmlspecialchars($_REQUEST['table']), \PHP_EOL);
            }

            if (isset($_REQUEST['subject'])) {
                echo '<input type="hidden" name="subject" value="', \htmlspecialchars($_REQUEST['subject']), '" />' . \PHP_EOL;
            }

            if (isset($_REQUEST['query'])) {
                echo '<input type="hidden" name="query" value="', \htmlspecialchars($_REQUEST['query']), '" />' . \PHP_EOL;
            }

            if (isset($_REQUEST['count'])) {
                echo '<input type="hidden" name="count" value="', \htmlspecialchars($_REQUEST['count']), '" />' . \PHP_EOL;
            }

            if (isset($_REQUEST['return'])) {
                echo '<input type="hidden" name="return" value="', \htmlspecialchars($_REQUEST['return']), '" />' . \PHP_EOL;
            }

            echo '<input type="hidden" name="page" value="', \htmlspecialchars($_REQUEST['page']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="sortkey" value="', \htmlspecialchars($_REQUEST['sortkey']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="sortdir" value="', \htmlspecialchars($_REQUEST['sortdir']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="strings" value="', \htmlspecialchars($_REQUEST['strings']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="key" value="', \htmlspecialchars(\urlencode(\serialize($_REQUEST['key']))), '" />' . \PHP_EOL;
            echo '</form>' . \PHP_EOL;
        } else {
            $status = $data->deleteRow($_POST['table'], \unserialize(\urldecode($_POST['key'])));

            if (0 === $status) {
                $this->doBrowse($this->lang['strrowdeleted']);
            } elseif (-2 === $status) {
                $this->doBrowse($this->lang['strrownotunique']);
            } else {
                $this->doBrowse($this->lang['strrowdeletedbad']);
            }
        }
    }

    /**
     * Build & return the FK information data structure
     * used when deciding if a field should have a FK link or not.
     *
     * @return array associative array describing the FK
     */
    public function &getFKInfo()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Get the foreign key(s) information from the current table
        $fkey_information = ['byconstr' => [], 'byfield' => []];

        if (isset($_REQUEST['table'])) {
            $constraints = $data->getConstraintsWithFields($_REQUEST['table']);

            if (0 < $constraints->recordCount()) {
                $fkey_information['common_url'] = $this->misc->getHREF('schema') . '&amp;subject=table';

                // build the FK constraints data structure
                while (!$constraints->EOF) {
                    $constr = &$constraints->fields;

                    if ('f' === $constr['contype']) {
                        if (!isset($fkey_information['byconstr'][$constr['conid']])) {
                            $fkey_information['byconstr'][$constr['conid']] = [
                                'url_data' => 'table=' . \urlencode($constr['f_table']) . '&amp;schema=' . \urlencode($constr['f_schema']),
                                'fkeys' => [],
                                'consrc' => $constr['consrc'],
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

    // Print the FK row, used in ajax requests
    public function doBrowseFK(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $ops = [];

        foreach ($_REQUEST['fkey'] as $x => $y) {
            $ops[$x] = '=';
        }
        $query = $data->getSelectSQL($_REQUEST['table'], [], $_REQUEST['fkey'], $ops);
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

        echo '<a href="javascript:void(0);" style="display:table-cell;" class="fk_delete"><img alt="[delete]" src="' . $this->view->icon('Delete') . '" /></a>' . \PHP_EOL;
        echo '<div style="display:table-cell;">';

        if (\is_object($resultset) && 0 < $resultset->recordCount()) {
            /* we are browsing a referenced table here
             * we should show OID if show_oids is true
             * so we give true to withOid in functions bellow
             */
            echo '<table><tr>';
            $this->printTableHeaderCells($resultset, false, true);
            echo '</tr>';
            echo '<tr class="data1">' . \PHP_EOL;
            $this->printTableRowCells($resultset, $fkinfo, true);
            echo '</tr>' . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
        } else {
            echo $this->lang['strnodata'];
        }
        echo '</div>';
    }

    private function _getKeyAndActions(object $resultset, $object, $data, $page, array $_gets)
    {
        $key = [];
        $strings = $_gets['strings'];

        // Fetch unique row identifier, if this is a table browse request.
        if ($object) {
            $key = $data->getRowIdentifier($object);
        }
        // -1 means no unique keys, other non iterable should be discarded as well
        if (-1 === $key || \is_iterable($key)) {
            $key = [];
        }
        // Check that the key is actually in the result set.  This can occur for select
        // operations where the key fields aren't part of the select.  XXX:  We should
        // be able to support this, somehow.
        foreach ($key as $v) {
            // If a key column is not found in the record set, then we
            // can't use the key.
            if (!\array_key_exists($v, $resultset->fields)) {
                $key = [];

                break;
            }
        }

        $buttons = [
            'edit' => [
                'content' => $this->lang['stredit'],
                'attr' => [
                    'href' => [
                        'url' => 'display',
                        'urlvars' => \array_merge(
                            [
                                'action' => 'confeditrow',
                                'strings' => $strings,
                                'page' => $page,
                            ],
                            $_gets
                        ),
                    ],
                ],
            ],
            'delete' => [
                'content' => $this->lang['strdelete'],
                'attr' => [
                    'href' => [
                        'url' => 'display',
                        'urlvars' => \array_merge(
                            [
                                'action' => 'confdelrow',
                                'strings' => $strings,
                                'page' => $page,
                            ],
                            $_gets
                        ),
                    ],
                ],
            ],
        ];
        $actions = [
            'actionbuttons' => &$buttons,
            'place' => 'display-browse',
        ];

        foreach (\array_keys($actions['actionbuttons']) as $action) {
            $actions['actionbuttons'][$action]['attr']['href']['urlvars'] = \array_merge(
                $actions['actionbuttons'][$action]['attr']['href']['urlvars'],
                $_gets
            );
        }

        return [$actions, $key];
    }

    private function _printResultsTableActionButtons(\ADORecordSet $resultset, $key, $actions, bool $display_action_column, string $buttonclass): void
    {
        if (!$display_action_column) {
            return;
        }

        $edit_params = $actions['actionbuttons']['edit'] ?? [];
        $delete_params = $actions['actionbuttons']['delete'] ?? [];

        $keys_array = [];
        $has_nulls = false;

        foreach ($key as $v) {
            if (null === $resultset->fields[$v]) {
                $has_nulls = true;

                break;
            }
            $keys_array["key[{$v}]"] = $resultset->fields[$v];
        }

        if ($has_nulls) {
            echo '<td>&nbsp;</td>' . \PHP_EOL;

            return;
        }
        // Display edit and delete links if we have a key
        if (isset($actions['actionbuttons']['edit'])) {
            $actions['actionbuttons']['edit'] = $edit_params;
            $actions['actionbuttons']['edit']['attr']['href']['urlvars'] = \array_merge(
                $actions['actionbuttons']['edit']['attr']['href']['urlvars'],
                $keys_array
            );
        }

        if (isset($actions['actionbuttons']['delete'])) {
            $actions['actionbuttons']['delete'] = $delete_params;
            $actions['actionbuttons']['delete']['attr']['href']['urlvars'] = \array_merge(
                $actions['actionbuttons']['delete']['attr']['href']['urlvars'],
                $keys_array
            );
        }
        echo \sprintf('<td class="%s" style="white-space:nowrap">', $buttonclass);

        foreach ($actions['actionbuttons'] as $action) {
            $this->printLink($action, true, __METHOD__);
        }
        echo '</td>' . \PHP_EOL;
    }

    /**
     * @param bool[]        $printvalOpts
     * @param \ADORecordSet $resultset
     * @param array         $fkey_information
     * @param mixed         $k
     * @param mixed         $v
     */
    private function _printFKLinks(\ADORecordSet $resultset, array $fkey_information, $k, $v, array &$printvalOpts): void
    {
        if ((null === $v) || !isset($fkey_information['byfield'][$k])) {
            return;
        }

        foreach ($fkey_information['byfield'][$k] as $conid) {
            $query_params = $fkey_information['byconstr'][$conid]['url_data'];

            foreach ($fkey_information['byconstr'][$conid]['fkeys'] as $p_field => $f_field) {
                $query_params .= '&amp;' . \urlencode("fkey[{$f_field}]") . '=' . \urlencode($resultset->fields[$p_field]);
            }

            // $fkey_information['common_url'] is already urlencoded
            $query_params .= '&amp;' . $fkey_information['common_url'];
            $title = \htmlentities($fkey_information['byconstr'][$conid]['consrc'], \ENT_QUOTES, 'UTF-8');
            echo '<div style="display:inline-block;">';
            echo \sprintf('<a class="fk fk_%s" href="display?%s">', \htmlentities($conid, \ENT_QUOTES, 'UTF-8'), $query_params);
            echo \sprintf('<img src="%s" style="vertical-align:middle;" alt="[fk]" title="%s" />', $this->view->icon('ForeignKey'), $title);
            echo '</a>';
            echo '</div>';
        }
        $printvalOpts['class'] = 'fk_value';
    }

    private function _unserializeIfNotArray(array $the_array, string $key)
    {
        if (!isset($the_array[$key])) {
            return [];
        }

        if (\is_array($the_array[$key])) {
            return $the_array[$key];
        }

        return \unserialize(\urldecode($the_array[$key]));
    }

    private function _getMinMaxPages(int $page, int $pages)
    {
        $window = 10;

        if ($page <= $window) {
            $min_page = 1;
            $max_page = \min(2 * $window, $pages);
        } elseif ($page > $window && $page + $window <= $pages) {
            $min_page = ($page - $window) + 1;
            $max_page = $page + $window;
        } else {
            $min_page = ($page - (2 * $window - ($pages - $page))) + 1;
            $max_page = $pages;
        }

        // Make sure min_page is always at least 1
        // and max_page is never greater than $pages
        $min_page = \max($min_page, 1);
        $max_page = \min($max_page, $pages);

        return [$min_page, $max_page];
    }

    /**
     * Do multi-page navigation.  Displays the prev, next and page options.
     *
     * @param int   $page      - the page currently viewed
     * @param int   $pages     - the maximum number of pages
     * @param array $gets      -  the parameters to include in the link to the wanted page
     * @param int   $max_width - the number of pages to make available at any one time (default = 20)
     *
     * @return null|string the pagination links
     */
    private function _printPages($page, $pages, $gets, $max_width = 20)
    {
        $lang = $this->lang;
        $page = (int) $page;

        if (0 > $page || $page > $pages || 1 >= $pages || 0 >= $max_width) {
            return;
        }

        unset($gets['page']);
        $url = \http_build_query($gets);

        $result = '<p style="text-align: center">' . \PHP_EOL;

        if (1 !== $page) {
            $result .= \sprintf('<a class="pagenav" href="?%s&page=1">%s</a>%s&nbsp;', $url, $lang['strfirst'], \PHP_EOL);
            $result .= \sprintf('<a class="pagenav" href="?%s&page=%s">%s</a>%s', $url, $page - 1, $lang['strprev'], \PHP_EOL);
        }

        [$min_page, $max_page] = $this->_getMinMaxPages($page, $pages);

        for ($i = $min_page; $i <= $max_page; ++$i) {
            $result .= (($i === $page) ? $i : \sprintf('<a class="pagenav" href="display?%s&page=%s">%s</a>', $url, $i, $i)) . \PHP_EOL;
        }

        if ($page !== $pages) {
            $result .= \sprintf('<a class="pagenav" href="?%s&page=%s">%s</a>%s', $url, $page + 1, $lang['strnext'], \PHP_EOL);
            $result .= \sprintf('&nbsp;<a class="pagenav" href="?%s&page=%s">%s</a>%s', $url, $pages, $lang['strlast'], \PHP_EOL);
        }
        $result .= '</p>' . \PHP_EOL;

        return $result;
    }
}
