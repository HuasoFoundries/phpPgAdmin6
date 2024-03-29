<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class UsersController extends BaseController
{
    public $controller_title = 'strusers';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->printHeader();

        $this->action = isset($_REQUEST['cancel']) ? 'cancel' : $this->action;

        switch ($this->action) {
            case 'cancel':
                 $this->printBody();
                $this->doDefault();

                break;
            case 'changepassword':
                if (isset($_REQUEST['ok'])) {
                    $this->printBody();
                    $this->doChangePassword(false);
                } else {
                    $this->printBody();
                    $this->doAccount();
                }

                break;
            case 'confchangepassword':
                 $this->printBody();
                $this->doChangePassword(true);

                break;
            case 'account':
                 $this->printBody();
                $this->doAccount();

                break;
            case 'save_create':
                 $this->printBody();
                $this->doSaveCreate();

                break;
            case 'create':
                 $this->printBody();
                $this->doCreate();

                break;
            case 'drop':
                 $this->printBody();
                $this->doDrop(false);

                break;
            case 'confirm_drop':
                 $this->printBody();
                $this->doDrop(true);

                break;
            case 'save_edit':
                 $this->printBody();
                $this->doSaveEdit();

                break;
            case 'edit':
                 $this->printBody();
                $this->doEdit();

                break;

            default:
                $this->view->offsetSet('trail', $this->printTrail('server', false));
                $this->printBody();
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
        $data = $this->misc->getDatabaseAccessor();

        $lang = $this->lang;
        $renderUseExpires = static function ($val) use ($lang) {
            return 'infinity' === $val ? $lang['strnever'] : \htmlspecialchars($val);
        };

        $this->printTabs('server', 'users');
        $this->printMsg($msg);

        $users = $data->getUsers();

        $columns = [
            'user' => [
                'title' => $this->lang['strusername'],
                'field' => Decorator::field('usename'),
            ],
            'group' => [
                'title' => $this->lang['strgroup'],
                'field' => Decorator::field('group'),
            ],
            'superuser' => [
                'title' => $this->lang['strsuper'],
                'field' => Decorator::field('usesuper'),
                'type' => 'yesno',
            ],
            'createdb' => [
                'title' => $this->lang['strcreatedb'],
                'field' => Decorator::field('usecreatedb'),
                'type' => 'yesno',
            ],
            'expires' => [
                'title' => $this->lang['strexpires'],
                'field' => Decorator::field('useexpires'),
                'type' => 'callback',
                'params' => ['function' => $renderUseExpires, 'null' => $this->lang['strnever']],
            ],
            'defaults' => [
                'title' => $this->lang['strsessiondefaults'],
                'field' => Decorator::field('useconfig'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
        ];

        $actions = [
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'users',
                        'urlvars' => [
                            'action' => 'edit',
                            'username' => Decorator::field('usename'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'users',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'username' => Decorator::field('usename'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($users)) {
            echo $this->printTable($users, $columns, $actions, 'users-users', $this->lang['strnousers']);
        }

        return $this->printNavLinks(['create' => [
            'attr' => [
                'href' => [
                    'url' => 'users',
                    'urlvars' => [
                        'action' => 'create',
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $this->lang['strcreateuser'],
        ]], 'users-users', \get_defined_vars());
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
        $data = $this->misc->getDatabaseAccessor();

        $server_info = $this->misc->getServerInfo();

        $userdata = $data->getUser($server_info['username']);
        $_REQUEST['user'] = $server_info['username'];

        $this->printTrail('user');
        $this->printTabs('server', 'account');
        $this->printMsg($msg);

        if (0 < $userdata->RecordCount()) {
            $userdata->fields['usesuper'] = $data->phpBool($userdata->fields['usesuper']);
            $userdata->fields['usecreatedb'] = $data->phpBool($userdata->fields['usecreatedb']);
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data">%s</th><th class="data">%s</th><th class="data">%s</th><th class="data">%s</th>',
                $this->lang['strusername'],
                $this->lang['strsuper'],
                $this->lang['strcreatedb'],
                $this->lang['strexpires']
            );
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strsessiondefaults']
            );
            echo '</tr>' . \PHP_EOL;
            echo "<tr>\n\t<td class=\"data1\">", $this->misc->printVal($userdata->fields['usename']), '</td>' . \PHP_EOL;
            echo "\t<td class=\"data1\">", $this->misc->printVal($userdata->fields['usesuper'], 'yesno'), '</td>' . \PHP_EOL;
            echo "\t<td class=\"data1\">", $this->misc->printVal($userdata->fields['usecreatedb'], 'yesno'), '</td>' . \PHP_EOL;
            echo "\t<td class=\"data1\">", ('infinity' === $userdata->fields['useexpires'] || null === $userdata->fields['useexpires'] ? $this->lang['strnever'] : $this->misc->printVal($userdata->fields['useexpires'])), '</td>' . \PHP_EOL;
            echo "\t<td class=\"data1\">", $this->misc->printVal($userdata->fields['useconfig']), '</td>' . \PHP_EOL;
            echo "</tr>\n</table>" . \PHP_EOL;
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;
        }

        return $this->printNavLinks(['changepassword' => [
            'attr' => [
                'href' => [
                    'url' => 'users',
                    'urlvars' => [
                        'action' => 'confchangepassword',
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $this->lang['strchangepassword'],
        ]], 'users-account', \get_defined_vars());
    }

    /**
     * Show confirmation of change password and actually change password.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doChangePassword($confirm, $msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $server_info = $this->misc->getServerInfo();

        if ($confirm) {
            $_REQUEST['user'] = $server_info['username'];
            $this->printTrail('user');
            $this->printTitle($this->lang['strchangepassword'], 'pg.user.alter');
            $this->printMsg($msg);
            $this->coalesceArr($_POST, 'password', '');
            $this->coalesceArr($_POST, 'confirm', '');
            echo '<form action="' . \containerInstance()->subFolder . '/users" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left required">%s</th>',
                $this->lang['strpassword']
            ) . \PHP_EOL;
            echo "\t\t<td><input type=\"password\" name=\"password\" size=\"32\" value=\"",
                \htmlspecialchars($_POST['password']),
                "\" /></td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left required">%s</th>',
                $this->lang['strconfirm']
            ) . \PHP_EOL;
            echo "\t\t<td><input type=\"password\" name=\"confirm\" size=\"32\" value=\"\" /></td>\n\t</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="changepassword" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="ok" value="%s" />',
                $this->lang['strok']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s" />',
                $this->lang['strcancel']
            ) . \PHP_EOL;
            echo '</p></form>' . \PHP_EOL;
        } elseif (\mb_strlen($_POST['password']) < $this->conf['min_password_length']) {
            $this->doChangePassword(true, $this->lang['strpasswordshort']);
        } elseif ($_POST['password'] !== $_POST['confirm']) {
            // Check that password matches confirmation password
            $this->doChangePassword(true, $this->lang['strpasswordconfirm']);
        } else {
            $status = $data->changePassword(
                $server_info['username'],
                $_POST['password']
            );

            if (0 === $status) {
                $this->doAccount($this->lang['strpasswordchanged']);
            } else {
                $this->doAccount($this->lang['strpasswordchangedbad']);
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
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('user');
        $this->printTitle($this->lang['stralter'], 'pg.user.alter');
        $this->printMsg($msg);

        $userdata = $data->getUser($_REQUEST['username']);

        if (!\is_object($userdata) || 0 < $userdata->RecordCount()) {
            $server_info = $this->misc->getServerInfo();
            $canRename = $data->hasUserRename() && ($_REQUEST['username'] !== $server_info['username']);
            $userdata->fields['usesuper'] = $data->phpBool($userdata->fields['usesuper'] ?? false);
            $userdata->fields['usecreatedb'] = $data->phpBool($userdata->fields['usecreatedb'] ?? false);

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

                $_POST['formExpires'] = 'infinity' === $userdata->fields['useexpires'] ? '' : $userdata->fields['useexpires'];
                $_POST['formPassword'] = '';
            }

            echo '<form action="' . \containerInstance()->subFolder . '/users" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strusername']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", ($canRename ? \sprintf(
                '<input name="newname" size="15" maxlength="%s" value="',
                $data->_maxNameLen
            ) . \htmlspecialchars($_POST['newname']) . '" />' : $this->misc->printVal($userdata->fields['usename'])), "</td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left"><label for="formSuper">%s</label></th>',
                $this->lang['strsuper']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formSuper\" name=\"formSuper\"",
                (isset($_POST['formSuper'])) ? ' checked="checked"' : '',
                " /></td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left"><label for="formCreateDB">%s</label></th>',
                $this->lang['strcreatedb']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCreateDB\" name=\"formCreateDB\"",
                (isset($_POST['formCreateDB'])) ? ' checked="checked"' : '',
                " /></td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strexpires']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><input size=\"16\" name=\"formExpires\" value=\"", \htmlspecialchars($_POST['formExpires']), "\" /></td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strpassword']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><input type=\"password\" size=\"16\" name=\"formPassword\" value=\"", \htmlspecialchars($_POST['formPassword']), "\" /></td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strconfirm']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><input type=\"password\" size=\"16\" name=\"formConfirm\" value=\"\" /></td>\n\t</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="save_edit" />' . \PHP_EOL;
            echo '<input type="hidden" name="username" value="', \htmlspecialchars($_REQUEST['username']), '" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="alter" value="%s" />',
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
     * Function to save after editing a user.
     */
    public function doSaveEdit(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check name and password
        if (isset($_POST['newname']) && '' === $_POST['newname']) {
            $this->doEdit($this->lang['struserneedsname']);
        } elseif ($_POST['formPassword'] !== $_POST['formConfirm']) {
            $this->doEdit($this->lang['strpasswordconfirm']);
        } else {
            if (isset($_POST['newname'])) {
                $status = $data->setRenameUser($_POST['username'], $_POST['formPassword'], isset($_POST['formCreateDB']), isset($_POST['formSuper']), $_POST['formExpires'], $_POST['newname']);
            } else {
                $status = $data->setUser($_POST['username'], $_POST['formPassword'], isset($_POST['formCreateDB']), isset($_POST['formSuper']), $_POST['formExpires']);
            }

            if (0 === $status) {
                $this->doDefault($this->lang['struserupdated']);
            } else {
                $this->doEdit($this->lang['struserupdatedbad']);
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
            $this->printTrail('user');
            $this->printTitle($this->lang['strdrop'], 'pg.user.drop');

            echo '<p>', \sprintf(
                $this->lang['strconfdropuser'],
                $this->misc->printVal($_REQUEST['username'])
            ), '</p>' . \PHP_EOL;

            echo '<form action="' . \containerInstance()->subFolder . '/users" method="post">' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="drop" />' . \PHP_EOL;
            echo '<input type="hidden" name="username" value="', \htmlspecialchars($_REQUEST['username']), '" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="drop" value="%s" />',
                $this->lang['strdrop']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s"  /></p>%s',
                $this->lang['strcancel'],
                \PHP_EOL
            );
            echo '</form>' . \PHP_EOL;
        } else {
            $status = $data->dropUser($_REQUEST['username']);

            if (0 === $status) {
                $this->doDefault($this->lang['struserdropped']);
            } else {
                $this->doDefault($this->lang['struserdroppedbad']);
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
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'formUsername', '');

        $this->coalesceArr($_POST, 'formPassword', '');

        $this->coalesceArr($_POST, 'formConfirm', '');

        $this->coalesceArr($_POST, 'formExpires', '');

        $this->printTrail('server');
        $this->printTitle($this->lang['strcreateuser'], 'pg.user.create');
        $this->printMsg($msg);

        echo '<form action="' . \containerInstance()->subFolder . '/users" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strusername']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data1"><input size="15" maxlength="%s" name="formUsername" value="',
            $data->_maxNameLen
        ), \htmlspecialchars($_POST['formUsername']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strpassword']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"15\" type=\"password\" name=\"formPassword\" value=\"", \htmlspecialchars($_POST['formPassword']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strconfirm']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"15\" type=\"password\" name=\"formConfirm\" value=\"", \htmlspecialchars($_POST['formConfirm']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formSuper">%s</label></th>',
            $this->lang['strsuper']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formSuper\" name=\"formSuper\"",
            (isset($_POST['formSuper'])) ? ' checked="checked"' : '',
            " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formCreateDB">%s</label></th>',
            $this->lang['strcreatedb']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCreateDB\" name=\"formCreateDB\"",
            (isset($_POST['formCreateDB'])) ? ' checked="checked"' : '',
            " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strexpires']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"30\" name=\"formExpires\" value=\"", \htmlspecialchars($_POST['formExpires']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo '</table>' . \PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create" />' . \PHP_EOL;
        echo $this->view->form;
        echo \sprintf(
            '<input type="submit" name="create" value="%s" />',
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
     * Actually creates the new user in the database.
     */
    public function doSaveCreate(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check data
        if ('' === $_POST['formUsername']) {
            $this->doCreate($this->lang['struserneedsname']);
        } elseif ($_POST['formPassword'] !== $_POST['formConfirm']) {
            $this->doCreate($this->lang['strpasswordconfirm']);
        } else {
            $status = $data->createUser(
                $_POST['formUsername'],
                $_POST['formPassword'],
                isset($_POST['formCreateDB']),
                isset($_POST['formSuper']),
                $_POST['formExpires'],
                []
            );

            if (0 === $status) {
                $this->doDefault($this->lang['strusercreated']);
            } else {
                $this->doCreate($this->lang['strusercreatedbad']);
            }
        }
    }
}
