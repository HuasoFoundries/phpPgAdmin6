<?php

/**
 * PHPPgAdmin v6.0.0-beta.45
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class DatabaseController extends BaseController
{
    use \PHPPgAdmin\Traits\AdminTrait;
    public $table_place = 'database-variables';
    public $fields;
    public $controller_title = 'strdatabase';

    private function _highlight($string, $term)
    {
        return str_replace($term, "<b>{$term}</b>", $string);
    }

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        if ('refresh_locks' == $this->action) {
            return $this->currentLocks(true);
        }

        if ('refresh_processes' == $this->action) {
            return $this->currentProcesses(true);
        }
        $scripts = '';
        // normal flow
        if ('locks' == $this->action || 'processes' == $this->action) {
            $scripts .= '<script src="' . \SUBFOLDER . '/assets/js/database.js" type="text/javascript"></script>';

            $refreshTime = $this->conf['ajax_refresh'] * 1500;

            $scripts .= "<script type=\"text/javascript\">\n";
            $scripts .= "var Database = {\n";
            $scripts .= "ajax_time_refresh: {$refreshTime},\n";
            $scripts .= "str_start: {text:'{$this->lang['strstart']}',icon: '" . $this->misc->icon('Execute') . "'},\n";
            $scripts .= "str_stop: {text:'{$this->lang['strstop']}',icon: '" . $this->misc->icon('Stop') . "'},\n";
            $scripts .= "load_icon: '" . $this->misc->icon('Loading') . "',\n";
            $scripts .= "server:'{$_REQUEST['server']}',\n";
            $scripts .= "dbname:'{$_REQUEST['database']}',\n";
            $scripts .= "action:'refresh_{$this->action}',\n";
            $scripts .= "errmsg: '" . str_replace("'", "\\'", $this->lang['strconnectionfail']) . "'\n";
            $scripts .= "};\n";
            $scripts .= "</script>\n";
        }

        $header_template = 'header.twig';
        $footer_template = 'footer.twig';
        // @todo convert all these methods to return text instead of print text
        ob_start();
        switch ($this->action) {
            case 'find':
                if (isset($_REQUEST['term'])) {
                    $this->doFind(false);
                } else {
                    $this->doFind(true);
                }

                break;
            case 'sql':
                $this->doSQL();
                $header_template = 'header_sqledit.twig';
                $footer_template = 'footer_sqledit.twig';

                break;
            case 'variables':
                $this->doVariables();

                break;
            case 'processes':
                $this->doProcesses();

                break;
            case 'locks':
                $this->doLocks();

                break;
            case 'export':
                $this->doExport();

                break;
            case 'signal':
                $this->doSignal();

                break;
            default:
                if (false === $this->adminActions($this->action, 'database')) {
                    $header_template = 'header_sqledit.twig';
                    $footer_template = 'footer_sqledit.twig';
                    $this->doSQL();
                }

                break;
        }
        $output = ob_get_clean();

        $this->printHeader($this->headerTitle(), $scripts, true, $header_template);
        $this->printBody();

        echo $output;

        $this->printFooter(true, $footer_template);
    }

    public function doTree($print = true)
    {
        $reqvars = $this->misc->getRequestVars('database');
        $tabs    = $this->misc->getNavTabs('database');
        $items   = $this->adjustTabsForTree($tabs);

        $attrs = [
            'text'   => Decorator::field('title'),
            'icon'   => Decorator::field('icon'),
            'action' => Decorator::actionurl(
                Decorator::field('url'),
                $reqvars,
                Decorator::field('urlvars'),
                [
                    'database' => $this->misc->getDatabase(),
                ]
            ),
            'branch' => Decorator::branchurl(
                Decorator::field('url'),
                $reqvars,
                Decorator::field('urlvars'),
                [
                    'action'   => 'tree',
                    'database' => $this->misc->getDatabase(),
                ]
            ),
        ];

        return $this->printTree($items, $attrs, 'database', $print);
    }

    /**
     * Sends a signal to a process.
     */
    public function doSignal()
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->sendSignal($_REQUEST['pid'], $_REQUEST['signal']);
        if (0 == $status) {
            $this->doProcesses($this->lang['strsignalsent']);
        } else {
            $this->doProcesses($this->lang['strsignalsentbad']);
        }
    }

    /**
     * Searches for a named database object.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doFind($confirm = true, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'term', '');

        $this->coalesceArr($_REQUEST, 'filter', '');

        $this->printTrail('database');
        $this->printTabs('database', 'find');
        $this->printMsg($msg);

        echo '<form action="' . \SUBFOLDER . "/src/views/database\" method=\"post\">\n";
        echo '<p><input name="term" value="', htmlspecialchars($_REQUEST['term']),
            "\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" />\n";
        // Output list of filters.  This is complex due to all the 'has' and 'conf' feature possibilities
        echo "<select name=\"filter\">\n";
        echo "\t<option value=\"\"", ('' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strallobjects']}</option>\n";
        echo "\t<option value=\"SCHEMA\"", ('SCHEMA' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strschemas']}</option>\n";
        echo "\t<option value=\"TABLE\"", ('TABLE' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strtables']}</option>\n";
        echo "\t<option value=\"VIEW\"", ('VIEW' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strviews']}</option>\n";
        echo "\t<option value=\"SEQUENCE\"", ('SEQUENCE' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strsequences']}</option>\n";
        echo "\t<option value=\"COLUMN\"", ('COLUMN' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strcolumns']}</option>\n";
        echo "\t<option value=\"RULE\"", ('RULE' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strrules']}</option>\n";
        echo "\t<option value=\"INDEX\"", ('INDEX' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strindexes']}</option>\n";
        echo "\t<option value=\"TRIGGER\"", ('TRIGGER' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strtriggers']}</option>\n";
        echo "\t<option value=\"CONSTRAINT\"", ('CONSTRAINT' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strconstraints']}</option>\n";
        echo "\t<option value=\"FUNCTION\"", ('FUNCTION' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strfunctions']}</option>\n";
        echo "\t<option value=\"DOMAIN\"", ('DOMAIN' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strdomains']}</option>\n";

        if ($this->conf['show_advanced']) {
            echo "\t<option value=\"AGGREGATE\"", ('AGGREGATE' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['straggregates']}</option>\n";
            echo "\t<option value=\"TYPE\"", ('TYPE' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strtypes']}</option>\n";
            echo "\t<option value=\"OPERATOR\"", ('OPERATOR' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['stroperators']}</option>\n";
            echo "\t<option value=\"OPCLASS\"", ('OPCLASS' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['stropclasses']}</option>\n";
            echo "\t<option value=\"CONVERSION\"", ('CONVERSION' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strconversions']}</option>\n";
            echo "\t<option value=\"LANGUAGE\"", ('LANGUAGE' == $_REQUEST['filter']) ? ' selected="selected"' : '', ">{$this->lang['strlanguages']}</option>\n";
        }
        echo "</select>\n";
        echo "<input type=\"submit\" value=\"{$this->lang['strfind']}\" />\n";
        echo $this->misc->form;
        echo '<input type="hidden" name="action" value="find" /></p>' . "\n";
        echo '<input type="hidden" name="confirm" value="true" /></p>' . "\n";
        echo "</form>\n";

        // Default focus
        $this->setFocus('forms[0].term');

        // If a search term has been specified, then perform the search
        // and display the results, grouped by object type
        if (!$confirm && '' != $_REQUEST['term']) {
            $rs = $data->findObject($_REQUEST['term'], $_REQUEST['filter']);
            if ($rs->RecordCount() > 0) {
                $curr = '';
                while (!$rs->EOF) {
                    // Output a new header if the current type has changed, but not if it's just changed the rule type
                    if ($rs->fields['type'] != $curr) {
                        // Short-circuit in the case of changing from table rules to view rules; table cols to view cols;
                        // table constraints to domain constraints
                        if ('RULEVIEW' == $rs->fields['type'] && 'RULETABLE' == $curr) {
                            $curr = $rs->fields['type'];
                        } elseif ('COLUMNVIEW' == $rs->fields['type'] && 'COLUMNTABLE' == $curr) {
                            $curr = $rs->fields['type'];
                        } elseif ('CONSTRAINTTABLE' == $rs->fields['type'] && 'CONSTRAINTDOMAIN' == $curr) {
                            $curr = $rs->fields['type'];
                        } else {
                            if ('' != $curr) {
                                echo "</ul>\n";
                            }

                            $curr = $rs->fields['type'];
                            echo '<h3>';
                            switch ($curr) {
                                case 'SCHEMA':
                                    echo $this->lang['strschemas'];

                                    break;
                                case 'TABLE':
                                    echo $this->lang['strtables'];

                                    break;
                                case 'VIEW':
                                    echo $this->lang['strviews'];

                                    break;
                                case 'SEQUENCE':
                                    echo $this->lang['strsequences'];

                                    break;
                                case 'COLUMNTABLE':
                                case 'COLUMNVIEW':
                                    echo $this->lang['strcolumns'];

                                    break;
                                case 'INDEX':
                                    echo $this->lang['strindexes'];

                                    break;
                                case 'CONSTRAINTTABLE':
                                case 'CONSTRAINTDOMAIN':
                                    echo $this->lang['strconstraints'];

                                    break;
                                case 'TRIGGER':
                                    echo $this->lang['strtriggers'];

                                    break;
                                case 'RULETABLE':
                                case 'RULEVIEW':
                                    echo $this->lang['strrules'];

                                    break;
                                case 'FUNCTION':
                                    echo $this->lang['strfunctions'];

                                    break;
                                case 'TYPE':
                                    echo $this->lang['strtypes'];

                                    break;
                                case 'DOMAIN':
                                    echo $this->lang['strdomains'];

                                    break;
                                case 'OPERATOR':
                                    echo $this->lang['stroperators'];

                                    break;
                                case 'CONVERSION':
                                    echo $this->lang['strconversions'];

                                    break;
                                case 'LANGUAGE':
                                    echo $this->lang['strlanguages'];

                                    break;
                                case 'AGGREGATE':
                                    echo $this->lang['straggregates'];

                                    break;
                                case 'OPCLASS':
                                    echo $this->lang['stropclasses'];

                                    break;
                            }
                            echo '</h3>';
                            echo "<ul>\n";
                        }
                    }

                    switch ($curr) {
                        case 'SCHEMA':
                            echo '<li><a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=";
                            echo $this->misc->printVal($rs->fields['name']), '">';
                            echo $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']);
                            echo "</a></li>\n";

                            break;
                        case 'TABLE':
                            echo '<li>';
                            echo "<a href=\"tables?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo '<a href="' . \SUBFOLDER . "/redirect/table?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=',
                            urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'VIEW':
                            echo '<li>';
                            echo "<a href=\"views?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo '<a href="' . \SUBFOLDER . "/redirect/view?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;view=',
                            urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'SEQUENCE':
                            echo '<li>';
                            echo "<a href=\"sequences?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"sequences?subject=sequence&amp;action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']),
                            '&amp;sequence=', urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'COLUMNTABLE':
                            echo '<li>';
                            echo '<a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"tblproperties?subject=table&{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                            echo "<a href=\"colproperties?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=',
                            urlencode($rs->fields['relname']), '&amp;column=', urlencode($rs->fields['name']), '">',
                            $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'COLUMNVIEW':
                            echo '<li>';
                            echo '<a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"viewproperties?subject=view&{$this->misc->href}&view=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                            echo "<a href=\"colproperties?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;view=',
                            urlencode($rs->fields['relname']), '&amp;column=', urlencode($rs->fields['name']), '">',
                            $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'INDEX':
                            echo '<li>';
                            echo '<a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo '<a href="' . \SUBFOLDER . "/redirect/table?{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                            echo "<a href=\"indexes?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=', urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'CONSTRAINTTABLE':
                            echo '<li>';
                            echo '<a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo '<a href="' . \SUBFOLDER . "/redirect/table?{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                            echo "<a href=\"constraints?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=',
                            urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'CONSTRAINTDOMAIN':
                            echo '<li>';
                            echo "<a href=\"domains?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"domains?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;domain=', urlencode($rs->fields['relname']), '">',
                            $this->misc->printVal($rs->fields['relname']), '.', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'TRIGGER':
                            echo '<li>';
                            echo '<a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo '<a href="' . \SUBFOLDER . "/redirect/table?{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                            echo "<a href=\"triggers?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=', urlencode($rs->fields['relname']), '">',
                            $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'RULETABLE':
                            echo '<li>';
                            echo '<a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo '<a href="' . \SUBFOLDER . "/redirect/table?{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                            echo "<a href=\"rules?subject=table&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;reltype=table&amp;table=',
                            urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'RULEVIEW':
                            echo '<li>';
                            echo '<a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo '<a href="' . \SUBFOLDER . "/redirect/view?{$this->misc->href}&view=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                            echo "<a href=\"rules?subject=view&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;reltype=view&amp;view=',
                            urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'FUNCTION':
                            echo '<li>';
                            echo "<a href=\"functions?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"functions?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;function=',
                            urlencode($rs->fields['name']), '&amp;function_oid=', urlencode($rs->fields['oid']), '">',
                            $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'TYPE':
                            echo '<li>';
                            echo "<a href=\"types?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"types?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;type=',
                            urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'DOMAIN':
                            echo '<li>';
                            echo "<a href=\"domains?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"domains?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;domain=',
                            urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'OPERATOR':
                            echo '<li>';
                            echo "<a href=\"operators?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"operators?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;operator=',
                            urlencode($rs->fields['name']), '&amp;operator_oid=', urlencode($rs->fields['oid']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'CONVERSION':
                            echo '<li>';
                            echo "<a href=\"conversions?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"conversions?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']),
                            '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'LANGUAGE':
                            echo "<li><a href=\"languages?{$this->misc->href}\">", $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'AGGREGATE':
                            echo '<li>';
                            echo "<a href=\"aggregates?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"aggregates?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">',
                            $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                        case 'OPCLASS':
                            echo '<li>';
                            echo '<a href="' . \SUBFOLDER . "/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                            echo "<a href=\"opclasses?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">',
                            $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), "</a></li>\n";

                            break;
                    }
                    $rs->moveNext();
                }
                echo "</ul>\n";

                echo '<p>', $rs->recordCount(), ' ', $this->lang['strobjects'], "</p>\n";
            } else {
                echo "<p>{$this->lang['strnoobjects']}</p>\n";
            }
        }
    }

    /**
     * Displays options for database download.
     *
     * @param mixed $msg
     */
    public function doExport($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('database');
        $this->printTabs('database', 'export');
        $this->printMsg($msg);

        echo '<form action="' . \SUBFOLDER . "/src/views/dbexport\" method=\"post\">\n";
        echo "<table>\n";
        echo "<tr><th class=\"data\">{$this->lang['strformat']}</th><th class=\"data\" colspan=\"2\">{$this->lang['stroptions']}</th></tr>\n";
        // Data only
        echo '<tr><th class="data left" rowspan="2">';
        echo "<input type=\"radio\" id=\"what1\" name=\"what\" value=\"dataonly\" checked=\"checked\" /><label for=\"what1\">{$this->lang['strdataonly']}</label></th>\n";
        echo "<td>{$this->lang['strformat']}</td>\n";
        echo "<td><select name=\"d_format\">\n";
        echo "<option value=\"copy\">COPY</option>\n";
        echo "<option value=\"sql\">SQL</option>\n";
        echo "</select>\n</td>\n</tr>\n";
        echo "<tr><td><label for=\"d_oids\">{$this->lang['stroids']}</label></td><td><input type=\"checkbox\" id=\"d_oids\" name=\"d_oids\" /></td>\n</tr>\n";
        // Structure only
        echo "<tr><th class=\"data left\"><input type=\"radio\" id=\"what2\" name=\"what\" value=\"structureonly\" /><label for=\"what2\">{$this->lang['strstructureonly']}</label></th>\n";
        echo "<td><label for=\"s_clean\">{$this->lang['strdrop']}</label></td><td><input type=\"checkbox\" id=\"s_clean\" name=\"s_clean\" /></td>\n</tr>\n";
        // Structure and data
        echo '<tr><th class="data left" rowspan="3">';
        echo "<input type=\"radio\" id=\"what3\" name=\"what\" value=\"structureanddata\" /><label for=\"what3\">{$this->lang['strstructureanddata']}</label></th>\n";
        echo "<td>{$this->lang['strformat']}</td>\n";
        echo "<td><select name=\"sd_format\">\n";
        echo "<option value=\"copy\">COPY</option>\n";
        echo "<option value=\"sql\">SQL</option>\n";
        echo "</select>\n</td>\n</tr>\n";
        echo "<tr><td><label for=\"sd_clean\">{$this->lang['strdrop']}</label></td><td><input type=\"checkbox\" id=\"sd_clean\" name=\"sd_clean\" /></td>\n</tr>\n";
        echo "<tr><td><label for=\"sd_oids\">{$this->lang['stroids']}</label></td><td><input type=\"checkbox\" id=\"sd_oids\" name=\"sd_oids\" /></td>\n</tr>\n";
        echo "</table>\n";

        echo "<h3>{$this->lang['stroptions']}</h3>\n";
        echo "<p><input type=\"radio\" id=\"output1\" name=\"output\" value=\"show\" checked=\"checked\" /><label for=\"output1\">{$this->lang['strshow']}</label>\n";
        echo "<br/><input type=\"radio\" id=\"output2\" name=\"output\" value=\"download\" /><label for=\"output2\">{$this->lang['strdownload']}</label>\n";
        // MSIE cannot download gzip in SSL mode - it's just broken
        if (!(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS']))) {
            echo "<br /><input type=\"radio\" id=\"output3\" name=\"output\" value=\"gzipped\" /><label for=\"output3\">{$this->lang['strdownloadgzipped']}</label>\n";
        }
        echo "</p>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"export\" />\n";
        echo "<input type=\"hidden\" name=\"subject\" value=\"database\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strexport']}\" /></p>\n";
        echo "</form>\n";
    }

    /**
     * Show the current status of all database variables.
     */
    public function doVariables()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Fetch the variables from the database
        $variables = $data->getVariables();
        $this->printTrail('database');
        $this->printTabs('database', 'variables');

        $columns = [
            'variable' => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('name'),
            ],
            'value'    => [
                'title' => $this->lang['strsetting'],
                'field' => Decorator::field('setting'),
            ],
        ];

        $actions = [];

        echo $this->printTable($variables, $columns, $actions, $this->table_place, $this->lang['strnodata']);
    }

    /**
     * Show all current database connections and any queries they
     * are running.
     *
     * @param mixed $msg
     */
    public function doProcesses($msg = '')
    {
        $this->printTrail('database');
        $this->printTabs('database', 'processes');
        $this->printMsg($msg);

        if (0 === strlen($msg)) {
            echo '<br /><a id="control" href=""><img src="' . $this->misc->icon('Refresh') . "\" alt=\"{$this->lang['strrefresh']}\" title=\"{$this->lang['strrefresh']}\"/>&nbsp;{$this->lang['strrefresh']}</a>";
        }

        echo '<div id="data_block">';
        $this->currentProcesses();
        echo '</div>';
    }

    public function currentProcesses($isAjax = false)
    {
        $data = $this->misc->getDatabaseAccessor();

        // Display prepared transactions
        if ($data->hasPreparedXacts()) {
            echo "<h3>{$this->lang['strpreparedxacts']}</h3>\n";
            $prep_xacts = $data->getPreparedXacts($_REQUEST['database']);

            $columns = [
                'transaction' => [
                    'title' => $this->lang['strxactid'],
                    'field' => Decorator::field('transaction'),
                ],
                'gid'         => [
                    'title' => $this->lang['strgid'],
                    'field' => Decorator::field('gid'),
                ],
                'prepared'    => [
                    'title' => $this->lang['strstarttime'],
                    'field' => Decorator::field('prepared'),
                ],
                'owner'       => [
                    'title' => $this->lang['strowner'],
                    'field' => Decorator::field('owner'),
                ],
            ];

            $actions = [];

            echo $this->printTable($prep_xacts, $columns, $actions, 'database-processes-preparedxacts', $this->lang['strnodata']);
        }

        // Fetch the processes from the database
        echo "<h3>{$this->lang['strprocesses']}</h3>\n";
        $processes = $data->getProcesses($_REQUEST['database']);

        $columns = [
            'user'             => [
                'title' => $this->lang['strusername'],
                'field' => Decorator::field('usename'),
            ],
            'process'          => [
                'title' => $this->lang['strprocess'],
                'field' => Decorator::field('pid'),
            ],
            'application_name' => [
                'title' => 'application',
                'field' => Decorator::field('application_name'),
            ],
            'client_addr'      => [
                'title' => 'address',
                'field' => Decorator::field('client_addr'),
            ],
            'blocked'          => [
                'title' => $this->lang['strblocked'],
                'field' => Decorator::field('waiting'),
            ],
            'query'            => [
                'title' => $this->lang['strsql'],
                'field' => Decorator::field('query'),
            ],
            'start_time'       => [
                'title' => $this->lang['strstarttime'],
                'field' => Decorator::field('query_start'),
            ],
        ];

        // Build possible actions for our process list
        $columns['actions'] = ['title' => $this->lang['stractions']];

        $actions = [];
        if ($data->hasUserSignals() || $data->isSuperUser()) {
            $actions = [
                'cancel' => [
                    'content' => $this->lang['strcancel'],
                    'attr'    => [
                        'href' => [
                            'url'     => 'database',
                            'urlvars' => [
                                'action' => 'signal',
                                'signal' => 'CANCEL',
                                'pid'    => Decorator::field('pid'),
                            ],
                        ],
                    ],
                ],
                'kill'   => [
                    'content' => $this->lang['strkill'],
                    'attr'    => [
                        'href' => [
                            'url'     => 'database',
                            'urlvars' => [
                                'action' => 'signal',
                                'signal' => 'KILL',
                                'pid'    => Decorator::field('pid'),
                            ],
                        ],
                    ],
                ],
            ];

            // Remove actions where not supported
            if (!$data->hasQueryKill()) {
                unset($actions['kill']);
            }

            if (!$data->hasQueryCancel()) {
                unset($actions['cancel']);
            }
        }

        if (0 == count($actions)) {
            unset($columns['actions']);
        }

        echo $this->printTable($processes, $columns, $actions, 'database-processes', $this->lang['strnodata']);
    }

    public function currentLocks($isAjax = false)
    {
        $data = $this->misc->getDatabaseAccessor();

        // Get the info from the pg_locks view
        $variables = $data->getLocks();

        $columns = [
            'namespace'     => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('nspname'),
            ],
            'tablename'     => [
                'title' => $this->lang['strtablename'],
                'field' => Decorator::field('tablename'),
            ],
            'vxid'          => [
                'title' => $this->lang['strvirtualtransaction'],
                'field' => Decorator::field('virtualtransaction'),
            ],
            'transactionid' => [
                'title' => $this->lang['strtransaction'],
                'field' => Decorator::field('transaction'),
            ],
            'processid'     => [
                'title' => $this->lang['strprocessid'],
                'field' => Decorator::field('pid'),
            ],
            'mode'          => [
                'title' => $this->lang['strmode'],
                'field' => Decorator::field('mode'),
            ],
            'granted'       => [
                'title' => $this->lang['strislockheld'],
                'field' => Decorator::field('granted'),
                'type'  => 'yesno',
            ],
        ];

        if (!$data->hasVirtualTransactionId()) {
            unset($columns['vxid']);
        }

        $actions = [];
        echo $this->printTable($variables, $columns, $actions, 'database-locks', $this->lang['strnodata']);
    }

    /**
     * Show the existing table locks in the current database.
     */
    public function doLocks()
    {
        $this->printTrail('database');
        $this->printTabs('database', 'locks');

        echo '<br /><a id="control" href=""><img src="' . $this->misc->icon('Refresh') . "\" alt=\"{$this->lang['strrefresh']}\" title=\"{$this->lang['strrefresh']}\"/>&nbsp;{$this->lang['strrefresh']}</a>";

        echo '<div id="data_block">';
        $this->currentLocks();
        echo '</div>';
    }

    /**
     * Allow execution of arbitrary SQL statements on a database.
     */
    public function doSQL()
    {
        if ((!isset($_SESSION['sqlquery'])) || isset($_REQUEST['new'])) {
            $_SESSION['sqlquery'] = '';
            $_REQUEST['paginate'] = 'on';
        }

        $this->printTrail('database');
        $this->printTabs('database', 'sql');
        echo "<p>{$this->lang['strentersql']}</p>\n";
        echo '<form action="' . \SUBFOLDER . '/src/views/sql" method="post" enctype="multipart/form-data" id="sqlform">' . "\n";
        echo "<p>{$this->lang['strsql']}<br />\n";
        echo '<textarea style="width:95%;" rows="15" cols="50" name="query" id="query">',
        htmlspecialchars($_SESSION['sqlquery']), "</textarea></p>\n";

        // Check that file uploads are enabled
        if (ini_get('file_uploads')) {
            // Don't show upload option if max size of uploads is zero
            $max_size = $this->misc->inisizeToBytes(ini_get('upload_max_filesize'));
            if (is_double($max_size) && $max_size > 0) {
                echo "<p><input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"{$max_size}\" />\n";
                echo "<label for=\"script\">{$this->lang['struploadscript']}</label> <input id=\"script\" name=\"script\" type=\"file\" /></p>\n";
            }
        }

        echo '<p><input type="checkbox" id="paginate" name="paginate"', (isset($_REQUEST['paginate']) ? ' checked="checked"' : ''), " /><label for=\"paginate\">{$this->lang['strpaginate']}</label></p>\n";
        echo "<p><input type=\"submit\" name=\"execute\" accesskey=\"r\" value=\"{$this->lang['strexecute']}\" />\n";
        echo $this->misc->form;
        echo "<input type=\"reset\" accesskey=\"q\" value=\"{$this->lang['strreset']}\" /></p>\n";
        echo "</form>\n";

        // Default focus
        $this->setFocus('forms[0].query');
    }

    /**
     * This functions does pretty much nothing. It's meant to implement
     * an abstract method of AdminTrait.
     *
     * @param string $msg The message
     *
     * @return string The message
     */
    public function doDefault($msg = '')
    {
        return $msg;
    }
}
