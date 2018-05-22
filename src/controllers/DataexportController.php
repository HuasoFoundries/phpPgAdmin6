<?php

/**
 * PHPPgAdmin v6.0.0-beta.45
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class DataexportController extends BaseController
{
    public $extensions = [
        'sql'  => 'sql',
        'copy' => 'sql',
        'csv'  => 'csv',
        'tab'  => 'txt',
        'html' => 'html',
        'xml'  => 'xml',
    ];
    public $controller_title = 'strexport';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        set_time_limit(0);

        // if (!isset($_REQUEST['table']) && !isset($_REQUEST['query']))
        // What must we do in this case? Maybe redirect to the homepage?

        $format = 'N/A';
        // If format is set, then perform the export
        if (!isset($_REQUEST['what'])) {
            return $this->doDefault();
        }

        $this->prtrace("REQUEST['what']", $_REQUEST['what']);

        // Include application functions
        $this->setNoOutput(true);
        $clean = false;
        $oids  = false;
        switch ($_REQUEST['what']) {
            case 'dataonly':
                // Check to see if they have pg_dump set up and if they do, use that
                // instead of custom dump code
                if ($this->misc->isDumpEnabled() && ('copy' == $_REQUEST['d_format'] || 'sql' == $_REQUEST['d_format'])) {
                    $this->prtrace('DUMP ENABLED, d_format is', $_REQUEST['d_format']);
                    $dbexport_controller = new \PHPPgAdmin\Controller\DbexportController($this->getContainer());

                    return $dbexport_controller->render();
                }
                $this->prtrace('d_format is', $_REQUEST['d_format'], 'd_oids is', isset($_REQUEST['d_oids']));
                $format = $_REQUEST['d_format'];
                $oids   = isset($_REQUEST['d_oids']);

                break;
            case 'structureonly':
                // Check to see if they have pg_dump set up and if they do, use that
                // instead of custom dump code
                if ($this->misc->isDumpEnabled()) {
                    $dbexport_controller = new \PHPPgAdmin\Controller\DbexportController($this->getContainer());

                    return $dbexport_controller->render();
                }
                $clean = isset($_REQUEST['s_clean']);

                break;
            case 'structureanddata':
                // Check to see if they have pg_dump set up and if they do, use that
                // instead of custom dump code
                if ($this->misc->isDumpEnabled()) {
                    $dbexport_controller = new \PHPPgAdmin\Controller\DbexportController($this->getContainer());

                    return $dbexport_controller->render();
                }
                $format = $_REQUEST['sd_format'];
                $clean  = isset($_REQUEST['sd_clean']);
                $oids   = isset($_REQUEST['sd_oids']);

                break;
        }

        return $this->mimicDumpFeature($format, $clean, $oids);
    }

    protected function mimicDumpFeature($format, $clean, $oids)
    {
        $data = $this->misc->getDatabaseAccessor();

        set_time_limit(0);

        // if (!isset($_REQUEST['table']) && !isset($_REQUEST['query']))
        // What must we do in this case? Maybe redirect to the homepage?

        $format = 'N/A';
        // If format is set, then perform the export
        if (!isset($_REQUEST['what'])) {
            return $this->doDefault();
        }

        $this->prtrace("REQUEST['what']", $_REQUEST['what']);

        // Include application functions
        $this->setNoOutput(true);
        $clean    = false;
        $response = $this
            ->container
            ->responseobj;

        // Make it do a download, if necessary
        if ('download' == $_REQUEST['output']) {
            // Set headers.  MSIE is totally broken for SSL downloading, so
            // we need to have it download in-place as plain text
            if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS'])) {
                $response = $response
                    ->withHeader('Content-type', 'text/plain');
            } else {
                $response = $response
                    ->withHeader('Content-type', 'application/download');

                if (isset($this->extensions[$format])) {
                    $ext = $this->extensions[$format];
                } else {
                    $ext = 'txt';
                }
                $response = $response
                    ->withHeader('Content-Disposition', 'attachment; filename=dump.' . $ext);
            }
        } else {
            $response = $response
                ->withHeader('Content-type', 'text/plain');
        }

        if (isset($_REQUEST['query'])) {
            $_REQUEST['query'] = trim(urldecode($_REQUEST['query']));
        }

        // Set the schema search path
        if (isset($_REQUEST['search_path'])) {
            $data->setSearchPath(array_map('trim', explode(',', $_REQUEST['search_path'])));
        }

        $subject = $this->coalesceArr($_REQUEST, 'subject', 'table')['subject'];

        $object = $this->coalesceArr($_REQUEST, $subject)[$subject];

        // Set up the dump transaction
        $status = $data->beginDump();
        $this->prtrace('subject', $subject);
        $this->prtrace('object', $object);

        // If the dump is not dataonly then dump the structure prefix
        if ('dataonly' != $_REQUEST['what']) {
            $tabledefprefix = $data->getTableDefPrefix($object, $clean);
            $this->prtrace('tabledefprefix', $tabledefprefix);
            echo $tabledefprefix;
        }

        // If the dump is not structureonly then dump the actual data
        if ('structureonly' != $_REQUEST['what']) {
            // Get database encoding
            $dbEncoding = $data->getDatabaseEncoding();

            // Set fetch mode to NUM so that duplicate field names are properly returned
            $data->conn->setFetchMode(ADODB_FETCH_NUM);

            // Execute the query, if set, otherwise grab all rows from the table
            if ($object) {
                $rs = $data->dumpRelation($object, $oids);
            } else {
                $rs = $data->conn->Execute($_REQUEST['query']);
            }
            $this->prtrace('$_REQUEST[query]', $_REQUEST['query']);

            if ('copy' == $format) {
                $data->fieldClean($object);
                echo "COPY \"{$_REQUEST['table']}\"";
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
                        $v = preg_replace('/\\\\([0-7]{3})/', '\\\\\1', $v);
                        if ($first) {
                            echo (is_null($v)) ? '\\N' : $v;
                            $first = false;
                        } else {
                            echo "\t", (is_null($v)) ? '\\N' : $v;
                        }
                    }
                    echo "\n";
                    $rs->moveNext();
                }
                echo "\\.\n";
            } elseif ('html' == $format) {
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
                    foreach ($rs->fields as $k => $v) {
                        $finfo = $rs->fetchField($j++);
                        if ($finfo->name == $data->id && !$oids) {
                            continue;
                        }

                        echo "\t\t<th>", $this->misc->printVal($finfo->name, true), "</th>\r\n";
                    }
                }
                echo "\t</tr>\r\n";
                while (!$rs->EOF) {
                    echo "\t<tr>\r\n";
                    $j = 0;
                    foreach ($rs->fields as $k => $v) {
                        $finfo = $rs->fetchField($j++);
                        if ($finfo->name == $data->id && !$oids) {
                            continue;
                        }

                        echo "\t\t<td>", $this->misc->printVal($v, true, $finfo->type), "</td>\r\n";
                    }
                    echo "\t</tr>\r\n";
                    $rs->moveNext();
                }
                echo "</table>\r\n";
                echo "</body>\r\n";
                echo "</html>\r\n";
            } elseif ('xml' == $format) {
                echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
                echo "<data>\n";
                if (!$rs->EOF) {
                    // Output header row
                    $j = 0;
                    echo "\t<header>\n";
                    foreach ($rs->fields as $k => $v) {
                        $finfo = $rs->fetchField($j++);
                        $name  = htmlspecialchars($finfo->name);
                        $type  = htmlspecialchars($finfo->type);
                        echo "\t\t<column name=\"{$name}\" type=\"{$type}\" />\n";
                    }
                    echo "\t</header>\n";
                }
                echo "\t<records>\n";
                while (!$rs->EOF) {
                    $j = 0;
                    echo "\t\t<row>\n";
                    foreach ($rs->fields as $k => $v) {
                        $finfo = $rs->fetchField($j++);
                        $name  = htmlspecialchars($finfo->name);
                        if (!is_null($v)) {
                            $v = htmlspecialchars($v);
                        }

                        echo "\t\t\t<column name=\"{$name}\"", (is_null($v) ? ' null="null"' : ''), ">{$v}</column>\n";
                    }
                    echo "\t\t</row>\n";
                    $rs->moveNext();
                }
                echo "\t</records>\n";
                echo "</data>\n";
            } elseif ('sql' == $format) {
                $data->fieldClean($object);
                while (!$rs->EOF) {
                    echo "INSERT INTO \"{$object}\" (";
                    $first = true;
                    $j     = 0;
                    foreach ($rs->fields as $k => $v) {
                        $finfo = $rs->fetchField($j++);
                        $k     = $finfo->name;
                        // SQL (INSERT) format cannot handle oids
                        //                        if ($k == $data->id) continue;
                        // Output field
                        $data->fieldClean($k);
                        if ($first) {
                            echo "\"{$k}\"";
                        } else {
                            echo ", \"{$k}\"";
                        }

                        if (!is_null($v)) {
                            // Output value
                            // addCSlashes converts all weird ASCII characters to octal representation,
                            // EXCEPT the 'special' ones like \r \n \t, etc.
                            $v = addcslashes($v, "\0..\37\177..\377");
                            // We add an extra escaping slash onto octal encoded characters
                            $v = preg_replace('/\\\\([0-7]{3})/', '\\\1', $v);
                            // Finally, escape all apostrophes
                            $v = str_replace("'", "''", $v);
                        }
                        if ($first) {
                            $values = (is_null($v) ? 'NULL' : "'{$v}'");
                            $first  = false;
                        } else {
                            $values .= ', ' . ((is_null($v) ? 'NULL' : "'{$v}'"));
                        }
                    }
                    echo ") VALUES ({$values});\n";
                    $rs->moveNext();
                }
            } else {
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
                        $finfo = $rs->fetchField($k);
                        $v     = $finfo->name;
                        if (!is_null($v)) {
                            $v = str_replace('"', '""', $v);
                        }

                        if ($first) {
                            echo "\"{$v}\"";
                            $first = false;
                        } else {
                            echo "{$sep}\"{$v}\"";
                        }
                    }
                    echo "\r\n";
                }
                while (!$rs->EOF) {
                    $first = true;
                    foreach ($rs->fields as $k => $v) {
                        if (!is_null($v)) {
                            $v = str_replace('"', '""', $v);
                        }

                        if ($first) {
                            echo (is_null($v)) ? '"\\N"' : "\"{$v}\"";
                            $first = false;
                        } else {
                            echo is_null($v) ? "{$sep}\"\\N\"" : "{$sep}\"{$v}\"";
                        }
                    }
                    echo "\r\n";
                    $rs->moveNext();
                }
            }
        }

        // If the dump is not dataonly then dump the structure suffix
        if ('dataonly' != $_REQUEST['what']) {
            // Set fetch mode back to ASSOC for the table suffix to work
            $data->conn->setFetchMode(ADODB_FETCH_ASSOC);
            $tabledefsuffix = $data->getTableDefSuffix($object);
            $this->prtrace('tabledefsuffix', $tabledefsuffix);
            echo $tabledefsuffix;
        }

        // Finish the dump transaction
        $status = $data->endDump();

        return $response;
    }

    public function doDefault($msg = '')
    {
        if (!isset($_REQUEST['query']) || empty($_REQUEST['query'])) {
            $_REQUEST['query'] = $_SESSION['sqlquery'];
        }

        $this->printHeader();
        $this->printBody();
        $this->printTrail(isset($_REQUEST['subject']) ? $_REQUEST['subject'] : 'database');
        $this->printTitle($this->lang['strexport']);
        if (isset($msg)) {
            $this->printMsg($msg);
        }

        echo '<form action="' . \SUBFOLDER . "/src/views/dataexport\" method=\"post\">\n";
        echo "<table>\n";
        echo "<tr><th class=\"data\">{$this->lang['strformat']}:</th><td><select name=\"d_format\">\n";
        // COPY and SQL require a table
        if (isset($_REQUEST['table'])) {
            echo "<option value=\"copy\">COPY</option>\n";
            echo "<option value=\"sql\">SQL</option>\n";
        }
        echo "<option value=\"csv\">CSV</option>\n";
        echo "<option value=\"tab\">{$this->lang['strtabbed']}</option>\n";
        echo "<option value=\"html\">XHTML</option>\n";
        echo "<option value=\"xml\">XML</option>\n";
        echo '</select></td></tr>';
        echo "</table>\n";

        echo "<h3>{$this->lang['stroptions']}</h3>\n";
        echo "<p><input type=\"radio\" id=\"output1\" name=\"output\" value=\"show\" checked=\"checked\" /><label for=\"output1\">{$this->lang['strshow']}</label>\n";
        echo "<br/><input type=\"radio\" id=\"output2\" name=\"output\" value=\"download\" /><label for=\"output2\">{$this->lang['strdownload']}</label></p>\n";

        echo "<p><input type=\"hidden\" name=\"action\" value=\"export\" />\n";
        echo "<input type=\"hidden\" name=\"what\" value=\"dataonly\" />\n";
        if (isset($_REQUEST['table'])) {
            echo '<input type="hidden" name="subject" value="table" />' . "\n";
            echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), "\" />\n";
        } else {
            echo '<input type="hidden" name="subject" value="table" />' . "\n";
        }
        $this->prtrace('$_REQUEST[query]', $_REQUEST['query'], htmlspecialchars(urlencode($_REQUEST['query'])));
        $this->prtrace('$_SESSION[sqlquery]', $_SESSION['sqlquery'], htmlspecialchars(urlencode($_SESSION['sqlquery'])));
        echo '<input type="hidden" name="query" value="', htmlspecialchars(urlencode($_REQUEST['query'])), "\" />\n";
        if (isset($_REQUEST['search_path'])) {
            echo '<input type="hidden" name="search_path" value="', htmlspecialchars($_REQUEST['search_path']), "\" />\n";
        }
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strexport']}\" /></p>\n";
        echo "</form>\n";

        $this->printFooter();
    }
}
