<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use PHPPgAdmin\Traits\AdminTrait;
use PHPPgAdmin\Traits\ExportTrait;
use Slim\Http\Response;

/**
 * Base controller class.
 */
class DatabaseController extends BaseController
{
    use AdminTrait;
    use ExportTrait;

    public $table_place = 'database-variables';

    public $fields;

    public $controller_title = 'strdatabase';

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

        if ('refresh_locks' === $this->action) {
            return $this->currentLocks(true);
        }

        if ('refresh_processes' === $this->action) {
            return $this->currentProcesses(true);
        }
        $scripts = '';
        // normal flow
        if ('locks' === $this->action || 'processes' === $this->action) {
            $scripts .= '<script src="assets/js/database.js" type="text/javascript"></script>';

            $refreshTime = $this->conf['ajax_refresh'] * 1500;

            $scripts .= '<script type="text/javascript">' . \PHP_EOL;
            $scripts .= "var Database = {\n";
            $scripts .= \sprintf(
                'ajax_time_refresh: %s,
',
                $refreshTime
            );
            $scripts .= \sprintf(
                'str_start: {text:\'%s\',icon: \'',
                $this->lang['strstart']
            ) . $this->view->icon('Execute') . "'},\n";
            $scripts .= \sprintf(
                'str_stop: {text:\'%s\',icon: \'',
                $this->lang['strstop']
            ) . $this->view->icon('Stop') . "'},\n";
            $scripts .= "load_icon: '" . $this->view->icon('Loading') . "',\n";
            $scripts .= \sprintf(
                'server:\'%s\',
',
                $_REQUEST['server']
            );
            $scripts .= \sprintf(
                'dbname:\'%s\',
',
                $_REQUEST['database']
            );
            $scripts .= \sprintf(
                'action:\'refresh_%s\',
',
                $this->action
            );
            $scripts .= "errmsg: '" . \str_replace("'", "\\'", $this->lang['strconnectionfail']) . "'\n";
            $scripts .= "};\n";
            $this->scripts = $scripts . '</script>' . \PHP_EOL;
        }

        $header_template = 'header.twig';
        $footer_template = 'footer.twig';
        // @todo convert all these methods to return text instead of print text
        \ob_start();

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
                $this->view->offsetSet('codemirror', true);

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
                    $this->view->offsetSet('codemirror', true);

                    $this->doSQL();
                }

                break;
        }
        $output = \ob_get_clean();

        $this->printHeader($this->headerTitle(), $this->scripts, true, $header_template);
        $this->printBody();

        echo $output;

        return $this->printFooter(true, $footer_template);
    }

    /**
     * @param mixed $print
     *
     * @return Response|string
     */
    public function doTree($print = true)
    {
        $reqvars = $this->misc->getRequestVars('database');
        $tabs = $this->misc->getNavTabs('database');
        $items = $this->adjustTabsForTree($tabs);

        $attrs = [
            'text' => Decorator::field('title'),
            'icon' => Decorator::field('icon'),
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
                    'action' => 'tree',
                    'database' => $this->misc->getDatabase(),
                ]
            ),
        ];

        return $this->printTree($items, $attrs, 'database', $print);
    }

    /**
     * Sends a signal to a process.
     */
    public function doSignal(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->sendSignal($_REQUEST['pid'], $_REQUEST['signal']);

        if (0 === $status) {
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

        echo '<form action="database" method="post">' . \PHP_EOL;
        echo '<p><input name="term" value="', \htmlspecialchars($_REQUEST['term']),
        \sprintf(
            '" size="32" maxlength="%s" />',
            $data->_maxNameLen
        ) . \PHP_EOL;
        // Output list of filters.  This is complex due to all the 'has' and 'conf' feature possibilities
        echo '<select name="filter">' . \PHP_EOL;

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

        echo '</select>' . \PHP_EOL;
        echo \sprintf(
            '<input type="submit" value="%s" />',
            $this->lang['strfind']
        ) . \PHP_EOL;
        echo $this->view->form;
        echo '<input type="hidden" name="action" value="find" /></p>' . \PHP_EOL;
        echo '<input type="hidden" name="confirm" value="true" /></p>' . \PHP_EOL;
        echo '</form>' . \PHP_EOL;

        // Default focus
        $this->setFocus('forms[0].term');

        // If a search term has been specified, then perform the search
        // and display the results, grouped by object type
        if (!$confirm && '' !== $_REQUEST['term']) {
            return $this->doFind();
        }
    }

    public function doFind(): void
    {
        $data = $this->misc->getDatabaseAccessor();
        $rs = $data->findObject($_REQUEST['term'], $_REQUEST['filter']);

        if (0 < $rs->RecordCount()) {
            $curr = '';

            while (!$rs->EOF) {
                // Output a new header if the current type has changed, but not if it's just changed the rule type
                if ($rs->fields['type'] !== $curr) {
                    // Short-circuit in the case of changing from table rules to view rules; table cols to view cols;
                    // table constraints to domain constraints
                    if ('RULEVIEW' === $rs->fields['type'] && 'RULETABLE' === $curr) {
                        $curr = $rs->fields['type'];
                    } elseif ('COLUMNVIEW' === $rs->fields['type'] && 'COLUMNTABLE' === $curr) {
                        $curr = $rs->fields['type'];
                    } elseif ('CONSTRAINTTABLE' === $rs->fields['type'] && 'CONSTRAINTDOMAIN' === $curr) {
                        $curr = $rs->fields['type'];
                    } else {
                        if ('' !== $curr) {
                            echo '</ul>' . \PHP_EOL;
                        }

                        $curr = $rs->fields['type'];
                        echo '<h3>';
                        echo $this->_translatedType($curr);
                        echo '</h3>';
                        echo '<ul>' . \PHP_EOL;
                    }
                }

                $this->_printHtmlForType($curr, $rs);
                $rs->MoveNext();
            }
            echo '</ul>' . \PHP_EOL;

            echo '<p>', $rs->RecordCount(), ' ', $this->lang['strobjects'], '</p>' . \PHP_EOL;
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnoobjects']
            ) . \PHP_EOL;
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
        $object = $_REQUEST['database'];

        echo $this->formHeader('dbexport');

        echo $this->dataOnly(true, true);

        echo $this->structureOnly();

        echo $this->structureAndData(true);

        // $server_info = $this->misc->getServerInfo();
        // echo $this->offerNoRoleExport(isset($server_info['pgVersion']) && floatval(substr($server_info['pgVersion'], 0, 3)) >= 10);

        echo $this->displayOrDownload(!(\mb_strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS'])));

        echo $this->formFooter($subject, $object);
    }

    /**
     * Show the current status of all database variables.
     */
    public function doVariables(): void
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
            'value' => [
                'title' => $this->lang['strsetting'],
                'field' => Decorator::field('setting'),
            ],
        ];

        $actions = [];

        if (self::isRecordset($variables)) {
            echo $this->printTable($variables, $columns, $actions, $this->table_place, $this->lang['strnodata']);
        }
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

        if (0 === \mb_strlen($msg)) {
            echo '<br /><a id="control" href=""><img src="' . $this->view->icon('Refresh') . \sprintf(
                '" alt="%s" title="%s"/>&nbsp;%s</a>',
                $this->lang['strrefresh'],
                $this->lang['strrefresh'],
                $this->lang['strrefresh']
            );
        }

        echo '<div id="data_block">';
        $this->currentProcesses();
        echo '</div>';
    }

    public function currentProcesses(bool $isAjax = false): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Display prepared transactions
        if ($data->hasPreparedXacts()) {
            echo \sprintf(
                '<h3>%s</h3>',
                $this->lang['strpreparedxacts']
            ) . \PHP_EOL;
            $prep_xacts = $data->getPreparedXacts($_REQUEST['database']);

            $columns = [
                'transaction' => [
                    'title' => $this->lang['strxactid'],
                    'field' => Decorator::field('transaction'),
                ],
                'gid' => [
                    'title' => $this->lang['strgid'],
                    'field' => Decorator::field('gid'),
                ],
                'prepared' => [
                    'title' => $this->lang['strstarttime'],
                    'field' => Decorator::field('prepared'),
                ],
                'owner' => [
                    'title' => $this->lang['strowner'],
                    'field' => Decorator::field('owner'),
                ],
            ];

            $actions = [];

            if (self::isRecordset($prep_xacts)) {
                echo $this->printTable($prep_xacts, $columns, $actions, 'database-processes-preparedxacts', $this->lang['strnodata']);
            }
        }

        // Fetch the processes from the database
        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['strprocesses']
        ) . \PHP_EOL;
        $processes = $data->getProcesses($_REQUEST['database']);

        $columns = [
            'user' => [
                'title' => $this->lang['strusername'],
                'field' => Decorator::field('usename'),
            ],
            'process' => [
                'title' => $this->lang['strprocess'],
                'field' => Decorator::field('pid'),
            ],
            'application_name' => [
                'title' => 'application',
                'field' => Decorator::field('application_name'),
            ],
            'client_addr' => [
                'title' => 'address',
                'field' => Decorator::field('client_addr'),
            ],
            'blocked' => [
                'title' => $this->lang['strblocked'],
                'field' => Decorator::field('waiting'),
            ],
            'query' => [
                'title' => $this->lang['strsql'],
                'field' => Decorator::field('query'),
            ],
            'start_time' => [
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
                    'attr' => [
                        'href' => [
                            'url' => 'database',
                            'urlvars' => [
                                'action' => 'signal',
                                'signal' => 'CANCEL',
                                'pid' => Decorator::field('pid'),
                            ],
                        ],
                    ],
                ],
                'kill' => [
                    'content' => $this->lang['strkill'],
                    'attr' => [
                        'href' => [
                            'url' => 'database',
                            'urlvars' => [
                                'action' => 'signal',
                                'signal' => 'KILL',
                                'pid' => Decorator::field('pid'),
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

        if (0 === \count($actions)) {
            unset($columns['actions']);
        }

        if (self::isRecordset($processes)) {
            echo $this->printTable($processes, $columns, $actions, 'database-processes', $this->lang['strnodata']);
        }
    }

    public function currentLocks(bool $isAjax = false): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Get the info from the pg_locks view
        $variables = $data->getLocks();

        $columns = [
            'namespace' => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('nspname'),
            ],
            'tablename' => [
                'title' => $this->lang['strtablename'],
                'field' => Decorator::field('tablename'),
            ],
            'vxid' => [
                'title' => $this->lang['strvirtualtransaction'],
                'field' => Decorator::field('virtualtransaction'),
            ],
            'transactionid' => [
                'title' => $this->lang['strtransaction'],
                'field' => Decorator::field('transaction'),
            ],
            'processid' => [
                'title' => $this->lang['strprocessid'],
                'field' => Decorator::field('pid'),
            ],
            'mode' => [
                'title' => $this->lang['strmode'],
                'field' => Decorator::field('mode'),
            ],
            'granted' => [
                'title' => $this->lang['strislockheld'],
                'field' => Decorator::field('granted'),
                'type' => 'yesno',
            ],
        ];

        if (!$data->hasVirtualTransactionId()) {
            unset($columns['vxid']);
        }

        $actions = [];

        if (self::isRecordset($variables)) {
            echo $this->printTable($variables, $columns, $actions, 'database-locks', $this->lang['strnodata']);
        }
    }

    /**
     * Show the existing table locks in the current database.
     */
    public function doLocks(): void
    {
        $this->printTrail('database');
        $this->printTabs('database', 'locks');

        echo '<br /><a id="control" href=""><img src="' . $this->view->icon('Refresh') . \sprintf(
            '" alt="%s" title="%s"/>&nbsp;%s</a>',
            $this->lang['strrefresh'],
            $this->lang['strrefresh'],
            $this->lang['strrefresh']
        );

        echo '<div id="data_block">';
        $this->currentLocks();
        echo '</div>';
    }

    /**
     * Allow execution of arbitrary SQL statements on a database.
     */
    public function doSQL(): void
    {
        if ((!isset($_SESSION['sqlquery'])) || isset($_REQUEST['new'])) {
            $_SESSION['sqlquery'] = '';
            $_REQUEST['paginate'] = 'on';
        }

        $this->printTrail('database');
        $this->printTabs('database', 'sql');
        echo \sprintf(
            '<p>%s</p>',
            $this->lang['strentersql']
        ) . \PHP_EOL;
        echo '<form action="sql" method="post" enctype="multipart/form-data" id="sqlform">' . \PHP_EOL;
        echo \sprintf(
            '<p>%s<br />',
            $this->lang['strsql']
        ) . \PHP_EOL;
        echo '<textarea style="width:95%;" rows="15" cols="50" name="query" id="query">',
        \htmlspecialchars($_SESSION['sqlquery']), '</textarea></p>' . \PHP_EOL;

        // Check that file uploads are enabled
        if (\ini_get('file_uploads')) {
            // Don't show upload option if max size of uploads is zero
            $max_size = $this->misc->inisizeToBytes(\ini_get('upload_max_filesize'));

            if (\is_float($max_size) && 0 < $max_size) {
                echo \sprintf(
                    '<p><input type="hidden" name="MAX_FILE_SIZE" value="%s" />',
                    $max_size
                ) . \PHP_EOL;
                echo \sprintf(
                    '<label for="script">%s</label> <input id="script" name="script" type="file" /></p>',
                    $this->lang['struploadscript']
                ) . \PHP_EOL;
            }
        }

        echo '<p><input type="checkbox" id="paginate" name="paginate"', (isset($_REQUEST['paginate']) ? ' checked="checked"' : ''), \sprintf(
            ' /><label for="paginate">%s</label></p>',
            $this->lang['strpaginate']
        ) . \PHP_EOL;
        echo \sprintf(
            '<p><input type="submit" name="execute" accesskey="r" value="%s" />',
            $this->lang['strexecute']
        ) . \PHP_EOL;
        echo $this->view->form;
        echo \sprintf(
            '<input type="reset" accesskey="q" value="%s" /></p>',
            $this->lang['strreset']
        ) . \PHP_EOL;
        echo '</form>' . \PHP_EOL;

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

    /**
     * @param mixed $string
     * @param mixed $term
     *
     * @return string
     */
    private function _highlight($string, $term)
    {
        return \str_replace($term, \sprintf(
            '<b>%s</b>',
            $term
        ), $string);
    }

    /**
     * @return string
     */
    private function _printTypeOption(string $curr)
    {
        $filter = $_REQUEST['filter'];
        $optionhtml = \sprintf(
            '%s<option value="%s" %s>',
            "\t",
            $curr,
            ($curr === $filter) ? ' selected="selected"' : ''
        );
        $optionhtml .= $this->_translatedType($curr);
        $optionhtml .= '</option>' . \PHP_EOL;

        return $optionhtml;
    }

    private function _translatedType(string $curr)
    {
        $types = [
            'COLUMN' => $this->lang['strcolumns'],
            'CONSTRAINT' => $this->lang['strconstraints'],
            'COLUMNTABLE' => $this->lang['strcolumns'],
            'COLUMNVIEW' => $this->lang['strcolumns'],
            'CONSTRAINTDOMAIN' => $this->lang['strconstraints'],
            'CONSTRAINTTABLE' => $this->lang['strconstraints'],
            'DOMAIN' => $this->lang['strdomains'],
            'FUNCTION' => $this->lang['strfunctions'],
            'INDEX' => $this->lang['strindexes'],
            'RULE' => $this->lang['strrules'],
            'RULETABLE' => $this->lang['strrules'],
            'RULEVIEW' => $this->lang['strrules'],
            'SCHEMA' => $this->lang['strschemas'],
            'SEQUENCE' => $this->lang['strsequences'],
            'TABLE' => $this->lang['strtables'],
            'TRIGGER' => $this->lang['strtriggers'],
            'VIEW' => $this->lang['strviews'],

            'AGGREGATE' => $this->lang['straggregates'],
            'CONVERSION' => $this->lang['strconversions'],
            'LANGUAGE' => $this->lang['strlanguages'],
            'OPCLASS' => $this->lang['stropclasses'],
            'OPERATOR' => $this->lang['stroperators'],
            'TYPE' => $this->lang['strtypes'],
        ];

        if (\array_key_exists($curr, $types)) {
            return $types[$curr];
        }

        return $this->lang['strallobjects'];
    }

    /**
     * @param string $curr
     * @param mixed  $rs
     */
    private function _printHtmlForType($curr, $rs): void
    {
        $destination = null;

        switch ($curr) {
            case 'SCHEMA':
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<li><a href="' . \containerInstance()->subFolder . $destination;
                echo $this->misc->printVal($rs->fields['name']), '">';
                echo $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']);
                echo '</a></li>' . \PHP_EOL;

                break;
            case 'TABLE':
                echo '<li>';
                echo \sprintf(
                    '<a href="tables?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                $destination = $this->container->getDestinationWithLastTab('table');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;table=',
                \urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'VIEW':
                echo '<li>';
                echo \sprintf(
                    '<a href="views?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                $destination = $this->container->getDestinationWithLastTab('view');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;view=',
                \urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'SEQUENCE':
                echo '<li>';
                echo \sprintf(
                    '<a href="sequences?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="sequences?subject=sequence&amp;action=properties&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']),
                '&amp;sequence=', \urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'COLUMNTABLE':
                echo '<li>';
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="tblproperties?subject=table&%s&table=',
                    $this->misc->href
                ), \urlencode($rs->fields['relname']), '&amp;schema=', \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo \sprintf(
                    '<a href="colproperties?%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;table=',
                \urlencode($rs->fields['relname']), '&amp;column=', \urlencode($rs->fields['name']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'COLUMNVIEW':
                echo '<li>';
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="viewproperties?subject=view&%s&view=',
                    $this->misc->href
                ), \urlencode($rs->fields['relname']), '&amp;schema=', \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo \sprintf(
                    '<a href="colproperties?%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;view=',
                \urlencode($rs->fields['relname']), '&amp;column=', \urlencode($rs->fields['name']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'INDEX':
                echo '<li>';
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                $destination = $this->container->getDestinationWithLastTab('table');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&table=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['relname']), '&amp;schema=', \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo \sprintf(
                    '<a href="indexes?%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;table=', \urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'CONSTRAINTTABLE':
                echo '<li>';
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                $destination = $this->container->getDestinationWithLastTab('table');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&table=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['relname']), '&amp;schema=', \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo \sprintf(
                    '<a href="constraints?%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;table=',
                \urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'CONSTRAINTDOMAIN':
                echo '<li>';
                echo \sprintf(
                    '<a href="domains?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="domains?action=properties&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;domain=', \urlencode($rs->fields['relname']), '">',
                $this->misc->printVal($rs->fields['relname']), '.', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'TRIGGER':
                echo '<li>';
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                $destination = $this->container->getDestinationWithLastTab('table');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&table=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['relname']), '&amp;schema=', \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo \sprintf(
                    '<a href="triggers?%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;table=', \urlencode($rs->fields['relname']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'RULETABLE':
                echo '<li>';
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                $destination = $this->container->getDestinationWithLastTab('table');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&table=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['relname']), '&amp;schema=', \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo \sprintf(
                    '<a href="rules?subject=table&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;reltype=table&amp;table=',
                \urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'RULEVIEW':
                echo '<li>';
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                $destination = $this->container->getDestinationWithLastTab('view');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&view=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['relname']), '&amp;schema=', \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['relname']), '</a>.';
                echo \sprintf(
                    '<a href="rules?subject=view&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;reltype=view&amp;view=',
                \urlencode($rs->fields['relname']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'FUNCTION':
                echo '<li>';
                echo \sprintf(
                    '<a href="functions?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="functions?action=properties&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;function=',
                \urlencode($rs->fields['name']), '&amp;function_oid=', \urlencode($rs->fields['oid']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'TYPE':
                echo '<li>';
                echo \sprintf(
                    '<a href="types?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="types?action=properties&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;type=',
                \urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'DOMAIN':
                echo '<li>';
                echo \sprintf(
                    '<a href="domains?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="domains?action=properties&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;domain=',
                \urlencode($rs->fields['name']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'OPERATOR':
                echo '<li>';
                echo \sprintf(
                    '<a href="operators?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="operators?action=properties&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '&amp;operator=',
                \urlencode($rs->fields['name']), '&amp;operator_oid=', \urlencode($rs->fields['oid']), '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'CONVERSION':
                echo '<li>';
                echo \sprintf(
                    '<a href="conversions?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="conversions?%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']),
                '">', $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'LANGUAGE':
                echo \sprintf(
                    '<li><a href="languages?%s">',
                    $this->misc->href
                ), $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'AGGREGATE':
                echo '<li>';
                echo \sprintf(
                    '<a href="aggregates?subject=schema&%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="aggregates?%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
            case 'OPCLASS':
                echo '<li>';
                $destination = $this->container->getDestinationWithLastTab('schema');
                echo '<a href="' . \containerInstance()->subFolder . \sprintf(
                    '%s?%s&schema=',
                    $destination,
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">', $this->misc->printVal($rs->fields['schemaname']), '</a>.';
                echo \sprintf(
                    '<a href="opclasses?%s&schema=',
                    $this->misc->href
                ), \urlencode($rs->fields['schemaname']), '">',
                $this->_highlight($this->misc->printVal($rs->fields['name']), $_REQUEST['term']), '</a></li>' . \PHP_EOL;

                break;
        }
        //$this->dump(['curr' => $curr, 'destination' => $destination]);
    }
}
