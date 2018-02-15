<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class GroupsController extends BaseController
{
    public $controller_name = 'GroupsController';

    /**
     * Default method to render the controller according to the action parameter
     */
    public function render()
    {
        $this->printHeader($lang['strgroups']);
        $this->printBody();

        switch ($action) {
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
            case 'save_edit':
                $this->doSaveEdit();

                break;
            case 'edit':
                $this->doEdit();

                break;
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
     * Show default list of groups in the database
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('server');
        $this->printTabs('server', 'groups');
        $this->printMsg($msg);

        $groups = $data->getGroups();

        $columns = [
            'group'   => [
                'title' => $lang['strgroup'],
                'field' => Decorator::field('groname'),
                'url'   => "groups.php?action=properties&amp;{$this->misc->href}&amp;",
                'vars'  => ['group' => 'groname'],
            ],
            'actions' => [
                'title' => $lang['stractions'],
            ],
        ];

        $actions = [
            'drop' => [
                'content' => $lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'groups.php',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'group'  => Decorator::field('groname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($groups, $columns, $actions, 'groups-properties', $lang['strnogroups']);

        $this->printNavLinks(['create' => [
            'attr'    => [
                'href' => [
                    'url'     => 'groups.php',
                    'urlvars' => [
                        'action' => 'create',
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $lang['strcreategroup'],
        ]], 'groups-groups', get_defined_vars());
    }

    /**
     * Add user to a group
     */
    public function doAddMember()
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->addGroupMember($_REQUEST['group'], $_REQUEST['user']);
        if (0 == $status) {
            $this->doProperties($lang['strmemberadded']);
        } else {
            $this->doProperties($lang['strmemberaddedbad']);
        }
    }

    /**
     * Show confirmation of drop user from group and perform actual drop
     * @param mixed $confirm
     */
    public function doDropMember($confirm)
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('group');
            $this->printTitle($lang['strdropmember'], 'pg.group.alter');

            echo '<p>', sprintf($lang['strconfdropmember'], $this->misc->printVal($_REQUEST['user']), $this->misc->printVal($_REQUEST['group'])), "</p>\n";

            echo '<form action="' . SUBFOLDER . "/src/views/groups.php\" method=\"post\">\n";
            echo $this->misc->form;
            echo "<input type=\"hidden\" name=\"action\" value=\"drop_member\" />\n";
            echo '<input type="hidden" name="group" value="', htmlspecialchars($_REQUEST['group']), "\" />\n";
            echo '<input type="hidden" name="user" value="', htmlspecialchars($_REQUEST['user']), "\" />\n";
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            echo "</form>\n";
        } else {
            $status = $data->dropGroupMember($_REQUEST['group'], $_REQUEST['user']);
            if (0 == $status) {
                $this->doProperties($lang['strmemberdropped']);
            } else {
                $this->doDropMember(true, $lang['strmemberdroppedbad']);
            }
        }
    }

    /**
     * Show read only properties for a group
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['user'])) {
            $_POST['user'] = '';
        }

        $this->printTrail('group');
        $this->printTitle($lang['strproperties'], 'pg.group');
        $this->printMsg($msg);

        $groupdata = $data->getGroup($_REQUEST['group']);
        $users     = $data->getUsers();

        if ($groupdata->recordCount() > 0) {
            $columns = [
                'members' => [
                    'title' => $lang['strmembers'],
                    'field' => Decorator::field('usename'),
                ],
                'actions' => [
                    'title' => $lang['stractions'],
                ],
            ];

            $actions = [
                'drop' => [
                    'content' => $lang['strdrop'],
                    'attr'    => [
                        'href' => [
                            'url'     => 'groups.php',
                            'urlvars' => [
                                'action' => 'confirm_drop_member',
                                'group'  => $_REQUEST['group'],
                                'user'   => Decorator::field('usename'),
                            ],
                        ],
                    ],
                ],
            ];

            echo $this->printTable($groupdata, $columns, $actions, 'groups-members', $lang['strnousers']);
        }

        // Display form for adding a user to the group
        echo '<form action="' . SUBFOLDER . "/src/views/groups.php\" method=\"post\">\n";
        echo '<select name="user">';
        while (!$users->EOF) {
            $uname = $this->misc->printVal($users->fields['usename']);
            echo "<option value=\"{$uname}\"",
            ($uname == $_POST['user']) ? ' selected="selected"' : '', ">{$uname}</option>\n";
            $users->moveNext();
        }
        echo "</select>\n";
        echo "<input type=\"submit\" value=\"{$lang['straddmember']}\" />\n";
        echo $this->misc->form;
        echo '<input type="hidden" name="group" value="', htmlspecialchars($_REQUEST['group']), "\" />\n";
        echo "<input type=\"hidden\" name=\"action\" value=\"add_member\" />\n";
        echo "</form>\n";

        $this->printNavLinks(['showall' => [
            'attr'    => [
                'href' => [
                    'url'     => 'groups.php',
                    'urlvars' => [
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $lang['strshowallgroups'],
        ]], 'groups-properties', get_defined_vars());
    }

    /**
     * Show confirmation of drop and perform actual drop
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('group');
            $this->printTitle($lang['strdrop'], 'pg.group.drop');

            echo '<p>', sprintf($lang['strconfdropgroup'], $this->misc->printVal($_REQUEST['group'])), "</p>\n";

            echo '<form action="' . SUBFOLDER . "/src/views/groups.php\" method=\"post\">\n";
            echo $this->misc->form;
            echo "<input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo '<input type="hidden" name="group" value="', htmlspecialchars($_REQUEST['group']), "\" />\n";
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            echo "</form>\n";
        } else {
            $status = $data->dropGroup($_REQUEST['group']);
            if (0 == $status) {
                $this->doDefault($lang['strgroupdropped']);
            } else {
                $this->doDefault($lang['strgroupdroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where they can enter a new group
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();
        if (!isset($_POST['name'])) {
            $_POST['name'] = '';
        }

        if (!isset($_POST['members'])) {
            $_POST['members'] = [];
        }

        // Fetch a list of all users in the cluster
        $users = $data->getUsers();

        $this->printTrail('server');
        $this->printTitle($lang['strcreategroup'], 'pg.group.create');
        $this->printMsg($msg);

        echo "<form action=\"\" method=\"post\">\n";
        echo $this->misc->form;
        echo "<table>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strname']}</th>\n";
        echo "\t\t<td class=\"data\"><input size=\"32\" maxlength=\"{$data->_maxNameLen}\" name=\"name\" value=\"", htmlspecialchars($_POST['name']), "\" /></td>\n\t</tr>\n";
        if ($users->recordCount() > 0) {
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strmembers']}</th>\n";

            echo "\t\t<td class=\"data\">\n";
            echo "\t\t\t<select name=\"members[]\" multiple=\"multiple\" size=\"", min(40, $users->recordCount()), "\">\n";
            while (!$users->EOF) {
                $username = $users->fields['usename'];
                echo "\t\t\t\t<option value=\"{$username}\"",
                (in_array($username, $_POST['members'], true) ? ' selected="selected"' : ''), '>', $this->misc->printVal($username), "</option>\n";
                $users->moveNext();
            }
            echo "\t\t\t</select>\n";
            echo "\t\t</td>\n\t</tr>\n";
        }
        echo "</table>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo "<input type=\"submit\" value=\"{$lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
    }

    /**
     * Actually creates the new group in the database
     */
    public function doSaveCreate()
    {
        $conf = $this->conf;

        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['members'])) {
            $_POST['members'] = [];
        }

        // Check form vars
        if ('' == trim($_POST['name'])) {
            $this->doCreate($lang['strgroupneedsname']);
        } else {
            $status = $data->createGroup($_POST['name'], $_POST['members']);
            if (0 == $status) {
                $this->doDefault($lang['strgroupcreated']);
            } else {
                $this->doCreate($lang['strgroupcreatedbad']);
            }
        }
    }
}
