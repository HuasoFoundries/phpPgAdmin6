<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class SchemasController extends BaseController
{
    use \PHPPgAdmin\Traits\ExportTrait;

    public $controller_title = 'strschemas';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        if ('subtree' === $this->action) {
            return $this->doSubTree();
        }

        if (isset($_POST['cancel'])) {
            $this->action = '';
        }

        $header_template = 'header.twig';

        \ob_start();

        switch ($this->action) {
            case 'create':
                if (isset($_POST['create'])) {
                    $this->doSaveCreate();
                } else {
                    $this->doCreate();
                }

                break;
            case 'alter':
                if (isset($_POST['alter'])) {
                    $this->doSaveAlter();
                } else {
                    $this->doAlter();
                }

                break;
            case 'drop':
                if (isset($_POST['drop'])) {
                    $this->doDrop(false);
                } else {
                    $this->doDrop(true);
                }

                break;
            case 'export':
                $this->doExport();

                break;

            default:
                $header_template = 'header_datatables.twig';
                $this->doDefault();

                break;
        }

        $output = \ob_get_clean();

        $this->printHeader($this->headerTitle(), null, true, $header_template);
        $this->printBody();

        echo $output;

        return $this->printFooter();
    }

    /**
     * Show default list of schemas in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('database');
        $this->printTabs('database', 'schemas');
        $this->printMsg($msg);

        // Check that the DB actually supports schemas
        $schemas = $data->getSchemas();
        $destination = $this->container->utils->getDestinationWithLastTab('schema');

        $columns = [
            'schema' => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('nspname'),
                'url' => self::SUBFOLDER . "{$destination}&amp;",
                'vars' => ['schema' => 'nspname'],
            ],
            'owner' => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('nspowner'),
            ],
            'schema_size' => [
                'title' => $this->lang['strsize'],
                'field' => Decorator::field('schema_size'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('nspcomment'),
            ],
        ];

        $actions = [
            'multiactions' => [
                'keycols' => ['nsp' => 'nspname'],
                'url' => 'schemas',
            ],
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'schemas',
                        'urlvars' => [
                            'action' => 'drop',
                            'nsp' => Decorator::field('nspname'),
                        ],
                    ],
                ],
                'multiaction' => 'drop',
            ],
            'privileges' => [
                'content' => $this->lang['strprivileges'],
                'attr' => [
                    'href' => [
                        'url' => 'privileges',
                        'urlvars' => [
                            'subject' => 'schema',
                            'schema' => Decorator::field('nspname'),
                        ],
                    ],
                ],
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'schemas',
                        'urlvars' => [
                            'action' => 'alter',
                            'schema' => Decorator::field('nspname'),
                        ],
                    ],
                ],
            ],
        ];

        if (!$data->hasAlterSchema()) {
            unset($actions['alter']);
        }

        echo $this->printTable($schemas, $columns, $actions, 'schemas-schemas', $this->lang['strnoschemas']);

        $this->printNavLinks(['create' => [
            'attr' => [
                'href' => [
                    'url' => 'schemas',
                    'urlvars' => [
                        'action' => 'create',
                        'server' => $_REQUEST['server'],
                        'database' => $_REQUEST['database'],
                    ],
                ],
            ],
            'content' => $this->lang['strcreateschema'],
        ]], 'schemas-schemas', \get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $schemas = $data->getSchemas();

        $reqvars = $this->misc->getRequestVars('schema');

        $attrs = [
            'text' => Decorator::field('nspname'),
            'icon' => 'Schema',
            'toolTip' => Decorator::field('nspcomment'),
            'action' => Decorator::redirecturl(
                'redirect',
                $reqvars,
                [
                    'subject' => 'schema',
                    'schema' => Decorator::field('nspname'),
                ]
            ),
            'branch' => Decorator::url(
                'schemas',
                $reqvars,
                [
                    'action' => 'subtree',
                    'schema' => Decorator::field('nspname'),
                ]
            ),
        ];

        return $this->printTree($schemas, $attrs, 'schemas');
    }

    public function doSubTree()
    {
        $tabs = $this->misc->getNavTabs('schema');

        $items = $this->adjustTabsForTree($tabs);

        $reqvars = $this->misc->getRequestVars('schema');

        $attrs = [
            'text' => Decorator::field('title'),
            'icon' => Decorator::field('icon'),
            'action' => Decorator::actionurl(
                Decorator::field('url'),
                $reqvars,
                Decorator::field('urlvars', [])
            ),
            'branch' => Decorator::url(
                Decorator::field('url'),
                $reqvars,
                Decorator::field('urlvars'),
                ['action' => 'tree']
            ),
        ];

        return $this->printTree($items, $attrs, 'schema');
    }

    /**
     * Displays a screen where they can enter a new schema.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $server_info = $this->misc->getServerInfo();

        $this->coalesceArr($_POST, 'formName', '');

        $this->coalesceArr($_POST, 'formAuth', $server_info['username']);

        $this->coalesceArr($_POST, 'formSpc', '');

        $this->coalesceArr($_POST, 'formComment', '');

        // Fetch all users from the database
        $users = $data->getUsers();

        $this->printTrail('database');
        $this->printTitle($this->lang['strcreateschema'], 'pg.schema.create');
        $this->printMsg($msg);

        echo '<form action="' . self::SUBFOLDER . '/src/views/schemas" method="post">' . \PHP_EOL;
        echo '<table style="width: 100%">' . \PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>" . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input name=\"formName\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        \htmlspecialchars($_POST['formName']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        // Owner
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strowner']}</th>" . \PHP_EOL;
        echo "\t\t<td class=\"data1\">\n\t\t\t<select name=\"formAuth\">" . \PHP_EOL;

        while (!$users->EOF) {
            $uname = \htmlspecialchars($users->fields['usename']);
            echo "\t\t\t\t<option value=\"{$uname}\"",
            ($uname === $_POST['formAuth']) ? ' selected="selected"' : '', ">{$uname}</option>" . \PHP_EOL;
            $users->moveNext();
        }
        echo "\t\t\t</select>\n\t\t</td>\n\t</tr>" . \PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>" . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
        \htmlspecialchars($_POST['formComment']), "</textarea></td>\n\t</tr>" . \PHP_EOL;

        echo '</table>' . \PHP_EOL;
        echo '<p>' . \PHP_EOL;
        echo '<input type="hidden" name="action" value="create" />' . \PHP_EOL;
        echo '<input type="hidden" name="database" value="', \htmlspecialchars($_REQUEST['database']), '" />' . \PHP_EOL;
        echo $this->misc->form;
        echo "<input type=\"submit\" name=\"create\" value=\"{$this->lang['strcreate']}\" />" . \PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />" . \PHP_EOL;
        echo '</p>' . \PHP_EOL;
        echo '</form>' . \PHP_EOL;
    }

    /**
     * Actually creates the new schema in the database.
     */
    public function doSaveCreate(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name
        if ('' === $_POST['formName']) {
            $this->doCreate($this->lang['strschemaneedsname']);
        } else {
            $status = $data->createSchema($_POST['formName'], $_POST['formAuth'], $_POST['formComment']);

            if (0 === $status) {
                $this->misc->setReloadBrowser(true);
                $this->doDefault($this->lang['strschemacreated']);
            } else {
                $this->doCreate($this->lang['strschemacreatedbad']);
            }
        }
    }

    /**
     * Display a form to permit editing schema properies.
     * TODO: permit changing owner.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTitle($this->lang['stralter'], 'pg.schema.alter');
        $this->printMsg($msg);

        $schema = $data->getSchemaByName($_REQUEST['schema']);

        if (0 < $schema->recordCount()) {
            $this->coalesceArr($_POST, 'comment', $schema->fields['nspcomment']);

            $this->coalesceArr($_POST, 'schema', $_REQUEST['schema']);

            $this->coalesceArr($_POST, 'name', $_REQUEST['schema']);

            $this->coalesceArr($_POST, 'owner', $schema->fields['ownername']);

            echo '<form action="' . self::SUBFOLDER . '/src/views/schemas" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;

            echo "\t<tr>" . \PHP_EOL;
            echo "\t\t<th class=\"data left required\">{$this->lang['strname']}</th>" . \PHP_EOL;
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            \htmlspecialchars($_POST['name']), '" />' . \PHP_EOL;
            echo "\t\t</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;

            if ($data->hasAlterSchemaOwner()) {
                $users = $data->getUsers();
                echo "<tr><th class=\"data left required\">{$this->lang['strowner']}</th>" . \PHP_EOL;
                echo '<td class="data2"><select name="owner">';

                while (!$users->EOF) {
                    $uname = $users->fields['usename'];
                    echo '<option value="', \htmlspecialchars($uname), '"',
                    ($uname === $_POST['owner']) ? ' selected="selected"' : '', '>', \htmlspecialchars($uname), '</option>' . \PHP_EOL;
                    $users->moveNext();
                }
                echo '</select></td></tr>' . \PHP_EOL;
            } else {
                echo "<input name=\"owner\" value=\"{$_POST['owner']}\" type=\"hidden\" />";
            }

            echo "\t<tr>" . \PHP_EOL;
            echo "\t\t<th class=\"data\">{$this->lang['strcomment']}</th>" . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><textarea cols=\"32\" rows=\"3\" name=\"comment\">", \htmlspecialchars($_POST['comment']), '</textarea></td>' . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="alter" />' . \PHP_EOL;
            echo '<input type="hidden" name="schema" value="', \htmlspecialchars($_POST['schema']), '" />' . \PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />" . \PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>" . \PHP_EOL;
            echo '</form>' . \PHP_EOL;
        } else {
            echo "<p>{$this->lang['strnodata']}</p>" . \PHP_EOL;
        }
    }

    /**
     * Save the form submission containing changes to a schema.
     *
     * @param mixed $msg
     */
    public function doSaveAlter(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->updateSchema($_POST['schema'], $_POST['comment'], $_POST['name'], $_POST['owner']);

        if (0 === $status) {
            $this->misc->setReloadBrowser(true);
            $this->doDefault($this->lang['strschemaaltered']);
        } else {
            $this->doAlter($this->lang['strschemaalteredbad']);
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

        if (empty($_REQUEST['nsp']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($this->lang['strspecifyschematodrop']);
        }

        if ($confirm) {
            $this->printTrail('schema');
            $this->printTitle($this->lang['strdrop'], 'pg.schema.drop');

            echo '<form action="' . self::SUBFOLDER . '/src/views/schemas" method="post">' . \PHP_EOL;
            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    echo '<p>', \sprintf($this->lang['strconfdropschema'], $this->misc->printVal($a['nsp'])), '</p>' . \PHP_EOL;
                    echo '<input type="hidden" name="nsp[]" value="', \htmlspecialchars($a['nsp']), '" />' . \PHP_EOL;
                }
            } else {
                echo '<p>', \sprintf($this->lang['strconfdropschema'], $this->misc->printVal($_REQUEST['nsp'])), '</p>' . \PHP_EOL;
                echo '<input type="hidden" name="nsp" value="', \htmlspecialchars($_REQUEST['nsp']), '" />' . \PHP_EOL;
            }

            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>" . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="drop" />' . \PHP_EOL;
            echo '<input type="hidden" name="database" value="', \htmlspecialchars($_REQUEST['database']), '" />' . \PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />" . \PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>" . \PHP_EOL;
            echo '</form>' . \PHP_EOL;
        } else {
            if (\is_array($_POST['nsp'])) {
                $msg = '';
                $status = $data->beginTransaction();

                if (0 === $status) {
                    foreach ($_POST['nsp'] as $s) {
                        $status = $data->dropSchema($s, isset($_POST['cascade']));

                        if (0 === $status) {
                            $msg .= \sprintf('%s: %s<br />', \htmlentities($s, \ENT_QUOTES, 'UTF-8'), $this->lang['strschemadropped']);
                        } else {
                            $data->endTransaction();
                            $this->doDefault(\sprintf('%s%s: %s<br />', $msg, \htmlentities($s, \ENT_QUOTES, 'UTF-8'), $this->lang['strschemadroppedbad']));

                            return;
                        }
                    }
                }

                if (0 === $data->endTransaction()) {
                    // Everything went fine, back to the Default page....
                    $this->misc->setReloadBrowser(true);
                    $this->doDefault($msg);
                } else {
                    $this->doDefault($this->lang['strschemadroppedbad']);
                }
            } else {
                $status = $data->dropSchema($_POST['nsp'], isset($_POST['cascade']));

                if (0 === $status) {
                    $this->misc->setReloadBrowser(true);
                    $this->doDefault($this->lang['strschemadropped']);
                } else {
                    $this->doDefault($this->lang['strschemadroppedbad']);
                }
            }
        }
    }

    /**
     * Displays options for database download.
     *
     * @param mixed $msg
     */
    public function doExport($msg = ''): void
    {
        $this->printTrail('schema');
        $this->printTabs('schema', 'export');
        $this->printMsg($msg);

        $subject = 'schema';
        $object = $_REQUEST['schema'];

        echo $this->formHeader('dbexport');

        echo $this->dataOnly(true, true);

        echo $this->structureOnly();

        echo $this->structureAndData(true);

        echo $this->displayOrDownload(!(\mb_strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS'])));

        echo $this->formFooter($subject, $object);
    }
}
