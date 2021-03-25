<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use ADORecordSet;
use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class RolesController extends BaseController
{
    public $controller_title = 'strroles';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            case 'create':
                $this->doCreate();

                break;
            case 'save_create':
                if (null !== $this->getPostParam('create')) {
                    $this->doSaveCreate();
                } else {
                    $this->doDefault();
                }

                break;
            case 'alter':
                $this->doAlter();

                break;
            case 'save_alter':
                if (null !== $this->getPostParam('alter')) {
                    $this->doSaveAlter();
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'drop':
                if (null !== $this->getPostParam('drop')) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'properties':
                $this->doProperties();

                break;
            case 'confchangepassword':
                $this->doChangePassword(true);

                break;
            case 'changepassword':
                if (isset($_REQUEST['ok'])) {
                    $this->doChangePassword(false);
                } else {
                    $this->doAccount();
                }

                break;
            case 'account':
                $this->doAccount();

                break;

            default:
                $this->doDefault();
        }

        $this->printFooter();
    }

    /**
     * Show default list of roles in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $lang = $this->lang;
        $renderRoleConnLimit = static function ($val) use ($lang) {
            return '-1' === $val ? $lang['strnolimit'] : \htmlspecialchars($val);
        };

        $renderRoleExpires = static function ($val) use ($lang) {
            return 'infinity' === $val ? $lang['strnever'] : \htmlspecialchars($val);
        };

        $this->printTrail('server');
        $this->printTabs('server', 'roles');
        $this->printMsg($msg);

        $roles = $data->getRoles();

        $columns = [
            'role' => [
                'title' => $this->lang['strrole'],
                'field' => Decorator::field('rolname'),
                'url' => \containerInstance()->getDestinationWithLastTab('role'),

                'vars' => ['rolename' => 'rolname'],
            ],
            'group' => [
                'title' => $this->lang['strgroup'],
                'field' => Decorator::field('group'),
            ],
            'superuser' => [
                'title' => $this->lang['strsuper'],
                'field' => Decorator::field('rolsuper'),
                'type' => 'yesno',
            ],
            'createdb' => [
                'title' => $this->lang['strcreatedb'],
                'field' => Decorator::field('rolcreatedb'),
                'type' => 'yesno',
            ],
            'createrole' => [
                'title' => $this->lang['strcancreaterole'],
                'field' => Decorator::field('rolcreaterole'),
                'type' => 'yesno',
            ],
            'inherits' => [
                'title' => $this->lang['strinheritsprivs'],
                'field' => Decorator::field('rolinherit'),
                'type' => 'yesno',
            ],
            'canloging' => [
                'title' => $this->lang['strcanlogin'],
                'field' => Decorator::field('rolcanlogin'),
                'type' => 'yesno',
            ],
            'connlimit' => [
                'title' => $this->lang['strconnlimit'],
                'field' => Decorator::field('rolconnlimit'),
                'type' => 'callback',
                'params' => ['function' => $renderRoleConnLimit],
            ],
            'expires' => [
                'title' => $this->lang['strexpires'],
                'field' => Decorator::field('rolvaliduntil'),
                'type' => 'callback',
                'params' => ['function' => $renderRoleExpires, 'null' => $this->lang['strnever']],
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
                        'url' => 'roles',
                        'urlvars' => [
                            'action' => 'alter',
                            'rolename' => Decorator::field('rolname'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'roles',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'rolename' => Decorator::field('rolname'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($roles)) {
            echo $this->printTable($roles, $columns, $actions, 'roles-roles', $this->lang['strnoroles']);
        }

        $navlinks = [
            'create' => [
                'attr' => [
                    'href' => [
                        'url' => 'roles',
                        'urlvars' => [
                            'action' => 'create',
                            'server' => $_REQUEST['server'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreaterole'],
            ],
        ];
return  $this->printNavLinks($navlinks, 'roles-roles', \get_defined_vars());
        return '';
    }

    /**
     * Displays a screen for create a new role.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'formRolename', '');

        $this->coalesceArr($_POST, 'formPassword', '');

        $this->coalesceArr($_POST, 'formConfirm', '');

        $this->coalesceArr($_POST, 'formConnLimit', '');

        $this->coalesceArr($_POST, 'formExpires', '');

        $this->coalesceArr($_POST, 'memberof', []);

        $this->coalesceArr($_POST, 'members', []);

        $this->coalesceArr($_POST, 'adminmembers', []);

        $this->printTrail('role');
        $this->printTitle($this->lang['strcreaterole'], 'pg.role.create');
        $this->printMsg($msg);

        echo '<form action="roles" class="max_height" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required" style="width: 130px">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data1"><input size="15" maxlength="%s" name="formRolename" value="',
            $data->_maxNameLen
        ), \htmlspecialchars($_POST['formRolename']), "\" /></td>\n\t</tr>" . \PHP_EOL;
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
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formSuper\" name=\"formSuper\"", (isset($_POST['formSuper'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formCreateDB">%s</label></th>',
            $this->lang['strcreatedb']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCreateDB\" name=\"formCreateDB\"", (isset($_POST['formCreateDB'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formCreateRole">%s</label></th>',
            $this->lang['strcancreaterole']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCreateRole\" name=\"formCreateRole\"", (isset($_POST['formCreateRole'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formInherits">%s</label></th>',
            $this->lang['strinheritsprivs']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formInherits\" name=\"formInherits\"", (isset($_POST['formInherits'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formCanLogin">%s</label></th>',
            $this->lang['strcanlogin']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCanLogin\" name=\"formCanLogin\"", (isset($_POST['formCanLogin'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strconnlimit']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"4\" name=\"formConnLimit\" value=\"", \htmlspecialchars($_POST['formConnLimit']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strexpires']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"23\" name=\"formExpires\" value=\"", \htmlspecialchars($_POST['formExpires']), "\" /></td>\n\t</tr>" . \PHP_EOL;

        $roles = $data->getRoles();

        if (0 < $roles->RecordCount()) {
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strmemberof']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data\">" . \PHP_EOL;
            echo "\t\t\t<select name=\"memberof[]\" multiple=\"multiple\" size=\"", \min(20, $roles->RecordCount()), '">' . \PHP_EOL;

            while (!$roles->EOF) {
                $rolename = $roles->fields['rolname'];
                echo \sprintf(
                    '				<option value="%s"',
                    $rolename
                ), (\in_array($rolename, $_POST['memberof'], true) ? ' selected="selected"' : ''), '>', $this->misc->printVal($rolename), '</option>' . \PHP_EOL;
                $roles->MoveNext();
            }
            echo "\t\t\t</select>" . \PHP_EOL;
            echo "\t\t</td>\n\t</tr>" . \PHP_EOL;

            $roles->moveFirst();
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strmembers']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data\">" . \PHP_EOL;
            echo "\t\t\t<select name=\"members[]\" multiple=\"multiple\" size=\"", \min(20, $roles->RecordCount()), '">' . \PHP_EOL;

            while (!$roles->EOF) {
                $rolename = $roles->fields['rolname'];
                echo \sprintf(
                    '				<option value="%s"',
                    $rolename
                ), (\in_array($rolename, $_POST['members'], true) ? ' selected="selected"' : ''), '>', $this->misc->printVal($rolename), '</option>' . \PHP_EOL;
                $roles->MoveNext();
            }
            echo "\t\t\t</select>" . \PHP_EOL;
            echo "\t\t</td>\n\t</tr>" . \PHP_EOL;

            $roles->moveFirst();
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['stradminmembers']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data\">" . \PHP_EOL;
            echo "\t\t\t<select name=\"adminmembers[]\" multiple=\"multiple\" size=\"", \min(20, $roles->RecordCount()), '">' . \PHP_EOL;

            while (!$roles->EOF) {
                $rolename = $roles->fields['rolname'];
                echo \sprintf(
                    '				<option value="%s"',
                    $rolename
                ), (\in_array($rolename, $_POST['adminmembers'], true) ? ' selected="selected"' : ''), '>', $this->misc->printVal($rolename), '</option>' . \PHP_EOL;
                $roles->MoveNext();
            }
            echo "\t\t\t</select>" . \PHP_EOL;
            echo "\t\t</td>\n\t</tr>" . \PHP_EOL;
        }

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
     * Actually creates the new role in the database.
     */
    public function doSaveCreate(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'memberof', []);

        $this->coalesceArr($_POST, 'members', []);

        $this->coalesceArr($_POST, 'adminmembers', []);

        // Check data
        if ('' === $_POST['formRolename']) {
            $this->doCreate($this->lang['strroleneedsname']);
        } elseif ($_POST['formPassword'] !== $_POST['formConfirm']) {
            $this->doCreate($this->lang['strpasswordconfirm']);
        } else {
            $status = $data->createRole(
                $_POST['formRolename'],
                $_POST['formPassword'],
                isset($_POST['formSuper']),
                isset($_POST['formCreateDB']),
                isset($_POST['formCreateRole']),
                isset($_POST['formInherits']),
                isset($_POST['formCanLogin']),
                $_POST['formConnLimit'],
                $_POST['formExpires'],
                $_POST['memberof'],
                $_POST['members'],
                $_POST['adminmembers']
            );

            if (0 === $status) {
                $this->doDefault($this->lang['strrolecreated']);
            } else {
                $this->doCreate($this->lang['strrolecreatedbad']);
            }
        }
    }

    /**
     * Function to allow alter a role.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('role');
        $this->printTitle($this->lang['stralter'], 'pg.role.alter');
        $this->printMsg($msg);

        $roledata = $data->getRole($_REQUEST['rolename']);

        if (0 >= $roledata->RecordCount()) {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;

            return;
        }
        $server_info = $this->misc->getServerInfo();
        $canRename = $data->hasUserRename() && ($_REQUEST['rolename'] !== $server_info['username']);
        $roledata->fields['rolsuper'] = $data->phpBool($roledata->fields['rolsuper']);
        $roledata->fields['rolcreatedb'] = $data->phpBool($roledata->fields['rolcreatedb']);
        $roledata->fields['rolcreaterole'] = $data->phpBool($roledata->fields['rolcreaterole']);
        $roledata->fields['rolinherit'] = $data->phpBool($roledata->fields['rolinherit']);
        $roledata->fields['rolcanlogin'] = $data->phpBool($roledata->fields['rolcanlogin']);

        $this->_adjustPostVars($roledata, $canRename);

        echo '<form action="roles" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left" style="width: 130px">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\">", ($canRename ? \sprintf(
            '<input name="formNewRoleName" size="15" maxlength="%s" value="',
            $data->_maxNameLen
        ) . \htmlspecialchars($_POST['formNewRoleName']) . '" />' : $this->misc->printVal($roledata->fields['rolname'])), "</td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strpassword']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"password\" size=\"15\" name=\"formPassword\" value=\"", \htmlspecialchars($_POST['formPassword']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strconfirm']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"password\" size=\"15\" name=\"formConfirm\" value=\"\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formSuper">%s</label></th>',
            $this->lang['strsuper']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formSuper\" name=\"formSuper\"", (isset($_POST['formSuper'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formCreateDB">%s</label></th>',
            $this->lang['strcreatedb']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCreateDB\" name=\"formCreateDB\"", (isset($_POST['formCreateDB'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formCreateRole">%s</label></th>',
            $this->lang['strcancreaterole']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCreateRole\" name=\"formCreateRole\"", (isset($_POST['formCreateRole'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formInherits">%s</label></th>',
            $this->lang['strinheritsprivs']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formInherits\" name=\"formInherits\"", (isset($_POST['formInherits'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left"><label for="formCanLogin">%s</label></th>',
            $this->lang['strcanlogin']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input type=\"checkbox\" id=\"formCanLogin\" name=\"formCanLogin\"", (isset($_POST['formCanLogin'])) ? ' checked="checked"' : '', " /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strconnlimit']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"4\" name=\"formConnLimit\" value=\"", \htmlspecialchars($_POST['formConnLimit']), "\" /></td>\n\t</tr>" . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strexpires']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input size=\"23\" name=\"formExpires\" value=\"", \htmlspecialchars($_POST['formExpires']), "\" /></td>\n\t</tr>" . \PHP_EOL;

        $this->_populateMemberof($data);
        $memberofold = \implode(',', $_POST['memberof']);

        $this->_populateMembers($data);
        $membersold = \implode(',', $_POST['members']);

        $this->_populateAdminmembers($data);
        $adminmembersold = \implode(',', $_POST['adminmembers']);

        $roles = $data->getRoles($_REQUEST['rolename']);

        if (0 < $roles->RecordCount()) {
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strmemberof']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data\">" . \PHP_EOL;
            echo "\t\t\t<select name=\"memberof[]\" multiple=\"multiple\" size=\"", \min(20, $roles->RecordCount()), '">' . \PHP_EOL;

            while (!$roles->EOF) {
                $rolename = $roles->fields['rolname'];
                echo \sprintf(
                    '				<option value="%s"',
                    $rolename
                ), (\in_array($rolename, $_POST['memberof'], true) ? ' selected="selected"' : ''), '>', $this->misc->printVal($rolename), '</option>' . \PHP_EOL;
                $roles->MoveNext();
            }
            echo "\t\t\t</select>" . \PHP_EOL;
            echo "\t\t</td>\n\t</tr>" . \PHP_EOL;

            $roles->moveFirst();
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['strmembers']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data\">" . \PHP_EOL;
            echo "\t\t\t<select name=\"members[]\" multiple=\"multiple\" size=\"", \min(20, $roles->RecordCount()), '">' . \PHP_EOL;

            while (!$roles->EOF) {
                $rolename = $roles->fields['rolname'];
                echo \sprintf(
                    '				<option value="%s"',
                    $rolename
                ), (\in_array($rolename, $_POST['members'], true) ? ' selected="selected"' : ''), '>', $this->misc->printVal($rolename), '</option>' . \PHP_EOL;
                $roles->MoveNext();
            }
            echo "\t\t\t</select>" . \PHP_EOL;
            echo "\t\t</td>\n\t</tr>" . \PHP_EOL;

            $roles->moveFirst();
            echo \sprintf(
                '	<tr>
		<th class="data left">%s</th>',
                $this->lang['stradminmembers']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data\">" . \PHP_EOL;
            echo "\t\t\t<select name=\"adminmembers[]\" multiple=\"multiple\" size=\"", \min(20, $roles->RecordCount()), '">' . \PHP_EOL;

            while (!$roles->EOF) {
                $rolename = $roles->fields['rolname'];
                echo \sprintf(
                    '				<option value="%s"',
                    $rolename
                ), (\in_array($rolename, $_POST['adminmembers'], true) ? ' selected="selected"' : ''), '>', $this->misc->printVal($rolename), '</option>' . \PHP_EOL;
                $roles->MoveNext();
            }
            echo "\t\t\t</select>" . \PHP_EOL;
            echo "\t\t</td>\n\t</tr>" . \PHP_EOL;
        }
        echo '</table>' . \PHP_EOL;

        echo '<p><input type="hidden" name="action" value="save_alter" />' . \PHP_EOL;
        echo '<input type="hidden" name="rolename" value="', \htmlspecialchars($_REQUEST['rolename']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="memberofold" value="', $_POST['memberofold'] ?? \htmlspecialchars($memberofold), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="membersold" value="', $_POST['membersold'] ?? \htmlspecialchars($membersold), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="adminmembersold" value="', $_POST['adminmembersold'] ?? \htmlspecialchars($adminmembersold), '" />' . \PHP_EOL;
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
    }

    /**
     * Function to save after editing a role.
     */
    public function doSaveAlter(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'memberof', []);

        $this->coalesceArr($_POST, 'members', []);

        $this->coalesceArr($_POST, 'adminmembers', []);

        // Check name and password
        if (isset($_POST['formNewRoleName']) && '' === $_POST['formNewRoleName']) {
            $this->doAlter($this->lang['strroleneedsname']);
        } elseif ($_POST['formPassword'] !== $_POST['formConfirm']) {
            $this->doAlter($this->lang['strpasswordconfirm']);
        } else {
            if (isset($_POST['formNewRoleName'])) {
                $status = $data->setRenameRole($_POST['rolename'], $_POST['formPassword'], isset($_POST['formSuper']), isset($_POST['formCreateDB']), isset($_POST['formCreateRole']), isset($_POST['formInherits']), isset($_POST['formCanLogin']), $_POST['formConnLimit'], $_POST['formExpires'], $_POST['memberof'], $_POST['members'], $_POST['adminmembers'], $_POST['memberofold'], $_POST['membersold'], $_POST['adminmembersold'], $_POST['formNewRoleName']);
            } else {
                $status = $data->setRole($_POST['rolename'], $_POST['formPassword'], isset($_POST['formSuper']), isset($_POST['formCreateDB']), isset($_POST['formCreateRole']), isset($_POST['formInherits']), isset($_POST['formCanLogin']), $_POST['formConnLimit'], $_POST['formExpires'], $_POST['memberof'], $_POST['members'], $_POST['adminmembers'], $_POST['memberofold'], $_POST['membersold'], $_POST['adminmembersold']);
            }

            if (0 === $status) {
                $this->doDefault($this->lang['strrolealtered']);
            } else {
                $this->doAlter($this->lang['strrolealteredbad']);
            }
        }
    }

    /**
     * Show confirmation of drop a role and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('role');
            $this->printTitle($this->lang['strdroprole'], 'pg.role.drop');

            echo '<p>', \sprintf(
                $this->lang['strconfdroprole'],
                $this->misc->printVal($_REQUEST['rolename'])
            ), '</p>' . \PHP_EOL;

            echo '<form action="roles" method="post">' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="drop" />' . \PHP_EOL;
            echo '<input type="hidden" name="rolename" value="', \htmlspecialchars($_REQUEST['rolename']), '" />' . \PHP_EOL;
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
            $status = $data->dropRole($_REQUEST['rolename']);

            if (0 === $status) {
                $this->doDefault($this->lang['strroledropped']);
            } else {
                $this->doDefault($this->lang['strroledroppedbad']);
            }
        }
    }

    /**
     * Show the properties of a role.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('role');
        $this->printTitle($this->lang['strproperties'], 'pg.role');
        $this->printMsg($msg);

        $roledata = $data->getRole($_REQUEST['rolename']);

        if (0 < $roledata->RecordCount()) {
            $roledata->fields['rolsuper'] = $data->phpBool($roledata->fields['rolsuper']);
            $roledata->fields['rolcreatedb'] = $data->phpBool($roledata->fields['rolcreatedb']);
            $roledata->fields['rolcreaterole'] = $data->phpBool($roledata->fields['rolcreaterole']);
            $roledata->fields['rolinherit'] = $data->phpBool($roledata->fields['rolinherit']);
            $roledata->fields['rolcanlogin'] = $data->phpBool($roledata->fields['rolcanlogin']);

            echo '<table>' . \PHP_EOL;
            echo "\t<tr>\n\t\t<th class=\"data\" style=\"width: 130px\">Description</th>" . \PHP_EOL;
            echo "\t\t<th class=\"data\" style=\"width: 120\">Value</th>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data1">%s</td>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", \htmlspecialchars($_REQUEST['rolename']), "</td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data2">%s</td>',
                $this->lang['strsuper']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data2\">", (($roledata->fields['rolsuper']) ? $this->lang['stryes'] : $this->lang['strno']), "</td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data1">%s</td>',
                $this->lang['strcreatedb']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", (($roledata->fields['rolcreatedb']) ? $this->lang['stryes'] : $this->lang['strno']), '</td>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data2">%s</td>',
                $this->lang['strcancreaterole']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data2\">", (($roledata->fields['rolcreaterole']) ? $this->lang['stryes'] : $this->lang['strno']), '</td>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data1">%s</td>',
                $this->lang['strinheritsprivs']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", (($roledata->fields['rolinherit']) ? $this->lang['stryes'] : $this->lang['strno']), '</td>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data2">%s</td>',
                $this->lang['strcanlogin']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data2\">", (($roledata->fields['rolcanlogin']) ? $this->lang['stryes'] : $this->lang['strno']), '</td>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data1">%s</td>',
                $this->lang['strconnlimit']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", ('-1' === $roledata->fields['rolconnlimit'] ? $this->lang['strnolimit'] : $this->misc->printVal($roledata->fields['rolconnlimit'])), '</td>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data2">%s</td>',
                $this->lang['strexpires']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data2\">", ('infinity' === $roledata->fields['rolvaliduntil'] || null === $roledata->fields['rolvaliduntil'] ? $this->lang['strnever'] : $this->misc->printVal($roledata->fields['rolvaliduntil'])), '</td>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data1">%s</td>',
                $this->lang['strsessiondefaults']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", $this->misc->printVal($roledata->fields['rolconfig']), '</td>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data2">%s</td>',
                $this->lang['strmemberof']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data2\">";
            $memberof = $data->getMemberOf($_REQUEST['rolename']);

            if (0 < $memberof->RecordCount()) {
                while (!$memberof->EOF) {
                    echo $this->misc->printVal($memberof->fields['rolname']), '<br />' . \PHP_EOL;
                    $memberof->MoveNext();
                }
            }
            echo "</td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data1">%s</td>',
                $this->lang['strmembers']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">";
            $members = $data->getMembers($_REQUEST['rolename']);

            if (0 < $members->RecordCount()) {
                while (!$members->EOF) {
                    echo $this->misc->printVal($members->fields['rolname']), '<br />' . \PHP_EOL;
                    $members->MoveNext();
                }
            }
            echo "</td>\n\t</tr>" . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<td class="data2">%s</td>',
                $this->lang['stradminmembers']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data2\">";
            $adminmembers = $data->getMembers($_REQUEST['rolename'], 't');

            if (0 < $adminmembers->RecordCount()) {
                while (!$adminmembers->EOF) {
                    echo $this->misc->printVal($adminmembers->fields['rolname']), '<br />' . \PHP_EOL;
                    $adminmembers->MoveNext();
                }
            }
            echo "</td>\n\t</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;
        }

        $navlinks = [
            'showall' => [
                'attr' => [
                    'href' => [
                        'url' => 'roles',
                        'urlvars' => [
                            'server' => $_REQUEST['server'],
                        ],
                    ],
                ],
                'content' => $this->lang['strshowallroles'],
            ],
            'alter' => [
                'attr' => [
                    'href' => [
                        'url' => 'roles',
                        'urlvars' => [
                            'action' => 'alter',
                            'server' => $_REQUEST['server'],
                            'rolename' => $_REQUEST['rolename'],
                        ],
                    ],
                ],
                'content' => $this->lang['stralter'],
            ],
            'drop' => [
                'attr' => [
                    'href' => [
                        'url' => 'roles',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'server' => $_REQUEST['server'],
                            'rolename' => $_REQUEST['rolename'],
                        ],
                    ],
                ],
                'content' => $this->lang['strdrop'],
            ],
        ];

return  $this->printNavLinks($navlinks, 'roles-properties', \get_defined_vars());
    }

    /**
     * If a role is not a superuser role, then we have an 'account management'
     * page for change his password, etc.  We don't prevent them from
     * messing with the URL to gain access to other role admin stuff, because
     * the PostgreSQL permissions will prevent them changing anything anyway.
     *
     * @param mixed $msg
     */
    public function doAccount($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $server_info = $this->misc->getServerInfo();

        $roledata = $data->getRole($server_info['username']);
        $_REQUEST['rolename'] = $server_info['username'];

        $this->printTrail('role');
        $this->printTabs('server', 'account');
        $this->printMsg($msg);

        if (0 < $roledata->RecordCount()) {
            $roledata->fields['rolsuper'] = $data->phpBool($roledata->fields['rolsuper']);
            $roledata->fields['rolcreatedb'] = $data->phpBool($roledata->fields['rolcreatedb']);
            $roledata->fields['rolcreaterole'] = $data->phpBool($roledata->fields['rolcreaterole']);
            $roledata->fields['rolinherit'] = $data->phpBool($roledata->fields['rolinherit']);
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strsuper']
            ) . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strcreatedb']
            ) . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strcancreaterole']
            ) . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strinheritsprivs']
            ) . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strconnlimit']
            ) . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strexpires']
            ) . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strsessiondefaults']
            ) . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            echo "\t<tr>\n\t\t<td class=\"data1\">", $this->misc->printVal($roledata->fields['rolname']), '</td>' . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", $this->misc->printVal($roledata->fields['rolsuper'], 'yesno'), '</td>' . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", $this->misc->printVal($roledata->fields['rolcreatedb'], 'yesno'), '</td>' . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", $this->misc->printVal($roledata->fields['rolcreaterole'], 'yesno'), '</td>' . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", $this->misc->printVal($roledata->fields['rolinherit'], 'yesno'), '</td>' . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", ('-1' === $roledata->fields['rolconnlimit'] ? $this->lang['strnolimit'] : $this->misc->printVal($roledata->fields['rolconnlimit'])), '</td>' . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", ('infinity' === $roledata->fields['rolvaliduntil'] || null === $roledata->fields['rolvaliduntil'] ? $this->lang['strnever'] : $this->misc->printVal($roledata->fields['rolvaliduntil'])), '</td>' . \PHP_EOL;
            echo "\t\t<td class=\"data1\">", $this->misc->printVal($roledata->fields['rolconfig']), '</td>' . \PHP_EOL;
            echo "\t</tr>\n</table>" . \PHP_EOL;
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnodata']
            ) . \PHP_EOL;
        }

return  $this->printNavLinks(['changepassword' => [
            'attr' => [
                'href' => [
                    'url' => 'roles',
                    'urlvars' => [
                        'action' => 'confchangepassword',
                        'server' => $_REQUEST['server'],
                    ],
                ],
            ],
            'content' => $this->lang['strchangepassword'],
        ]], 'roles-account', \get_defined_vars());
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
            $_REQUEST['rolename'] = $server_info['username'];
            $this->printTrail('role');
            $this->printTitle($this->lang['strchangepassword'], 'pg.role.alter');
            $this->printMsg($msg);
            $this->coalesceArr($_POST, 'password', '');
            $this->coalesceArr($_POST, 'confirm', '');
            echo '<form action="roles" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '	<tr>
		<th class="data left required">%s</th>',
                $this->lang['strpassword']
            ) . \PHP_EOL;
            echo "\t\t<td><input type=\"password\" name=\"password\" size=\"32\" value=\"",
            \htmlspecialchars($_POST['password']), "\" /></td>\n\t</tr>" . \PHP_EOL;
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
            $status = $data->changePassword($server_info['username'], $_POST['password']);

            if (0 === $status) {
                $this->doAccount($this->lang['strpasswordchanged']);
            } else {
                $this->doAccount($this->lang['strpasswordchangedbad']);
            }
        }
    }

    /**
     * Adjusts the content of the $_POST superglobal according to role data.
     *
     * @param ADORecordSet $roledata  The roledata
     * @param bool         $canRename Indicates if role can be renamed
     */
    private function _adjustPostVars($roledata, $canRename): void
    {
        if (isset($_POST['formExpires'])) {
            return;
        }

        if ($canRename) {
            $_POST['formNewRoleName'] = $roledata->fields['rolname'];
        }

        if ($roledata->fields['rolsuper']) {
            $_POST['formSuper'] = '';
        }

        if ($roledata->fields['rolcreatedb']) {
            $_POST['formCreateDB'] = '';
        }

        if ($roledata->fields['rolcreaterole']) {
            $_POST['formCreateRole'] = '';
        }

        if ($roledata->fields['rolinherit']) {
            $_POST['formInherits'] = '';
        }

        if ($roledata->fields['rolcanlogin']) {
            $_POST['formCanLogin'] = '';
        }

        $_POST['formConnLimit'] = '-1' === $roledata->fields['rolconnlimit'] ? '' : $roledata->fields['rolconnlimit'];
        $_POST['formExpires'] = 'infinity' === $roledata->fields['rolvaliduntil'] ? '' : $roledata->fields['rolvaliduntil'];
        $_POST['formPassword'] = '';
    }

    /**
     * @param \PHPPgAdmin\Database\Postgres $data
     */
    private function _populateMemberof($data): void
    {
        if (!isset($_POST['memberof'])) {
            $memberof = $data->getMemberOf($_REQUEST['rolename']);

            if (0 < $memberof->RecordCount()) {
                $i = 0;

                while (!$memberof->EOF) {
                    $_POST['memberof'][$i++] = $memberof->fields['rolname'];
                    $memberof->MoveNext();
                }
            } else {
                $_POST['memberof'] = [];
            }
        }
    }

    /**
     * @param \PHPPgAdmin\Database\Postgres $data
     */
    private function _populateMembers($data): void
    {
        if (!isset($_POST['members'])) {
            $members = $data->getMembers($_REQUEST['rolename']);

            if (0 < $members->RecordCount()) {
                $i = 0;

                while (!$members->EOF) {
                    $_POST['members'][$i++] = $members->fields['rolname'];
                    $members->MoveNext();
                }
            } else {
                $_POST['members'] = [];
            }
        }
    }

    /**
     * @param \PHPPgAdmin\Database\Postgres $data
     */
    private function _populateAdminmembers($data): void
    {
        if (!isset($_POST['adminmembers'])) {
            $adminmembers = $data->getMembers($_REQUEST['rolename'], 't');

            if (0 < $adminmembers->RecordCount()) {
                $i = 0;

                while (!$adminmembers->EOF) {
                    $_POST['adminmembers'][$i++] = $adminmembers->fields['rolname'];
                    $adminmembers->MoveNext();
                }
            } else {
                $_POST['adminmembers'] = [];
            }
        }
    }
}
