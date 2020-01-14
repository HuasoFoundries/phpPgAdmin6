<?php

/**
 * PHPPgAdmin v6.0.0-RC3
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class SqlController extends BaseController
{
    public $query   = '';
    public $subject = '';
    public $start_time;
    public $duration;
    public $controller_title = 'strqueryresults';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $data = $this->misc->getDatabaseAccessor();

        set_time_limit(0);

        // We need to store the query in a session for editing purposes
        // We avoid GPC vars to avoid truncating long queries
        if (isset($_REQUEST['subject']) && 'history' == $_REQUEST['subject']) {
            // Or maybe we came from the history popup
            $_SESSION['sqlquery'] = $_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']][$_GET['queryid']]['query'];
            $this->query          = $_SESSION['sqlquery'];
        } elseif (isset($_POST['query'])) {
            // Or maybe we came from an sql form
            $_SESSION['sqlquery'] = $_POST['query'];
            $this->query          = $_SESSION['sqlquery'];
        } else {
            echo 'could not find the query!!';
        }

        // Pagination maybe set by a get link that has it as FALSE,
        // if that's the case, unset the variable.
        if (isset($_REQUEST['paginate']) && 'f' == $_REQUEST['paginate']) {
            unset($_REQUEST['paginate'], $_POST['paginate'], $_GET['paginate']);
        }

        if (isset($_REQUEST['subject'])) {
            $this->subject = $_REQUEST['subject'];
        }

        // Check to see if pagination has been specified. In that case, send to display
        // script for pagination
        // if a file is given or the request is an explain, do not paginate
        if (isset($_REQUEST['paginate']) &&
            !(isset($_FILES['script']) && $_FILES['script']['size'] > 0) &&
            (0 == preg_match('/^\s*explain/i', $this->query))) {
            //if (!(isset($_FILES['script']) && $_FILES['script']['size'] > 0)) {

            $display_controller = new DisplayController($this->getContainer());

            return $display_controller->render();
        }

        $this->printHeader($this->headerTitle(), null, true, 'header_sqledit.twig');
        $this->printBody();
        $this->printTrail('database');
        $this->printTitle($this->lang['strqueryresults']);

        // Set the schema search path
        if (isset($_REQUEST['search_path'])) {
            if (0 != $data->setSearchPath(array_map('trim', explode(',', $_REQUEST['search_path'])))) {
                return $this->printFooter();
            }
        }

        // May as well try to time the query
        if (function_exists('microtime')) {
            list($usec, $sec) = explode(' ', microtime());
            $this->start_time = ((float) $usec + (float) $sec);
        }

        $rs = $this->doDefault();

        $this->doFooter(true, 'footer_sqledit.twig', $rs);
    }

    public function doDefault()
    {
        $_connection = $this->misc->getConnection();

        try {
            // Execute the query.  If it's a script upload, special handling is necessary
            if (isset($_FILES['script']) && $_FILES['script']['size'] > 0) {
                return $this->execute_script();
            }

            return $this->execute_query();
        } catch (\PHPPgAdmin\ADOdbException $e) {
            $message   = $e->getMessage();
            $trace     = $e->getTraceAsString();
            $lastError = $_connection->getLastError();
            $this->prtrace(['message' => $message, 'trace' => $trace, 'lastError' => $lastError]);

            return null;
        }
    }

    private function execute_script()
    {
        $misc        = $this->misc;
        $data        = $this->misc->getDatabaseAccessor();
        $_connection = $this->misc->getConnection();
        $lang        = $this->lang;
        /**
         * This is a callback function to display the result of each separate query.
         *
         * @param ADORecordSet $rs The recordset returned by the script execetor
         */
        $sqlCallback = function ($query, $rs, $lineno) use ($data, $misc, $lang, $_connection) {
            // Check if $rs is false, if so then there was a fatal error
            if (false === $rs) {
                echo htmlspecialchars($_FILES['script']['name']), ':', $lineno, ': ', nl2br(htmlspecialchars($_connection->getLastError())), '<br/>'.PHP_EOL;
            } else {
                // Print query results
                switch (pg_result_status($rs)) {
                    case \PGSQL_TUPLES_OK:
                        // If rows returned, then display the results
                        $num_fields = pg_numfields($rs);
                        echo "<p><table>\n<tr>";
                        for ($k = 0; $k < $num_fields; ++$k) {
                            echo '<th class="data">', $misc->printVal(pg_fieldname($rs, $k)), '</th>';
                        }

                        $i   = 0;
                        $row = pg_fetch_row($rs);
                        while (false !== $row) {
                            $id = (0 == ($i % 2) ? '1' : '2');
                            echo "<tr class=\"data{$id}\">".PHP_EOL;
                            foreach ($row as $k => $v) {
                                echo '<td style="white-space:nowrap;">', $misc->printVal($v, pg_fieldtype($rs, $k), ['null' => true]), '</td>';
                            }
                            echo '</tr>'.PHP_EOL;
                            $row = pg_fetch_row($rs);
                            ++$i;
                        }

                        echo '</table><br/>'.PHP_EOL;
                        echo $i, " {$lang['strrows']}</p>".PHP_EOL;

                        break;
                    case \PGSQL_COMMAND_OK:
                        // If we have the command completion tag
                        if (version_compare(PHP_VERSION, '4.3', '>=')) {
                            echo htmlspecialchars(pg_result_status($rs, PGSQL_STATUS_STRING)), '<br/>'.PHP_EOL;
                        } elseif ($data->conn->Affected_Rows() > 0) {
                            // Otherwise if any rows have been affected
                            echo $data->conn->Affected_Rows(), " {$lang['strrowsaff']}<br/>".PHP_EOL;
                        }
                        // Otherwise output nothing...
                        break;
                    case \PGSQL_EMPTY_QUERY:
                        break;
                    default:
                        break;
                }
            }
        };

        return $data->executeScript('script', $sqlCallback);
    }

    private function execute_query()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Set fetch mode to NUM so that duplicate field names are properly returned
        $data->conn->setFetchMode(\ADODB_FETCH_NUM);
        set_time_limit(25000);

        $rs = $data->conn->Execute($this->query);

        echo '<form method="post" id="sqlform" action="'.$_SERVER['REQUEST_URI'].'">';
        echo '<textarea width="90%" name="query"  id="query" rows="5" cols="100" resizable="true">';

        echo htmlspecialchars($this->query);
        echo '</textarea><br>';
        echo $this->misc->setForm();
        echo '<input type="submit"/></form>';

        // $rs will only be an object if there is no error
        if (is_object($rs)) {
            // Request was run, saving it in history
            if (!isset($_REQUEST['nohistory'])) {
                $this->misc->saveScriptHistory($this->query);
            }

            // Now, depending on what happened do various things

            // First, if rows returned, then display the results
            if ($rs->recordCount() > 0) {
                echo "<table>\n<tr>";
                foreach ($rs->fields as $k => $v) {
                    $finfo = $rs->fetchField($k);
                    echo '<th class="data">', $this->misc->printVal($finfo->name), '</th>';
                }
                echo '</tr>'.PHP_EOL;
                $i = 0;
                while (!$rs->EOF) {
                    $id = (0 == ($i % 2) ? '1' : '2');
                    echo "<tr class=\"data{$id}\">".PHP_EOL;
                    foreach ($rs->fields as $k => $v) {
                        $finfo = $rs->fetchField($k);
                        echo '<td style="white-space:nowrap;">', $this->misc->printVal($v, $finfo->type, ['null' => true]), '</td>';
                    }
                    echo '</tr>'.PHP_EOL;
                    $rs->moveNext();
                    ++$i;
                }
                echo '</table>'.PHP_EOL;
                echo '<p>', $rs->recordCount(), " {$this->lang['strrows']}</p>".PHP_EOL;
            } elseif ($data->conn->Affected_Rows() > 0) {
                // Otherwise if any rows have been affected
                echo '<p>', $data->conn->Affected_Rows(), " {$this->lang['strrowsaff']}</p>".PHP_EOL;
            } else {
                // Otherwise nodata to print
                echo '<p>', $this->lang['strnodata'], '</p>'.PHP_EOL;
            }

            return $rs;
        }
    }

    private function doFooter($doBody = true, $template = 'footer.twig', $rs = null)
    {
        $data = $this->misc->getDatabaseAccessor();

        // May as well try to time the query
        if (null !== $this->start_time) {
            list($usec, $sec) = explode(' ', microtime());
            $end_time         = ((float) $usec + (float) $sec);
            // Get duration in milliseconds, round to 3dp's
            $this->duration = number_format(($end_time - $this->start_time) * 1000, 3);
        }

        // Reload the browser as we may have made schema changes
        $this->misc->setReloadBrowser(true);

        // Display duration if we know it
        if (null !== $this->duration) {
            echo '<p>', sprintf($this->lang['strruntime'], $this->duration), '</p>'.PHP_EOL;
        }

        echo "<p>{$this->lang['strsqlexecuted']}</p>".PHP_EOL;

        $navlinks = [];
        $fields   = [
            'server'   => $_REQUEST['server'],
            'database' => $_REQUEST['database'],
        ];

        if (isset($_REQUEST['schema'])) {
            $fields['schema'] = $_REQUEST['schema'];
        }

        // Return
        if (isset($_REQUEST['return'])) {
            $urlvars          = $this->misc->getSubjectParams($_REQUEST['return']);
            $navlinks['back'] = [
                'attr'    => [
                    'href' => [
                        'url'     => $urlvars['url'],
                        'urlvars' => $urlvars['params'],
                    ],
                ],
                'content' => $this->lang['strback'],
            ];
        }

        // Edit
        $navlinks['alter'] = [
            'attr'    => [
                'href' => [
                    'url'     => 'database',
                    'urlvars' => array_merge($fields, [
                        'action' => 'sql',
                    ]),
                ],
            ],
            'content' => $this->lang['streditsql'],
        ];

        // Create view and download
        if ('' !== $this->query && isset($rs) && is_object($rs) && $rs->recordCount() > 0) {
            // Report views don't set a schema, so we need to disable create view in that case
            if (isset($_REQUEST['schema'])) {
                $navlinks['createview'] = [
                    'attr'    => [
                        'href' => [
                            'url'     => 'views',
                            'urlvars' => array_merge($fields, [
                                'action' => 'create',
                            ]),
                        ],
                    ],
                    'content' => $this->lang['strcreateview'],
                ];
            }

            if (isset($_REQUEST['search_path'])) {
                $fields['search_path'] = $_REQUEST['search_path'];
            }

            $navlinks['download'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'dataexport',
                        'urlvars' => $fields,
                    ],
                ],
                'content' => $this->lang['strdownload'],
            ];
        }

        $this->printNavLinks($navlinks, 'sql-form', get_defined_vars());

        return $this->printFooter($doBody, $template);
    }
}
