<?php

/**
 * PHPPgAdmin v6.0.0-RC1.
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class GroupsController extends BaseController
{
    public $controller_title = 'strgroups';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            case 'add_member':
                $this->doAddMember();

                break;
            case 'drop_member':
                if (isset($_REQUEST['drop'])) {
                    $this->doDropMember(false);
                } else {
                    $this->doProperties();
                }

                break;
            case 'confirm_drop_member':
                $this->doDropMember(true);

                break;
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
                if (isset($_REQUEST['drop'])) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            /*case 'save_edit':
            $this->doSaveEdit();

            break;
            case 'edit':
            $this->doEdit();

            break;*/
            case 'properties':
                $this->doProperties();

                break;
            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * Show default list of groups in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('server');
        $this->printTabs('server', 'groups');
        $this->printMsg($msg);

        $groups = $data->getGroups();

        $columns = [
            'group'   => [
                'title' => $this->lang['strgroup'],
                'field' => Decorator::field('groname'),
                'url'   => "groups?action=properties&amp;{$this->misc->href}&amp;",
                'vars'  => ['group' => 'groname'],
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
        ];

        $actions = [
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'groups',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'group'  => Decorator::field('groname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($groups, $columns, $actions, 'groups-properties', $this->lang['strnogroups']);

        $this->printNavLinks(['create' => [
            'attr'    => [
                'href' => [
                    'url'     => 'groups',
                    'urlvars' => [
                        'action' => 'create',
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $this->lang['strcreategroup'],
        ]], 'groups-groups', get_defined_vars());
    }

    /**
     * Add user to a group.
     */
    public function doAddMember()
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->addGroupMember($_REQUEST['group'], $_REQUEST['user']);
        if (0 == $status) {
            $this->doProperties($this->lang['strmemberadded']);
        } else {
            $this->doProperties($this->lang['strmemberaddedbad']);
        }
    }

    /**
     * Show confirmation of drop user from group and perform actual drop.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doDropMember($confirm, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($msg) {
            $this->printMsg($msg);
        }

        if ($confirm) {
            $this->printTrail('group');
            $this->printTitle($this->lang['strdropmember'], 'pg.group.alter');

            echo '<p>', sprintf($this->lang['strconfdropmember'], $this->misc->printVal($_REQUEST['user']), $this->misc->printVal($_REQUEST['group'])), '</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/groups" method="post">'.PHP_EOL;
            echo $this->misc->form;
            echo '<input type="hidden" name="action" value="drop_member" />'.PHP_EOL;
            echo '<input type="hidden" name="group" value="', htmlspecialchars($_REQUEST['group']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="user" value="', htmlspecialchars($_REQUEST['user']), '" />'.PHP_EOL;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->dropGroupMember($_REQUEST['group'], $_REQUEST['user']);
            if (0 == $status) {
                $this->doProperties($this->lang['strmemberdropped']);
            } else {
                $this->doDropMember(true, $this->lang['strmemberdroppedbad']);
            }
        }
    }

    /**
     * Show read only properties for a group.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'user', '');

        $this->printTrail('group');
        $this->printTitle($this->lang['strproperties'], 'pg.group');
        $this->printMsg($msg);

        $groupdata = $data->getGroup($_REQUEST['group']);
        $users     = $data->getUsers();

        if ($groupdata->recordCount() > 0) {
            $columns = [
                'members' => [
                    'title' => $this->lang['strmembers'],
                    'field' => Decorator::field('usename'),
                ],
                'actions' => [
                    'title' => $this->lang['stractions'],
                ],
            ];

            $actions = [
                'drop' => [
                    'content' => $this->lang['strdrop'],
                    'attr'    => [
                        'href' => [
                            'url'     => 'groups',
                            'urlvars' => [
                                'action' => 'confirm_drop_member',
                                'group'  => $_REQUEST['group'],
                                'user'   => Decorator::field('usename'),
                            ],
                        ],
                    ],
                ],
            ];

            echo $this->printTable($groupdata, $columns, $actions, 'groups-members', $this->lang['strnousers']);
        }

        // Display form for adding a user to the group
        echo '<form action="'.\SUBFOLDER.'/src/views/groups" method="post">'.PHP_EOL;
        echo '<select name="user">';
        while (!$users->EOF) {
            $uname = $this->misc->printVal($users->fields['usename']);
            echo "<option value=\"{$uname}\"",
            ($uname == $_POST['user']) ? ' selected="selected"' : '', ">{$uname}</option>".PHP_EOL;
            $users->moveNext();
        }
        echo '</select>'.PHP_EOL;
        echo "<input type=\"submit\" value=\"{$this->lang['straddmember']}\" />".PHP_EOL;
        echo $this->misc->form;
        echo '<input type="hidden" name="group" value="', htmlspecialchars($_REQUEST['group']), '" />'.PHP_EOL;
        echo '<input type="hidden" name="action" value="add_member" />'.PHP_EOL;
        echo '</form>'.PHP_EOL;

        $this->printNavLinks(['showall' => [
            'attr'    => [
                'href' => [
                    'url'     => 'groups',
                    'urlvars' => [
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $this->lang['strshowallgroups'],
        ]], 'groups-properties', get_defined_vars());
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('group');
            $this->printTitle($this->lang['strdrop'], 'pg.group.drop');

            echo '<p>', sprintf($this->lang['strconfdropgroup'], $this->misc->printVal($_REQUEST['group'])), '</p>'.PHP_EOL;

            echo '<form action="'.\SUBFOLDER.'/src/views/groups" method="post">'.PHP_EOL;
            echo $this->misc->form;
            echo '<input type="hidden" name="action" value="drop" />'.PHP_EOL;
            echo '<input type="hidden" name="group" value="', htmlspecialchars($_REQUEST['group']), '" />'.PHP_EOL;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->dropGroup($_REQUEST['group']);
            if (0 == $status) {
                $this->doDefault($this->lang['strgroupdropped']);
            } else {
                $this->doDefault($this->lang['strgroupdroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where they can enter a new group.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();
        $this->coalesceArr($_POST, 'name', '');

        $this->coalesceArr($_POST, 'members', []);

        // Fetch a list of all users in the cluster
        $users = $data->getUsers();

        $this->printTrail('server');
        $this->printTitle($this->lang['strcreategroup'], 'pg.group.create');
        $this->printMsg($msg);

        echo '<form action="" method="post">'.PHP_EOL;
        echo $this->misc->form;
        echo '<table>'.PHP_EOL;
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>".PHP_EOL;
        echo "\t\t<td class=\"data\"><input size=\"32\" maxlength=\"{$data->_maxNameLen}\" name=\"name\" value=\"", htmlspecialchars($_POST['name']), "\" /></td>\n\t</tr>".PHP_EOL;
        if ($users->recordCount() > 0) {
            echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strmembers']}</th>".PHP_EOL;

            echo "\t\t<td class=\"data\">".PHP_EOL;
            echo "\t\t\t<select name=\"members[]\" multiple=\"multiple\" size=\"", min(40, $users->recordCount()), '">'.PHP_EOL;
            while (!$users->EOF) {
                $username = $users->fields['usename'];
                echo "\t\t\t\t<option value=\"{$username}\"",
                (in_array($username, $_POST['members'], true) ? ' selected="selected"' : ''), '>', $this->misc->printVal($username), '</option>'.PHP_EOL;
                $users->moveNext();
            }
            echo "\t\t\t</select>".PHP_EOL;
            echo "\t\t</td>\n\t</tr>".PHP_EOL;
        }
        echo '</table>'.PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create" />'.PHP_EOL;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;
    }

    /**
     * Actually creates the new group in the database.
     */
    public function doSaveCreate()
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'members', []);

        // Check form vars
        if ('' == trim($_POST['name'])) {
            $this->doCreate($this->lang['strgroupneedsname']);
        } else {
            $status = $data->createGroup($_POST['name'], $_POST['members']);
            if (0 == $status) {
                $this->doDefault($this->lang['strgroupcreated']);
            } else {
                $this->doCreate($this->lang['strgroupcreatedbad']);
            }
        }
    }
}
