<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class DataexportController extends BaseController
{
    public $extensions = [
        'sql' => 'sql',
        'copy' => 'sql',
        'csv' => 'csv',
        'tab' => 'txt',
        'html' => 'html',
        'xml' => 'xml',
    ];

    public $controller_title = 'strexport';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        \set_time_limit(0);

        // if (!isset($_REQUEST['table']) && !isset($_REQUEST['query']))
        // What must we do in this case? Maybe redirect to the homepage?

        $format = 'N/A';

        // force behavior to assume there is no pg_dump in the system
        $forcemimic = $_REQUEST['forcemimic'] ?? false;

        // If format is set, then perform the export
        if (!isset($_REQUEST['what'])) {
            return $this->doDefault();
        }

        //$this->prtrace("REQUEST['what']", $_REQUEST['what']);

        // Include application functions
        $this->setNoOutput(true);
        $clean = false;
        $oids = false;

        switch ($_REQUEST['what']) {
            case 'dataonly':
                // Check to see if they have pg_dump set up and if they do, use that
                // instead of custom dump code
                if (!$forcemimic && $this->misc->isDumpEnabled() && ('copy' === $_REQUEST['d_format'] || 'sql' === $_REQUEST['d_format'])) {
                    //$this->prtrace('DUMP ENABLED, d_format is', $_REQUEST['d_format']);
                    $dbexport_controller = new DbexportController($this->getContainer());

                    return $dbexport_controller->render();
                }

                $format = $_REQUEST['d_format'];
                $oids = isset($_REQUEST['d_oids']);

                break;
            case 'structureonly':
                // Check to see if they have pg_dump set up and if they do, use that
                // instead of custom dump code
                if (!$forcemimic && $this->misc->isDumpEnabled()) {
                    $dbexport_controller = new DbexportController($this->getContainer());

                    return $dbexport_controller->render();
                }
                $clean = isset($_REQUEST['s_clean']);

                break;
            case 'structureanddata':
                // Check to see if they have pg_dump set up and if they do, use that
                // instead of custom dump code
                if (!$forcemimic && $this->misc->isDumpEnabled()) {
                    $dbexport_controller = new DbexportController($this->getContainer());

                    return $dbexport_controller->render();
                }
                $format = $_REQUEST['sd_format'];
                $clean = isset($_REQUEST['sd_clean']);
                $oids = isset($_REQUEST['sd_oids']);

                break;
        }
        $cleanprefix = $clean ? '' : '-- ';

        return $this->mimicDumpFeature($format, $cleanprefix, $oids);
    }

    public function doDefault($msg = '')
    {
        if (!isset($_REQUEST['query']) || empty($_REQUEST['query'])) {
            $_REQUEST['query'] = $_SESSION['sqlquery'];
        }

        $this->printHeader();
        $this->printBody();
        $this->printTrail($_REQUEST['subject'] ?? 'database');
        $this->printTitle($this->lang['strexport']);

        if (isset($msg)) {
            $this->printMsg($msg);
        }

        echo '<form action="dataexport" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data">%s:</th><td><select name="d_format">',
            $this->lang['strformat']
        ) . \PHP_EOL;
        // COPY and SQL require a table
        if (isset($_REQUEST['table'])) {
            echo '<option value="copy">COPY</option>' . \PHP_EOL;
            echo '<option value="sql">SQL</option>' . \PHP_EOL;
        }
        echo '<option value="csv">CSV</option>' . \PHP_EOL;
        echo \sprintf(
            '<option value="tab">%s</option>',
            $this->lang['strtabbed']
        ) . \PHP_EOL;
        echo '<option value="html">XHTML</option>' . \PHP_EOL;
        echo '<option value="xml">XML</option>' . \PHP_EOL;
        echo '</select></td></tr>';
        echo '</table>' . \PHP_EOL;

        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['stroptions']
        ) . \PHP_EOL;
        echo \sprintf(
            '<p><input type="radio" id="output1" name="output" value="show" checked="checked" /><label for="output1">%s</label>',
            $this->lang['strshow']
        ) . \PHP_EOL;
        echo \sprintf(
            '<br/><input type="radio" id="output2" name="output" value="download" /><label for="output2">%s</label></p>',
            $this->lang['strdownload']
        ) . \PHP_EOL;

        echo '<p><input type="hidden" name="action" value="export" />' . \PHP_EOL;
        echo '<input type="hidden" name="what" value="dataonly" />' . \PHP_EOL;

        if (isset($_REQUEST['table'])) {
            echo '<input type="hidden" name="subject" value="table" />' . \PHP_EOL;
            echo \sprintf(
                '<input type="hidden" name="table" value="%s"  />%s',
                \htmlspecialchars($_REQUEST['table']),
                \PHP_EOL
            );
        } else {
            echo '<input type="hidden" name="subject" value="table" />' . \PHP_EOL;
        }
        //$this->prtrace('$_REQUEST[query]', $_REQUEST['query'], \htmlspecialchars(\urlencode($_REQUEST['query'])));
        //$this->prtrace('$_SESSION[sqlquery]', $_SESSION['sqlquery'], \htmlspecialchars(\urlencode($_SESSION['sqlquery'])));
        echo '<input type="hidden" name="query" value="', \htmlspecialchars(\urlencode($_REQUEST['query'])), '" />' . \PHP_EOL;

        if (isset($_REQUEST['search_path'])) {
            echo '<input type="hidden" name="search_path" value="', \htmlspecialchars($_REQUEST['search_path']), '" />' . \PHP_EOL;
        }
        echo $this->view->form;
        echo \sprintf(
            '<input type="submit" value="%s" /></p>',
            $this->lang['strexport']
        ) . \PHP_EOL;
        echo '</form>' . \PHP_EOL;

        return $this->printFooter();
    }

    protected function mimicDumpFeature($format, string $cleanprefix, bool $oids)
    {
        $data = $this->misc->getDatabaseAccessor();

        \set_time_limit(0);

        // if (!isset($_REQUEST['table']) && !isset($_REQUEST['query']))
        // What must we do in this case? Maybe redirect to the homepage?

        // If format is set, then perform the export
        if (!isset($_REQUEST['what'])) {
            return $this->doDefault();
        }

        //$this->prtrace("REQUEST['what']", $_REQUEST['what']);

        // Include application functions
        $this->setNoOutput(true);

        $response = $this->_getResponse($format);

        $this->coalesceArr($_REQUEST, 'query', '');

        $_REQUEST['query'] = \trim(\urldecode($_REQUEST['query']));

        // Set the schema search path
        if (isset($_REQUEST['search_path'])) {
            $data->setSearchPath(\array_map('trim', \explode(',', $_REQUEST['search_path'])));
        } elseif (isset($_REQUEST['schema'])) {
            $data->setSearchPath($_REQUEST['schema']);
        }

        $subject = $this->coalesceArr($_REQUEST, 'subject', 'table')['subject'];

        $object = $this->coalesceArr($_REQUEST, $subject)[$subject];

        // Set up the dump transaction
        $status = $data->beginDump();
        //$this->prtrace('subject', $subject);
        //$this->prtrace('object', $object);
        $tabledefprefix = '';
        $tabledefsuffix = '';

        // If the dump is not dataonly then dump the structure prefix
        if ('dataonly' !== $_REQUEST['what']) {
            $tabledefprefix = $data->getTableDefPrefix($object, $cleanprefix);
            //$this->prtrace('tabledefprefix', $tabledefprefix);
            echo $tabledefprefix;
        }

        // If the dump is not structureonly then dump the actual data
        if ('structureonly' !== $_REQUEST['what']) {
            // Get database encoding
            //$dbEncoding = $data->getDatabaseEncoding();

            // Set fetch mode to NUM so that duplicate field names are properly returned
            $data->conn->setFetchMode(\ADODB_FETCH_NUM);

            // Execute the query, if set, otherwise grab all rows from the table
            $rs = $this->_getRS($data, $object, $oids);

            $response = $this->pickFormat($data, $object, $oids, $rs, $format, $response);
        }

        if ('dataonly' !== $_REQUEST['what']) {
            $data->conn->setFetchMode(\ADODB_FETCH_ASSOC);
            $tabledefsuffix = $data->getTableDefSuffix($object);
            //$this->prtrace('tabledefsuffix', $tabledefsuffix);
            echo $tabledefsuffix;
        }

        // Finish the dump transaction
        $status = $data->endDump();

        return $response;
    }

    /**
     * @param \PHPPgAdmin\Database\Postgres $data
     * @param mixed                         $object
     *
     * @return \ADORecordSet|bool|int|string
     */
    private function _getRS($data, $object, bool $oids)
    {
        if ($object) {
            return $data->dumpRelation($object, $oids);
        }

        //$this->prtrace('$_REQUEST[query]', $_REQUEST['query']);

        return $data->conn->Execute($_REQUEST['query']);
    }

    private function _getResponse($format)
    {
        $response = $this
            ->container
            ->response;

        // Make it do a download, if necessary
        if ('download' !== $_REQUEST['output']) {
            return $response
                ->withHeader('Content-type', 'text/plain');
        }
        // Set headers.  MSIE is totally broken for SSL downloading, so
        // we need to have it download in-place as plain text
        if (\mb_strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS'])) {
            return $response
                ->withHeader('Content-type', 'text/plain');
        }
        $response = $response
            ->withHeader('Content-type', 'application/download');

        $ext = 'txt';

        if (isset($this->extensions[$format])) {
            $ext = $this->extensions[$format];
        }

        return $response
            ->withHeader('Content-Disposition', 'attachment; filename=dump.' . $ext);
    }

    /**
     * @param \PHPPgAdmin\Database\Postgres $data
     * @param \ADORecordSet|bool|int|string $rs
     * @param mixed                         $object
     * @param mixed                         $format
     * @param mixed                         $response
     */
    private function pickFormat($data, $object, bool $oids, $rs, $format, $response)
    {
        if ('copy' === $format) {
            $this->_mimicCopy($data, $object, $oids, $rs);
        } elseif ('html' === $format) {
            $response = $response
                ->withHeader('Content-type', 'text/html');
            $this->_mimicHtml($data, $object, $oids, $rs);
        } elseif ('xml' === $format) {
            $response = $response
                ->withHeader('Content-type', 'application/xml');
            $this->_mimicXml($data, $object, $oids, $rs);
        } elseif ('sql' === $format) {
            $this->_mimicSQL($data, $object, $oids, $rs);
        }
        $this->_csvOrTab($data, $object, $oids, $rs, $format);

        return $response;
    }

    /**
     * @param bool                          $oids
     * @param \PHPPgAdmin\Database\Postgres $data
     * @param mixed                         $object
     * @param mixed                         $rs
     */
    private function _mimicCopy($data, $object, $oids, $rs): void
    {
        $data->fieldClean($object);
        echo \sprintf(
            'COPY "%s"',
            $_REQUEST['table']
        );

        if ($oids) {
            echo ' WITH OIDS';
        }

        echo " FROM stdin;\n";

        while (!$rs->EOF) {
            $first = true;
            //while (list($k, $v) = each($rs->fields)) {
            foreach ($rs->fields as $k => $v) {
                // Escape value
                $v = $data->escapeBytea($v);

                // We add an extra escaping slash onto octal encoded characters
                $v = \preg_replace('/\\\\([0-7]{3})/', '\\\\\1', $v);

                if ($first) {
                    echo (null === $v) ? '\\N' : $v;
                    $first = false;
                } else {
                    echo "\t", (null === $v) ? '\\N' : $v;
                }
            }
            echo \PHP_EOL;
            $rs->MoveNext();
        }
        echo "\\.\n";
    }

    /**
     * @param bool                          $oids
     * @param \PHPPgAdmin\Database\Postgres $data
     * @param mixed                         $object
     * @param mixed                         $rs
     */
    private function _mimicHtml($data, $object, $oids, $rs): void
    {
        echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n";
        echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\r\n";
        echo "<head>\r\n";
        echo "\t<title></title>\r\n";
        echo "\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n";
        echo "</head>\r\n";
        echo "<body>\r\n";
        echo "<table class=\"phppgadmin\">\r\n";
        echo "\t<tr>\r\n";

        if (!$rs->EOF) {
            // Output header row
            $j = 0;

            foreach (\array_keys($rs->fields) as $k) {
                $finfo = $rs->FetchField($j++);

                if ($finfo->name === $data->id && !$oids) {
                    continue;
                }

                echo "\t\t<th>", $this->misc->printVal($finfo->name, 'verbatim'), "</th>\r\n";
            }
        }
        echo "\t</tr>\r\n";

        while (!$rs->EOF) {
            echo "\t<tr>\r\n";
            $j = 0;

            foreach ($rs->fields as $k => $v) {
                $finfo = $rs->FetchField($j++);

                if ($finfo->name === $data->id && !$oids) {
                    continue;
                }

                echo "\t\t<td>", $this->misc->printVal($v, 'verbatim', $finfo->type), "</td>\r\n";
            }
            echo "\t</tr>\r\n";
            $rs->MoveNext();
        }
        echo "</table>\r\n";
        echo "</body>\r\n";
        echo "</html>\r\n";
    }

    /**
     * @param bool                          $oids
     * @param \PHPPgAdmin\Database\Postgres $data
     * @param mixed                         $object
     * @param mixed                         $rs
     */
    private function _mimicXml($data, $object, $oids, $rs): void
    {
        echo '<?xml version="1.0" encoding="utf-8" ?>' . \PHP_EOL;
        echo '<data>' . \PHP_EOL;

        if (!$rs->EOF) {
            // Output header row
            $j = 0;
            echo "\t<header>" . \PHP_EOL;

            foreach (\array_keys($rs->fields) as $k) {
                $finfo = $rs->FetchField($j++);
                $name = \htmlspecialchars($finfo->name);
                $type = \htmlspecialchars($finfo->type);
                echo \sprintf(
                    '		<column name="%s" type="%s" />',
                    $name,
                    $type
                ) . \PHP_EOL;
            }
            echo "\t</header>" . \PHP_EOL;
        }
        echo "\t<records>" . \PHP_EOL;

        while (!$rs->EOF) {
            $j = 0;
            echo "\t\t<row>" . \PHP_EOL;

            foreach ($rs->fields as $k => $v) {
                $finfo = $rs->FetchField($j++);
                $name = \htmlspecialchars($finfo->name);

                if (null !== $v) {
                    $v = \htmlspecialchars($v);
                }

                echo \sprintf(
                    '			<column name="%s"',
                    $name
                ), (null === $v ? ' null="null"' : ''), \sprintf(
                    '>%s</column>',
                    $v
                ) . \PHP_EOL;
            }
            echo "\t\t</row>" . \PHP_EOL;
            $rs->MoveNext();
        }
        echo "\t</records>" . \PHP_EOL;
        echo '</data>' . \PHP_EOL;
    }

    /**
     * @param bool                          $oids
     * @param \PHPPgAdmin\Database\Postgres $data
     * @param mixed                         $object
     * @param mixed                         $rs
     */
    private function _mimicSQL($data, $object, $oids, $rs): void
    {
        $data->fieldClean($object);
        $values = '';

        while (!$rs->EOF) {
            echo \sprintf(
                'INSERT INTO "%s" (',
                $object
            );
            $first = true;
            $j = 0;

            foreach ($rs->fields as $k => $v) {
                $finfo = $rs->FetchField($j++);
                $k = $finfo->name;
                // SQL (INSERT) format cannot handle oids
                //                        if ($k == $data->id) continue;
                // Output field
                $data->fieldClean($k);

                if ($first) {
                    echo \sprintf(
                        '"%s"',
                        $k
                    );
                } else {
                    echo \sprintf(
                        ', "%s"',
                        $k
                    );
                }

                if (null !== $v) {
                    // Output value
                    // addCSlashes converts all weird ASCII characters to octal representation,
                    // EXCEPT the 'special' ones like \r \n \t, etc.
                    $v = \addcslashes($v, "\0..\37\177..\377");
                    // We add an extra escaping slash onto octal encoded characters
                    $v = \preg_replace('/\\\\([0-7]{3})/', '\\\1', $v);
                    // Finally, escape all apostrophes
                    $v = \str_replace("'", "''", $v);
                }

                if ($first) {
                    $values = (null === $v ? 'NULL' : \sprintf(
                        '\'%s\'',
                        $v
                    ));
                    $first = false;
                } else {
                    $values .= ', ' . ((null === $v ? 'NULL' : \sprintf(
                        '\'%s\'',
                        $v
                    )));
                }
            }
            echo \sprintf(
                ') VALUES (%s);
',
                $values
            );
            $rs->MoveNext();
        }
    }

    /**
     * @param bool                          $oids
     * @param \PHPPgAdmin\Database\Postgres $data
     * @param mixed                         $object
     * @param mixed                         $rs
     * @param mixed                         $format
     */
    private function _csvOrTab($data, $object, $oids, $rs, $format): void
    {
        switch ($format) {
            case 'tab':
                $sep = "\t";

                break;
            case 'csv':
            default:
                $sep = ',';

                break;
        }

        if (!$rs->EOF) {
            // Output header row
            $first = true;

            foreach ($rs->fields as $k => $v) {
                $finfo = $rs->FetchField($k);
                $v = $finfo->name;

                if (null !== $v) {
                    $v = \str_replace('"', '""', $v);
                }

                if ($first) {
                    echo \sprintf(
                        '"%s"',
                        $v
                    );
                    $first = false;
                } else {
                    echo \sprintf(
                        '%s"%s"',
                        $sep,
                        $v
                    );
                }
            }
            echo "\r\n";
        }

        while (!$rs->EOF) {
            $first = true;

            foreach ($rs->fields as $k => $v) {
                if (null !== $v) {
                    $v = \str_replace('"', '""', $v);
                }

                if ($first) {
                    echo (null === $v) ? '"\\N"' : \sprintf(
                        '"%s"',
                        $v
                    );
                    $first = false;
                } else {
                    echo null === $v ? \sprintf(
                        '%s"\N"',
                        $sep
                    ) : \sprintf(
                        '%s"%s"',
                        $sep,
                        $v
                    );
                }
            }
            echo "\r\n";
            $rs->MoveNext();
        }
    }
}
