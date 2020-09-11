<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class TablespacesController extends BaseController
{
    public $controller_title = 'strtablespaces';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render(): void
    {
        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            case 'save_create':
                if (isset($_REQUEST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'create':
                $this->doCreate();

                break;
            case 'drop':
                if (isset($_REQUEST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doDrop(false);
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'save_edit':
                if (isset($_REQUEST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveAlter();
                }

                break;
            case 'edit':
                $this->doAlter();

                break;

            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * Show default list of tablespaces in the cluster.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('server');
        $this->printTabs('server', 'tablespaces');
        $this->printMsg($msg);

        $tablespaces = $data->getTablespaces();

        $columns = [
            'database' => [
                'title' => $this->lang['strname'],
                'field' => \PHPPgAdmin\Decorators\Decorator::field('spcname'),
            ],
            'owner' => [
                'title' => $this->lang['strowner'],
                'field' => \PHPPgAdmin\Decorators\Decorator::field('spcowner'),
            ],
            'location' => [
                'title' => $this->lang['strlocation'],
                'field' => \PHPPgAdmin\Decorators\Decorator::field('spclocation'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
        ];

        if ($data->hasSharedComments()) {
            $columns['comment'] = [
                'title' => $this->lang['strcomment'],
                'field' => \PHPPgAdmin\Decorators\Decorator::field('spccomment'),
            ];
        }

        $actions = [
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'tablespaces',
                        'urlvars' => [
                            'action' => 'edit',
                            'tablespace' => \PHPPgAdmin\Decorators\Decorator::field('spcname'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'tablespaces',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'tablespace' => \PHPPgAdmin\Decorators\Decorator::field('spcname'),
                        ],
                    ],
                ],
            ],
            'privileges' => [
                'content' => $this->lang['strprivileges'],
                'attr' => [
                    'href' => [
                        'url' => 'privileges',
                        'urlvars' => [
                            'subject' => 'tablespace',
                            'tablespace' => \PHPPgAdmin\Decorators\Decorator::field('spcname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($tablespaces, $columns, $actions, 'tablespaces-tablespaces', $this->lang['strnotablespaces']);

        $this->printNavLinks(['create' => [
            'attr' => [
                'href' => [
                    'url' => 'tablespaces',
                    'urlvars' => [
                        'action' => 'create',
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $this->lang['strcreatetablespace'],
        ]], 'tablespaces-tablespaces', \get_defined_vars());
    }

    /**
     * Function to allow altering of a tablespace.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('tablespace');
        $this->printTitle($this->lang['stralter'], 'pg.tablespace.alter');
        $this->printMsg($msg);

        // Fetch tablespace info
        $tablespace = $data->getTablespace($_REQUEST['tablespace']);
        // Fetch all users
        $users = $data->getUsers();

        if (0 < $tablespace->recordCount()) {
            $this->coalesceArr($_POST, 'name', $tablespace->fields['spcname']);

            $this->coalesceArr($_POST, 'owner', $tablespace->fields['spcowner']);

            $this->coalesceArr($_POST, 'comment', ($data->hasSharedComments()) ? $tablespace->fields['spccomment'] : '');

            echo '<form action="' . self::SUBFOLDER . '/src/views/tablespaces" method="post">' . \PHP_EOL;
            echo $this->view->form;
            echo '<table>' . \PHP_EOL;
            echo "<tr><th class=\"data left required\">{$this->lang['strname']}</th>" . \PHP_EOL;
            echo '<td class="data1">';
            echo "<input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            \htmlspecialchars($_POST['name']), '" /></td></tr>' . \PHP_EOL;
            echo "<tr><th class=\"data left required\">{$this->lang['strowner']}</th>" . \PHP_EOL;
            echo '<td class="data1"><select name="owner">';

            while (!$users->EOF) {
                $uname = $users->fields['usename'];
                echo '<option value="', \htmlspecialchars($uname), '"',
                ($uname === $_POST['owner']) ? ' selected="selected"' : '', '>', \htmlspecialchars($uname), '</option>' . \PHP_EOL;
                $users->moveNext();
            }
            echo '</select></td></tr>' . \PHP_EOL;

            if ($data->hasSharedComments()) {
                echo "<tr><th class=\"data left\">{$this->lang['strcomment']}</th>" . \PHP_EOL;
                echo '<td class="data1">';
                echo '<textarea rows="3" cols="32" name="comment">',
                \htmlspecialchars($_POST['comment']), '</textarea></td></tr>' . \PHP_EOL;
            }
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="save_edit" />' . \PHP_EOL;
            echo '<input type="hidden" name="tablespace" value="', \htmlspecialchars($_REQUEST['tablespace']), '" />' . \PHP_EOL;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />" . \PHP_EOL;
            echo \sprintf('<input type="submit" name="cancel" value="%s"  /></p>%s', $this->lang['strcancel'], \PHP_EOL);
            echo '</form>' . \PHP_EOL;
        } else {
            echo "<p>{$this->lang['strnodata']}</p>" . \PHP_EOL;
        }
    }

    /**
     * Function to save after altering a tablespace.
     */
    public function doSaveAlter(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check data
        if ('' === \trim($_POST['name'])) {
            $this->doAlter($this->lang['strtablespaceneedsname']);
        } else {
            $status = $data->alterTablespace($_POST['tablespace'], $_POST['name'], $_POST['owner'], $_POST['comment']);

            if (0 === $status) {
                // If tablespace has been renamed, need to change to the new name
                if ($_POST['tablespace'] !== $_POST['name']) {
                    // Jump them to the new table name
                    $_REQUEST['tablespace'] = $_POST['name'];
                }
                $this->doDefault($this->lang['strtablespacealtered']);
            } else {
                $this->doAlter($this->lang['strtablespacealteredbad']);
            }
        }
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('tablespace');
            $this->printTitle($this->lang['strdrop'], 'pg.tablespace.drop');

            echo '<p>', \sprintf($this->lang['strconfdroptablespace'], $this->misc->printVal($_REQUEST['tablespace'])), '</p>' . \PHP_EOL;

            echo '<form action="' . self::SUBFOLDER . '/src/views/tablespaces" method="post">' . \PHP_EOL;
            echo $this->view->form;
            echo '<input type="hidden" name="action" value="drop" />' . \PHP_EOL;
            echo '<input type="hidden" name="tablespace" value="', \htmlspecialchars($_REQUEST['tablespace']), '" />' . \PHP_EOL;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />" . \PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />" . \PHP_EOL;
            echo '</form>' . \PHP_EOL;
        } else {
            $status = $data->droptablespace($_REQUEST['tablespace']);

            if (0 === $status) {
                $this->doDefault($this->lang['strtablespacedropped']);
            } else {
                $this->doDefault($this->lang['strtablespacedroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where they can enter a new tablespace.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $server_info = $this->misc->getServerInfo();

        $this->coalesceArr($_POST, 'formSpcname', '');

        $this->coalesceArr($_POST, 'formOwner', $server_info['username']);

        $this->coalesceArr($_POST, 'formLoc', '');

        $this->coalesceArr($_POST, 'formComment', '');

        // Fetch all users
        $users = $data->getUsers();

        $this->printTrail('server');
        $this->printTitle($this->lang['strcreatetablespace'], 'pg.tablespace.create');
        $this->printMsg($msg);

        echo '<form action="' . self::SUBFOLDER . '/src/views/tablespaces" method="post">' . \PHP_EOL;
        echo $this->view->form;
        echo '<table>' . \PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>" . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"32\" name=\"formSpcname\" maxlength=\"{$data->_maxNameLen}\" value=\"", \htmlspecialchars($_POST['formSpcname']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strowner']}</th>" . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><select name=\"formOwner\">" . \PHP_EOL;

        while (!$users->EOF) {
            $uname = $users->fields['usename'];
            echo "\t\t\t<option value=\"", \htmlspecialchars($uname), '"',
            ($uname === $_POST['formOwner']) ? ' selected="selected"' : '', '>', \htmlspecialchars($uname), '</option>' . \PHP_EOL;
            $users->moveNext();
        }
        echo "\t\t</select></td>\n\t</tr>" . \PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strlocation']}</th>" . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"32\" name=\"formLoc\" value=\"", \htmlspecialchars($_POST['formLoc']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        // Comments (if available)
        if ($data->hasSharedComments()) {
            echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>" . \PHP_EOL;
            echo "\t\t<td><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
            \htmlspecialchars($_POST['formComment']), "</textarea></td>\n\t</tr>" . \PHP_EOL;
        }
        echo '</table>' . \PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create" />' . \PHP_EOL;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />" . \PHP_EOL;
        echo \sprintf('<input type="submit" name="cancel" value="%s"  /></p>%s', $this->lang['strcancel'], \PHP_EOL);
        echo '</form>' . \PHP_EOL;
    }

    /**
     * Actually creates the new tablespace in the cluster.
     */
    public function doSaveCreate(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check data
        if ('' === \trim($_POST['formSpcname'])) {
            $this->doCreate($this->lang['strtablespaceneedsname']);
        } elseif ('' === \trim($_POST['formLoc'])) {
            $this->doCreate($this->lang['strtablespaceneedsloc']);
        } else {
            // Default comment to blank if it isn't set
            $this->coalesceArr($_POST, 'formComment', null);

            $status = $data->createTablespace($_POST['formSpcname'], $_POST['formOwner'], $_POST['formLoc'], $_POST['formComment']);

            if (0 === $status) {
                $this->doDefault($this->lang['strtablespacecreated']);
            } else {
                $this->doCreate($this->lang['strtablespacecreatedbad']);
            }
        }
    }
}
