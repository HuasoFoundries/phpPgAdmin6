<?php

/**
 * PHPPgAdmin v6.0.0-RC1
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
    use \PHPPgAdmin\Traits\ExportTrait;
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
            $scripts .= '<script src="'.\SUBFOLDER.'/assets/js/database.js" type="text/javascript"></script>';

            $refreshTime = $this->conf['ajax_refresh'] * 1500;

            $scripts .= '<script type="text/javascript">'.PHP_EOL;
            $scripts .= "var Database = {\n";
            $scripts .= "ajax_time_refresh: {$refreshTime},\n";
            $scripts .= "str_start: {text:'{$this->lang['strstart']}',icon: '".$this->misc->icon('Execute')."'},\n";
            $scripts .= "str_stop: {text:'{$this->lang['strstop']}',icon: '".$this->misc->icon('Stop')."'},\n";
            $scripts .= "load_icon: '".$this->misc->icon('Loading')."',\n";
            $scripts .= "server:'{$_REQUEST['server']}',\n";
            $scripts .= "dbname:'{$_REQUEST['database']}',\n";
            $scripts .= "action:'refresh_{$this->action}',\n";
            $scripts .= "errmsg: '".str_replace("'", "\\'", $this->lang['strconnectionfail'])."'\n";
            $scripts .= "};\n";
            $scripts .= '</script>'.PHP_EOL;
        }

        $header_template = 'header.twig';
        $footer_template = 'footer.twig';
        // @todo convert all these methods to return text instead of print text
        ob_start();
        switch ($this->action) {
            case 'find':
                if (isset($_REQUEST['term'])) {
                    $this->printFindForm(false);
                } else {
                    $this->printFindForm(true);
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
    public function printFindForm($confirm = true, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'term', '');

        $this->coalesceArr($_REQUEST, 'filter', '');

        $this->printTrail('database');
        $this->printTabs('database', 'find');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER.'/src/views/database" method="post">'.PHP_EOL;
        echo '<p><input name="term" value="', htmlspecialchars($_REQUEST['term']),
            "\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" />".PHP_EOL;
        // Output list of filters.  This is complex due to all the 'has' and 'conf' feature possibilities
        echo '<select name="filter">'.PHP_EOL;

        echo $this->_printTypeOption('');
        echo $this->_printTypeOption('COLUMN');
        echo $this->_printTypeOption('CONSTRAINT');
        echo $this->_printTypeOption('DOMAIN');
        echo $this->_printTypeOption('FUNCTION');
        echo $this->_printTypeOption('INDEX');
        echo $this->_printTypeOption('RULE');
        echo $this->_printTypeOption('SCHEMA');
        echo $this->_printTypeOption('SEQUENCE');
        echo $this->_printTypeOption('TABLE');
        echo $this->_printTypeOption('TRIGGER');
        echo $this->_printTypeOption('VIEW');

        if ($this->conf['show_advanced']) {
            echo $this->_printTypeOption('AGGREGATE');
            echo $this->_printTypeOption('TYPE');
            echo $this->_printTypeOption('OPERATOR');
            echo $this->_printTypeOption('OPCLASS');
            echo $this->_printTypeOption('CONVERSION');
            echo $this->_printTypeOption('LANGUAGE');
        }

        echo '</select>'.PHP_EOL;
        echo "<input type=\"submit\" value=\"{$this->lang['strfind']}\" />".PHP_EOL;
        echo $this->misc->form;
        echo '<input type="hidden" name="action" value="find" /></p>'.PHP_EOL;
        echo '<input type="hidden" name="confirm" value="true" /></p>'.PHP_EOL;
        echo '</form>'.PHP_EOL;

        // Default focus
        $this->setFocus('forms[0].term');

        // If a search term has been specified, then perform the search
        // and display the results, grouped by object type
        if (!$confirm && '' != $_REQUEST['term']) {
            return $this->doFind();
        }
    }

    private function _printTypeOption($curr)
    {
        $filter     = $_REQUEST['filter'];
        $optionhtml = sprintf('%s<option value="%s" %s>', "\t", $curr, ($curr === $filter) ? ' selected="selected"' : '');
        $optionhtml .= $this->_translatedType($curr);
        $optionhtml .= '</option>'.PHP_EOL;

        return $optionhtml;
    }

    private function _translatedType($curr)
    {
        $types = [
            'COLUMN'           => $this->lang['strcolumns'],
            'CONSTRAINT'       => $this->lang['strconstraints'],
            'COLUMNTABLE'      => $this->lang['strcolumns'],
            'COLUMNVIEW'       => $this->lang['strcolumns'],
            'CONSTRAINTDOMAIN' => $this->lang['strconstraints'],
            'CONSTRAINTTABLE'  => $this->lang['strconstraints'],
            'DOMAIN'           => $this->lang['strdomains'],
            'FUNCTION'         => $this->lang['strfunctions'],
            'INDEX'            => $this->lang['strindexes'],
            'RULE'             => $this->lang['strrules'],
            'RULETABLE'        => $this->lang['strrules'],
            'RULEVIEW'         => $this->lang['strrules'],
            'SCHEMA'           => $this->lang['strschemas'],
            'SEQUENCE'         => $this->lang['strsequences'],
            'TABLE'            => $this->lang['strtables'],
            'TRIGGER'          => $this->lang['strtriggers'],
            'VIEW'             => $this->lang['strviews'],

            'AGGREGATE'        => $this->lang['straggregates'],
            'CONVERSION'       => $this->lang['strconversions'],
            'LANGUAGE'         => $this->lang['strlanguages'],
            'OPCLASS'          => $this->lang['stropclasses'],
            'OPERATOR'         => $this->lang['stroperators'],
            'TYPE'             => $this->lang['strtypes'],
        ];
        if (array_key_exists($curr, $types)) {
            return $types[$curr];
        }

        return $this->lang['strallobjects'];
    }

    private function _printHtmlForType($curr, $rs)
    {
        switch ($curr) {
            case 'SCHEMA':
                echo '<li><a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=";
                echo $this->misc->printVal($rs->fields['name']), '">';
                echo $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']);
                echo '</a></li>'.PHP_EOL;

                break;
            case 'TABLE':
                echo '<li>';
                echo "<a href=\"tables?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo '<a href="'.\SUBFOLDER."/redirect/table?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=',
                urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'VIEW':
                echo '<li>';
                echo "<a href=\"views?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo '<a href="'.\SUBFOLDER."/redirect/view?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;view=',
                urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'SEQUENCE':
                echo '<li>';
                echo "<a href=\"sequences?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"sequences?subject=sequence&amp;action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']),
                '&amp;sequence=', urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'COLUMNTABLE':
                echo '<li>';
                echo '<a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"tblproperties?subject=table&{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo "<a href=\"colproperties?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=',
                urlencode($rs->fields['relname']), '&amp;column=', urlencode($rs->fields['name']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'COLUMNVIEW':
                echo '<li>';
                echo '<a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"viewproperties?subject=view&{$this->misc->href}&view=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo "<a href=\"colproperties?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;view=',
                urlencode($rs->fields['relname']), '&amp;column=', urlencode($rs->fields['name']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'INDEX':
                echo '<li>';
                echo '<a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo '<a href="'.\SUBFOLDER."/redirect/table?{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo "<a href=\"indexes?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=', urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'CONSTRAINTTABLE':
                echo '<li>';
                echo '<a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo '<a href="'.\SUBFOLDER."/redirect/table?{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo "<a href=\"constraints?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=',
                urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'CONSTRAINTDOMAIN':
                echo '<li>';
                echo "<a href=\"domains?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"domains?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;domain=', urlencode($rs->fields['relname']), '">',
                $this->misc->printVal($rs->fields['relname']), '.', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'TRIGGER':
                echo '<li>';
                echo '<a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo '<a href="'.\SUBFOLDER."/redirect/table?{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo "<a href=\"triggers?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;table=', urlencode($rs->fields['relname']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'RULETABLE':
                echo '<li>';
                echo '<a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo '<a href="'.\SUBFOLDER."/redirect/table?{$this->misc->href}&table=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo "<a href=\"rules?subject=table&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;reltype=table&amp;table=',
                urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'RULEVIEW':
                echo '<li>';
                echo '<a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo '<a href="'.\SUBFOLDER."/redirect/view?{$this->misc->href}&view=", urlencode($rs->fields['relname']), '&amp;schema=', urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo "<a href=\"rules?subject=view&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;reltype=view&amp;view=',
                urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'FUNCTION':
                echo '<li>';
                echo "<a href=\"functions?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"functions?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;function=',
                urlencode($rs->fields['name']), '&amp;function_oid=', urlencode($rs->fields['oid']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'TYPE':
                echo '<li>';
                echo "<a href=\"types?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"types?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;type=',
                urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'DOMAIN':
                echo '<li>';
                echo "<a href=\"domains?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"domains?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;domain=',
                urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'OPERATOR':
                echo '<li>';
                echo "<a href=\"operators?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"operators?action=properties&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '&amp;operator=',
                urlencode($rs->fields['name']), '&amp;operator_oid=', urlencode($rs->fields['oid']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'CONVERSION':
                echo '<li>';
                echo "<a href=\"conversions?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"conversions?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']),
                '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'LANGUAGE':
                echo "<li><a href=\"languages?{$this->misc->href}\">", $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'AGGREGATE':
                echo '<li>';
                echo "<a href=\"aggregates?subject=schema&{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"aggregates?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
            case 'OPCLASS':
                echo '<li>';
                echo '<a href="'.\SUBFOLDER."/redirect/schema?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo "<a href=\"opclasses?{$this->misc->href}&schema=", urlencode($rs->fields['schemaname']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>'.PHP_EOL;

                break;
        }
    }

    public function doFind()
    {
        $data = $this->misc->getDatabaseAccessor();
        $rs   = $data->findObject($_REQUEST['term'], $_REQUEST['filter']);
        if ($rs->recordCount() > 0) {
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
                            echo '</ul>'.PHP_EOL;
                        }

                        $curr = $rs->fields['type'];
                        echo '<h3>';
                        echo $this->_translatedType($curr);
                        echo '</h3>';
                        echo '<ul>'.PHP_EOL;
                    }
                }

                $this->_printHtmlForType($curr, $rs);
                $rs->moveNext();
            }
            echo '</ul>'.PHP_EOL;

            echo '<p>', $rs->recordCount(), ' ', $this->lang['strobjects'], '</p>'.PHP_EOL;
        } else {
            echo "<p>{$this->lang['strnoobjects']}</p>".PHP_EOL;
        }
    }

    /**
     * Displays options for database download.
     *
     * @param mixed $msg
     */
    public function doExport($msg = '')
    {
        $this->printTrail('database');
        $this->printTabs('database', 'export');
        $this->printMsg($msg);

        $subject = 'database';
        $object  = $_REQUEST['database'];

        echo $this->formHeader('dbexport');

        echo $this->dataOnly(true, true);

        echo $this->structureOnly();

        echo $this->structureAndData(true);

        // $server_info = $this->misc->getServerInfo();
        // echo $this->offerNoRoleExport(isset($server_info['pgVersion']) && floatval(substr($server_info['pgVersion'], 0, 3)) >= 10);

        echo $this->displayOrDownload(!(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS'])));

        echo $this->formFooter($subject, $object);
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
            echo '<br /><a id="control" href=""><img src="'.$this->misc->icon('Refresh')."\" alt=\"{$this->lang['strrefresh']}\" title=\"{$this->lang['strrefresh']}\"/>&nbsp;{$this->lang['strrefresh']}</a>";
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
            echo "<h3>{$this->lang['strpreparedxacts']}</h3>".PHP_EOL;
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
        echo "<h3>{$this->lang['strprocesses']}</h3>".PHP_EOL;
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

        echo '<br /><a id="control" href=""><img src="'.$this->misc->icon('Refresh')."\" alt=\"{$this->lang['strrefresh']}\" title=\"{$this->lang['strrefresh']}\"/>&nbsp;{$this->lang['strrefresh']}</a>";

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
        echo "<p>{$this->lang['strentersql']}</p>".PHP_EOL;
        echo '<form action="'.\SUBFOLDER.'/src/views/sql" method="post" enctype="multipart/form-data" id="sqlform">'.PHP_EOL;
        echo "<p>{$this->lang['strsql']}<br />".PHP_EOL;
        echo '<textarea style="width:95%;" rows="15" cols="50" name="query" id="query">',
        htmlspecialchars($_SESSION['sqlquery']), '</textarea></p>'.PHP_EOL;

        // Check that file uploads are enabled
        if (ini_get('file_uploads')) {
            // Don't show upload option if max size of uploads is zero
            $max_size = $this->misc->inisizeToBytes(ini_get('upload_max_filesize'));
            if (is_double($max_size) && $max_size > 0) {
                echo "<p><input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"{$max_size}\" />".PHP_EOL;
                echo "<label for=\"script\">{$this->lang['struploadscript']}</label> <input id=\"script\" name=\"script\" type=\"file\" /></p>".PHP_EOL;
            }
        }

        echo '<p><input type="checkbox" id="paginate" name="paginate"', (isset($_REQUEST['paginate']) ? ' checked="checked"' : ''), " /><label for=\"paginate\">{$this->lang['strpaginate']}</label></p>".PHP_EOL;
        echo "<p><input type=\"submit\" name=\"execute\" accesskey=\"r\" value=\"{$this->lang['strexecute']}\" />".PHP_EOL;
        echo $this->misc->form;
        echo "<input type=\"reset\" accesskey=\"q\" value=\"{$this->lang['strreset']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;

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
