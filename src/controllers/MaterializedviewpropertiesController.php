<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Traits\ExportTrait;
use PHPPgAdmin\Traits\ViewsMatViewsPropertiesTrait;

/**
 * Base controller class.
 */
class MaterializedviewpropertiesController extends BaseController
{
    use ExportTrait;
    use ViewsMatViewsPropertiesTrait;

    public $controller_title = 'strviews';

    public $subject = 'matview';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        $this->printHeader($this->headerTitle('', '', $_REQUEST[$this->subject]));
        $this->printBody();

        switch ($this->action) {
            case 'save_edit':
                if (null !== $this->getPostParam('cancel')) {
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
                if (null !== $this->getPostParam('cancel')) {
                    $this->doDefault();
                } else {
                    $this->doProperties();
                }

                break;
            case 'alter':
                if (null !== $this->getPostParam('alter')) {
                    $this->doAlter(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_alter':
                $this->doAlter(true);

                break;
            /*case 'drop':
            if($this->getPostParam('drop')!==null){
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
    public function doSaveEdit(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->setView($_POST[$this->subject], $_POST['formDefinition'], $_POST['formComment'], true);

        if (0 === $status) {
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
        $sql = 'REFRESH MATERIALIZED VIEW ' . $_REQUEST[$this->subject];
        //$this->prtrace($sql);
        $status = $data->execute($sql);

        if (0 === $status) {
            return $this->doDefault($this->lang['strviewupdated']);
        }

        return $this->doDefault($this->lang['strviewupdatedbad']);
    }

    /**
     * Function to allow editing of a matview.
     *
     * @param mixed $msg
     */
    public function doEdit($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail($this->subject);
        $this->printTitle($this->lang['stredit'], 'pg.matview.alter');
        $this->printMsg($msg);

        $viewdata = $data->getView($_REQUEST[$this->subject]);

        if (0 < $viewdata->RecordCount()) {
            if (!isset($_POST['formDefinition'])) {
                $_POST['formDefinition'] = $viewdata->fields['vwdefinition'];
                $_POST['formComment'] = $viewdata->fields['relcomment'];
            }

            echo '<form action="materializedviewproperties" method="post">' . \PHP_EOL;
            echo '<table style="width: 100%">' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left required">%s</th>',
                $this->lang['strdefinition']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><textarea style=\"width: 100%;\" rows=\"20\" cols=\"50\" name=\"formDefinition\">",
            \htmlspecialchars($_POST['formDefinition']), "</textarea></td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strcomment']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><textarea rows=\"3\" cols=\"32\" name=\"formComment\">",
            \htmlspecialchars($_POST['formComment']), "</textarea></td>\n\t</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="save_edit" />' . \PHP_EOL;
            echo '<input type="hidden" name="matview" value="', \htmlspecialchars($_REQUEST[$this->subject]), '" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" value="%s" />',
                $this->lang['stralter']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s"  /></p>%s',
                $this->lang['strcancel'],
                \PHP_EOL
            );
            echo '</form>' . \PHP_EOL;
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;
        }
    }

    /**
     * Displays a screen where they can alter a column in a matview.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'stage', 1);

        switch ($_REQUEST['stage']) {
            case 1:
                $this->printTrail('column');
                $this->printTitle($this->lang['stralter'], 'pg.column.alter');
                $this->printMsg($msg);

                echo '<form action="materializedviewproperties" method="post">' . \PHP_EOL;

                // Output matview header
                echo '<table>' . \PHP_EOL;
                echo \sprintf(
                    '<tr><th class="data required">%s</th><th class="data required">%s</th>',
                    $this->lang['strname'],
                    $this->lang['strtype']
                );
                echo \sprintf(
                    '<th class="data">%s</th><th class="data">%s</th></tr>',
                    $this->lang['strdefault'],
                    $this->lang['strcomment']
                );

                $column = $data->getTableAttributes($_REQUEST[$this->subject], $_REQUEST['column']);

                if (!isset($_REQUEST['default'])) {
                    $_REQUEST['field'] = $column->fields['attname'];
                    $_REQUEST['default'] = $_REQUEST['olddefault'] = $column->fields['adsrc'];
                    $_REQUEST['comment'] = $column->fields['comment'];
                }

                echo '<tr><td><input name="field" size="32" value="',
                \htmlspecialchars($_REQUEST['field']), '" /></td>';

                echo '<td>', $this->misc->printVal($data->formatType($column->fields['type'], $column->fields['atttypmod'])), '</td>';
                echo '<td><input name="default" size="20" value="',
                \htmlspecialchars($_REQUEST['default']), '" /></td>';
                echo '<td><input name="comment" size="32" value="',
                \htmlspecialchars($_REQUEST['comment']), '" /></td>';

                echo '</table>' . \PHP_EOL;
                echo '<p><input type="hidden" name="action" value="properties" />' . \PHP_EOL;
                echo '<input type="hidden" name="stage" value="2" />' . \PHP_EOL;
                echo $this->view->form;
                echo '<input type="hidden" name="matview" value="', \htmlspecialchars($_REQUEST[$this->subject]), '" />' . \PHP_EOL;
                echo '<input type="hidden" name="column" value="', \htmlspecialchars($_REQUEST['column']), '" />' . \PHP_EOL;
                echo '<input type="hidden" name="olddefault" value="', \htmlspecialchars($_REQUEST['olddefault']), '" />' . \PHP_EOL;
                echo \sprintf(
                    '<input type="submit" value="%s" />',
                    $this->lang['stralter']
                ) . \PHP_EOL;
                echo \sprintf(
                    '<input type="submit" name="cancel" value="%s"  /></p>%s',
                    $this->lang['strcancel'],
                    \PHP_EOL
                );
                echo '</form>' . \PHP_EOL;

                break;
            case 2:
                // Check inputs
                if ('' === \trim($_REQUEST['field'])) {
                    $_REQUEST['stage'] = 1;
                    $this->doProperties($this->lang['strcolneedsname']);

                    return;
                }

                // Alter the matview column
                [$status, $sql] = $data->alterColumn(
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

                if (0 === $status) {
                    $this->doDefault($this->lang['strcolumnaltered']);
                } else {
                    $_REQUEST['stage'] = 1;
                    $this->doProperties($this->lang['strcolumnalteredbad']);

                    return;
                }

                break;

            default:
                echo \sprintf(
                    '<p>%s</p>',
                    $this->lang['strinvalidparam']
                ) . \PHP_EOL;
        }
    }

    public function doAlter(bool $confirm = false, $msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail($this->subject);
            $this->printTitle($this->lang['stralter'], 'pg.matview.alter');
            $this->printMsg($msg);

            // Fetch matview info
            $matview = $data->getView($_REQUEST[$this->subject]);

            if (0 < $matview->RecordCount()) {
                $this->coalesceArr($_POST, 'name', $matview->fields['relname']);

                $this->coalesceArr($_POST, 'owner', $matview->fields['relowner']);

                $this->coalesceArr($_POST, 'newschema', $matview->fields['nspname']);

                $this->coalesceArr($_POST, 'comment', $matview->fields['relcomment']);

                echo '<form action="materializedviewproperties" method="post">' . \PHP_EOL;
                echo '<table>' . \PHP_EOL;
                echo \sprintf(
                    '<tr><th class="data left required">%s</th>',
                    $this->lang['strname']
                ) . \PHP_EOL;
                echo '<td class="data1">';
                echo \sprintf(
                    '<input name="name" size="32" maxlength="%s" value="',
                    $data->_maxNameLen
                ),
                \htmlspecialchars($_POST['name']), '" /></td></tr>' . \PHP_EOL;

                if ($data->isSuperUser()) {
                    // Fetch all users
                    $users = $data->getUsers();

                    echo \sprintf(
                        '<tr><th class="data left required">%s</th>',
                        $this->lang['strowner']
                    ) . \PHP_EOL;
                    echo '<td class="data1"><select name="owner">';

                    while (!$users->EOF) {
                        $uname = $users->fields['usename'];
                        echo '<option value="', \htmlspecialchars($uname), '"',
                        ($uname === $_POST['owner']) ? ' selected="selected"' : '', '>', \htmlspecialchars($uname), '</option>' . \PHP_EOL;
                        $users->MoveNext();
                    }
                    echo '</select></td></tr>' . \PHP_EOL;
                }

                if ($data->hasAlterTableSchema()) {
                    $schemas = $data->getSchemas();
                    echo \sprintf(
                        '<tr><th class="data left required">%s</th>',
                        $this->lang['strschema']
                    ) . \PHP_EOL;
                    echo '<td class="data1"><select name="newschema">';

                    while (!$schemas->EOF) {
                        $schema = $schemas->fields['nspname'];
                        echo '<option value="', \htmlspecialchars($schema), '"',
                        ($schema === $_POST['newschema']) ? ' selected="selected"' : '', '>', \htmlspecialchars($schema), '</option>' . \PHP_EOL;
                        $schemas->MoveNext();
                    }
                    echo '</select></td></tr>' . \PHP_EOL;
                }

                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strcomment']
                ) . \PHP_EOL;
                echo '<td class="data1">';
                echo '<textarea rows="3" cols="32" name="comment">';
                echo \htmlspecialchars($_POST['comment']), '</textarea></td></tr>' . \PHP_EOL;
                echo '</table>' . \PHP_EOL;
                echo '<input type="hidden" name="action" value="alter" />' . \PHP_EOL;
                echo '<input type="hidden" name="matview" value="', \htmlspecialchars($_REQUEST[$this->subject]), '" />' . \PHP_EOL;
                echo $this->view->form;
                echo \sprintf(
                    '<p><input type="submit" name="alter" value="%s" />',
                    $this->lang['stralter']
                ) . \PHP_EOL;
                echo \sprintf(
                    '<input type="submit" name="cancel" value="%s"  /></p>%s',
                    $this->lang['strcancel'],
                    \PHP_EOL
                );
                echo '</form>' . \PHP_EOL;
            } else {
                echo \sprintf(
                    '<p>%s</p>',
                    $this->lang['strnodata']
                ) . \PHP_EOL;
            }
        } else {
            // For databases that don't allow owner change
            $this->coalesceArr($_POST, 'owner', '');

            $this->coalesceArr($_POST, 'newschema', null);

            $status = $data->alterMatView($_POST[$this->subject], $_POST['name'], $_POST['owner'], $_POST['newschema'], $_POST['comment']);

            if (0 === $status) {
                // If matview has been renamed, need to change to the new name and
                // reload the browser frame.
                if ($_POST[$this->subject] !== $_POST['name']) {
                    // Jump them to the new matview name
                    $_REQUEST[$this->subject] = $_POST['name'];
                    // Force a browser reload
                    $this->view->setReloadBrowser(true);
                }
                // If schema has changed, need to change to the new schema and reload the browser
                if (!empty($_POST['newschema']) && ($_POST['newschema'] !== $data->_schema)) {
                    // Jump them to the new sequence schema
                    $this->misc->setCurrentSchema($_POST['newschema']);
                    $this->view->setReloadBrowser(true);
                }
                $this->doDefault($this->lang['strviewaltered']);
            } else {
                $this->doAlter(true, $this->lang['strviewalteredbad']);
            }
        }
    }
}
