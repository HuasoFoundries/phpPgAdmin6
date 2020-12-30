<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\XHtml;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Class to render tables. Formerly part of Misc.php.
 */
class HTMLTableController extends HTMLController
{
    public $controller_name = 'HTMLTableController';

    protected $ma = [];

    protected $plugin_functions_parameters = [];

    protected $has_ma = false;

    protected $tabledata;

    protected $columns;

    protected $actions;

    protected $place;

    protected $nodata;

    protected $pre_fn;

    /**
     * Display a table of data.
     *
     * @param \ADORecordSet|\PHPPgAdmin\ArrayRecordSet $tabledata a set of data to be formatted, as returned by $data->getDatabases() etc
     * @param array                                    $columns   An associative array of columns to be displayed:
     *                                                            $columns = array(
     *                                                            column_id => array(
     *                                                            'title' => Column heading,
     *                                                            'class' => The class to apply on the column cells,
     *                                                            'field' => Field name for $tabledata->fields[...],
     *                                                            'help'  => Help page for this column,
     *                                                            ), ...
     *                                                            );
     * @param array                                    $actions   Actions that can be performed on each object:
     *                                                            $actions = array(
     *                                                            * multi action support
     *                                                            * parameters are serialized for each entries and given in $_REQUEST['ma']
     *                                                            'multiactions' => array(
     *                                                            'keycols' => Associative array of (URL variable => field name), // fields included in the form
     *                                                            'url' => URL submission,
     *                                                            'default' => Default selected action in the form. If null, an empty action is added & selected
     *                                                            ),
     *                                                            * actions *
     *                                                            action_id => array(
     *                                                            'title' => Action heading,
     *                                                            'url'   => Static part of URL.  Often we rely
     *                                                            relative urls, usually the page itself (not '' !), or just a query string,
     *                                                            'vars'  => Associative array of (URL variable => field name),
     *                                                            'multiaction' => Name of the action to execute.
     *                                                            Add this action to the multi action form
     *                                                            ), ...
     *                                                            );
     * @param string                                   $place     Place where the $actions are displayed. Like 'display-browse',  where 'display'
     *                                                            is the entrypoint (/src/views/display) and 'browse' is the action used inside its controller (in this case, doBrowse).
     * @param string                                   $nodata    (optional) Message to display if data set is empty
     * @param callable                                 $pre_fn    (optional) callback closure for each row. It will be passed two params: $rowdata and $actions,
     *                                                            it may be used to derive new fields or modify actions.
     *                                                            It can return an array of actions specific to the row,  or if nothing is returned then the standard actions are used.
     *                                                            (see TblpropertiesController and ConstraintsController for examples)
     *                                                            The function must not must not store urls because     they are relative and won't work out of context.
     */
    public function initialize(&$tabledata, &$columns, &$actions, $place, $nodata = '', $pre_fn = null): void
    {
        // Action buttons hook's place
        $this->plugin_functions_parameters = [
            'actionbuttons' => &$actions,
            'place' => $place,
        ];

        if ($this->has_ma = isset($actions['multiactions'])) {
            $this->ma = $actions['multiactions'];
        }
        unset($actions['multiactions']);

        $this->tabledata = $tabledata;
        $this->columns = $columns;
        $this->actions = $actions;
        $this->place = $place;
        $this->nodata = $nodata;
        $this->pre_fn = $pre_fn;
    }

    public function printTable($turn_into_datatable = true, $with_body = true)
    {
        if (0 >= $this->tabledata->recordCount()) {
            return "<p>{$this->nodata}</p>" . \PHP_EOL;
        }

        $tablehtml = '';
        // Remove the 'comment' column if they have been disabled
        if (!$this->conf['show_comments']) {
            unset($this->columns['comment']);
        }

        if (isset($this->columns['comment'])) {
            // Uncomment this for clipped comments.
            // TODO: This should be a user option.
            //$columns['comment']['params']['clip'] = true;
        }

        [$matop_html, $mabottom_html] = $this->_getMaHtml();

        $tablehtml .= $matop_html;

        $tablehtml .= '<table width="auto" class="' . ($turn_into_datatable ? 'will_be_datatable ' : ' ') . $this->place . '">' . \PHP_EOL;

        $tablehtml .= $this->getThead();

        $tablehtml .= $with_body ? $this->getTbody() : '';

        $tablehtml .= $this->getTfooter();

        $tablehtml .= '</table>' . \PHP_EOL;

        // Multi action table footer w/ options & [un]check'em all
        $tablehtml .= $mabottom_html;

        return $tablehtml;
    }

    public function getThead()
    {
        $columns = $this->columns;
        $actions = $this->actions;

        $thead_html = '<thead><tr>' . \PHP_EOL;

        // Display column headings
        if ($this->has_ma) {
            $thead_html .= '<th></th>';
        }

        foreach ($columns as $column_id => $column) {
            // Handle cases where no class has been passed

            $class = (isset($column['class']) && '' !== $column['class']) ? $column['class'] : '';

            switch ($column_id) {
                case 'actions':
                    if (0 < \count($actions)) {
                        $thead_html .= '<th class="data" >' . $column['title'] . '</th>' . \PHP_EOL;
                    }

                    break;

                default:
                    $thead_html .= '<th class="data' . $class . '">';

                    if (isset($column['help'])) {
                        $thead_html .= $this->view->printHelp($column['title'], $column['help'], false);
                    } else {
                        $thead_html .= $column['title'];
                    }

                    $thead_html .= '</th>' . \PHP_EOL;

                    break;
            }
        }
        $thead_html .= '</tr></thead>' . \PHP_EOL;

        return $thead_html;
    }

    public function getTfooter()
    {
        $columns = $this->columns;
        $actions = $this->actions;

        $tfoot_html = '<tfoot><tr>' . \PHP_EOL;

        // Display column headings
        if ($this->has_ma) {
            $tfoot_html .= '<td></td>';
        }

        foreach ($columns as $column_id => $column) {
            // Handle cases where no class has been passed

            $class = (isset($column['class']) && '' !== $column['class']) ? $column['class'] : '';

            if ('actions' !== $column_id || 0 < \count($actions)) {
                $tfoot_html .= "<td class=\"data{$class}\"></td>" . \PHP_EOL;
            }
        }
        $tfoot_html .= '</tr></tfoot>' . \PHP_EOL;

        return $tfoot_html;
    }

    private function _getMaHtml()
    {
        $matop_html = '';
        $ma_bottomhtml = '';
        $lang = $this->lang;

        if ($this->has_ma) {
            $matop_html .= '<script src="' . \containerInstance()->subFolder . '/assets/js/multiactionform.js" type="text/javascript"></script>' . \PHP_EOL;
            $matop_html .= \sprintf('<form id="multi_form" action="%s" method="post" enctype="multipart/form-data">%s', $this->ma['url'], \PHP_EOL);
            $this->coalesceArr($this->ma, 'vars', []);

            foreach ($this->ma['vars'] as $k => $v) {
                $matop_html .= \sprintf('<input type="hidden" name="%s" value="%s" />', $k, $v);
            }

            // if default is not set or doesn't exist, set it to null
            if (!isset($this->ma['default']) || !isset($this->actions[$this->ma['default']])) {
                $this->ma['default'] = null;
            }

            $ma_bottomhtml .= '<br />' . \PHP_EOL;
            $ma_bottomhtml .= '<table>' . \PHP_EOL;
            $ma_bottomhtml .= '<tr>' . \PHP_EOL;
            $ma_bottomhtml .= "<th class=\"data\" style=\"text-align: left\" colspan=\"3\">{$lang['stractionsonmultiplelines']}</th>" . \PHP_EOL;
            $ma_bottomhtml .= '</tr>' . \PHP_EOL;
            $ma_bottomhtml .= '<tr class="row1">' . \PHP_EOL;
            $ma_bottomhtml .= '<td>';
            $ma_bottomhtml .= "<a href=\"#\" onclick=\"javascript:checkAll(true);\">{$lang['strselectall']}</a> / ";
            $ma_bottomhtml .= "<a href=\"#\" onclick=\"javascript:checkAll(false);\">{$lang['strunselectall']}</a></td>" . \PHP_EOL;
            $ma_bottomhtml .= '<td>&nbsp;--->&nbsp;</td>' . \PHP_EOL;
            $ma_bottomhtml .= '<td>' . \PHP_EOL;
            $ma_bottomhtml .= "\t<select name=\"action\">" . \PHP_EOL;

            if (null === $this->ma['default']) {
                $ma_bottomhtml .= "\t\t<option value=\"\">--</option>" . \PHP_EOL;
            }

            foreach ($this->actions as $k => $a) {
                if (isset($a['multiaction'])) {
                    $selected = $this->ma['default'] === $k ? ' selected="selected" ' : '';
                    $ma_bottomhtml .= "\t\t";
                    $ma_bottomhtml .= '<option value="' . $a['multiaction'] . '" ' . $selected . ' rel="' . $k . '">' . $a['content'] . '</option>';
                    $ma_bottomhtml .= \PHP_EOL;
                }
            }

            $ma_bottomhtml .= "\t</select>" . \PHP_EOL;
            $ma_bottomhtml .= "<input type=\"submit\" value=\"{$lang['strexecute']}\" />" . \PHP_EOL;
            $ma_bottomhtml .= $this->getForm();
            $ma_bottomhtml .= '</td>' . \PHP_EOL;
            $ma_bottomhtml .= '</tr>' . \PHP_EOL;
            $ma_bottomhtml .= '</table>' . \PHP_EOL;
            $ma_bottomhtml .= '</form>';
        }

        return [$matop_html, $ma_bottomhtml];
    }

    private function getTbody()
    {
        $columns = $this->columns;
        $actions = $this->actions;
        $tabledata = $this->tabledata;
        $pre_fn = $this->pre_fn;

        // Display table rows
        $i = 0;
        $tbody_html = '<tbody>';

        while (!$tabledata->EOF) {
            $id = ($i % 2) + 1;

            unset($alt_actions);

            if (null !== $pre_fn) {
                $alt_actions = $pre_fn($tabledata, $actions);
            }

            if (!isset($alt_actions)) {
                $alt_actions = &$actions;
            }

            $tbody_html .= \sprintf('<tr class="data%s">', $id) . \PHP_EOL;

            if ($this->has_ma) {
                $a = [];

                foreach ($this->ma['keycols'] as $k => $v) {
                    $a[$k] = $tabledata->fields[$v];
                }
                $tbody_html .= \sprintf('<td><input type="checkbox" name="ma[]" value="%s"/></td>', \htmlentities(\serialize($a), \ENT_COMPAT, 'UTF-8')) . \PHP_EOL;
            }

            foreach ($columns as $column_id => $column) {
                // Apply default values for missing parameters
                if (isset($column['url']) && !isset($column['vars'])) {
                    $column['vars'] = [];
                }
                $class = (isset($column['class']) && '' !== $column['class']) ? $column['class'] : '';

                switch ($column_id) {
                    case 'actions':
                        $tbody_html .= "<td class=\"opbutton{$id} {$class}\">";

                        foreach ($alt_actions as $action) {
                            if (isset($action['disable']) && true === $action['disable']) {
                                continue;
                            }
                            $action['fields'] = $tabledata->fields;
                            $tbody_html .= $this->printLink($action, false, __METHOD__);
                        }
                        $tbody_html .= '</td>' . \PHP_EOL;

                        break;
                    case 'comment':
                        $tbody_html .= "<td class='comment_cell'>";
                        $tbody_html .= \htmlentities(Decorator::get_sanitized_value($column['field'], $tabledata->fields));
                        $tbody_html .= '</td>';

                        break;

                    default:
                        $tbody_html .= '<td class="' . $class . '">';
                        $val = Decorator::get_sanitized_value($column['field'], $tabledata->fields);

                        if (null !== $val) {
                            if (isset($column['url'])) {
                                $tbody_html .= "<a href=\"{$column['url']}";
                                $tbody_html .= $this->printUrlVars($column['vars'], $tabledata->fields, false);
                                $tbody_html .= '">';
                            }
                            $type = $column['type'] ?? null;
                            $params = $column['params'] ?? [];
                            $tbody_html .= $this->misc->printVal($val, $type, $params);

                            if (isset($column['url'])) {
                                $tbody_html .= '</a>';
                            }
                        }

                        $tbody_html .= '</td>' . \PHP_EOL;

                        break;
                }
            }
            $tbody_html .= '</tr>' . \PHP_EOL;

            $tabledata->moveNext();
            ++$i;
        }

        $tbody_html .= '</tbody>';

        return $tbody_html;
    }

    private function getForm()
    {
        if (!$this->form) {
            $this->form = $this->view->setForm();
        }

        return $this->form;
    }

    private function printUrlVars(&$vars, &$fields, bool $do_print = true)
    {
        $url_vars_html = '';

        foreach ($vars as $var => $varfield) {
            $url_vars_html .= "{$var}=" . \urlencode($fields[$varfield]) . '&amp;';
        }

        if ($do_print) {
            echo $url_vars_html;
        } else {
            return $url_vars_html;
        }
    }
}
