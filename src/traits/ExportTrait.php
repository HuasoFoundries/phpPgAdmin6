<?php

/**
 * PHPPgAdmin v6.0.0-RC1
 */

namespace PHPPgAdmin\Traits;

/**
 * Common trait for exporting tables, views or materialized views.
 */
trait ExportTrait
{
    public $href = '';
    public $misc;

    /**
     * prints the dataOnly option when exporting a table, view or materialized view.
     *
     * @param bool $hasID          Indicates if the object has has an object ID
     * @param bool $onlyCopyAndSQL when exporting schema or DB, only copy or SQL formats are offered
     *
     * @return string html table row
     */
    public function dataOnly($hasID, $onlyCopyAndSQL = false)
    {
        $content = '<tr>';
        $content .= '<th class="data left" rowspan="'.($hasID ? 2 : 1).'">';
        $content .= '<input type="radio" id="what1" name="what" value="dataonly" checked="checked" />';
        $content .= sprintf(
            '<label for="what1">%s</label></th>%s',
            $this->lang['strdataonly'],
            PHP_EOL
        );
        $content .= sprintf(
            '<td>%s</td>%s',
            $this->lang['strformat'],
            PHP_EOL
        );
        $content .= '<td><select name="d_format">'.PHP_EOL;
        $content .= '<option value="copy">COPY</option>'.PHP_EOL;
        $content .= '<option value="sql">SQL</option>'.PHP_EOL;

        if (!$onlyCopyAndSQL) {
            $content .= '<option value="csv">CSV</option>'.PHP_EOL;
            $content .= "<option value=\"tab\">{$this->lang['strtabbed']}</option>".PHP_EOL;
            $content .= '<option value="html">XHTML</option>'.PHP_EOL;
            $content .= '<option value="xml">XML</option>'.PHP_EOL;
        }

        $content .= sprintf(
            '</select>%s</td>%s</tr>%s',
            PHP_EOL,
            PHP_EOL,
            PHP_EOL
        );

        if ($hasID) {
            $content .= sprintf(
                '<tr><td><label for="d_oids">%s</td>',
                $this->lang['stroids']
            );
            $content .= sprintf(
                '<td><input type="checkbox" id="d_oids" name="d_oids" /></td>%s</tr>%s',
                PHP_EOL,
                PHP_EOL
            );
        }

        return $content;
    }

    /**
     * prints the structureAndData option when exporting a table, view or materialized view.
     *
     * @param bool $hasID Indicates if the object has an object ID
     *
     * @return string html table row
     */
    public function structureAndData($hasID)
    {
        $content = '<tr>';
        $content .= '<th class="data left" rowspan="'.($hasID ? 3 : 2).'">';
        $content .= '<input type="radio" id="what3" name="what" value="structureanddata" />';
        $content .= sprintf(
            '<label for="what3">%s</label></th>%s',
            $this->lang['strstructureanddata'],
            PHP_EOL
        );
        $content .= sprintf(
            '<td>%s</td>%s',
            $this->lang['strformat'],
            PHP_EOL
        );
        $content .= '<td><select name="sd_format">'.PHP_EOL;
        $content .= '<option value="copy">COPY</option>'.PHP_EOL;
        $content .= '<option value="sql">SQL</option>'.PHP_EOL;
        $content .= sprintf(
            '</select>%s</td>%s</tr>%s',
            PHP_EOL,
            PHP_EOL,
            PHP_EOL
        );

        $content .= sprintf(
            '<tr><td><label for="sd_clean">%s</label></td>',
            $this->lang['strdrop']
        );
        $content .= sprintf(
            '<td><input type="checkbox" id="sd_clean" name="sd_clean" /></td>%s</tr>%s',
            PHP_EOL,
            PHP_EOL
        );
        if ($hasID) {
            $content .= sprintf(
                '<tr><td><label for="sd_oids">%s</label></td>',
                $this->lang['stroids']
            );
            $content .= sprintf(
                '<td><input type="checkbox" id="sd_oids" name="sd_oids" /></td>%s</tr>%s',
                PHP_EOL,
                PHP_EOL
            );
        }

        return $content;
    }

    /**
     * prints the structureAndData option when exporting a table, view or
     * materialized view.
     *
     * @param bool $checked Tell if this option should be checked
     *
     * @return string html table row
     */
    public function structureOnly($checked = false)
    {
        $content = '<tr><th class="data left">';
        $content .= sprintf(
            '<input type="radio" id="what2" name="what" value="structureonly" %s />',
            $checked ? 'checked="checked"' : ''
        );
        $content .= sprintf(
            '<label for="what2">%s</label></th>',
            $this->lang['strstructureonly'],
            PHP_EOL
        );
        $content .= sprintf(
            '<td><label for="no_role_info">%s</label></td>',
            $this->lang['strdrop']
        );
        $content .= sprintf(
            '<td><input type="checkbox" id="no_role_info" name="no_role_info" /></td>%s</tr>%s',
            PHP_EOL,
            PHP_EOL
        );

        return $content;
    }

    /**
     * Returns the export form header.
     *
     * @param string $endpoint The endpoint to send the request to (dataexport or dbexport)
     *
     * @return string the html for the form header
     */
    public function formHeader($endpoint = 'dataexport')
    {
        $content = sprintf(
            '<form id="export_form" action="%s/%s" method="post">%s',
            \SUBFOLDER.'/src/views',
            $endpoint,
            PHP_EOL
        );
        $content .= '<table>'.PHP_EOL;
        $content .= sprintf(
            '<tr><th class="data">%s</th>',
            $this->lang['strformat']
        );
        $content .= sprintf(
            '<th class="data" colspan="2">%s</th></tr>%s',
            $this->lang['stroptions'],
            PHP_EOL
        );

        return $content;
    }

    /**
     * prints the formFooter section when exporting a table, view or materialized view.
     *
     * @param string $subject either table, view or matview
     * @param string $object  name of the table, view or matview
     *
     * @return string html table row
     */
    public function formFooter($subject, $object)
    {
        $content = '<p><input type="hidden" name="action" value="export" />'.PHP_EOL;

        $content .= $this->misc->form;
        $content .= sprintf(
            '<input type="hidden" name="subject" value="%s" />%s',
            $subject,
            PHP_EOL
        );
        $content .= sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            $subject,
            htmlspecialchars($object),
            PHP_EOL
        );
        $content .= sprintf(
            '<input type="submit" value="%s" /></p>%s',
            $this->lang['strexport'],
            PHP_EOL
        );
        $content .= sprintf(
            '</form>%s',
            PHP_EOL
        );

        return $content;
    }

    /**
     * Offers the option of display, download and conditionally download gzipped.
     *
     * @param bool $offerGzip Offer to download gzipped
     *
     * @return string the html of the display or download section
     */
    public function displayOrDownload($offerGzip = false)
    {
        $content = sprintf(
            '</table>%s',
            PHP_EOL
        );
        $content .= sprintf(
            '<h3>%s</h3>%s',
            $this->lang['stroptions'],
            PHP_EOL
        );
        $content .= '<p><input type="radio" id="output1" name="output" value="show" checked="checked" />';
        $content .= sprintf(
            '<label for="output1">%s</label>',
            $this->lang['strshow'],
            PHP_EOL
        );
        $content .= '<br/><input type="radio" id="output2" name="output" value="download" />';
        $content .= sprintf(
            '<label for="output2">%s</label>',
            $this->lang['strdownload']
        );

        if ($offerGzip) {
            $content .= '<br /><input type="radio" id="output3" name="output" value="gzipped" />';
            $content .= sprintf(
                '<label for="output3">%s</label>%s',
                $this->lang['strdownloadgzipped'],
                PHP_EOL
            );
        }
        $content .= sprintf(
            '</p>%s',
            PHP_EOL
        );

        return $content;
    }

    /**
     * Offers the option of export database without user credentials. When running in Amazon RDS this is the workaround
     * to make pg_dumpall work at all.
     *
     * @param mixed $version10orMore
     *
     * @return string the html of the options snipper
     */
    public function offerNoRoleExport($version10orMore)
    {
        $this->prtrace($version10orMore);
        if (!$version10orMore) {
            return '';
        }
        $content = '<tr>'.PHP_EOL;
        $content .= sprintf(
            '<tr>%s<td colspan="3">&nbsp</td></tr>%s',
            PHP_EOL,
            PHP_EOL
        );
        $content .= sprintf(
            '<tr>%s<th class="data left" colspan="3"><h3>%s <br> %s</h3></th></tr>%s',
            PHP_EOL,
            'Use the option below if your platform prevents dumping DBs',
            'with role info (e.g. Amazon RDS)',
            PHP_EOL
        );
        $content .= sprintf(
            '<tr>%s<td colspan="2"><label for="no_role_passwords">%s</label><a href="%s">?</></td>%s',
            PHP_EOL,
            'Avoid dumping roles',
            'https://www.postgresql.org/docs/10/app-pg-dumpall.html',
            PHP_EOL
        );
        $content .= sprintf(
            '<td><input type="checkbox" id="no_role_passwords" name="no_role_passwords" /></td>%s</tr>%s',
            PHP_EOL,
            PHP_EOL
        );

        return $content;
    }
}
