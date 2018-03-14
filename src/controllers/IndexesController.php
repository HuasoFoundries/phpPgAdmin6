<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class IndexesController extends BaseController
{
    public $controller_name = 'IndexesController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang = $this->lang;

        $action = $this->action;
        if ('tree' == $action) {
            return $this->doTree();
        }

        $this->printHeader($lang['strindexes'], '<script src="'.\SUBFOLDER.'/js/indexes.js" type="text/javascript"></script>');

        $onloadInit = false;
        if ('create_index' == $action || 'save_create_index' == $action) {
            $onloadInit = true;
        }
        $this->printBody(true, 'detailbody', $onloadInit);

        switch ($action) {
            case 'cluster_index':
                if (isset($_POST['cluster'])) {
                    $this->doClusterIndex(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_cluster_index':
                $this->doClusterIndex(true);

                break;
            case 'reindex':
                $this->doReindex();

                break;
            case 'save_create_index':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreateIndex();
                }

                break;
            case 'create_index':
                $this->doCreateIndex();

                break;
            case 'drop_index':
                if (isset($_POST['drop'])) {
                    $this->doDropIndex(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop_index':
                $this->doDropIndex(true);

                break;
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    public function doDefault($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $indPre = function (&$rowdata, $actions) use ($data, $lang) {
            if ($data->phpBool($rowdata->fields['indisprimary'])) {
                $rowdata->fields['+constraints'] = $lang['strprimarykey'];
                $actions['drop']['disable']      = true;
            } elseif ($data->phpBool($rowdata->fields['indisunique'])) {
                $rowdata->fields['+constraints'] = $lang['struniquekey'];
                $actions['drop']['disable']      = true;
            } else {
                $rowdata->fields['+constraints'] = '';
            }

            return $actions;
        };
        if (!isset($_REQUEST['subject'])) {
            $_REQUEST['subject'] = 'table';
        }

        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$_REQUEST['subject']]);

        $this->printTrail($subject);
        $this->printTabs($subject, 'indexes');
        $this->printMsg($msg);

        $indexes = $data->getIndexes($_REQUEST[$_REQUEST['subject']]);

        $columns = [
            'index' => [
                'title' => $lang['strname'],
                'field' => Decorator::field('indname'),
            ],
            'definition' => [
                'title' => $lang['strdefinition'],
                'field' => Decorator::field('inddef'),
            ],
            'constraints' => [
                'title'  => $lang['strconstraints'],
                'field'  => Decorator::field('+constraints'),
                'type'   => 'verbatim',
                'params' => ['align' => 'center'],
            ],
            'clustered' => [
                'title' => $lang['strclustered'],
                'field' => Decorator::field('indisclustered'),
                'type'  => 'yesno',
            ],
            'actions' => [
                'title' => $lang['stractions'],
            ],
            'comment' => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('idxcomment'),
            ],
        ];

        $url = \SUBFOLDER.'/src/views/indexes';

        $actions = [
            'cluster' => [
                'content' => $lang['strclusterindex'],
                'attr'    => [
                    'href' => [
                        'url'     => $url,
                        'urlvars' => [
                            'action'  => 'confirm_cluster_index',
                            'subject' => $subject,
                            $subject  => $object,
                            'index'   => Decorator::field('indname'),
                        ],
                    ],
                ],
            ],
            'reindex' => [
                'content' => $lang['strreindex'],
                'attr'    => [
                    'href' => [
                        'url'     => $url,
                        'urlvars' => [
                            'action'  => 'reindex',
                            'subject' => $subject,
                            $subject  => $object,
                            'index'   => Decorator::field('indname'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'content' => $lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => $url,
                        'urlvars' => [
                            'action'  => 'confirm_drop_index',
                            'subject' => $subject,
                            $subject  => $object,
                            'index'   => Decorator::field('indname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($indexes, $columns, $actions, 'indexes-indexes', $lang['strnoindexes'], $indPre);

        $this->printNavLinks([
            'create' => [
                'attr' => [
                    'href' => [
                        'url'     => 'indexes.php',
                        'urlvars' => [
                            'action'   => 'create_index',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            $subject   => $object,
                            'subject'  => $subject,
                        ],
                    ],
                ],
                'content' => $lang['strcreateindex'],
            ],
        ], 'indexes-indexes', get_defined_vars());
    }

    public function doTree()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();
        if (!isset($_REQUEST['subject'])) {
            $_REQUEST['subject'] = 'table';
        }

        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$_REQUEST['subject']]);

        $indexes = $data->getIndexes($object);

        $reqvars = $this->misc->getRequestVars($subject);

        $getIcon = function ($f) {
            if ('t' == $f['indisprimary']) {
                return 'PrimaryKey';
            }

            if ('t' == $f['indisunique']) {
                return 'UniqueConstraint';
            }

            return 'Index';
        };

        $attrs = [
            'text' => Decorator::field('indname'),
            'icon' => Decorator::callback($getIcon),
        ];

        return $this->printTree($indexes, $attrs, 'indexes');
    }

    /**
     * Show confirmation of cluster index and perform actual cluster.
     *
     * @param mixed $confirm
     */
    public function doClusterIndex($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_REQUEST['subject'])) {
            $_REQUEST['subject'] = 'table';
        }
        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$subject]);

        //$this->printTrail($subject);

        if ($confirm) {
            // Default analyze to on
            $_REQUEST['analyze'] = true;

            $this->printTrail('index');
            $this->printTabs($subject, 'indexes');
            $this->printTitle($lang['strclusterindex'], 'pg.index.cluster');

            echo '<p>', sprintf($lang['strconfcluster'], $this->misc->printVal($_REQUEST['index'])), '</p>'."\n";

            echo '<form action="'.\SUBFOLDER.'/src/views/indexes.php" method="post">'."\n";
            echo '<p><input type="checkbox" id="analyze" name="analyze"', (isset($_REQUEST['analyze']) ? ' checked="checked"' : ''), ' />';
            echo "<label for=\"analyze\">{$lang['stranalyze']}</label></p>"."\n";
            echo '<input type="hidden" name="action" value="cluster_index" />'."\n";
            echo '<input type="hidden" name="table" value="', htmlspecialchars($object), '" />'."\n";
            echo '<input type="hidden" name="index" value="', htmlspecialchars($_REQUEST['index']), '" />'."\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"cluster\" value=\"{$lang['strclusterindex']}\" />"."\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />"."\n";
            echo '</form>'."\n";
        } else {
            set_time_limit(0);
            list($status, $sql) = $data->clusterIndex($object, $_POST['index']);
            if (0 == $status) {
                if (isset($_POST['analyze'])) {
                    $status = $data->analyzeDB($object);
                    if (0 == $status) {
                        $this->doDefault($sql.'<br>'.$lang['strclusteredgood'].' '.$lang['stranalyzegood']);
                    } else {
                        $this->doDefault($sql.'<br>'.$lang['stranalyzebad']);
                    }
                } else {
                    $this->doDefault($sql.'<br>'.$lang['strclusteredgood']);
                }
            } else {
                $this->doDefault($sql.'<br>'.$lang['strclusteredbad']);
            }
        }
    }

    public function doReindex()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();
        set_time_limit(0);
        $status = $data->reindex('INDEX', $_REQUEST['index']);
        if (0 == $status) {
            $this->doDefault($lang['strreindexgood']);
        } else {
            $this->doDefault($lang['strreindexbad']);
        }
    }

    /**
     * Displays a screen where they can enter a new index.
     *
     * @param mixed $msg
     */
    public function doCreateIndex($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_REQUEST['subject'])) {
            $_REQUEST['subject'] = 'table';
        }
        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$subject]);

        if (!isset($_POST['formIndexName'])) {
            $_POST['formIndexName'] = '';
        }

        if (!isset($_POST['formIndexType'])) {
            $_POST['formIndexType'] = null;
        }

        if (!isset($_POST['formCols'])) {
            $_POST['formCols'] = '';
        }

        if (!isset($_POST['formWhere'])) {
            $_POST['formWhere'] = '';
        }

        if (!isset($_POST['formSpc'])) {
            $_POST['formSpc'] = '';
        }

        $attrs = $data->getTableAttributes($object);
        // Fetch all tablespaces from the database
        if ($data->hasTablespaces()) {
            $tablespaces = $data->getTablespaces();
        }
        $this->prtrace('tablespaces', $tablespaces->recordCount());
        $this->printTrail($subject);
        $this->printTabs($subject, 'indexes');
        $this->printTitle($lang['strcreateindex'], 'pg.index.create');
        $this->printMsg($msg);

        $selColumns = new \PHPPgAdmin\XHtml\XHtmlSelect('TableColumnList', true, 10);
        $selColumns->set_style('width: 14em;');

        if ($attrs->recordCount() > 0) {
            while (!$attrs->EOF) {
                $attname = new \PHPPgAdmin\XHtml\XHtmlOption($attrs->fields['attname']);
                $selColumns->add($attname);
                $attrs->moveNext();
            }
        }

        $selIndex = new \PHPPgAdmin\XHtml\XHtmlSelect('IndexColumnList[]', true, 10);
        $selIndex->set_style('width: 14em;');
        $selIndex->set_attribute('id', 'IndexColumnList');
        $buttonAdd = new \PHPPgAdmin\XHtml\XHtmlButton('add', '>>');
        $buttonAdd->set_attribute('onclick', 'buttonPressed(this);');
        $buttonAdd->set_attribute('type', 'button');

        $buttonRemove = new \PHPPgAdmin\XHtml\XHtmlButton('remove', '<<');
        $buttonRemove->set_attribute('onclick', 'buttonPressed(this);');
        $buttonRemove->set_attribute('type', 'button');

        echo '<form onsubmit="doSelectAll();" name="formIndex" action="indexes.php" method="post">'."\n";

        echo '<table>'."\n";
        echo '<tr><th class="data required" colspan="3">'.$lang['strindexname'].'</th></tr>';
        echo '<tr>';
        echo '<td class="data1" colspan="3">';
        echo 'Index name cannot exceed '.$data->_maxNameLen.' characters<br>';
        echo '<input type="text" name="formIndexName" size="32" placeholder="Index Name" maxlength="'.
        $data->_maxNameLen.'" value="'.
        htmlspecialchars($_POST['formIndexName']).'" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th class="data">'.$lang['strtablecolumnlist'].'</th><th class="data">&nbsp;</th>';
        echo '<th class="data required">'.$lang['strindexcolumnlist'].'</th>';
        echo '</tr>'."\n";

        echo '<tr><td class="data1">'.$selColumns->fetch().'</td>'."\n";
        echo '<td class="data1">'.$buttonRemove->fetch().$buttonAdd->fetch().'</td>';
        echo '<td class="data1">'.$selIndex->fetch().'</td></tr>'."\n";
        echo '<tr>';
        echo '<th class="data left required" scope="row">'.$lang['strindextype'].'</th>';
        echo '<td colspan="2" class="data1"><select name="formIndexType">';
        foreach ($data->typIndexes as $v) {
            echo '<option value="', htmlspecialchars($v), '"',
            ($v == $_POST['formIndexType']) ? ' selected="selected"' : '', '>', htmlspecialchars($v), '</option>'."\n";
        }
        echo '</select></td></tr>'."\n";
        echo '<tr>';
        echo "<th class=\"data left\" scope=\"row\"><label for=\"formUnique\">{$lang['strunique']}</label></th>";
        echo '<td  colspan="2" class="data1"><input type="checkbox" id="formUnique" name="formUnique"', (isset($_POST['formUnique']) ? 'checked="checked"' : ''), ' /></td>';
        echo '</tr>';
        echo '<tr>';
        echo "<th class=\"data left\" scope=\"row\">{$lang['strwhere']}</th>";
        echo '<td  colspan="2"  class="data1">(<input name="formWhere" size="32" maxlength="'.$data->_maxNameLen.'" value="'.htmlspecialchars($_POST['formWhere']).'" />)</td>';
        echo '</tr>';

        // Tablespace (if there are any)
        if ($data->hasTablespaces() && $tablespaces->recordCount() > 0) {
            echo '<tr>'."\n";
            echo "<th class=\"data left\">{$lang['strtablespace']}</th>"."\n";
            echo '<td  colspan="2" class="data1">';
            echo "\n\t\t\t<select name=\"formSpc\">"."\n";
            // Always offer the default (empty) option
            echo "\t\t\t\t<option value=\"\"",
            ('' == $_POST['formSpc']) ? ' selected="selected"' : '', '></option>'."\n";
            // Display all other tablespaces
            while (!$tablespaces->EOF) {
                $spcname = htmlspecialchars($tablespaces->fields['spcname']);
                echo "\t\t\t\t<option value=\"{$spcname}\"",
                ($spcname == $_POST['formSpc']) ? ' selected="selected"' : '', ">{$spcname}</option>"."\n";
                $tablespaces->moveNext();
            }
            echo "\t\t\t</select>\n\t\t</td>\n\t</tr>"."\n";
        }

        if ($data->hasConcurrentIndexBuild()) {
            echo '<tr>';
            echo "<th class=\"data left\" scope=\"row\"><label for=\"formConcur\">{$lang['strconcurrently']}</label></th>";
            echo '<td  colspan="2"  class="data1"><input type="checkbox" id="formConcur" name="formConcur"', (isset($_POST['formConcur']) ? 'checked="checked"' : ''), ' /></td>';
            echo '</tr>';
        }

        echo '</table>';

        echo '<p><input type="hidden" name="action" value="save_create_index" />'."\n";
        echo $this->misc->form;
        echo '<input type="hidden" name="subject" value="', htmlspecialchars($subject), '" />'."\n";
        echo '<input type="hidden" name="'.$subject.'" value="', htmlspecialchars($object), '" />'."\n";
        echo "<input type=\"submit\" value=\"{$lang['strcreate']}\" />"."\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>"."\n";
        echo '</form>'."\n";
    }

    /**
     * Actually creates the new index in the database.
     *
     * @@ Note: this function can't handle columns with commas in them
     */
    public function doSaveCreateIndex()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['subject'])) {
            $_POST['subject'] = 'table';
        }
        $subject = urlencode($_POST['subject']);
        $object  = urlencode($_POST[$subject]);

        // Handle databases that don't have partial indexes
        if (!isset($_POST['formWhere'])) {
            $_POST['formWhere'] = '';
        }

        // Default tablespace to null if it isn't set
        if (!isset($_POST['formSpc'])) {
            $_POST['formSpc'] = null;
        }

        // Check that they've given a name and at least one column
        if ('' == $_POST['formIndexName']) {
            $this->doCreateIndex($lang['strindexneedsname']);
        } elseif (!isset($_POST['IndexColumnList']) || '' == $_POST['IndexColumnList']) {
            $this->doCreateIndex($lang['strindexneedscols']);
        } else {
            $status = $data->createIndex(
                $_POST['formIndexName'],
                $object,
                $_POST['IndexColumnList'],
                $_POST['formIndexType'],
                isset($_POST['formUnique']),
                $_POST['formWhere'],
                $_POST['formSpc'],
                isset($_POST['formConcur'])
            );
            if (0 == $status) {
                $this->doDefault($lang['strindexcreated']);
            } else {
                $this->doCreateIndex($lang['strindexcreatedbad']);
            }
        }
    }

    /**
     * Show confirmation of drop index and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDropIndex($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_REQUEST['subject'])) {
            $_REQUEST['subject'] = 'table';
        }
        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$_REQUEST['subject']]);

        if ($confirm) {
            $this->printTrail('index');
            $this->printTitle($lang['strdrop'], 'pg.index.drop');

            echo '<p>', sprintf($lang['strconfdropindex'], $this->misc->printVal($_REQUEST['index'])), '</p>'."\n";

            echo '<form action="'.\SUBFOLDER.'/src/views/indexes.php" method="post">'."\n";
            echo '<input type="hidden" name="action" value="drop_index" />'."\n";
            echo '<input type="hidden" name="table" value="', htmlspecialchars($object), '" />'."\n";
            echo '<input type="hidden" name="index" value="', htmlspecialchars($_REQUEST['index']), '" />'."\n";
            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$lang['strcascade']}</label></p>"."\n";
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />"."\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />"."\n";
            echo '</form>'."\n";
        } else {
            $status = $data->dropIndex($_POST['index'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($lang['strindexdropped']);
            } else {
                $this->doDefault($lang['strindexdroppedbad']);
            }
        }
    }
}
