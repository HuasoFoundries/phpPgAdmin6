<?php

/**
 * PHPPgAdmin v6.0.0-beta.51
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
    public $controller_title = 'strindexes';
    public $scripts          = '<script src="'.\SUBFOLDER.'/assets/js/indexes.js" type="text/javascript"></script>';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $this->printHeader($this->headerTitle(), $this->scripts);

        $onloadInit = false;
        if ('create_index' == $this->action || 'save_create_index' == $this->action) {
            $onloadInit = true;
        }
        $this->printBody(true, 'detailbody', $onloadInit);

        switch ($this->action) {
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
        $data = $this->misc->getDatabaseAccessor();

        $lang   = $this->lang;
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
        $this->coalesceArr($_REQUEST, 'subject', 'table');

        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$_REQUEST['subject']]);

        $this->printTrail($subject);
        $this->printTabs($subject, 'indexes');
        $this->printMsg($msg);

        $indexes = $data->getIndexes($_REQUEST[$_REQUEST['subject']]);

        $columns = [
            'index'       => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('indname'),
            ],
            'definition'  => [
                'title' => $this->lang['strdefinition'],
                'field' => Decorator::field('inddef'),
            ],
            'constraints' => [
                'title'  => $this->lang['strconstraints'],
                'field'  => Decorator::field('+constraints'),
                'type'   => 'verbatim',
                'params' => ['align' => 'center'],
            ],
            'clustered'   => [
                'title' => $this->lang['strclustered'],
                'field' => Decorator::field('indisclustered'),
                'type'  => 'yesno',
            ],
            'actions'     => [
                'title' => $this->lang['stractions'],
            ],
            'comment'     => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('idxcomment'),
            ],
        ];

        $url = \SUBFOLDER.'/src/views/indexes';

        $actions = [
            'cluster' => [
                'content' => $this->lang['strclusterindex'],
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
                'content' => $this->lang['strreindex'],
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
            'drop'    => [
                'content' => $this->lang['strdrop'],
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

        echo $this->printTable($indexes, $columns, $actions, 'indexes-indexes', $this->lang['strnoindexes'], $indPre);

        $this->printNavLinks([
            'create' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'indexes',
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
                'content' => $this->lang['strcreateindex'],
            ],
        ], 'indexes-indexes', get_defined_vars());
    }

    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();
        $this->coalesceArr($_REQUEST, 'subject', 'table');

        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$subject]);

        $indexes = $data->getIndexes($object);

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
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'subject', 'table');
        $subject = urlencode($_REQUEST['subject']);
        $object  = urlencode($_REQUEST[$subject]);

        //$this->printTrail($subject);

        if ($confirm) {
            // Default analyze to on
            $_REQUEST['analyze'] = true;

            $this->printTrail('index');
            $this->printTabs($subject, 'indexes');
            $this->printTitle($this->lang['strclusterindex'], 'pg.index.cluster');

            echo '<p>', sprintf($this->lang['strconfcluster'], $this->misc->printVal($_REQUEST['index'])), '</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/indexes" method="post">'.PHP_EOL;
            echo '<p><input type="checkbox" id="analyze" name="analyze"', (isset($_REQUEST['analyze']) ? ' checked="checked"' : ''), ' />';
            echo "<label for=\"analyze\">{$this->lang['stranalyze']}</label></p>".PHP_EOL;
            echo '<input type="hidden" name="action" value="cluster_index" />'.PHP_EOL;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($object), '" />'.PHP_EOL;
            echo '<input type="hidden" name="index" value="', htmlspecialchars($_REQUEST['index']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"cluster\" value=\"{$this->lang['strclusterindex']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            set_time_limit(0);
            list($status, $sql) = $data->clusterIndex($object, $_POST['index']);
            if (0 == $status) {
                if (isset($_POST['analyze'])) {
                    $status = $data->analyzeDB($object);
                    if (0 == $status) {
                        $this->doDefault($sql.'<br>'.$this->lang['strclusteredgood'].' '.$this->lang['stranalyzegood']);
                    } else {
                        $this->doDefault($sql.'<br>'.$this->lang['stranalyzebad']);
                    }
                } else {
                    $this->doDefault($sql.'<br>'.$this->lang['strclusteredgood']);
                }
            } else {
                $this->doDefault($sql.'<br>'.$this->lang['strclusteredbad']);
            }
        }
    }

    public function doReindex()
    {
        $data = $this->misc->getDatabaseAccessor();
        set_time_limit(0);
        $status = $data->reindex('INDEX', $_REQUEST['index']);
        if (0 == $status) {
            $this->doDefault($this->lang['strreindexgood']);
        } else {
            $this->doDefault($this->lang['strreindexbad']);
        }
    }

    /**
     * Displays a screen where they can enter a new index.
     *
     * @param mixed $msg
     */
    public function doCreateIndex($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $subject = urlencode($this->getRequestParam('subject', 'table'));
        $object  = urlencode($this->getRequestParam($subject));

        $formIndexName = $this->getPostParam('formIndexName', '');
        $formIndexType = $this->getPostParam('formIndexType');
        $formUnique    = $this->getPostParam('formUnique');
        $formConcur    = $this->getPostParam('formConcur');
        $formWhere     = $this->getPostParam('formWhere', '');
        $formSpc       = $this->getPostParam('formSpc', '');
        $tablespaces   = null;

        $attrs = $data->getTableAttributes($object);
        // Fetch all tablespaces from the database
        if ($data->hasTablespaces()) {
            $tablespaces = $data->getTablespaces();
        }

        $this->printTrail($subject);
        $this->printTabs($subject, 'indexes');
        $this->printTitle($this->lang['strcreateindex'], 'pg.index.create');
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

        echo '<form onsubmit="doSelectAll();" name="formIndex" action="indexes" method="post">'.PHP_EOL;

        echo '<table>'.PHP_EOL;
        echo '<tr><th class="data required" colspan="3">'.$this->lang['strindexname'].'</th></tr>';
        echo '<tr>';
        echo '<td class="data1" colspan="3">';
        echo 'Index name cannot exceed '.$data->_maxNameLen.' characters<br>';
        echo '<input type="text" name="formIndexName" size="32" placeholder="Index Name" maxlength="'.
        $data->_maxNameLen.'" value="'.
        htmlspecialchars($formIndexName).'" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th class="data">'.$this->lang['strtablecolumnlist'].'</th><th class="data">&nbsp;</th>';
        echo '<th class="data required">'.$this->lang['strindexcolumnlist'].'</th>';
        echo '</tr>'.PHP_EOL;

        echo '<tr><td class="data1">'.$selColumns->fetch().'</td>'.PHP_EOL;
        echo '<td class="data1">'.$buttonRemove->fetch().$buttonAdd->fetch().'</td>';
        echo '<td class="data1">'.$selIndex->fetch().'</td></tr>'.PHP_EOL;
        echo '<tr>';
        echo '<th class="data left required" scope="row">'.$this->lang['strindextype'].'</th>';
        echo '<td colspan="2" class="data1"><select name="formIndexType">';
        foreach ($data->typIndexes as $v) {
            echo '<option value="', htmlspecialchars($v), '"',
            ($v == $formIndexType) ? ' selected="selected"' : '', '>', htmlspecialchars($v), '</option>'.PHP_EOL;
        }
        echo '</select></td></tr>'.PHP_EOL;
        echo '<tr>';
        echo "<th class=\"data left\" scope=\"row\"><label for=\"formUnique\">{$this->lang['strunique']}</label></th>";
        echo '<td  colspan="2" class="data1"><input type="checkbox" id="formUnique" name="formUnique"', ($formUnique ? 'checked="checked"' : ''), ' /></td>';
        echo '</tr>';
        echo '<tr>';
        echo "<th class=\"data left\" scope=\"row\">{$this->lang['strwhere']}</th>";
        echo '<td  colspan="2"  class="data1">(<input name="formWhere" size="32" maxlength="'.$data->_maxNameLen.'" value="'.htmlspecialchars($formWhere).'" />)</td>';
        echo '</tr>';

        // Tablespace (if there are any)
        if ($data->hasTablespaces() && $tablespaces->recordCount() > 0) {
            echo '<tr>'.PHP_EOL;
            echo "<th class=\"data left\">{$this->lang['strtablespace']}</th>".PHP_EOL;
            echo '<td  colspan="2" class="data1">';
            echo "\n\t\t\t<select name=\"formSpc\">".PHP_EOL;
            // Always offer the default (empty) option
            echo "\t\t\t\t<option value=\"\"",
            ('' == $formSpc) ? ' selected="selected"' : '', '></option>'.PHP_EOL;
            // Display all other tablespaces
            while (!$tablespaces->EOF) {
                $spcname = htmlspecialchars($tablespaces->fields['spcname']);
                echo "\t\t\t\t<option value=\"{$spcname}\"",
                ($spcname == $formSpc) ? ' selected="selected"' : '', ">{$spcname}</option>".PHP_EOL;
                $tablespaces->moveNext();
            }
            echo "\t\t\t</select>\n\t\t</td>\n\t</tr>".PHP_EOL;
        }

        if ($data->hasConcurrentIndexBuild()) {
            echo '<tr>';
            echo "<th class=\"data left\" scope=\"row\"><label for=\"formConcur\">{$this->lang['strconcurrently']}</label></th>";
            echo '<td  colspan="2"  class="data1"><input type="checkbox" id="formConcur" name="formConcur"', ($formConcur ? 'checked="checked"' : ''), ' /></td>';
            echo '</tr>';
        }

        echo '</table>';

        echo '<p><input type="hidden" name="action" value="save_create_index" />'.PHP_EOL;
        echo $this->misc->form;
        echo '<input type="hidden" name="subject" value="', htmlspecialchars($subject), '" />'.PHP_EOL;
        echo '<input type="hidden" name="'.$subject.'" value="', htmlspecialchars($object), '" />'.PHP_EOL;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;
    }

    /**
     * Actually creates the new index in the database.
     *
     * @@ Note: this function can't handle columns with commas in them
     */
    public function doSaveCreateIndex()
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'subject', 'table');
        $subject = urlencode($_POST['subject']);
        $object  = urlencode($_POST[$subject]);

        // Handle databases that don't have partial indexes
        $formWhere = $this->getPostParam('formWhere', '');

        // Default tablespace to null if it isn't set
        $formSpc = $this->getPostParam('formSpc');

        $IndexColumnList = $this->getPostParam('IndexColumnList', '');

        // Check that they've given a name and at least one column
        if ('' == $IndexColumnList) {
            $this->doCreateIndex($this->lang['strindexneedscols']);
        } else {
            list($status, $sql) = $data->createIndex(
                $this->getPostParam('formIndexName', ''),
                $object,
                $IndexColumnList,
                $this->getPostParam('formIndexType'),
                $this->getPostParam('formUnique'),
                $formWhere,
                $formSpc,
                $this->getPostParam('formConcur')
            );

            if (0 == $status) {
                $this->doDefault($sql.'<br>'.$this->lang['strindexcreated']);
            } else {
                $this->doCreateIndex($this->lang['strindexcreatedbad']);
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
        $data = $this->misc->getDatabaseAccessor();

        $subject = urlencode($this->getRequestParam('subject', 'table'));
        $object  = urlencode($this->getRequestParam($subject));

        if ($confirm) {
            $this->printTrail('index');
            $this->printTitle($this->lang['strdrop'], 'pg.index.drop');

            echo '<p>', sprintf($this->lang['strconfdropindex'], $this->misc->printVal($this->getRequestParam('index'))), '</p>'.PHP_EOL;
            echo '<form action="'.\SUBFOLDER.'/src/views/indexes" method="post">'.PHP_EOL;
            echo '<input type="hidden" name="action" value="drop_index" />'.PHP_EOL;
            echo '<input type="hidden" name="table" value="', htmlspecialchars($object), '" />'.PHP_EOL;
            echo '<input type="hidden" name="index" value="', htmlspecialchars($this->getRequestParam('index')), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo '<p><input type="checkbox" id="cascade" name="cascade" value="1" />';
            echo '<label for="cascade">'.$this->lang['strcascade'].'</label></p>'.PHP_EOL;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            try {
                list($status, $sql) = $data->dropIndex($this->getPostParam('index'), $this->getPostParam('cascade'));
                if (0 == $status) {
                    $this->doDefault($sql.PHP_EOL.$this->lang['strindexdropped']);
                } else {
                    $this->doDefault($sql.PHP_EOL.$this->lang['strindexdroppedbad']);
                }
            } catch (\PHPPgAdmin\ADOdbException $e) {
                $this->doDefault($this->lang['strindexdroppedbad']);
            }
        }
    }
}
