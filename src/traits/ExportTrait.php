<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Traits;

/**
 * Common trait for exporting tables, views or materialized views
 */
trait ExportTrait
{
    public $href = '';
    public $misc;
    /**
     * prints the dataOnly option when exporting a table, view or materialized view
     *
     * @param boolean  $hasID  Indicates if the object has has an object ID
     *
     * @return string  html table row
     */
    public function dataOnly($hasID)
    {
        $content = '<tr>';
        $content .= '<th class="data left" rowspan="' . ($hasID ? 2 : 1) . '">';
        $content .= '<input type="radio" id="what1" name="what" value="dataonly" checked="checked" />';
        $content .= sprintf('<label for="what1">%s</label></th>%s', $this->lang['strdataonly'], "\n");
        $content .= sprintf('<td>%s</td>%s', $this->lang['strformat'], "\n");
        $content .= '<td><select name="d_format">' . "\n";
        $content .= '<option value="copy">COPY</option>' . "\n";
        $content .= '<option value="sql">SQL</option>' . "\n";
        $content .= '<option value="csv">CSV</option>' . "\n";
        $content .= "<option value=\"tab\">{$this->lang['strtabbed']}</option>" . "\n";
        $content .= '<option value="html">XHTML</option>' . "\n";
        $content .= '<option value="xml">XML</option>' . "\n";
        $content .= sprintf('</select>%s</td>%s</tr>%s', "\n", "\n", "\n");

        if ($hasID) {
            $content .= sprintf('<tr><td><label for="d_oids">%s</td>', $this->lang['stroids']);
            $content .= sprintf('<td><input type="checkbox" id="d_oids" name="d_oids" /></td>%s</tr>%s', "\n", "\n");
        }
        return $content;
    }

    /**
     * prints the structureAndData option when exporting a table, view or materialized view
     *
     * @param boolean  $hasID  Indicates if the object has an object ID
     *
     * @return string  html table row
     */
    public function structureAndData($hasID)
    {
        $content = '<tr>';
        $content .= '<th class="data left" rowspan="' . ($hasID ? 3 : 2) . '">';
        $content .= '<input type="radio" id="what3" name="what" value="structureanddata" />';
        $content .= sprintf('<label for="what3">%s</label></th>%s', $this->lang['strstructureanddata'], "\n");
        $content .= sprintf('<td>%s</td>%s', $this->lang['strformat'], "\n");
        $content .= '<td><select name="sd_format">' . "\n";
        $content .= '<option value="copy">COPY</option>' . "\n";
        $content .= '<option value="sql">SQL</option>' . "\n";
        $content .= sprintf('</select>%s</td>%s</tr>%s', "\n", "\n", "\n");

        $content .= sprintf('<tr><td><label for="sd_clean">%s</label></td>', $this->lang['strdrop']);
        $content .= sprintf('<td><input type="checkbox" id="sd_clean" name="sd_clean" /></td>%s</tr>%s', "\n", "\n");
        if ($hasID) {
            $content .= sprintf('<tr><td><label for="sd_oids">%s</label></td>', $this->lang['stroids']);
            $content .= sprintf('<td><input type="checkbox" id="sd_oids" name="sd_oids" /></td>%s</tr>%s', "\n", "\n");
        }
        return $content;
    }

    /**
     * prints the structureAndData option when exporting a table, view or
     * materialized view
     *
     * @param boolean  $checked  Tell if this option should be checked
     *
     * @return string  html table row
     */
    public function structureOnly($checked = false)
    {
        $content = '<tr><th class="data left">';
        $content .= sprintf('<input type="radio" id="what2" name="what" value="structureonly" %s />', $checked ? 'checked="checked"' : '');
        $content .= sprintf('<label for="what2">%s</label></th>', $this->lang['strstructureonly'], "\n");
        $content .= sprintf('<td><label for="s_clean">%s</label></td>', $this->lang['strdrop']);
        $content .= sprintf('<td><input type="checkbox" id="s_clean" name="s_clean" /></td>%s</tr>%s', "\n", "\n");
        return $content;
    }

    public function formHeader()
    {
        $content = sprintf('<form action="%s" method=\"post\">%s', \SUBFOLDER . '/src/views/dataexport', "\n");
        $content .= "<table>\n";
        $content .= sprintf('<tr><th class="data">%s</th>', $this->lang['strformat']);
        $content .= sprintf('<th class="data" colspan="2">%s</th></tr>%s', $this->lang['stroptions'], "\n");
        return $content;
    }

    /**
     * prints the formFooter section when exporting a table, view or materialized view
     *
     * @param string  $subject  either table, view or matview
     * @param string  $object  name of the table, view or matview
     *
     * @return string  html table row
     */
    public function formFooter($subject, $object)
    {

        $content = '<p><input type="hidden" name="action" value="export" />' . "\n";
        $content .= $this->misc->form;
        $content .= sprintf('<input type="hidden" name="subject" value="%s" />%s', $subject, "\n");
        $content .= sprintf('<input type="hidden" name="%s" value="%s" />', $subject, htmlspecialchars($object), "\n");
        $content .= sprintf('<input type="submit" value="%s" /></p>%s', $this->lang['strexport'], "\n");
        $content .= sprintf('</form>%s', "\n");

        return $content;
    }

    public function displayOrDownload()
    {
        $content = sprintf('<h3>%s</h3>%s', $this->lang['stroptions'], "\n");
        $content .= '<p><input type="radio" id="output1" name="output" value="show" checked="checked" />';
        $content .= sprintf('<label for="output1">%s</label>', $this->lang['strshow'], "\n");
        $content .= '<br/><input type="radio" id="output2" name="output" value="download" />';
        $content .= sprintf('<label for="output2">%s</label></p>%s', $this->lang['strdownload'], "\n");

        return $content;
    }
}
