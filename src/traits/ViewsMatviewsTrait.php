<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
 */

namespace PHPPgAdmin\Traits;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Common trait for dealing with views or materialized views.
 */
trait ViewsMatviewsTrait
{
    public $href = '';
    public $misc;
    public $view_name;

    public function doSubTree()
    {
        $tabs    = $this->misc->getNavTabs($this->keystring);
        $items   = $this->adjustTabsForTree($tabs);
        $reqvars = $this->misc->getRequestVars($this->keystring);

        $attrs = [
            'text'   => Decorator::field('title'),
            'icon'   => Decorator::field('icon'),
            'action' => Decorator::actionurl(Decorator::field('url'), $reqvars, Decorator::field('urlvars'), [$this->keystring => $_REQUEST[$this->keystring]]),
            'branch' => Decorator::ifempty(
                Decorator::field('branch'),
                '',
                Decorator::url(
                    Decorator::field('url'),
                    Decorator::field('urlvars'),
                    $reqvars,
                    [
                        'action'         => 'tree',
                        $this->keystring => $_REQUEST[$this->keystring],
                    ]
                )
            ),
        ];

        return $this->printTree($items, $attrs, $this->keystring);
    }

    /**
     * Ask for select parameters and perform select.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doSelectRows($confirm, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail($this->keystring);
            $this->printTabs($this->keystring, 'select');
            $this->printMsg($msg);

            $attrs = $data->getTableAttributes($_REQUEST[$this->keystring]);

            echo '<form action="'.\SUBFOLDER.'/src/views/'.$this->script.'" method="post" id="selectform">';
            echo "\n";

            if ($attrs->recordCount() > 0) {
                // JavaScript for select all feature
                echo "<script type=\"text/javascript\">\n";
                echo "//<![CDATA[\n";
                echo "  function selectAll() {\n";
                echo "      for (var i=0; i<document.getElementById('selectform').elements.length; i++) {\n";
                echo "          var e = document.getElementById('selectform').elements[i];\n";
                echo "          if (e.name.indexOf('show') == 0) { \n ";
                echo "              e.checked = document.getElementById('selectform').selectall.checked;\n";
                echo "          }\n";
                echo "      }\n";
                echo "  }\n";
                echo "//]]>\n";
                echo "</script>\n";

                echo "<table>\n";

                // Output table header
                echo "<tr><th class=\"data\">{$this->lang['strshow']}</th><th class=\"data\">{$this->lang['strcolumn']}</th>";
                echo "<th class=\"data\">{$this->lang['strtype']}</th><th class=\"data\">{$this->lang['stroperator']}</th>";
                echo "<th class=\"data\">{$this->lang['strvalue']}</th></tr>";

                $i = 0;
                while (!$attrs->EOF) {
                    $attrs->fields['attnotnull'] = $data->phpBool($attrs->fields['attnotnull']);
                    // Set up default value if there isn't one already
                    if (!isset($_REQUEST['values'][$attrs->fields['attname']])) {
                        $_REQUEST['values'][$attrs->fields['attname']] = null;
                    }

                    if (!isset($_REQUEST['ops'][$attrs->fields['attname']])) {
                        $_REQUEST['ops'][$attrs->fields['attname']] = null;
                    }

                    // Continue drawing row
                    $id = (0 == ($i % 2) ? '1' : '2');
                    echo "<tr class=\"data{$id}\">\n";
                    echo '<td style="white-space:nowrap;">';
                    echo '<input type="checkbox" name="show[', htmlspecialchars($attrs->fields['attname']), ']"',
                    isset($_REQUEST['show'][$attrs->fields['attname']]) ? ' checked="checked"' : '', ' /></td>';
                    echo '<td style="white-space:nowrap;">', $this->misc->printVal($attrs->fields['attname']), '</td>';
                    echo '<td style="white-space:nowrap;">', $this->misc->printVal($data->formatType($attrs->fields['type'], $attrs->fields['atttypmod'])), '</td>';
                    echo '<td style="white-space:nowrap;">';
                    echo "<select name=\"ops[{$attrs->fields['attname']}]\">\n";
                    foreach (array_keys($data->selectOps) as $v) {
                        echo '<option value="', htmlspecialchars($v), '"', ($_REQUEST['ops'][$attrs->fields['attname']] == $v) ? ' selected="selected"' : '',
                        '>', htmlspecialchars($v), "</option>\n";
                    }
                    echo "</select></td>\n";
                    echo '<td style="white-space:nowrap;">', $data->printField(
                        "values[{$attrs->fields['attname']}]",
                        $_REQUEST['values'][$attrs->fields['attname']],
                        $attrs->fields['type']
                    ), '</td>';
                    echo "</tr>\n";
                    ++$i;
                    $attrs->moveNext();
                }
                // Select all checkbox
                echo "<tr><td colspan=\"5\"><input type=\"checkbox\" id=\"selectall\" name=\"selectall\" accesskey=\"a\" onclick=\"javascript:selectAll()\" /><label for=\"selectall\">{$this->lang['strselectallfields']}</label></td></tr>";
                echo "</table>\n";
            } else {
                echo "<p>{$this->lang['strinvalidparam']}</p>\n";
            }

            echo "<p><input type=\"hidden\" name=\"action\" value=\"selectrows\" />\n";
            echo '<input type="hidden" name="view" value="', htmlspecialchars($_REQUEST[$this->keystring]), "\" />\n";
            echo "<input type=\"hidden\" name=\"subject\" value=\"view\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"select\" accesskey=\"r\" value=\"{$this->lang['strselect']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";

            return;
        }
        $this->coalesceArr($_POST, 'show', []);

        $this->coalesceArr($_POST, 'values', []);

        $this->coalesceArr($_POST, 'nulls', []);

        // Verify that they haven't supplied a value for unary operators
        foreach ($_POST['ops'] as $k => $v) {
            if ('p' == $data->selectOps[$v] && $_POST['values'][$k] != '') {
                $this->doSelectRows(true, $this->lang['strselectunary']);

                return;
            }
        }

        if (0 == sizeof($_POST['show'])) {
            return $this->doSelectRows(true, $this->lang['strselectneedscol']);
        }
        // Generate query SQL
        $query = $data->getSelectSQL($_REQUEST[$this->keystring], array_keys($_POST['show']), $_POST['values'], $_POST['ops']);

        $_REQUEST['query']  = $query;
        $_REQUEST['return'] = 'schema';

        $this->setNoOutput(true);

        $display_controller = new DisplayController($this->getContainer());

        return $display_controller->render();
    }

    /**
     * Prints the form wizard to create view or materialized view.
     */
    public function printWizardCreateForm()
    {
        $data = $this->misc->getDatabaseAccessor();

        $tables = $data->getTables(true);

        echo '<form action="'.\SUBFOLDER."/src/views/{$this->script}\" method=\"post\">\n";
        echo "<table>\n";
        echo "<tr><th class=\"data\">{$this->lang['strtables']}</th></tr>";
        echo "<tr>\n<td class=\"data1\">\n";

        $arrTables = [];
        while (!$tables->EOF) {
            $arrTmp                      = [];
            $arrTmp['schemaname']        = $tables->fields['nspname'];
            $arrTmp['tablename']         = $tables->fields['relname'];
            $schema_and_name             = $tables->fields['nspname'].'.'.$tables->fields['relname'];
            $arrTables[$schema_and_name] = serialize($arrTmp);
            $tables->moveNext();
        }
        echo \PHPPgAdmin\XHtml\HTMLController::printCombo($arrTables, 'formTables[]', false, '', true);

        echo "</td>\n</tr>\n";
        echo "</table>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"set_params_create\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strnext']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
    }

    /**
     * Actually creates the new wizard view in the database.
     *
     * @param bool $is_materialized true if it's a materialized view, false by default
     *
     * @return mixed either a sucess message, a redirection, an error message and who knows
     */
    public function doSaveCreateWiz($is_materialized = false)
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name and fields they want to select

        if (!strlen($_POST['formView'])) {
            return $this->doSetParamsCreate($this->lang['strviewneedsname']);
        }
        if (!isset($_POST['formFields']) || !count($_POST['formFields'])) {
            return $this->doSetParamsCreate($this->lang['strviewneedsfields']);
        }
        $selFields = '';

        if (!empty($_POST['dblFldMeth'])) {
            $tmpHsh = [];
        }

        foreach ($_POST['formFields'] as $curField) {
            $arrTmp = unserialize($curField);
            $data->fieldArrayClean($arrTmp);
            $field_arr = [$arrTmp['schemaname'], $arrTmp['tablename'], $arrTmp['fieldname']];

            $field_element = '"'.implode('"."', $field_arr).'"';
            if (empty($_POST['dblFldMeth'])) {
                // no doublon control
                $selFields .= $field_element.', ';
            // doublon control
            } elseif (empty($tmpHsh[$arrTmp['fieldname']])) {
                // field does not exist
                $selFields .= $field_element.', ';
                $tmpHsh[$arrTmp['fieldname']] = 1;
            } elseif ('rename' == $_POST['dblFldMeth']) {
                // field exist and must be renamed
                ++$tmpHsh[$arrTmp['fieldname']];
                $selFields .= $field_element.'  AS  "'.implode('_', $field_arr).'_'.$tmpHsh[$arrTmp['fieldname']].'", ';
            } //  field already exist, just ignore this one
        }

        $selFields = substr($selFields, 0, -2);
        unset($arrTmp, $tmpHsh);
        $linkFields = '';
        $count      = 0;

        // If we have links, out put the JOIN ... ON statements
        if (is_array($_POST['formLink'])) {
            // Filter out invalid/blank entries for our links
            $arrLinks = [];
            foreach ($_POST['formLink'] as $curLink) {
                if (strlen($curLink['leftlink']) && strlen($curLink['rightlink']) && strlen($curLink['operator'])) {
                    $arrLinks[] = $curLink;
                }
            }
            // We must perform some magic to make sure that we have a valid join order
            $count       = sizeof($arrLinks);
            $arrJoined   = [];
            $arrUsedTbls = [];
        }
        // If we have at least one join condition, output it

        $j = 0;
        $this->prtrace('arrLinks ', $arrLinks);
        while ($j < $count) {
            foreach ($arrLinks as $curLink) {
                $arrLeftLink  = unserialize($curLink['leftlink']);
                $arrRightLink = unserialize($curLink['rightlink']);
                $data->fieldArrayClean($arrLeftLink);
                $data->fieldArrayClean($arrRightLink);

                $tbl1 = "\"{$arrLeftLink['schemaname']}\".\"{$arrLeftLink['tablename']}\"";
                $tbl2 = "\"{$arrRightLink['schemaname']}\".\"{$arrRightLink['tablename']}\"";

                if (!((!in_array($curLink, $arrJoined, true) && in_array($tbl1, $arrUsedTbls, true)) || !count($arrJoined))) {
                    continue;
                }
                // Make sure for multi-column foreign keys that we use a table alias tables joined to more than once
                // This can (and should be) more optimized for multi-column foreign keys
                $adj_tbl2 = in_array($tbl2, $arrUsedTbls, true) ? "${tbl2} AS alias_ppa_".time() : $tbl2;

                $clause1 = "{$curLink['operator']} ${adj_tbl2} ON ({$tbl1}.\"{$arrLeftLink['fieldname']}\" = {$tbl2}.\"{$arrRightLink['fieldname']}\") ";
                $clause2 = "${tbl1} {$curLink['operator']} ${adj_tbl2} ON ({$tbl1}.\"{$arrLeftLink['fieldname']}\" = {$tbl2}.\"{$arrRightLink['fieldname']}\") ";

                $linkFields .= strlen($linkFields) ? $clause1 : $clause2;

                $arrJoined[] = $curLink;
                if (!in_array($tbl1, $arrUsedTbls, true)) {
                    $arrUsedTbls[] = $tbl1;
                }

                if (!in_array($tbl2, $arrUsedTbls, true)) {
                    $arrUsedTbls[] = $tbl2;
                }
            }
            ++$j;
        }

        //if linkFields has no length then either _POST['formLink'] was not set, or there were no join conditions
        //just select from all seleted tables - a cartesian join do a
        if (!strlen($linkFields)) {
            foreach ($_POST['formTables'] as $curTable) {
                $arrTmp = unserialize($curTable);
                $data->fieldArrayClean($arrTmp);
                $linkFields .= (strlen($linkFields) ? ', ' : ' ')."\"{$arrTmp['schemaname']}\".\"{$arrTmp['tablename']}\"";
            }
        }

        $addConditions = '';
        if (is_array($_POST['formCondition'])) {
            foreach ($_POST['formCondition'] as $curCondition) {
                if (strlen($curCondition['field']) && strlen($curCondition['txt'])) {
                    $arrTmp = unserialize($curCondition['field']);
                    $data->fieldArrayClean($arrTmp);
                    $condition = " \"{$arrTmp['schemaname']}\".\"{$arrTmp['tablename']}\".\"{$arrTmp['fieldname']}\" {$curCondition['operator']} '{$curCondition['txt']}' ";
                    $addConditions .= (strlen($addConditions) ? ' AND ' : ' ').$condition;
                }
            }
        }

        $viewQuery = "SELECT ${selFields} FROM ${linkFields} ";

        //add where from additional conditions
        if (strlen($addConditions)) {
            $viewQuery .= ' WHERE '.$addConditions;
        }

        try {
            $status = $data->createView($_POST['formView'], $viewQuery, false, $_POST['formComment'], $is_materialized);
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);

                return $this->doDefault($this->lang['strviewcreated']);
            }

            return $this->doSetParamsCreate($this->lang['strviewcreatedbad']);
        } catch (\PHPPgAdmin\ADOdbException $e) {
            return $this->halt($e->getMessage());
        }
    }
}
