<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class DataimportController extends BaseController
{
    public $controller_title = 'strimport';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Prevent timeouts on large exports
        \set_time_limit(0);

        $this->printHeader();
        $this->printTrail('table');
        $this->printTabs('table', 'import');

        // Default state for XML parser
        $state = 'XML';
        $curr_col_name = null;
        $curr_col_val = null;
        $curr_col_null = false;
        $curr_row = [];

        /**
         * Character data handler for XML import feature.
         *
         * @param $parser
         * @param $cdata
         */
        $_charHandler = static function ($parser, $cdata) use (&$state, &$curr_col_val): void {
            if ('COLUMN' === $state) {
                $curr_col_val .= $cdata;
            }
        };

        $lang = $this->lang;
        /**
         * Open tag handler for XML import feature.
         *
         * @param resource $parser
         * @param string   $name
         * @param array    $attrs
         */
        $_startElement = function ($parser, $name, $attrs) use ($curr_row, $data, &$state, &$curr_col_name, &$curr_col_null, $lang): void {
            switch ($name) {
                case 'DATA':
                    if ('XML' !== $state) {
                        $data->rollbackTransaction();
                        $this->halt($lang['strimporterror']);
                    }
                    $state = 'DATA';

                    break;
                case 'HEADER':
                    if ('DATA' !== $state) {
                        $data->rollbackTransaction();
                        $this->halt($lang['strimporterror']);
                    }
                    $state = 'HEADER';

                    break;
                case 'RECORDS':
                    if ('READ_HEADER' !== $state) {
                        $data->rollbackTransaction();
                        $this->halt($lang['strimporterror']);
                    }
                    $state = 'RECORDS';

                    break;
                case 'ROW':
                    if ('RECORDS' !== $state) {
                        $data->rollbackTransaction();
                        $this->halt($lang['strimporterror']);
                    }
                    $state = 'ROW';
                    $curr_row = [];

                    break;
                case 'COLUMN':
                    // We handle columns in rows
                    if ('ROW' === $state) {
                        $state = 'COLUMN';
                        $curr_col_name = $attrs['NAME'];
                        $curr_col_null = isset($attrs['NULL']);
                    } elseif ('HEADER' !== $state) {
                        // And we ignore columns in headers and fail in any other context
                        $data->rollbackTransaction();
                        $this->halt($lang['strimporterror']);
                    }

                    break;

                default:
                    // An unrecognised tag means failure
                    $data->rollbackTransaction();
                    $this->halt($lang['strimporterror']);
            }
        };

        /**
         * Close tag handler for XML import feature.
         *
         * @param resource $parser
         * @param string   $name
         */
        $_endElement = function ($parser, $name) use ($curr_row, $data, &$state, &$curr_col_name, &$curr_col_null, &$curr_col_val, $lang): void {
            switch ($name) {
                case 'DATA':
                    $state = 'READ_DATA';

                    break;
                case 'HEADER':
                    $state = 'READ_HEADER';

                    break;
                case 'RECORDS':
                    $state = 'READ_RECORDS';

                    break;
                case 'ROW':
                    // Build value map in order to insert row into table
                    $fields = [];
                    $vars = [];
                    $nulls = [];
                    $format = [];
                    $types = [];
                    $i = 0;

                    foreach ($curr_row as $k => $v) {
                        $fields[$i] = $k;
                        // Check for nulls
                        if (null === $v) {
                            $nulls[$i] = 'on';
                        }

                        // Add to value array
                        $vars[$i] = $v;
                        // Format is always VALUE
                        $format[$i] = 'VALUE';
                        // Type is always text
                        $types[$i] = 'text';
                        ++$i;
                    }
                    $status = $data->insertRow($_REQUEST['table'], $fields, $vars, $nulls, $format, $types);

                    if (0 !== $status) {
                        $data->rollbackTransaction();
                        $this->halt($lang['strimporterror']);
                    }
                    $curr_row = [];
                    $state = 'RECORDS';

                    break;
                case 'COLUMN':
                    $curr_row[$curr_col_name] = ($curr_col_null ? null : $curr_col_val);
                    $curr_col_name = null;
                    $curr_col_val = null;
                    $curr_col_null = false;
                    $state = 'ROW';

                    break;

                default:
                    // An unrecognised tag means failure
                    $data->rollbackTransaction();
                    $this->halt($lang['strimporterror']);
            }
        };

        // Check that file is specified and is an uploaded file
        if (!isset($_FILES['source']) || !\is_uploaded_file($_FILES['source']['tmp_name']) || !\is_readable($_FILES['source']['tmp_name'])) {
            // Upload went wrong
            $this->printMsg($this->lang['strimporterror-uploadedfile']);

            return $this->printFooter();
        }
        $fd = \fopen($_FILES['source']['tmp_name'], 'rb');
        // Check that file was opened successfully
        if (false === $fd) {
            // File could not be opened
            $this->printMsg($this->lang['strimporterror']);

            return $this->printFooter();
        }
        $null_array = self::loadNULLArray();
        $status = $data->beginTransaction();

        if (0 !== $status) {
            $this->halt($this->lang['strimporterror']);
        }

        // If format is set to 'auto', then determine format automatically from file name
        if ('auto' === $_REQUEST['format']) {
            $extension = \mb_substr(\mb_strrchr($_FILES['source']['name'], '.'), 1);

            switch ($extension) {
                case 'csv':
                    $_REQUEST['format'] = 'csv';

                    break;
                case 'txt':
                    $_REQUEST['format'] = 'tab';

                    break;
                case 'xml':
                    $_REQUEST['format'] = 'xml';

                    break;

                default:
                    $data->rollbackTransaction();
                    $this->halt($this->lang['strimporterror-fileformat']);
            }
        }

        // Do different import technique depending on file format
        switch ($_REQUEST['format']) {
            case 'csv':
            case 'tab':
                // XXX: Length of CSV lines limited to 100k
                $csv_max_line = 100000;
                // Set delimiter to tabs or commas
                if ('csv' === $_REQUEST['format']) {
                    $csv_delimiter = ',';
                } else {
                    $csv_delimiter = "\t";
                }

                // Get first line of field names
                $fields = \fgetcsv($fd, $csv_max_line, $csv_delimiter);
                $row = 2; //We start on the line AFTER the field names
                while ($line = \fgetcsv($fd, $csv_max_line, $csv_delimiter)) {
                    // Build value map
                    $t_fields = [];
                    $vars = [];
                    $nulls = [];
                    $format = [];
                    $types = [];
                    $i = 0;

                    foreach ($fields as $f) {
                        // Check that there is a column
                        if (!isset($line[$i])) {
                            $this->halt(\sprintf($this->lang['strimporterrorline-badcolumnnum'], $row));
                        }
                        $t_fields[$i] = $f;

                        // Check for nulls
                        if (self::determineNull($line[$i], $null_array)) {
                            $nulls[$i] = 'on';
                        }
                        // Add to value array
                        $vars[$i] = $line[$i];
                        // Format is always VALUE
                        $format[$i] = 'VALUE';
                        // Type is always text
                        $types[$i] = 'text';
                        ++$i;
                    }

                    $status = $data->insertRow($_REQUEST['table'], $t_fields, $vars, $nulls, $format, $types);

                    if (0 !== $status) {
                        $data->rollbackTransaction();
                        $this->halt(\sprintf($this->lang['strimporterrorline'], $row));
                    }
                    ++$row;
                }

                break;
            case 'xml':
                $parser = \xml_parser_create();
                \xml_set_element_handler($parser, $_startElement, $_endElement);
                \xml_set_character_data_handler($parser, $_charHandler);

                while (!\feof($fd)) {
                    $line = \fgets($fd, 4096);
                    \xml_parse($parser, $line);
                }

                \xml_parser_free($parser);

                break;

            default:
                // Unknown type
                $data->rollbackTransaction();
                $this->halt($this->lang['strinvalidparam']);
        }

        $status = $data->endTransaction();

        if (0 !== $status) {
            $this->printMsg($this->lang['strimporterror']);
        }
        \fclose($fd);

        $this->printMsg($this->lang['strfileimported']);

        return $this->printFooter();
    }

    public static function loadNULLArray()
    {
        $array = [];

        if (isset($_POST['allowednulls'])) {
            foreach ($_POST['allowednulls'] as $null_char) {
                $array[] = $null_char;
            }
        }

        return $array;
    }

    /**
     * @param null|string $field
     */
    public static function determineNull(?string $field, $null_array)
    {
        return \in_array($field, $null_array, true);
    }
}
