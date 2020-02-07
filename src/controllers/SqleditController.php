<?php

/**
 * PHPPgAdmin v6.0.0-RC8.
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class SqleditController extends BaseController
{
    use \PHPPgAdmin\Traits\ServersTrait;

    public $query = '';
    public $subject = '';
    public $start_time;
    public $duration;

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        switch ($this->action) {
            case 'find':
                $title = $this->lang['strfind'];
                $body_text = $this->doFind();

                break;
            case 'sql':
            default:
                $title = $this->lang['strsql'];
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
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_SESSION['sqlquery'])) {
            $_SESSION['sqlquery'] = '';
        }

        $this->coalesceArr($_REQUEST, 'search_path', implode(',', $data->getSearchPath()));
        $search_path = htmlspecialchars($_REQUEST['search_path']);
        $sqlquery = htmlspecialchars($_SESSION['sqlquery']);

        $default_html = $this->printTabs($this->misc->getNavTabs('popup'), 'sql', false);

        $default_html .= '<form action="'.\SUBFOLDER.'/src/views/sql" method="post" enctype="multipart/form-data" class="sqlform" id="sqlform" target="detail">';
        $default_html .= PHP_EOL;
        $default_html .= $this->printConnection('sql', false);

        $default_html .= PHP_EOL;

        $default_html .= ' <div class="searchpath">';
        $default_html .= '<label>';
        $default_html .= $this->misc->printHelp($this->lang['strsearchpath'], 'pg.schema.search_path', false);

        $default_html .= ': <input type="text" name="search_path" id="search_path" size="45" value="'.$search_path.'" />';
        $default_html .= '</label>'.PHP_EOL;

        $default_html .= '</div>'.PHP_EOL;

        $default_html .= '<div id="queryedition" style="padding:1%;width:98%;float:left;">';
        $default_html .= PHP_EOL;
        $default_html .= '<textarea style="width:98%;" rows="10" cols="50" name="query" id="query" resizable="true">'.$sqlquery.'</textarea>';
        $default_html .= PHP_EOL;
        $default_html .= '</div>'.PHP_EOL;

        $default_html .= '<div class="sqledit_bottom_inputs" >';

        if (ini_get('file_uploads')) {
            // Don't show upload option if max size of uploads is zero
            $max_size = $this->misc->inisizeToBytes(ini_get('upload_max_filesize'));
            if (is_float($max_size) && $max_size > 0) {
                $default_html .= '<p class="upload_sql_script">';
                $default_html .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.$max_size.'" />';
                $default_html .= PHP_EOL;
                $default_html .= '<label for="script">'.$this->lang['struploadscript'].'</label>';
                $default_html .= '&nbsp;&nbsp; <input class="btn btn-small"  id="script" name="script" type="file" /></p>';
                $default_html .= '</p>'.PHP_EOL;
            }
        }

        // Check that file uploads are enabled
        $checked = (isset($_REQUEST['paginate']) ? ' checked="checked"' : '');

        $default_html .= '<p><input type="submit" class="btn btn-small" name="execute" accesskey="r" value="'.$this->lang['strexecute'].'" />';
        $default_html .= PHP_EOL;

        $default_html .= '<input type="reset" class="btn btn-small"  accesskey="q" value="'.$this->lang['strreset'].'" /></p>';
        $default_html .= PHP_EOL;

        $default_html .= '<p>';
        $default_html .= '<label for="paginate">';
        $default_html .= '<input type="checkbox" id="paginate" name="paginate"'.$checked.' />&nbsp;'.$this->lang['strpaginate'].'&nbsp;';
        $default_html .= '</label>'.PHP_EOL;
        $default_html .= '</p>'.PHP_EOL;

        $default_html .= '</div>'.PHP_EOL;
        $default_html .= '</form>';
        $default_html .= PHP_EOL;

        // Default focus
        //$this->setFocus('forms[0].query');
        return $default_html;
    }

    private function _getFilters()
    {
        $filters = [
            'SCHEMA'     => ['langkey' => 'strschemas', 'selected' => ''],
            'TABLE'      => ['langkey' => 'strtables', 'selected' => ''],
            'VIEW'       => ['langkey' => 'strviews', 'selected' => ''],
            'SEQUENCE'   => ['langkey' => 'strsequences', 'selected' => ''],
            'COLUMN'     => ['langkey' => 'strcolumns', 'selected' => ''],
            'RULE'       => ['langkey' => 'strrules', 'selected' => ''],
            'INDEX'      => ['langkey' => 'strindexes', 'selected' => ''],
            'TRIGGER'    => ['langkey' => 'strtriggers', 'selected' => ''],
            'CONSTRAINT' => ['langkey' => 'strconstraints', 'selected' => ''],
            'FUNCTION'   => ['langkey' => 'strfunctions', 'selected' => ''],
            'DOMAIN'     => ['langkey' => 'strdomains', 'selected' => ''],
        ];

        return $filters;
    }

    private function _getAdvancedFilters()
    {
        $advanced_filters = [
            'AGGREGATE'  => ['langkey' => 'straggregates', 'selected' => ''],
            'TYPE'       => ['langkey' => 'strtypes', 'selected' => ''],
            'OPERATOR'   => ['langkey' => 'stroperators', 'selected' => ''],
            'OPCLASS'    => ['langkey' => 'stropclasses', 'selected' => ''],
            'CONVERSION' => ['langkey' => 'strconversions', 'selected' => ''],
            'LANGUAGE'   => ['langkey' => 'strlanguages', 'selected' => ''],
        ];

        return $advanced_filters;
    }

    /**
     * Searches for a named database object.
     */
    public function doFind()
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'term', '');

        $this->coalesceArr($_REQUEST, 'filter', '');

        $default_html = $this->printTabs($this->misc->getNavTabs('popup'), 'find', false);

        $default_html .= '<form action="database" method="post" target="detail">'.PHP_EOL;
        $default_html .= $this->printConnection('find', false);
        $default_html .= '<p><input class="focusme" name="term" id="term" value="'.htmlspecialchars($_REQUEST['term'])."\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" />".PHP_EOL;

        $filters = $this->_getFilters();
        $advanced_filters = $this->_getAdvancedFilters();

        if (isset($filters[$_REQUEST['filter']])) {
            $filters[$_REQUEST['filter']]['selected'] = ' selected="selected" ';
        }

        if (isset($advanced_filters[$_REQUEST['filter']])) {
            $advanced_filters[$_REQUEST['filter']]['selected'] = ' selected="selected" ';
        }

        // Output list of filters.  This is complex due to all the 'has' and 'conf' feature possibilities
        $default_html .= "<select id='filter' name=\"filter\">".PHP_EOL;
        $default_html .= sprintf('%s<option value=""'.('' == $_REQUEST['filter'] ? ' selected="selected" ' : '').">{$this->lang['strallobjects']}</option>".PHP_EOL, "\t");
        foreach ($filters as $type => $props) {
            $default_html .= sprintf('%s<option value="%s"  %s >%s</option>'.PHP_EOL, "\t", $type, $props['selected'], $this->lang[$props['langkey']]);
        }

        if ($this->conf['show_advanced']) {
            foreach ($advanced_filters as $type => $props) {
                $default_html .= sprintf('%s<option value="%s"  %s >%s</option>'.PHP_EOL, "\t", $type, $props['selected'], $this->lang[$props['langkey']]);
            }
        }
        $default_html .= '</select>'.PHP_EOL;

        $default_html .= "<input type=\"submit\" value=\"{$this->lang['strfind']}\" />".PHP_EOL;
        $default_html .= '<input type="hidden" name="action" value="find" /></p>'.PHP_EOL;
        $default_html .= '</form>'.PHP_EOL;

        // Default focus
        $this->setFocus('forms[0].term');

        return $default_html;
    }
}
