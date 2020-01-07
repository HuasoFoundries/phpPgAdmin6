<?php

/**
 * PHPPgAdmin v6.0.0-RC2
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class ViewpropertiesController extends BaseController
{
    use \PHPPgAdmin\Traits\ExportTrait;
    use \PHPPgAdmin\Traits\ViewsMatViewsPropertiesTrait;

    public $controller_title = 'strviews';
    public $subject          = 'view';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }
        $footer_template = 'footer.twig';
        $header_template = 'header.twig';

        ob_start();

        $this->printFooter(true, $footer_template);
        switch ($this->action) {
            case 'save_edit':
                if (isset($_POST['cancel'])) {
                    $this->doDefinition();
                } else {
                    $this->doSaveEdit();
                }

                break;
            case 'edit':
                $footer_template = 'header_sqledit.twig';
                $footer_template = 'footer_sqledit.twig';
                $this->doEdit();

                break;
            case 'export':
                $this->doExport();

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
        $output = ob_get_clean();

        $this->printHeader($this->headerTitle('', '', $_REQUEST[$this->subject]), null, true, $header_template);
        $this->printBody();

        echo $output;
        $this->printFooter(true, $footer_template);
    }

    /**
     * Function to save after editing a view.
     */
    public function doSaveEdit()
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->setView($_POST[$this->subject], $_POST['formDefinition'], $_POST['formComment']);
        if (0 == $status) {
            $this->doDefinition($this->lang['strviewupdated']);
        } else {
            $this->doEdit($this->lang['strviewupdatedbad']);
        }
    }

    /**
     * Function to allow editing of a view.
     *
     * @param mixed $msg
     */
    public function doEdit($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail($this->subject);
        $this->printTitle($this->lang['stredit'], 'pg.view.alter');
        $this->printMsg($msg);

        $viewdata = $data->getView($_REQUEST[$this->subject]);
        $this->printHeader($this->headerTitle(), null, true, 'header_sqledit.twig');

        if ($viewdata->recordCount() > 0) {
            if (!isset($_POST['formDefinition'])) {
                $_POST['formDefinition'] = $viewdata->fields['vwdefinition'];
                $_POST['formComment']    = $viewdata->fields['relcomment'];
            }

            $variables = (object) [
                'subfolder'      => \SUBFOLDER.'/src/views/viewproperties',

                'formDefinition' => htmlspecialchars($_POST['formDefinition']),

                'formComment'    => htmlspecialchars($_POST['formComment']),
                'subject'        => htmlspecialchars($_REQUEST[$this->subject]), ];

            $edition_area = <<<EOT

<form action="{$variables->subfolder}" method="post">
  <table style="width: 100%">
    <tr>
      <th class="data left required">{$this->lang['strdefinition']}</th>
      <td class="data1">
        <textarea style="width: 100%;" rows="20" cols="50" id="query" name="formDefinition">
            {$variables->formDefinition}
        </textarea>
      </td>
    </tr>
    <tr>
      <th class="data left">{$this->lang['strcomment']}</th>
      <td class="data1">
        <textarea rows="3" cols="32" name="formComment">
          {$variables->formComment}
        </textarea>
      </td>
    </tr>
  </table>
  <p>
    <input type="hidden" name="action" value="save_edit" />
    <input type="hidden" name="view" value="{$variables->subject}" />
    {$this->misc->form}
    <input type="submit" value="{$this->lang['stralter']}" />
    <input type="submit" name="cancel" value="{$this->lang['strcancel']}" />
  </p>
</form>
EOT;
            echo $edition_area;
        } else {
            echo "<p>{$this->lang['strnodata']}</p>".PHP_EOL;
        }
    }

    /**
     * Displays a screen where they can alter a column in a view.
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

                echo '<form action="'.\SUBFOLDER.'/src/views/viewproperties" method="post">'.PHP_EOL;

                // Output view header
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
                echo '<input type="hidden" name="view" value="', htmlspecialchars($_REQUEST[$this->subject]), '" />'.PHP_EOL;
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

                // Alter the view column
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
            $this->printTitle($this->lang['stralter'], 'pg.view.alter');
            $this->printMsg($msg);

            // Fetch view info
            $view = $data->getView($_REQUEST[$this->subject]);

            if ($view->recordCount() > 0) {
                $this->coalesceArr($_POST, 'name', $view->fields['relname']);

                $this->coalesceArr($_POST, 'owner', $view->fields['relowner']);

                $this->coalesceArr($_POST, 'newschema', $view->fields['nspname']);

                $this->coalesceArr($_POST, 'comment', $view->fields['relcomment']);

                echo '<form action="'.\SUBFOLDER.'/src/views/viewproperties" method="post">'.PHP_EOL;
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
                echo '<textarea rows="3" cols="32" name="comment">',
                htmlspecialchars($_POST['comment']), '</textarea></td></tr>'.PHP_EOL;
                echo '</table>'.PHP_EOL;
                echo '<input type="hidden" name="action" value="alter" />'.PHP_EOL;
                echo '<input type="hidden" name="view" value="', htmlspecialchars($_REQUEST[$this->subject]), '" />'.PHP_EOL;
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

            $status = $data->alterView($_POST[$this->subject], $_POST['name'], $_POST['owner'], $_POST['newschema'], $_POST['comment']);
            if (0 == $status) {
                // If view has been renamed, need to change to the new name and
                // reload the browser frame.
                if ($_POST[$this->subject] != $_POST['name']) {
                    // Jump them to the new view name
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
