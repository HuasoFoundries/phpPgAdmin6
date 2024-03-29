<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use ADOFieldObject;
use ADORecordSet;
use Exception;
use PHPPgAdmin\Core\ADOdbException;
use PHPPgAdmin\Traits\InsertEditRowTrait;

/**
 * Base controller class.
 */
class DisplayController extends BaseController
{
    use InsertEditRowTrait;

    /**
     * Default method to render the controller according to the action parameter.
     *
     * @return null|\Slim\Http\Response
     */
    public function render()
    {
        $this->misc = $this->misc;

        if ('dobrowsefk' === $this->action) {
            return $this->doBrowseFK();
        }

        \set_time_limit(0);

        $scripts = '<script src="assets/js/display.js" type="text/javascript"></script>';

        $scripts .= '<script type="text/javascript">' . \PHP_EOL;
        $scripts .= "var Display = {\n";
        $scripts .= "errmsg: '" . \str_replace("'", "\\'", $this->lang['strconnectionfail']) . "'\n";
        $scripts .= "};\n";
        $this->scripts = $scripts . '</script>' . \PHP_EOL;

        $footer_template = 'footer.twig';
        $header_template = 'header.twig';
        $browseResult = [];
        \ob_start();
        $jsonResult = [];

        switch ($this->action) {
            case 'editrow':
                $this->view->offsetSet('codemirror', true);

                if (isset($_POST['save'])) {
                    $this->doEditRow();
                } else {
                    $this->doBrowse();
                }

                break;
            case 'confeditrow':
                // d($_REQUEST);
                $this->formEditRow();

                break;
            case 'delrow':
                $this->view->offsetSet('codemirror', true);

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
                $this->view->offsetSet('datatables', true);
                $this->view->offsetSet('codemirror', true);

                $jsonResult = $this->doBrowse();

                break;
        }
        $output = \ob_get_clean();
        $json = (bool) ($this->getRequestParam('json', null));

        if ($json) {
            return responseInstance()->withJson($jsonResult);
        }
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
        $this->view->offsetSet('serverSide', 1);
        $this->printHeader($title, $this->scripts, true, $header_template);

        $this->printBody();

        echo $output;

        return $this->printFooter(true, $footer_template);
    }

    /**
     * Displays requested data.
     *
     * @param mixed $msg
     *
     * @return (array[]|int)[]|string|null
     *
     * @psalm-return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array>}|string|null
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

            $_REQUEST['query'] = \sprintf(
                'SELECT "%s",
            count(*) AS "count"
            FROM "%s"."%s"
            GROUP BY "%s" ORDER BY "%s"',
                $object,
                $f_schema,
                $f_table,
                $object,
                $object
            );
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

        $json = (bool) ($this->getRequestParam('json', null));

        //$object = $this->setIfIsset($object, $_REQUEST[$subject]);
        $trailsubject = $subject;

        if ('table' === $subject && !$this->getRequestParam($subject, null)) {
            $trailsubject = 'database';
        }
        $this->printTrail($trailsubject, !$json);
        $tabsPosition = 'browse';

        if ('database' === $trailsubject) {
            $tabsPosition = 'sql';
        } elseif ('column' === $trailsubject) {
            $tabsPosition = 'colproperties';
        }

        $this->printTabs($trailsubject, $tabsPosition, !$json);

        [$query, $title, $type] = $this->getQueryTitleAndType($data, $object);

        $this->printTitle($this->lang[$title], null, !$json);

        $this->printMsg($msg, !$json);

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

        $paginate = $this->getRequestParam('paginate', null);

        try {
            $max_pages = 0;

            // Retrieve page from query.  $max_pages is returned by reference.
            $resultset = $data->browseQuery(
                $type,
                $object,
                $query,
                $sortkey,
                $sortdir,
                (int) ($page ?? 1),
                $this->conf['max_rows'],
                $max_pages
            );

            if ($json) {
                $jsonResult = [];

                while (!$resultset->EOF) {
                    $jsonResult[] = $this->getJsonRowCells($resultset, isset($object));
                    $resultset->MoveNext();
                }

                return [
                    'draw' => 1,
                    'recordsTotal' => $max_pages,
                    'recordsFiltered' => $max_pages,
                    'data' => $jsonResult,
                ];
            }
        } catch (ADOdbException $e) {
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
            'subject' => $subject,
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
        // d($_gets);
        if ($save_history && \is_object($resultset) && ('QUERY' === $type)) {
            //{
            $this->misc->saveScriptHistory($_REQUEST['query']);
        }

        $query = $query ?: \sprintf(
            'SELECT * FROM %s.%s',
            $_REQUEST['schema'],
            $object
        );

        //$query = isset($_REQUEST['query'])? $_REQUEST['query'] : "select * from {$_REQUEST['schema']}.{$_REQUEST['table']};";

        //die(htmlspecialchars($query));

        $formHTML = '<form method="post" id="sqlform" action="' . $_SERVER['REQUEST_URI'] . '">';
        $formHTML .= $this->view->form;

        if ($object) {
            $formHTML .= '<input type="hidden" name="' . $subject . '" value="' . \htmlspecialchars($object) . '" />' . \PHP_EOL;
        }
        $search_path = \htmlspecialchars($_REQUEST['search_path'] ?? null);
        $formHTML .= '<input type="hidden" name="search_path" id="search_path" size="45" value="' . $search_path . '" />';

        if (isset($_REQUEST['paginate'])) {
            $formHTML .= '<input type="hidden" name="paginate" value="on" />';
        }
        //  $formHTML.=  '<input type="checkbox" name="json" />';
        $formHTML .= '<textarea width="90%" name="query"  id="query" rows="5" cols="100" resizable="true">';
        $formHTML .= \htmlspecialchars($query);
        $formHTML .= '</textarea><br><input type="submit"/>';
        $formHTML .= '</form>';
        echo $formHTML;

        $this->printResultsTable($resultset, $page, $max_pages, $_gets, $object);
        // Navigation links

        $navlinks = $this->getBrowseNavLinks($type, $_gets, $page, $subject, $object, $resultset);

        return $this->printNavLinks($navlinks, 'display-browse', \get_defined_vars());
    }

    /**
     * @psalm-return array{0: mixed, 1: string, 2: string}
     *
     * @param mixed $data
     * @param mixed $object
     *
     * @return (mixed|string)[]
     */
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
     * @param mixed $resultset
     * @param mixed $page
     * @param mixed $max_pages
     * @param mixed $object
     */
    public function printResultsTable($resultset, $page, $max_pages, array $_gets, $object): void
    {
        if (!\is_object($resultset) || 0 >= $resultset->RecordCount()) {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;

            return;
        }

        $data = $this->misc->getDatabaseAccessor();

        [$actions, $key] = $this->_getKeyAndActions($resultset, $object, $data, $page, $_gets);
        //d($actions['actionbuttons']);
        $fkey_information = $this->getFKInfo();
        // Show page navigation
        $paginator = $this->_printPages($page, $max_pages, $_gets);
        echo $paginator;
        echo '<table id="data">' . \PHP_EOL;
        echo '<tr>';

        try {
            // Display edit and delete actions if we have a key
            $display_action_column = (0 < \count($actions['actionbuttons']) && 0 < \count($key));
        } catch (Exception $e) {
            $display_action_column = false;
        }

        echo $display_action_column ? \sprintf(
            '<th class="data">%s</th>',
            $this->lang['stractions']
        ) . \PHP_EOL : '';

        // we show OIDs only if we are in TABLE or SELECT type browsing
        $this->printTableHeaderCells($resultset, $_gets, isset($object));

        echo '</tr>' . \PHP_EOL;

        \reset($resultset->fields);

        $trclass = 'data2';
        $buttonclass = 'opbutton2';

        while (!$resultset->EOF) {
            $trclass = ('data2' === $trclass) ? 'data1' : 'data2';
            $buttonclass = ('opbutton2' === $buttonclass) ? 'opbutton1' : 'opbutton2';

            echo \sprintf(
                '<tr class="%s">',
                $trclass
            ) . \PHP_EOL;

            $this->_printResultsTableActionButtons($resultset, $key, $actions, $display_action_column, $buttonclass);

            $this->printTableRowCells($resultset, $fkey_information, isset($object));

            echo '</tr>' . \PHP_EOL;
            $resultset->MoveNext();
        }
        echo '</table>' . \PHP_EOL;

        echo '<p>', $resultset->RecordCount(), \sprintf(
            ' %s</p>',
            $this->lang['strrows']
        ) . \PHP_EOL;
        // Show page navigation
        echo $paginator;
    }

    /**
     * Print table header cells.
     *
     * @param ADORecordSet $resultset set of results from getRow operation
     * @param array|bool   $args      - associative array for sort link parameters, or false if there isn't any
     * @param bool         $withOid   either to display OIDs or not
     */
    public function printTableHeaderCells(&$resultset, $args, $withOid): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if (!\is_object($resultset) || 0 >= $resultset->RecordCount()) {
            return;
        }
        $dttFields = [];

        foreach (\array_keys($resultset->fields) as $index => $key) {
            if (($key === $data->id) && (!($withOid && $this->conf['show_oids']))) {
                continue;
            }
            $finfo = $resultset->FetchField($index);

            $dttFields[] = ['data' => $finfo->name];

            if (false === $args) {
                echo '<th class="data">', $this->misc->printVal($finfo->name), '</th>' . \PHP_EOL;

                continue;
            }

            $args['page'] = $_REQUEST['page'];
            $args['sortkey'] = $index + 1;
            // Sort direction opposite to current direction, unless it's currently ''
            $args['sortdir'] = ('asc' === $_REQUEST['sortdir'] && ($index + 1) === $_REQUEST['sortkey']) ? 'desc' : 'asc';

            $sortLink = \http_build_query($args);

            echo \sprintf(
                '<th class="data"><a href="?%s">',
                $sortLink
            );
            echo $this->misc->printVal($finfo->name);

            if (($index + 1) === $_REQUEST['sortkey']) {
                $icon = ('asc' === $_REQUEST['sortdir']) ? $this->view->icon('RaiseArgument') : $this->view->icon('LowerArgument');
                echo \sprintf(
                    '<img src="%s" alt="%s">',
                    $icon,
                    $_REQUEST['sortdir']
                );
            }
            echo '</a></th>' . \PHP_EOL;
        }
        $this->view->offsetSet('dttFields', $dttFields);
        \reset($resultset->fields);
    }

    /**
     * Print table rows.
     *
     * @param ADORecordSet $resultset The resultset
     * @param bool         $withOid   either to display OIDs or not
     */
    public function getJsonRowCells(&$resultset, $withOid): array
    {
        $data = $this->misc->getDatabaseAccessor();
        $j = 0;

        $strings = $this->getRequestParam('string', 'collapsed');
        $result = [];

        foreach ($resultset->fields as $fieldname => $fieldvalue) {
            /** @var ADOFieldObject */
            $finfo = $this->FetchField($resultset, $j++);

            if (($fieldname === $data->id) && (!($withOid && $this->conf['show_oids']))) {
                continue;
            }
            $result[$finfo->name] = $fieldvalue;
        }

        return $result;
    }

    /**
     * Print table rows.
     *
     * @param ADORecordSet $resultset        The resultset
     * @param array        $fkey_information The fkey information
     * @param bool         $withOid          either to display OIDs or not
     */
    public function printTableRowCells(&$resultset, &$fkey_information, $withOid): void
    {
        $data = $this->misc->getDatabaseAccessor();
        $j = 0;

        $strings = $this->getRequestParam('string', 'collapsed');

        foreach ($resultset->fields as $fieldName => $fieldValue) {
            /** @var ADOFieldObject */
            $finfo = $this->FetchField($resultset, $j++);

            if (($fieldName === $data->id) && (!($withOid && $this->conf['show_oids']))) {
                continue;
            }
            $printvalOpts = ['null' => true, 'clip' => ('collapsed' === $strings)];

            if (null !== $fieldValue && '' === $fieldValue) {
                echo '<td>&nbsp;</td>';
            } else {
                echo '<td style="white-space:nowrap;">';

                if ((null !== $fieldValue) && isset($fkey_information['byfield'][$fieldName])) {
                    $this->_printFKLinks($resultset, $fkey_information, $fieldName, $fieldValue, $printvalOpts);
                }
                $val = $this->misc->printVal($fieldValue, $finfo->type, $printvalOpts);

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
    public function formEditRow($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $key = $this->_unserializeIfNotArray($_REQUEST, 'key');

        $this->printTrail($_REQUEST['subject']);
        $this->printTitle($this->lang['streditrow']);
        $this->printMsg($msg);

        $attrs = $data->getTableAttributes($_REQUEST['table']);
        $resultset = $data->browseRow($_REQUEST['table'], $key);

        $fksprops = $this->_getFKProps();

        echo '<form action="display" method="post" id="ac_form">' . \PHP_EOL;

        $elements = 0;
        $error = true;

        if (1 === $resultset->RecordCount() && 0 < $attrs->RecordCount()) {
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
            ) . \PHP_EOL;
            echo \sprintf(
                '<th class="data">%s</th><th class="data">%s</th></tr>',
                $this->lang['strnull'],
                $this->lang['strvalue']
            );

            $i = 0;

            while (!$attrs->EOF) {
                $attrs->fields['attnotnull'] = $data->phpBool($attrs->fields['attnotnull']);
                $id = (0 === ($i % 2) ? '1' : '2');

                // Initialise variables
                if (!isset($_REQUEST['format'][$attrs->fields['attname']])) {
                    $_REQUEST['format'][$attrs->fields['attname']] = 'VALUE';
                }

                echo \sprintf(
                    '<tr class="data%s">',
                    $id
                ) . \PHP_EOL;
                echo '<td style="white-space:nowrap;">', $this->misc->printVal($attrs->fields['attname']), '</td>';
                echo '<td style="white-space:nowrap;">' . \PHP_EOL;
                echo $this->misc->printVal($data->formatType($attrs->fields['type'], $attrs->fields['atttypmod']));
                echo '<input type="hidden" name="types[', \htmlspecialchars($attrs->fields['attname']), ']" value="',
                \htmlspecialchars($attrs->fields['type']), '" /></td>';
                ++$elements;
                echo '<td style="white-space:nowrap;">' . \PHP_EOL;
                echo '<select name="format[' . \htmlspecialchars($attrs->fields['attname']), ']">' . \PHP_EOL;
                echo '<option value="VALUE"', ($_REQUEST['format'][$attrs->fields['attname']] === 'VALUE') ? ' selected="selected"' : '', \sprintf(
                    '>%s</option>',
                    $this->lang['strvalue']
                ) . \PHP_EOL;
                $selected = ($_REQUEST['format'][$attrs->fields['attname']] === 'EXPRESSION') ? ' selected="selected"' : '';
                echo '<option value="EXPRESSION"' . $selected . \sprintf(
                    '>%s</option>',
                    $this->lang['strexpression']
                ) . \PHP_EOL;
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
                    echo \sprintf(
                        '<label><span><input type="checkbox" class="nullcheckbox" name="nulls[%s]"',
                        $attrs->fields['attname']
                    ),
                    isset($_REQUEST['nulls'][$attrs->fields['attname']]) ? ' checked="checked"' : '', ' /></span></label></td>' . \PHP_EOL;
                    ++$elements;
                } else {
                    echo '&nbsp;</td>';
                }

                echo \sprintf(
                    '<td id="row_att_%s" style="white-space:nowrap;">',
                    $attrs->fields['attnum']
                );

                $extras = [];

                // If the column allows nulls, then we put a JavaScript action on the data field to unset the
                // NULL checkbox as soon as anything is entered in the field.  We use the $elements variable to
                // keep track of which element offset we're up to.  We can't refer to the null checkbox by name
                // as it contains '[' and ']' characters.
                if (!$attrs->fields['attnotnull']) {
                    $extras['class'] = 'insert_row_input';
                }

                if ((false !== $fksprops) && isset($fksprops['byfield'][$attrs->fields['attnum']])) {
                    $extras['id'] = \sprintf(
                        'attr_%s',
                        $attrs->fields['attnum']
                    );
                    $extras['autocomplete'] = 'off';
                }

                echo $data->printField(\sprintf(
                    'values[%s]',
                    $attrs->fields['attname']
                ), $resultset->fields[$attrs->fields['attname']], $attrs->fields['type'], $extras);

                echo '</td>';
                ++$elements;
                echo '</tr>' . \PHP_EOL;
                ++$i;
                $attrs->MoveNext();
            }
            echo '</table>' . \PHP_EOL;

            $error = false;
        } elseif (1 !== $resultset->RecordCount()) {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strrownotunique']
            ) . \PHP_EOL;
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strinvalidparam']
            ) . \PHP_EOL;
        }

        echo '<input type="hidden" name="action" value="editrow" />' . \PHP_EOL;

        echo $this->view->form;

        $subject = $this->getRequestParam('subject', $_REQUEST['subject'] ?? null);
        $return = $this->getRequestParam('return', $_REQUEST['return'] ?? null);
        echo isset($_REQUEST['table']) ? \sprintf(
            '<input type="hidden" name="table" value="%s" />%s',
            \htmlspecialchars($_REQUEST['table']),
            \PHP_EOL
        ) : '';

        echo isset($subject) ? \sprintf(
            '<input type="hidden" name="subject" value="%s" />%s',
            \htmlspecialchars($_REQUEST['subject']),
            \PHP_EOL
        ) : '';

        echo isset($_REQUEST['query']) ? \sprintf(
            '<input type="hidden" name="query" value="%s" />%s',
            \htmlspecialchars($_REQUEST['query']),
            \PHP_EOL
        ) : '';

        echo isset($_REQUEST['count']) ? \sprintf(
            '<input type="hidden" name="count" value="%s" />%s',
            \htmlspecialchars($_REQUEST['count']),
            \PHP_EOL
        ) : '';

        echo isset($return) ? \sprintf(
            '<input type="hidden" name="return" value="%s" />%s',
            \htmlspecialchars($_REQUEST['return']),
            \PHP_EOL
        ) : '';

        echo '<input type="hidden" name="page" value="', \htmlspecialchars($_REQUEST['page']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="sortkey" value="', \htmlspecialchars($_REQUEST['sortkey']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="sortdir" value="', \htmlspecialchars($_REQUEST['sortdir']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="strings" value="', \htmlspecialchars($_REQUEST['strings']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="key" value="', \htmlspecialchars(\urlencode(\serialize($key))), '" />' . \PHP_EOL;
        echo '<p>';

        if (!$error) {
            echo \sprintf(
                '<input type="submit" name="save" accesskey="r" value="%s" />',
                $this->lang['strsave']
            ) . \PHP_EOL;
        }

        echo \sprintf(
            '<input type="submit" name="cancel" value="%s" />',
            $this->lang['strcancel']
        ) . \PHP_EOL;

        if (false !== $fksprops) {
            $autocomplete_string = \sprintf(
                '<input type="checkbox" id="no_ac" value="0" /><label for="no_ac">%s</label>',
                $this->lang['strac']
            );

            if ('default off' !== $this->conf['autocomplete']) {
                $autocomplete_string = \sprintf(
                    '<input type="checkbox" id="no_ac" value="1" checked="checked" /><label for="no_ac">%s</label>',
                    $this->lang['strac']
                );
            }
            echo $autocomplete_string . \PHP_EOL;
        }

        echo '</p>' . \PHP_EOL;
        echo '</form>' . \PHP_EOL;
        echo '<script src="assets/js/insert_or_edit_row.js" type="text/javascript"></script>';
    }

    /**
     * @param mixed $type
     * @param mixed $page
     * @param mixed $object
     * @param mixed $resultset
     *
     * @return ((array|mixed|string)[][]|mixed)[][]
     *
     * @psalm-return array{back?: array{attr: array{href: array{url: mixed, urlvars: mixed}}, content: mixed}, edit?: array{attr: array{href: array{url: string, urlvars: array{server: mixed, database: mixed, action: string, paginate: string}}}, content: mixed}, collapse: array{attr: array{href: array{url: string, urlvars: array{strings: string, page: mixed}}}, content: mixed}, createview?: array{attr: array{href: array{url: string, urlvars: array{server: mixed, database: mixed, action: string, formDefinition: mixed}}}, content: mixed}, download?: array{attr: array{href: array{url: string, urlvars: array{server: mixed, database: mixed}}}, content: mixed}, insert?: array{attr: array{href: array{url: string, urlvars: array{server: mixed, database: mixed, action: string, table: mixed}}}, content: mixed}, refresh: array{attr: array{href: array{url: string, urlvars: array{strings: mixed, page: mixed}}}, content: mixed}}
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
        if (isset($_REQUEST['query'], $resultset) && \is_object($resultset) && 0 < $resultset->RecordCount()) {
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
     * Performs actual edition of row.
     *
     * @return (array[]|int)[]|string|null
     *
     * @psalm-return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array>}|string|null
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
    public function doDelRow($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail($_REQUEST['subject']);
            $this->printTitle($this->lang['strdeleterow']);

            $resultset = $data->browseRow($_REQUEST['table'], $_REQUEST['key']);

            echo '<form action="display" method="post">' . \PHP_EOL;
            echo $this->view->form;

            if (1 === $resultset->RecordCount()) {
                echo \sprintf(
                    '<p>%s</p>',
                    $this->lang['strconfdeleterow']
                ) . \PHP_EOL;

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
                echo \sprintf(
                    '<input type="submit" name="yes" value="%s" />',
                    $this->lang['stryes']
                ) . \PHP_EOL;
                echo \sprintf(
                    '<input type="submit" name="no" value="%s" />',
                    $this->lang['strno']
                ) . \PHP_EOL;
            } elseif (1 !== $resultset->RecordCount()) {
                echo \sprintf(
                    '<p>%s</p>',
                    $this->lang['strrownotunique']
                ) . \PHP_EOL;
                echo \sprintf(
                    '<input type="submit" name="cancel" value="%s" />',
                    $this->lang['strcancel']
                ) . \PHP_EOL;
            } else {
                echo \sprintf(
                    '<p>%s</p>',
                    $this->lang['strinvalidparam']
                ) . \PHP_EOL;
                echo \sprintf(
                    '<input type="submit" name="cancel" value="%s" />',
                    $this->lang['strcancel']
                ) . \PHP_EOL;
            }

            if (isset($_REQUEST['table'])) {
                echo \sprintf(
                    '<input type="hidden" name="table" value="%s"  />%s',
                    \htmlspecialchars($_REQUEST['table']),
                    \PHP_EOL
                );
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
     * @return (array[]|string)[]
     *
     * @psalm-return array{byconstr: array<array-key, array{url_data: string, fkeys: array, consrc: mixed}>, byfield: array<array-key, list<mixed>>, common_url?: string}
     */
    public function &getFKInfo()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Get the foreign key(s) information from the current table
        $fkey_information = ['byconstr' => [], 'byfield' => []];

        if (isset($_REQUEST['table'])) {
            $constraints = $data->getConstraintsWithFields($_REQUEST['table']);

            if (0 < $constraints->RecordCount()) {
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
                    $constraints->MoveNext();
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

        foreach (\array_keys($_REQUEST['fkey']) as $x) {
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

        if (\is_object($resultset) && 0 < $resultset->RecordCount()) {
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

    private function FetchField(ADORecordSet $ADORecordSet, int $index): ADOFieldObject
    {
        return $ADORecordSet->FetchField($index);
    }

    /**
     * @psalm-return array{0: array{actionbuttons: array{edit: array{content: mixed, attr: array{href: array{url: string, urlvars: array{action: mixed|string, strings: mixed, page: mixed}}}}, delete: array{content: mixed, attr: array{href: array{url: string, urlvars: array{action: mixed|string, strings: mixed, page: mixed}}}}}, place: string}, 1: array<empty, empty>|iterable}
     *
     * @param mixed $object
     * @param mixed $data
     * @param mixed $page
     *
     * @return (((((mixed|string)[]|string)[][]|mixed)[][]|string)[]|iterable)[]
     */
    private function _getKeyAndActions(object $resultset, $object, $data, $page, array $_gets)
    {
        $key = [];
        $strings = $_gets['strings'];

        // Fetch unique row identifier, if this is a table browse request.
        if ($object) {
            $key = $data->getRowIdentifier($object);
            // d([$object=>$key]);
        }
        // -1 means no unique keys, other non iterable should be discarded as well
        if (-1 === $key || !\is_iterable($key)) {
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

    /**
     * @param array|iterable                                       $key
     * @param ((((mixed|string)[]|string)[][]|mixed)[][]|string)[] $actions
     */
    private function _printResultsTableActionButtons(ADORecordSet $resultset, $key, $actions, bool $display_action_column, string $buttonclass): void
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
            $keys_array[\sprintf(
                'key[%s]',
                $v
            )] = $resultset->fields[$v];
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
        echo \sprintf(
            '<td class="%s" style="white-space:nowrap">',
            $buttonclass
        );

        foreach ($actions['actionbuttons'] as $action) {
            $this->printLink($action, true, __METHOD__);
        }
        echo '</td>' . \PHP_EOL;
    }

    /**
     * @param bool[] $printvalOpts
     * @param mixed  $fieldName
     * @param mixed  $fieldValue
     */
    private function _printFKLinks(ADORecordSet $resultset, array $fkey_information, $fieldName, $fieldValue, array &$printvalOpts): void
    {
        if ((null === $fieldValue) || !isset($fkey_information['byfield'][$fieldName])) {
            return;
        }

        foreach ($fkey_information['byfield'][$fieldName] as $conid) {
            $query_params = $fkey_information['byconstr'][$conid]['url_data'];

            foreach ($fkey_information['byconstr'][$conid]['fkeys'] as $p_field => $f_field) {
                $query_params .= '&amp;' . \urlencode(\sprintf(
                    'fkey[%s]',
                    $f_field
                )) . '=' . \urlencode($resultset->fields[$p_field]);
            }

            // $fkey_information['common_url'] is already urlencoded
            $query_params .= '&amp;' . $fkey_information['common_url'];
            $title = \htmlentities($fkey_information['byconstr'][$conid]['consrc'], \ENT_QUOTES, 'UTF-8');
            echo '<div style="display:inline-block;">';
            echo \sprintf(
                '<a class="fk fk_%s" href="display?%s">',
                \htmlentities($conid, \ENT_QUOTES, 'UTF-8'),
                $query_params
            );
            echo \sprintf(
                '<img src="%s" style="vertical-align:middle;" alt="[fk]" title="%s" />',
                $this->view->icon('ForeignKey'),
                $title
            );
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

    /**
     * @psalm-return array{0: int, 1: int}
     *
     * @return int[]
     */
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
            $result .= \sprintf(
                '<a class="pagenav" href="?%s&page=1">%s</a>%s&nbsp;',
                $url,
                $lang['strfirst'],
                \PHP_EOL
            );
            $result .= \sprintf(
                '<a class="pagenav" href="?%s&page=%s">%s</a>%s',
                $url,
                $page - 1,
                $lang['strprev'],
                \PHP_EOL
            );
        }

        [$min_page, $max_page] = $this->_getMinMaxPages($page, $pages);

        for ($i = $min_page; $i <= $max_page; ++$i) {
            $result .= (($i === $page) ? $i : \sprintf(
                '<a class="pagenav" href="display?%s&page=%s">%s</a>',
                $url,
                $i,
                $i
            )) . \PHP_EOL;
        }

        if ($page !== $pages) {
            $result .= \sprintf(
                '<a class="pagenav" href="?%s&page=%s">%s</a>%s',
                $url,
                $page + 1,
                $lang['strnext'],
                \PHP_EOL
            );
            $result .= \sprintf(
                '&nbsp;<a class="pagenav" href="?%s&page=%s">%s</a>%s',
                $url,
                $pages,
                $lang['strlast'],
                \PHP_EOL
            );
        }

        return $result . ('</p>' . \PHP_EOL);
    }
}
