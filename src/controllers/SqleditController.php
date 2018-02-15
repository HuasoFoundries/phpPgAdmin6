<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class SqleditController extends BaseController
{
    public $controller_name = 'SqleditController';
    public $query           = '';
    public $subject         = '';
    public $start_time;
    public $duration;

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {

        $lang = $this->lang;

        $action = $this->action;
        $data   = $this->misc->getDatabaseAccessor();

        switch ($action) {
            case 'find':
                $title     = $this->lang['strfind'];
                $body_text = $this->doFind();

                break;
            case 'sql':
            default:
                $title     = $this->lang['strsql'];
                $body_text = $this->doDefault();

                break;
        }

        $this->setWindowName('sqledit');

        $this->scripts = '<script type="text/javascript">window.inPopUp=true;</script>';

        $this->printHeader($title, $this->scripts, true, 'header_sqledit.twig');
        $this->printBody(true, 'sql_edit');
        echo $body_text;

        $this->printFooter(true, 'footer_sqledit.twig');
    }

    /**
     * Allow execution of arbitrary SQL statements on a database.
     */
    public function doDefault()
    {

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_SESSION['sqlquery'])) {
            $_SESSION['sqlquery'] = '';
        }

        if (!isset($_REQUEST['search_path'])) {
            $_REQUEST['search_path'] = implode(',', $data->getSearchPath());
        }
        $search_path = htmlspecialchars($_REQUEST['search_path']);
        $sqlquery    = htmlspecialchars($_SESSION['sqlquery']);

        $default_html = $this->printTabs($this->misc->getNavTabs('popup'), 'sql', false);

        $default_html .= '<form action="' . \SUBFOLDER . '/src/views/sql" method="post" enctype="multipart/form-data" class="sqlform" id="sqlform" target="detail">';
        $default_html .= "\n";
        $default_html .= $this->_printConnection('sql');

        $default_html .= "\n";

        $default_html .= ' <div class="searchpath">';
        $default_html .= '<label>';
        $default_html .= $this->misc->printHelp($lang['strsearchpath'], 'pg.schema.search_path', false);

        $default_html .= ': <input type="text" name="search_path" size="45" value="' . $search_path . '" />';
        $default_html .= "</label>\n";

        $default_html .= "</div>\n";

        $default_html .= '<div id="queryedition" style="padding:1%;width:98%;float:left;">';
        $default_html .= "\n";
        $default_html .= '<textarea style="width:98%;" rows="10" cols="50" name="query" id="query" resizable="true">' . $sqlquery . '</textarea>';
        $default_html .= "\n";
        $default_html .= "</div>\n";

        $default_html .= '<div class="sqledit_bottom_inputs" >';

        if (ini_get('file_uploads')) {
            // Don't show upload option if max size of uploads is zero
            $max_size = $this->misc->inisizeToBytes(ini_get('upload_max_filesize'));
            if (is_double($max_size) && $max_size > 0) {
                $default_html .= '<p class="upload_sql_script">';
                $default_html .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $max_size . '" />';
                $default_html .= "\n";
                $default_html .= '<label for="script">' . $lang['struploadscript'] . '</label>';
                $default_html .= '&nbsp;&nbsp; <input class="btn btn-small"  id="script" name="script" type="file" /></p>';
                $default_html .= "</p>\n";
            }
        }

        // Check that file uploads are enabled
        $checked = (isset($_REQUEST['paginate']) ? ' checked="checked"' : '');

        $default_html .= '<p><input type="submit" class="btn btn-small" name="execute" accesskey="r" value="' . $lang['strexecute'] . '" />';
        $default_html .= "\n";

        $default_html .= '<input type="reset" class="btn btn-small"  accesskey="q" value="' . $lang['strreset'] . '" /></p>';
        $default_html .= "\n";

        $default_html .= '<p>';
        $default_html .= '<label for="paginate">';
        $default_html .= '<input type="checkbox" id="paginate" name="paginate"' . $checked . ' />&nbsp;' . $lang['strpaginate'] . '&nbsp;';
        $default_html .= "</label>\n";
        $default_html .= "</p>\n";

        $default_html .= "</div>\n";
        $default_html .= '</form>';
        $default_html .= "\n";

        // Default focus
        //$this->setFocus('forms[0].query');
        return $default_html;
    }

    /**
     * Private function to display server and list of databases.
     *
     * @param mixed $action
     */
    public function _printConnection($action)
    {

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        // The javascript action on the select box reloads the
        // popup whenever the server or database is changed.
        // This ensures that the correct page encoding is used.
        $onchange = "onchange=\"location.href='" . \SUBFOLDER . '/sqledit/' .
        urlencode($action) . "?server=' + encodeURI(server.options[server.selectedIndex].value) + '&amp;database=' + encodeURI(database.options[database.selectedIndex].value) + ";

        // The exact URL to reload to is different between SQL and Find mode, however.
        if ('find' == $action) {
            $onchange .= "'&amp;term=' + encodeURI(term.value) + '&amp;filter=' + encodeURI(filter.value) + '&amp;'\"";
        } else {
            $onchange .= "'&amp;query=' + encodeURI(query.value) + '&amp;search_path=' + encodeURI(search_path.value) + (paginate.checked ? '&amp;paginate=on' : '')  + '&amp;'\"";
        }

        return $this->misc->printConnection($onchange, false);
    }

    /**
     * Searches for a named database object.
     */
    public function doFind()
    {

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_REQUEST['term'])) {
            $_REQUEST['term'] = '';
        }

        if (!isset($_REQUEST['filter'])) {
            $_REQUEST['filter'] = '';
        }

        $default_html = $this->printTabs($this->misc->getNavTabs('popup'), 'find', false);

        $default_html .= "<form action=\"database.php\" method=\"post\" target=\"detail\">\n";
        $default_html .= $this->_printConnection('find');
        $default_html .= '<p><input class="focusme" name="term" value="' . htmlspecialchars($_REQUEST['term']) . "\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" />\n";

        // Output list of filters.  This is complex due to all the 'has' and 'conf' feature possibilities
        $default_html .= "<select name=\"filter\">\n";
        $default_html .= "\t<option value=\"\"" . ('' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strallobjects']}</option>\n";
        $default_html .= "\t<option value=\"SCHEMA\"" . ('SCHEMA' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strschemas']}</option>\n";
        $default_html .= "\t<option value=\"TABLE\"" . ('TABLE' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strtables']}</option>\n";
        $default_html .= "\t<option value=\"VIEW\"" . ('VIEW' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strviews']}</option>\n";
        $default_html .= "\t<option value=\"SEQUENCE\"" . ('SEQUENCE' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strsequences']}</option>\n";
        $default_html .= "\t<option value=\"COLUMN\"" . ('COLUMN' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strcolumns']}</option>\n";
        $default_html .= "\t<option value=\"RULE\"" . ('RULE' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strrules']}</option>\n";
        $default_html .= "\t<option value=\"INDEX\"" . ('INDEX' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strindexes']}</option>\n";
        $default_html .= "\t<option value=\"TRIGGER\"" . ('TRIGGER' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strtriggers']}</option>\n";
        $default_html .= "\t<option value=\"CONSTRAINT\"" . ('CONSTRAINT' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strconstraints']}</option>\n";
        $default_html .= "\t<option value=\"FUNCTION\"" . ('FUNCTION' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strfunctions']}</option>\n";
        $default_html .= "\t<option value=\"DOMAIN\"" . ('DOMAIN' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strdomains']}</option>\n";
        if ($conf['show_advanced']) {
            $default_html .= "\t<option value=\"AGGREGATE\"" . ('AGGREGATE' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['straggregates']}</option>\n";
            $default_html .= "\t<option value=\"TYPE\"" . ('TYPE' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strtypes']}</option>\n";
            $default_html .= "\t<option value=\"OPERATOR\"" . ('OPERATOR' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['stroperators']}</option>\n";
            $default_html .= "\t<option value=\"OPCLASS\"" . ('OPCLASS' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['stropclasses']}</option>\n";
            $default_html .= "\t<option value=\"CONVERSION\"" . ('CONVERSION' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strconversions']}</option>\n";
            $default_html .= "\t<option value=\"LANGUAGE\"" . ('LANGUAGE' == $_REQUEST['filter'] ? ' selected="selected" ' : '') . ">{$lang['strlanguages']}</option>\n";
        }
        $default_html .= "</select>\n";

        $default_html .= "<input type=\"submit\" value=\"{$lang['strfind']}\" />\n";
        $default_html .= "<input type=\"hidden\" name=\"action\" value=\"find\" /></p>\n";
        $default_html .= "</form>\n";

        // Default focus
        $this->setFocus('forms[0].term');

        return $default_html;
    }
}
