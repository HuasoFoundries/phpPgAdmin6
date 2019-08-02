<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class MaterializedviewpropertiesController extends BaseController
{
    use \PHPPgAdmin\Traits\ExportTrait;
    use \PHPPgAdmin\Traits\ViewsMatViewsPropertiesTrait;
    public $controller_title = 'strviews';
    public $subject          = 'matview';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $this->printHeader($this->headerTitle('', '', $_REQUEST[$this->subject]));
        $this->printBody();

        switch ($this->action) {
            case 'save_edit':
                if (isset($_POST['cancel'])) {
                    $this->doDefinition();
                } else {
                    $this->doSaveEdit();
                }

                break;
            case 'edit':
                $this->doEdit();

                break;
            case 'export':
                $this->doExport();

                break;
            case 'refresh':
                $this->doRefresh();

                break;
            case 'definition':
                $this->doDefinition();

                break;
            case 'properties':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doProperties();
                }

                break;
            case 'alter':
                if (isset($_POST['alter'])) {
                    $this->doAlter(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_alter':
                $this->doAlter(true);

                break;
            /*case 'drop':
            if (isset($_POST['drop'])) {
            $this->doDrop(false);
            } else {
            $this->doDefault();
            }

            break;
            case 'confirm_drop':
            $this->doDrop(true);

            break;*/
            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * Function to save after editing a matview.
     */
    public function doSaveEdit()
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->setView($_POST[$this->subject], $_POST['formDefinition'], $_POST['formComment'], true);
        if (0 == $status) {
            $this->doDefinition($this->lang['strviewupdated']);
        } else {
            $this->doEdit($this->lang['strviewupdatedbad']);
        }
    }

    /**
     * Function to refresh a matview.
     */
    public function doRefresh()
    {
        $data = $this->misc->getDatabaseAccessor();
        $sql  = 'REFRESH MATERIALIZED VIEW '.$_REQUEST[$this->subject];
        $this->prtrace($sql);
        $status = $data->execute($sql);

        if (0 == $status) {
            return $this->doDefault($this->lang['strviewupdated']);
        }

        return $this->doDefault($this->lang['strviewupdatedbad']);
    }

    /**
     * Function to allow editing of a matview.
     *
     * @param mixed $msg
     */
    public function doEdit($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail($this->subject);
        $this->printTitle($this->lang['stredit'], 'pg.matview.alter');
        $this->printMsg($msg);

        $viewdata = $data->getView($_REQUEST[$this->subject]);

        if ($viewdata->recordCount() > 0) {
            if (!isset($_POST['formDefinition'])) {
                $_POST['formDefinition'] = $viewdata->fields['vwdefinition'];
                $_POST['formComment']    = $viewdata->fields['relcomment'];
            }

            echo '<form action="'.\SUBFOLDER.'/src/views/materializedviewproperties" method="post">'.PHP_EOL;
            echo '<table style="width: 100%">'.PHP_EOL;
            echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strdefinition']}</th>".PHP_EOL;
            echo "\t\t<td class=\"data1\"><textarea style=\"width: 100%;\" rows=\"20\" cols=\"50\" name=\"formDefinition\">",
            htmlspecialchars($_POST['formDefinition']), "</textarea></td>\n\t</tr>".PHP_EOL;
            echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>".PHP_EOL;
            echo "\t\t<td class=\"data1\"><textarea rows=\"3\" cols=\"32\" name=\"formComment\">",
            htmlspecialchars($_POST['formComment']), "</textarea></td>\n\t</tr>".PHP_EOL;
            echo '</table>'.PHP_EOL;
            echo '<p><input type="hidden" name="action" value="save_edit" />'.PHP_EOL;
            echo '<input type="hidden" name="matview" value="', htmlspecialchars($_REQUEST[$this->subject]), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" value=\"{$this->lang['stralter']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            echo "<p>{$this->lang['strnodata']}</p>".PHP_EOL;
        }
    }

    /**
     * Displays a screen where they can alter a column in a matview.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'stage', 1);

        switch ($_REQUEST['stage']) {
            case 1:
                $this->printTrail('column');
                $this->printTitle($this->lang['stralter'], 'pg.column.alter');
                $this->printMsg($msg);

                echo '<form action="'.\SUBFOLDER.'/src/views/materializedviewproperties" method="post">'.PHP_EOL;

                // Output matview header
                echo '<table>'.PHP_EOL;
                echo "<tr><th class=\"data required\">{$this->lang['strname']}</th><th class=\"data required\">{$this->lang['strtype']}</th>";
                echo "<th class=\"data\">{$this->lang['strdefault']}</th><th class=\"data\">{$this->lang['strcomment']}</th></tr>";

                $column = $data->getTableAttributes($_REQUEST[$this->subject], $_REQUEST['column']);

                if (!isset($_REQUEST['default'])) {
                    $_REQUEST['field']   = $column->fields['attname'];
                    $_REQUEST['default'] = $_REQUEST['olddefault'] = $column->fields['adsrc'];
                    $_REQUEST['comment'] = $column->fields['comment'];
                }

                echo '<tr><td><input name="field" size="32" value="',
                htmlspecialchars($_REQUEST['field']), '" /></td>';

                echo '<td>', $this->misc->printVal($data->formatType($column->fields['type'], $column->fields['atttypmod'])), '</td>';
                echo '<td><input name="default" size="20" value="',
                htmlspecialchars($_REQUEST['default']), '" /></td>';
                echo '<td><input name="comment" size="32" value="',
                htmlspecialchars($_REQUEST['comment']), '" /></td>';

                echo '</table>'.PHP_EOL;
                echo '<p><input type="hidden" name="action" value="properties" />'.PHP_EOL;
                echo '<input type="hidden" name="stage" value="2" />'.PHP_EOL;
                echo $this->misc->form;
                echo '<input type="hidden" name="matview" value="', htmlspecialchars($_REQUEST[$this->subject]), '" />'.PHP_EOL;
                echo '<input type="hidden" name="column" value="', htmlspecialchars($_REQUEST['column']), '" />'.PHP_EOL;
                echo '<input type="hidden" name="olddefault" value="', htmlspecialchars($_REQUEST['olddefault']), '" />'.PHP_EOL;
                echo "<input type=\"submit\" value=\"{$this->lang['stralter']}\" />".PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
                echo '</form>'.PHP_EOL;

                break;
            case 2:
                // Check inputs
                if ('' == trim($_REQUEST['field'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doProperties($this->lang['strcolneedsname']);

                    return;
                }

                // Alter the matview column
                list($status, $sql) = $data->alterColumn(
                    $_REQUEST[$this->subject],
                    $_REQUEST['column'],
                    $_REQUEST['field'],
                    false,
                    false,
                    $_REQUEST['default'],
                    $_REQUEST['olddefault'],
                    '',
                    '',
                    '',
                    '',
                    $_REQUEST['comment']
                );
                if (0 == $status) {
                    $this->doDefault($this->lang['strcolumnaltered']);
                } else {
                    $_REQUEST['stage'] = 1;
                    $this->doProperties($this->lang['strcolumnalteredbad']);

                    return;
                }

                break;
            default:
                echo "<p>{$this->lang['strinvalidparam']}</p>".PHP_EOL;
        }
    }

    public function doAlter($confirm = false, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail($this->subject);
            $this->printTitle($this->lang['stralter'], 'pg.matview.alter');
            $this->printMsg($msg);

            // Fetch matview info
            $matview = $data->getView($_REQUEST[$this->subject]);

            if ($matview->recordCount() > 0) {
                $this->coalesceArr($_POST, 'name', $matview->fields['relname']);

                $this->coalesceArr($_POST, 'owner', $matview->fields['relowner']);

                $this->coalesceArr($_POST, 'newschema', $matview->fields['nspname']);

                $this->coalesceArr($_POST, 'comment', $matview->fields['relcomment']);

                echo '<form action="'.\SUBFOLDER.'/src/views/materializedviewproperties" method="post">'.PHP_EOL;
                echo '<table>'.PHP_EOL;
                echo "<tr><th class=\"data left required\">{$this->lang['strname']}</th>".PHP_EOL;
                echo '<td class="data1">';
                echo "<input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
                htmlspecialchars($_POST['name']), '" /></td></tr>'.PHP_EOL;

                if ($data->isSuperUser()) {
                    // Fetch all users
                    $users = $data->getUsers();

                    echo "<tr><th class=\"data left required\">{$this->lang['strowner']}</th>".PHP_EOL;
                    echo '<td class="data1"><select name="owner">';
                    while (!$users->EOF) {
                        $uname = $users->fields['usename'];
                        echo '<option value="', htmlspecialchars($uname), '"',
                        ($uname == $_POST['owner']) ? ' selected="selected"' : '', '>', htmlspecialchars($uname), '</option>'.PHP_EOL;
                        $users->moveNext();
                    }
                    echo '</select></td></tr>'.PHP_EOL;
                }

                if ($data->hasAlterTableSchema()) {
                    $schemas = $data->getSchemas();
                    echo "<tr><th class=\"data left required\">{$this->lang['strschema']}</th>".PHP_EOL;
                    echo '<td class="data1"><select name="newschema">';
                    while (!$schemas->EOF) {
                        $schema = $schemas->fields['nspname'];
                        echo '<option value="', htmlspecialchars($schema), '"',
                        ($schema == $_POST['newschema']) ? ' selected="selected"' : '', '>', htmlspecialchars($schema), '</option>'.PHP_EOL;
                        $schemas->moveNext();
                    }
                    echo '</select></td></tr>'.PHP_EOL;
                }

                echo "<tr><th class=\"data left\">{$this->lang['strcomment']}</th>".PHP_EOL;
                echo '<td class="data1">';
                echo '<textarea rows="3" cols="32" name="comment">';
                echo htmlspecialchars($_POST['comment']), '</textarea></td></tr>'.PHP_EOL;
                echo '</table>'.PHP_EOL;
                echo '<input type="hidden" name="action" value="alter" />'.PHP_EOL;
                echo '<input type="hidden" name="matview" value="', htmlspecialchars($_REQUEST[$this->subject]), '" />'.PHP_EOL;
                echo $this->misc->form;
                echo "<p><input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />".PHP_EOL;
                echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
                echo '</form>'.PHP_EOL;
            } else {
                echo "<p>{$this->lang['strnodata']}</p>".PHP_EOL;
            }
        } else {
            // For databases that don't allow owner change
            $this->coalesceArr($_POST, 'owner', '');

            $this->coalesceArr($_POST, 'newschema', null);

            $status = $data->alterMatView($_POST[$this->subject], $_POST['name'], $_POST['owner'], $_POST['newschema'], $_POST['comment']);
            if (0 == $status) {
                // If matview has been renamed, need to change to the new name and
                // reload the browser frame.
                if ($_POST[$this->subject] != $_POST['name']) {
                    // Jump them to the new matview name
                    $_REQUEST[$this->subject] = $_POST['name'];
                    // Force a browser reload
                    $this->misc->setReloadBrowser(true);
                }
                // If schema has changed, need to change to the new schema and reload the browser
                if (!empty($_POST['newschema']) && ($_POST['newschema'] != $data->_schema)) {
                    // Jump them to the new sequence schema
                    $this->misc->setCurrentSchema($_POST['newschema']);
                    $this->misc->setReloadBrowser(true);
                }
                $this->doDefault($this->lang['strviewaltered']);
            } else {
                $this->doAlter(true, $this->lang['strviewalteredbad']);
            }
        }
    }
}
