<?php

/**
 * PHPPgAdmin v6.0.0-beta.39
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class UsersController extends BaseController
{
    public $controller_name = 'UsersController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->printHeader($lang['strusers']);
        $this->printBody();

        switch ($action) {
            case 'changepassword':
                if (isset($_REQUEST['ok'])) {
                    $this->doChangePassword(false);
                } else {
                    $this->doAccount();
                }

                break;
            case 'confchangepassword':
                $this->doChangePassword(true);

                break;
            case 'account':
                $this->doAccount();

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
                    $this->doSaveEdit();
                }

                break;
            case 'edit':
                $this->doEdit();

                break;
            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * Show default list of users in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $renderUseExpires = function ($val) use ($lang) {
            return 'infinity' == $val ? $lang['strnever'] : htmlspecialchars($val);
        };

        $this->printTrail('server');
        $this->printTabs('server', 'users');
        $this->printMsg($msg);

        $users = $data->getUsers();

        $columns = [
            'user'      => [
                'title' => $lang['strusername'],
                'field' => Decorator::field('usename'),
            ],
            'superuser' => [
                'title' => $lang['strsuper'],
                'field' => Decorator::field('usesuper'),
                'type'  => 'yesno',
            ],
            'createdb'  => [
                'title' => $lang['strcreatedb'],
                'field' => Decorator::field('usecreatedb'),
                'type'  => 'yesno',
            ],
            'expires'   => [
                'title'  => $lang['strexpires'],
                'field'  => Decorator::field('useexpires'),
                'type'   => 'callback',
                'params' => ['function' => $renderUseExpires, 'null' => $lang['strnever']],
            ],
            'defaults'  => [
                'title' => $lang['strsessiondefaults'],
                'field' => Decorator::field('useconfig'),
            ],
            'actions'   => [
                'title' => $lang['stractions'],
            ],
        ];

        $actions = [
            'alter' => [
                'content' => $lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'users',
                        'urlvars' => [
                            'action'   => 'edit',
                            'username' => Decorator::field('usename'),
                        ],
                    ],
                ],
            ],
            'drop'  => [
                'content' => $lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'users',
                        'urlvars' => [
                            'action'   => 'confirm_drop',
                            'username' => Decorator::field('usename'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($users, $columns, $actions, 'users-users', $lang['strnousers']);

        $this->printNavLinks(['create' => [
            'attr'    => [
                'href' => [
                    'url'     => 'users',
                    'urlvars' => [
                        'action' => 'create',
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $lang['strcreateuser'],
        ]], 'users-users', get_defined_vars());
    }

    /**
     * If a user is not a superuser, then we have an 'account management' page
     * where they can change their password, etc.  We don't prevent them from
     * messing with the URL to gain access to other user admin stuff, because
     * the PostgreSQL permissions will prevent them changing anything anyway.
     *
     * @param mixed $msg
     */
    public function doAccount($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $server_info = $this->misc->getServerInfo();

        $userdata         = $data->getUser($server_info['username']);
        $_REQUEST['user'] = $server_info['username'];

        $this->printTrail('user');
        $this->printTabs('server', 'account');
        $this->printMsg($msg);

        if ($userdata->recordCount() > 0) {
            $userdata->fields['usesuper']    = $data->phpBool($userdata->fields['usesuper']);
            $userdata->fields['usecreatedb'] = $data->phpBool($userdata->fields['usecreatedb']);
            echo "<table>\n";
            echo "<tr><th class=\"data\">{$lang['strusername']}</th><th class=\"data\">{$lang['strsuper']}</th><th class=\"data\">{$lang['strcreatedb']}</th><th class=\"data\">{$lang['strexpires']}</th>";
            echo "<th class=\"data\">{$lang['strsessiondefaults']}</th>";
            echo "</tr>\n";
            echo "<tr>\n\t<td class=\"data1\">", $this->misc->printVal($userdata->fields['usename']), "</td>\n";
            echo "\t<td class=\"data1\">", $this->misc->printVal($userdata->fields['usesuper'], 'yesno'), "</td>\n";
            echo "\t<td class=\"data1\">", $this->misc->printVal($userdata->fields['usecreatedb'], 'yesno'), "</td>\n";
            echo "\t<td class=\"data1\">", ('infinity' == $userdata->fields['useexpires'] || is_null($userdata->fields['useexpires']) ? $lang['strnever'] : $this->misc->printVal($userdata->fields['useexpires'])), "</td>\n";
            echo "\t<td class=\"data1\">", $this->misc->printVal($userdata->fields['useconfig']), "</td>\n";
            echo "</tr>\n</table>\n";
        } else {
            echo "<p>{$lang['strnodata']}</p>\n";
        }

        $this->printNavLinks(['changepassword' => [
            'attr'    => [
                'href' => [
                    'url'     => 'users',
                    'urlvars' => [
                        'action' => 'confchangepassword',
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $lang['strchangepassword'],
        ]], 'users-account', get_defined_vars());
    }

    /**
     * Show confirmation of change password and actually change password.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doChangePassword($confirm, $msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $server_info = $this->misc->getServerInfo();

        if ($confirm) {
            $_REQUEST['user'] = $server_info['username'];
            $this->printTrail('user');
            $this->printTitle($lang['strchangepassword'], 'pg.user.alter');
            $this->printMsg($msg);

            if (!isset($_POST['password'])) {
                $_POST['password'] = '';
            }

            if (!isset($_POST['confirm'])) {
                $_POST['confirm'] = '';
            }

            echo '<form action="'.\SUBFOLDER."/src/views/users\" method=\"post\">\n";
            echo "<table>\n";
            echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strpassword']}</th>\n";
            echo "\t\t<td><input type=\"password\" name=\"password\" size=\"32\" value=\"",
            htmlspecialchars($_POST['password']), "\" /></td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strconfirm']}</th>\n";
            echo "\t\t<td><input type=\"password\" name=\"confirm\" size=\"32\" value=\"\" /></td>\n\t</tr>\n";
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"changepassword\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"ok\" value=\"{$lang['strok']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            echo "</p></form>\n";
        } else {
            // Check that password is minimum length
            if (strlen($_POST['password']) < $this->conf['min_password_length']) {
                $this->doChangePassword(true, $lang['strpasswordshort']);
            }

            // Check that password matches confirmation password
            elseif ($_POST['password'] != $_POST['confirm']) {
                $this->doChangePassword(true, $lang['strpasswordconfirm']);
            } else {
                $status = $data->changePassword(
                    $server_info['username'],
                    $_POST['password']
                );
                if (0 == $status) {
                    $this->doAccount($lang['strpasswordchanged']);
                } else {
                    $this->doAccount($lang['strpasswordchangedbad']);
                }
            }
        }
    }

    /**
     * Function to allow editing of a user.
     *
     * @param mixed $msg
     */
    public function doEdit($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('user');
        $this->printTitle($lang['stralter'], 'pg.user.alter');
        $this->printMsg($msg);

        $userdata = $data->getUser($_REQUEST['username']);

        if ($userdata->recordCount() > 0) {
            $server_info                     = $this->misc->getServerInfo();
            $canRename                       = $data->hasUserRename() && ($_REQUEST['username'] != $server_info['username']);
            $userdata->fields['usesuper']    = $data->phpBool($userdata->fields['usesuper']);
            $userdata->fields['usecreatedb'] = $data->phpBool($userdata->fields['usecreatedb']);

            if (!isset($_POST['formExpires'])) {
                if ($canRename) {
                    $_POST['newname'] = $userdata->fields['usename'];
                }

                if ($userdata->fields['usesuper']) {
                    $_POST['formSuper'] = '';
                }

                if ($userdata->fields['usecreatedb']) {
                    $_POST['formCreateDB'] = '';
                }

                $_POST['formExpires']  = 'infinity' == $userdata->fields['useexpires'] ? '' : $userdata->fields['useexpires'];
                $_POST['formPassword'] = '';
            }

            echo '<form action="'.\SUBFOLDER."/src/views/users\" method=\"post\">\n";
            echo "<table>\n";
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strusername']}</th>\n";
            echo "\t\t<td class=\"data1\">", ($canRename ? "<input name=\"newname\" size=\"15\" maxlength=\"{$data->_maxNameLen}\" value=\"".htmlspecialchars($_POST['newname']).'" />' : $this->misc->printVal($userdata->fields['usename'])), "</td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left\"><label for=\"formSuper\">{$lang['strsuper']}</label></th>\n";
            echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formSuper\" name=\"formSuper\"",
            (isset($_POST['formSuper'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left\"><label for=\"formCreateDB\">{$lang['strcreatedb']}</label></th>\n";
            echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCreateDB\" name=\"formCreateDB\"",
            (isset($_POST['formCreateDB'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strexpires']}</th>\n";
            echo "\t\t<td class=\"data1\"><input size=\"16\" name=\"formExpires\" value=\"", htmlspecialchars($_POST['formExpires']), "\" /></td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strpassword']}</th>\n";
            echo "\t\t<td class=\"data1\"><input type=\"password\" size=\"16\" name=\"formPassword\" value=\"", htmlspecialchars($_POST['formPassword']), "\" /></td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strconfirm']}</th>\n";
            echo "\t\t<td class=\"data1\"><input type=\"password\" size=\"16\" name=\"formConfirm\" value=\"\" /></td>\n\t</tr>\n";
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"save_edit\" />\n";
            echo '<input type="hidden" name="username" value="', htmlspecialchars($_REQUEST['username']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            echo "<p>{$lang['strnodata']}</p>\n";
        }
    }

    /**
     * Function to save after editing a user.
     */
    public function doSaveEdit()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        // Check name and password
        if (isset($_POST['newname']) && '' == $_POST['newname']) {
            $this->doEdit($lang['struserneedsname']);
        } elseif ($_POST['formPassword'] != $_POST['formConfirm']) {
            $this->doEdit($lang['strpasswordconfirm']);
        } else {
            if (isset($_POST['newname'])) {
                $status = $data->setRenameUser($_POST['username'], $_POST['formPassword'], isset($_POST['formCreateDB']), isset($_POST['formSuper']), $_POST['formExpires'], $_POST['newname']);
            } else {
                $status = $data->setUser($_POST['username'], $_POST['formPassword'], isset($_POST['formCreateDB']), isset($_POST['formSuper']), $_POST['formExpires']);
            }

            if (0 == $status) {
                $this->doDefault($lang['struserupdated']);
            } else {
                $this->doEdit($lang['struserupdatedbad']);
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
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('user');
            $this->printTitle($lang['strdrop'], 'pg.user.drop');

            echo '<p>', sprintf($lang['strconfdropuser'], $this->misc->printVal($_REQUEST['username'])), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/users\" method=\"post\">\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo '<input type="hidden" name="username" value="', htmlspecialchars($_REQUEST['username']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            $status = $data->dropUser($_REQUEST['username']);
            if (0 == $status) {
                $this->doDefault($lang['struserdropped']);
            } else {
                $this->doDefault($lang['struserdroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where they can enter a new user.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['formUsername'])) {
            $_POST['formUsername'] = '';
        }

        if (!isset($_POST['formPassword'])) {
            $_POST['formPassword'] = '';
        }

        if (!isset($_POST['formConfirm'])) {
            $_POST['formConfirm'] = '';
        }

        if (!isset($_POST['formExpires'])) {
            $_POST['formExpires'] = '';
        }

        $this->printTrail('server');
        $this->printTitle($lang['strcreateuser'], 'pg.user.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/users\" method=\"post\">\n";
        echo "<table>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strusername']}</th>\n";
        echo "\t\t<td class=\"data1\"><input size=\"15\" maxlength=\"{$data->_maxNameLen}\" name=\"formUsername\" value=\"", htmlspecialchars($_POST['formUsername']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strpassword']}</th>\n";
        echo "\t\t<td class=\"data1\"><input size=\"15\" type=\"password\" name=\"formPassword\" value=\"", htmlspecialchars($_POST['formPassword']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strconfirm']}</th>\n";
        echo "\t\t<td class=\"data1\"><input size=\"15\" type=\"password\" name=\"formConfirm\" value=\"", htmlspecialchars($_POST['formConfirm']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\"><label for=\"formSuper\">{$lang['strsuper']}</label></th>\n";
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formSuper\" name=\"formSuper\"",
        (isset($_POST['formSuper'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\"><label for=\"formCreateDB\">{$lang['strcreatedb']}</label></th>\n";
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCreateDB\" name=\"formCreateDB\"",
        (isset($_POST['formCreateDB'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strexpires']}</th>\n";
        echo "\t\t<td class=\"data1\"><input size=\"30\" name=\"formExpires\" value=\"", htmlspecialchars($_POST['formExpires']), "\" /></td>\n\t</tr>\n";
        echo "</table>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" name=\"create\" value=\"{$lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
    }

    /**
     * Actually creates the new user in the database.
     */
    public function doSaveCreate()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        // Check data
        if ('' == $_POST['formUsername']) {
            $this->doCreate($lang['struserneedsname']);
        } elseif ($_POST['formPassword'] != $_POST['formConfirm']) {
            $this->doCreate($lang['strpasswordconfirm']);
        } else {
            $status = $data->createUser(
                $_POST['formUsername'],
                $_POST['formPassword'],
                isset($_POST['formCreateDB']),
                isset($_POST['formSuper']),
                $_POST['formExpires'],
                []
            );
            if (0 == $status) {
                $this->doDefault($lang['strusercreated']);
            } else {
                $this->doCreate($lang['strusercreatedbad']);
            }
        }
    }
}
