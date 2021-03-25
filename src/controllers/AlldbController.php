<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use PHPPgAdmin\Traits\ExportTrait;
use Slim\Http\Response;

/**
 * Base controller class.
 */
class AlldbController extends BaseController
{
    use ExportTrait;

    public $table_place = 'alldb-databases';

    public $controller_title = 'strdatabases';

    /**
     * Default method to render the controller according to the action parameter.
     *
     * @return null|Response|string
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        $header_template = 'header.twig';

        \ob_start();

        switch ($this->action) {
            case 'export':
                $this->doExport();

                break;
            case 'save_create':
                if (null !== $this->getPostParam('cancel')) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'create':
                $this->doCreate();

                break;
            case 'drop':
                if (isset($_REQUEST['drop'])) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'alter':
                if (isset($_POST['oldname'], $_POST['newname']) && !isset($_POST['cancel'])) {
                    $this->doAlter(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_alter':
                $this->doAlter(true);

                break;

            default:
                $header_template = 'header_datatables.twig';
                $this->doDefault();

                break;
        }
        $output = \ob_get_clean();

        $this->printHeader($this->headerTitle(), null, true, $header_template);
        $this->printBody(true, 'flexbox_body', false, true);
        echo $output;

        return $this->printFooter();
    }

    /**
     * Show default list of databases in the server.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $this->printTrail('server');
        $this->printTabs('server', 'databases');
        $this->printMsg($msg);
        $data = $this->misc->getDatabaseAccessor();

        $databases = $data->getDatabases();

        $this->view->setReloadBrowser(true);

        $href = $this->misc->getHREF();
        $redirecturl = $this->container->getDestinationWithLastTab('database');

        $columns = [
            'database' => [
                'title' => $this->lang['strdatabase'],
                'field' => Decorator::field('datname'),
                'url' => $redirecturl . '&amp;',
                'vars' => ['database' => 'datname'],
            ],
            'owner' => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('datowner'),
            ],
            'encoding' => [
                'title' => $this->lang['strencoding'],
                'field' => Decorator::field('datencoding'),
            ],

            'tablespace' => [
                'title' => $this->lang['strtablespace'],
                'field' => Decorator::field('tablespace'),
            ],
            'dbsize' => [
                'title' => $this->lang['strsize'],
                'field' => Decorator::field('dbsize'),
                'type' => 'prettysize',
            ],
            'lc_collate' => [
                'title' => $this->lang['strcollation'],
                'field' => Decorator::field('datcollate'),
            ],
            'lc_ctype' => [
                'title' => $this->lang['strctype'],
                'field' => Decorator::field('datctype'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('datcomment'),
            ],
        ];

        $actions = [
            'multiactions' => [
                'keycols' => ['database' => 'datname'],
                'url' => 'alldb',
                'default' => null,
            ],
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'alldb',
                        'urlvars' => [
                            'subject' => 'database',
                            'action' => 'confirm_drop',
                            'dropdatabase' => Decorator::field('datname'),
                        ],
                    ],
                ],
                'multiaction' => 'confirm_drop',
            ],
            'privileges' => [
                'content' => $this->lang['strprivileges'],
                'attr' => [
                    'href' => [
                        'url' => 'privileges',
                        'urlvars' => [
                            'subject' => 'database',
                            'database' => Decorator::field('datname'),
                        ],
                    ],
                ],
            ],
        ];

        if ($data->hasAlterDatabase()) {
            $actions['alter'] = [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'alldb',
                        'urlvars' => [
                            'subject' => 'database',
                            'action' => 'confirm_alter',
                            'alterdatabase' => Decorator::field('datname'),
                        ],
                    ],
                ],
            ];
        }

        if (!$data->hasTablespaces()) {
            unset($columns['tablespace']);
        }

        if (!$data->hasServerAdminFuncs()) {
            unset($columns['dbsize']);
        }

        if (!$data->hasDatabaseCollation()) {
            unset($columns['lc_collate'], $columns['lc_ctype']);
        }

        if (!isset($data->privlist['database'])) {
            unset($actions['privileges']);
        }

        if (self::isRecordSet($databases)) {
            echo $this->printTable($databases, $columns, $actions, $this->table_place, $this->lang['strnodatabases']);
        }

        $navlinks = [
            'create' => [
                'attr' => [
                    'href' => [
                        'url' => 'alldb',
                        'urlvars' => [
                            'action' => 'create',
                            'server' => $_REQUEST['server'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreatedatabase'],
            ],
        ];
return  $this->printNavLinks($navlinks, $this->table_place, \get_defined_vars());
    }

    /**
     * @return Response|string
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $databases = $data->getDatabases();

        $reqvars = $this->misc->getRequestVars('database');

        $attrs = [
            'text' => Decorator::field('datname'),
            'icon' => 'Database',
            'toolTip' => Decorator::field('datcomment'),
            'action' => Decorator::redirecturl('redirect', $reqvars, ['subject' => 'database', 'database' => Decorator::field('datname')]),
            'branch' => Decorator::url('/src/views/database', $reqvars, ['action' => 'tree', 'database' => Decorator::field('datname')]),
        ];

        return $this->printTree($databases, $attrs, 'databases');
    }

    /**
     * Display a form for alter and perform actual alter.
     *
     * @param mixed $confirm
     */
    public function doAlter($confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('database');
            $this->printTitle($this->lang['stralter'], 'pg.database.alter');

            echo '<form action="alldb" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data left required">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo '<td class="data1">';
            echo \sprintf(
                '<input name="newname" size="32" maxlength="%s" value="',
                $data->_maxNameLen
            ),
            \htmlspecialchars($_REQUEST['alterdatabase']), '" /></td></tr>' . \PHP_EOL;

            if ($data->hasAlterDatabaseOwner() && $data->isSuperUser()) {
                // Fetch all users

                $rs = $data->getDatabaseOwner($_REQUEST['alterdatabase']);
                $owner = $rs->fields['usename'] ?? '';
                $users = $data->getUsers();

                echo \sprintf(
                    '<tr><th class="data left required">%s</th>',
                    $this->lang['strowner']
                ) . \PHP_EOL;
                echo '<td class="data1"><select name="owner">';

                while (!$users->EOF) {
                    $uname = $users->fields['usename'];
                    echo '<option value="', \htmlspecialchars($uname), '"',
                    ($uname === $owner) ? ' selected="selected"' : '', '>', \htmlspecialchars($uname), '</option>' . \PHP_EOL;
                    $users->MoveNext();
                }
                echo '</select></td></tr>' . \PHP_EOL;
            }

            if ($data->hasSharedComments()) {
                $rs = $data->getDatabaseComment($_REQUEST['alterdatabase']);
                $comment = $rs->fields['description'] ?? '';
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strcomment']
                ) . \PHP_EOL;
                echo '<td class="data1">';
                echo '<textarea rows="3" cols="32" name="dbcomment">',
                \htmlspecialchars($comment), '</textarea></td></tr>' . \PHP_EOL;
            }
            echo '</table>' . \PHP_EOL;
            echo '<input type="hidden" name="action" value="alter" />' . \PHP_EOL;
            echo $this->view->form;
            echo '<input type="hidden" name="oldname" value="',
            \htmlspecialchars($_REQUEST['alterdatabase']), '" />' . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="alter" value="%s" />',
                $this->lang['stralter']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s" />',
                $this->lang['strcancel']
            ) . \PHP_EOL;
            echo '</form>' . \PHP_EOL;
        } else {
            $this->coalesceArr($_POST, 'owner', '');

            $this->coalesceArr($_POST, 'dbcomment', '');

            if (0 === $data->alterDatabase($_POST['oldname'], $_POST['newname'], $_POST['owner'], $_POST['dbcomment'])) {
                $this->view->setReloadBrowser(true);
                $this->doDefault($this->lang['strdatabasealtered']);
            } else {
                $this->doDefault($this->lang['strdatabasealteredbad']);
            }
        }
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['dropdatabase']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($this->lang['strspecifydatabasetodrop']);
        }

        if ($confirm) {
            $this->printTrail('database');
            $this->printTitle($this->lang['strdrop'], 'pg.database.drop');

            echo '<form action="alldb" method="post">' . \PHP_EOL;
            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    echo '<p>', \sprintf(
                        $this->lang['strconfdropdatabase'],
                        $this->misc->printVal($a['database'])
                    ), '</p>' . \PHP_EOL;
                    \printf('<input type="hidden" name="dropdatabase[]" value="%s" />', \htmlspecialchars($a['database']));
                }
            } else {
                echo '<p>', \sprintf(
                    $this->lang['strconfdropdatabase'],
                    $this->misc->printVal($_REQUEST['dropdatabase'])
                ), '</p>' . \PHP_EOL;
                echo '<input type="hidden" name="dropdatabase" value="', \htmlspecialchars($_REQUEST['dropdatabase']), '" />' . \PHP_EOL;
                // END if multi drop
            }

            echo '<input type="hidden" name="action" value="drop" />' . \PHP_EOL;

            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="drop" value="%s" />',
                $this->lang['strdrop']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s" />',
                $this->lang['strcancel']
            ) . \PHP_EOL;
            echo "</form>\n"; //  END confirm
        } else {
            //If multi drop
            if (\is_array($_REQUEST['dropdatabase'])) {
                $msg = '';

                foreach ($_REQUEST['dropdatabase'] as $d) {
                    $status = $data->dropDatabase($d);

                    if (0 === $status) {
                        $msg .= \sprintf(
                            '%s: %s<br />',
                            \htmlentities($d, \ENT_QUOTES, 'UTF-8'),
                            $this->lang['strdatabasedropped']
                        );
                    } else {
                        $this->doDefault(\sprintf(
                            '%s%s: %s<br />',
                            $msg,
                            \htmlentities($d, \ENT_QUOTES, 'UTF-8'),
                            $this->lang['strdatabasedroppedbad']
                        ));

                        return;
                    }
                    // Everything went fine, back to Default page...
                }
                $this->setReloadDropDatabase(true);
                $this->doDefault($msg);
            } else {
                $status = $data->dropDatabase($_POST['dropdatabase']);

                if (0 === $status) {
                    $this->setReloadDropDatabase(true);
                    $this->doDefault($this->lang['strdatabasedropped']);
                } else {
                    $this->doDefault($this->lang['strdatabasedroppedbad']);
                }
            }
            //END DROP
        }
    }

    // END FUNCTION

    /**
     * Displays a screen where they can enter a new database.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('server');
        $this->printTitle($this->lang['strcreatedatabase'], 'pg.database.create');
        $this->printMsg($msg);

        $this->coalesceArr($_POST, 'formName', '');

        // Default encoding is that in language file
        $this->coalesceArr($_POST, 'formEncoding', '');
        $this->coalesceArr($_POST, 'formTemplate', 'template1');

        $this->coalesceArr($_POST, 'formSpc', '');

        $this->coalesceArr($_POST, 'formComment', '');

        // Fetch a list of databases in the cluster
        $templatedbs = $data->getDatabases(false);

        $tablespaces = null;
        // Fetch all tablespaces from the database
        if ($data->hasTablespaces()) {
            $tablespaces = $data->getTablespaces();
        }

        echo '<form action="alldb" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data1"><input name="formName" size="32" maxlength="%s" value="',
            $data->_maxNameLen
        ),
        \htmlspecialchars($_POST['formName']), "\" /></td>\n\t</tr>" . \PHP_EOL;

        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strtemplatedb']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\">" . \PHP_EOL;
        echo "\t\t\t<select name=\"formTemplate\">" . \PHP_EOL;
        // Always offer template0 and template1
        echo "\t\t\t\t<option value=\"template0\"",
        ('template0' === $_POST['formTemplate']) ? ' selected="selected"' : '', '>template0</option>' . \PHP_EOL;
        echo "\t\t\t\t<option value=\"template1\"",
        ('template1' === $_POST['formTemplate']) ? ' selected="selected"' : '', '>template1</option>' . \PHP_EOL;

        while (!$templatedbs->EOF) {
            $dbname = \htmlspecialchars($templatedbs->fields['datname']);

            if ('template1' !== $dbname) {
                // filter out for $this->conf[show_system] users so we dont get duplicates
                echo \sprintf(
                    '				<option value="%s"',
                    $dbname
                ),
                ($dbname === $_POST['formTemplate']) ? ' selected="selected"' : '', \sprintf(
                    '>%s</option>',
                    $dbname
                ) . \PHP_EOL;
            }
            $templatedbs->MoveNext();
        }
        echo "\t\t\t</select>" . \PHP_EOL;
        echo "\t\t</td>\n\t</tr>" . \PHP_EOL;

        // ENCODING
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strencoding']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\">" . \PHP_EOL;
        echo "\t\t\t<select name=\"formEncoding\">" . \PHP_EOL;
        echo "\t\t\t\t<option value=\"\"></option>" . \PHP_EOL;

        foreach ($data->codemap as $key) {
            echo "\t\t\t\t<option value=\"", \htmlspecialchars($key), '"',
            ($key === $_POST['formEncoding']) ? ' selected="selected"' : '', '>',
            $this->misc->printVal($key), '</option>' . \PHP_EOL;
        }
        echo "\t\t\t</select>" . \PHP_EOL;
        echo "\t\t</td>\n\t</tr>" . \PHP_EOL;

        if ($data->hasDatabaseCollation()) {
            $this->coalesceArr($_POST, 'formCollate', '');

            $this->coalesceArr($_POST, 'formCType', '');

            // LC_COLLATE
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strcollation']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">" . \PHP_EOL;
            echo "\t\t\t<input name=\"formCollate\" value=\"", \htmlspecialchars($_POST['formCollate']), '" />' . \PHP_EOL;
            echo "\t\t</td>\n\t</tr>" . \PHP_EOL;

            // LC_CTYPE
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strctype']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">" . \PHP_EOL;
            echo "\t\t\t<input name=\"formCType\" value=\"", \htmlspecialchars($_POST['formCType']), '" />' . \PHP_EOL;
            echo "\t\t</td>\n\t</tr>" . \PHP_EOL;
        }

        // Tablespace (if there are any)
        if ($data->hasTablespaces() && 0 < $tablespaces->RecordCount()) {
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strtablespace']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">\n\t\t\t<select name=\"formSpc\">" . \PHP_EOL;
            // Always offer the default (empty) option
            echo "\t\t\t\t<option value=\"\"",
            ('' === $_POST['formSpc']) ? ' selected="selected"' : '', '></option>' . \PHP_EOL;
            // Display all other tablespaces
            while (!$tablespaces->EOF) {
                $spcname = \htmlspecialchars($tablespaces->fields['spcname'] ?? '');
                echo \sprintf(
                    '				<option value="%s"',
                    $spcname
                ),
                ($spcname === $_POST['formSpc']) ? ' selected="selected"' : '', \sprintf(
                    '>%s</option>',
                    $spcname
                ) . \PHP_EOL;
                $tablespaces->MoveNext();
            }
            echo "\t\t\t</select>\n\t\t</td>\n\t</tr>" . \PHP_EOL;
        }

        // Comments (if available)
        if ($data->hasSharedComments()) {
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strcomment']
            ) . \PHP_EOL;
            echo "\t\t<td><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
            \htmlspecialchars($_POST['formComment']), "</textarea></td>\n\t</tr>" . \PHP_EOL;
        }

        echo '</table>' . \PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create" />' . \PHP_EOL;
        echo $this->view->form;
        echo \sprintf(
            '<input type="submit" value="%s" />',
            $this->lang['strcreate']
        ) . \PHP_EOL;
        echo \sprintf(
            '<input type="submit" name="cancel" value="%s"  /></p>%s',
            $this->lang['strcancel'],
            \PHP_EOL
        );
        echo '</form>' . \PHP_EOL;
    }

    /**
     * Actually creates the new view in the database.
     */
    public function doSaveCreate(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Default tablespace to null if it isn't set
        $this->coalesceArr($_POST, 'formSpc', '');

        // Default comment to blank if it isn't set
        $this->coalesceArr($_POST, 'formComment', '');

        // Default collate to blank if it isn't set
        $this->coalesceArr($_POST, 'formCollate', '');

        // Default ctype to blank if it isn't set
        $this->coalesceArr($_POST, 'formCType', '');

        // Check that they've given a name and a definition
        if ('' === $_POST['formName']) {
            $this->doCreate($this->lang['strdatabaseneedsname']);
        } else {
            $status = $data->createDatabase(
                $_POST['formName'],
                $_POST['formEncoding'],
                $_POST['formSpc'],
                $_POST['formComment'],
                $_POST['formTemplate'],
                $_POST['formCollate'],
                $_POST['formCType']
            );

            if (0 === $status) {
                $this->view->setReloadBrowser(true);
                $this->doDefault($this->lang['strdatabasecreated']);
            } else {
                $this->doCreate($this->lang['strdatabasecreatedbad']);
            }
        }
    }

    /**
     * Displays options for cluster download.
     *
     * @param mixed $msg
     */
    public function doExport($msg = '')
    {
        $this->printTrail('server');
        $this->printTabs('server', 'export');
        $this->printMsg($msg);

        $subject = 'server';
        $object = $_REQUEST['server'];

        echo $this->formHeader('dbexport');

        echo $this->dataOnly(true, true);

        echo $this->structureOnly();

        echo $this->structureAndData(true);

        $server_info = $this->misc->getServerInfo();

        echo $this->offerNoRoleExport(isset($server_info['pgVersion']) && 10 <= (float) (\mb_substr($server_info['pgVersion'], 0, 3)));

        // dumpall doesn't support gzip
        echo $this->displayOrDownload(false);

        echo $this->formFooter($subject, $object);
    }
}
