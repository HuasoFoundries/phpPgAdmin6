<?php

/**
 * PHPPgAdmin v6.0.0-beta.41
 */

namespace PHPPgAdmin\Database;

/**
 * Common trait for roles and users manipulation.
 */
trait RoleTrait
{
    /**
     * Returns all roles in the database cluster.
     *
     * @param string $rolename (optional) The role name to exclude from the select
     *
     * @return \PHPPgAdmin\ADORecordSet Either one or All roles
     */
    public function getRoles($rolename = '')
    {
        $sql = '
			SELECT rolname, rolsuper, rolcreatedb, rolcreaterole, rolinherit,
				rolcanlogin, rolconnlimit, rolvaliduntil, rolconfig
			FROM pg_catalog.pg_roles';
        if ($rolename) {
            $sql .= " WHERE rolname!='{$rolename}'";
        }

        $sql .= ' ORDER BY rolname';

        return $this->selectSet($sql);
    }

    /**
     * Returns information about a single role.
     *
     * @param string $rolename The name of the role to retrieve
     *
     * @return \PHPPgAdmin\ADORecordSet The role's data
     */
    public function getRole($rolename)
    {
        $this->clean($rolename);

        $sql = "
			SELECT rolname, rolsuper, rolcreatedb, rolcreaterole, rolinherit,
				rolcanlogin, rolconnlimit, rolvaliduntil, rolconfig
			FROM pg_catalog.pg_roles WHERE rolname='{$rolename}'";

        return $this->selectSet($sql);
    }

    /**
     * Returns all users in the database cluster.
     *
     * @return \PHPPgAdmin\ADORecordSet All users
     */
    public function getUsers()
    {
        $sql = 'SELECT usename, usesuper, usecreatedb, valuntil AS useexpires, useconfig
			FROM pg_user
			ORDER BY usename';

        return $this->selectSet($sql);
    }

    /**
     * Returns information about a single user.
     *
     * @param string $username The username of the user to retrieve
     *
     * @return \PHPPgAdmin\ADORecordSet The user's data
     */
    public function getUser($username)
    {
        $this->clean($username);

        $sql = "SELECT usename, usesuper, usecreatedb, valuntil AS useexpires, useconfig
			FROM pg_user
			WHERE usename='{$username}'";

        return $this->selectSet($sql);
    }

    /**
     * Creates a new role.
     *
     * @param string $rolename     The name of the role to create
     * @param string $password     A password for the role
     * @param bool   $superuser    Boolean whether or not the role is a superuser
     * @param bool   $createdb     Boolean whether or not the role can create databases
     * @param bool   $createrole   Boolean whether or not the role can create other roles
     * @param bool   $inherits     Boolean whether or not the role inherits the privileges from parent roles
     * @param bool   $login        Boolean whether or not the role will be allowed to login
     * @param number $connlimit    Number of concurrent connections the role can make
     * @param string $expiry       String Format 'YYYY-MM-DD HH:MM:SS'.  '' means never expire
     * @param array  $memberof     (array) Roles to which the new role will be immediately added as a new member
     * @param array  $members      (array) Roles which are automatically added as members of the new role
     * @param array  $adminmembers (array) Roles which are automatically added as admin members of the new role
     *
     * @return int 0 if operation was successful
     */
    public function createRole(
        $rolename,
        $password,
        $superuser,
        $createdb,
        $createrole,
        $inherits,
        $login,
        $connlimit,
        $expiry,
        $memberof,
        $members,
        $adminmembers
    ) {
        $enc = $this->_encryptPassword($rolename, $password);
        $this->fieldClean($rolename);
        $this->clean($enc);
        $this->clean($connlimit);
        $this->clean($expiry);
        $this->fieldArrayClean($memberof);
        $this->fieldArrayClean($members);
        $this->fieldArrayClean($adminmembers);

        $sql = "CREATE ROLE \"{$rolename}\"";
        if ($password != '') {
            $sql .= " WITH ENCRYPTED PASSWORD '{$enc}'";
        }

        $sql .= $superuser ? ' SUPERUSER' : ' NOSUPERUSER';
        $sql .= $createdb ? ' CREATEDB' : ' NOCREATEDB';
        $sql .= $createrole ? ' CREATEROLE' : ' NOCREATEROLE';
        $sql .= $inherits ? ' INHERIT' : ' NOINHERIT';
        $sql .= $login ? ' LOGIN' : ' NOLOGIN';
        if ($connlimit != '') {
            $sql .= " CONNECTION LIMIT {$connlimit}";
        } else {
            $sql .= ' CONNECTION LIMIT -1';
        }

        if ($expiry != '') {
            $sql .= " VALID UNTIL '{$expiry}'";
        } else {
            $sql .= " VALID UNTIL 'infinity'";
        }

        if (is_array($memberof) && sizeof($memberof) > 0) {
            $sql .= ' IN ROLE "'.join('", "', $memberof).'"';
        }

        if (is_array($members) && sizeof($members) > 0) {
            $sql .= ' ROLE "'.join('", "', $members).'"';
        }

        if (is_array($adminmembers) && sizeof($adminmembers) > 0) {
            $sql .= ' ADMIN "'.join('", "', $adminmembers).'"';
        }

        return $this->execute($sql);
    }

    /**
     * Helper function that computes encypted PostgreSQL passwords.
     *
     * @param string $username The username
     * @param string $password The password
     *
     * @return string
     */
    public function _encryptPassword($username, $password)
    {
        return 'md5'.md5($password.$username);
    }

    /**
     * Adjusts a role's info and renames it.
     *
     * @param string $rolename        The name of the role to adjust
     * @param string $password        A password for the role
     * @param bool   $superuser       Boolean whether or not the role is a superuser
     * @param bool   $createdb        Boolean whether or not the role can create databases
     * @param bool   $createrole      Boolean whether or not the role can create other roles
     * @param bool   $inherits        Boolean whether or not the role inherits the privileges from parent roles
     * @param bool   $login           Boolean whether or not the role will be allowed to login
     * @param number $connlimit       Number of concurrent connections the role can make
     * @param string $expiry          string Format 'YYYY-MM-DD HH:MM:SS'.  '' means never expire
     * @param array  $memberof        (array) Roles to which the role will be immediately added as a new member
     * @param array  $members         (array) Roles which are automatically added as members of the role
     * @param array  $adminmembers    (array) Roles which are automatically added as admin members of the role
     * @param array  $memberofold     (array) Original roles whose the role belongs to
     * @param array  $membersold      (array) Original roles that are members of the role
     * @param array  $adminmembersold (array) Original roles that are admin members of the role
     * @param string $newrolename     The new name of the role
     *
     * @return bool|int 0 success
     */
    public function setRenameRole(
        $rolename,
        $password,
        $superuser,
        $createdb,
        $createrole,
        $inherits,
        $login,
        $connlimit,
        $expiry,
        $memberof,
        $members,
        $adminmembers,
        $memberofold,
        $membersold,
        $adminmembersold,
        $newrolename
    ) {
        $status = $this->beginTransaction();
        if ($status != 0) {
            return -1;
        }

        if ($rolename != $newrolename) {
            $status = $this->renameRole($rolename, $newrolename);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -3;
            }
            $rolename = $newrolename;
        }

        $status =
        $this->setRole(
            $rolename,
            $password,
            $superuser,
            $createdb,
            $createrole,
            $inherits,
            $login,
            $connlimit,
            $expiry,
            $memberof,
            $members,
            $adminmembers,
            $memberofold,
            $membersold,
            $adminmembersold
        );
        if ($status != 0) {
            $this->rollbackTransaction();

            return -2;
        }

        return $this->endTransaction();
    }

    /**
     * Renames a role.
     *
     * @param string $rolename    The name of the role to rename
     * @param string $newrolename The new name of the role
     *
     * @return int 0 if operation was successful
     */
    public function renameRole($rolename, $newrolename)
    {
        $this->fieldClean($rolename);
        $this->fieldClean($newrolename);

        $sql = "ALTER ROLE \"{$rolename}\" RENAME TO \"{$newrolename}\"";

        return $this->execute($sql);
    }

    /**
     * Adjusts a role's info.
     *
     * @param string $rolename        The name of the role to adjust
     * @param string $password        A password for the role
     * @param bool   $superuser       Boolean whether or not the role is a superuser
     * @param bool   $createdb        Boolean whether or not the role can create databases
     * @param bool   $createrole      Boolean whether or not the role can create other roles
     * @param bool   $inherits        Boolean whether or not the role inherits the privileges from parent roles
     * @param bool   $login           Boolean whether or not the role will be allowed to login
     * @param number $connlimit       Number of concurrent connections the role can make
     * @param string $expiry          string Format 'YYYY-MM-DD HH:MM:SS'.  '' means never expire
     * @param array  $memberof        (array) Roles to which the role will be immediately added as a new member
     * @param array  $members         (array) Roles which are automatically added as members of the role
     * @param array  $adminmembers    (array) Roles which are automatically added as admin members of the role
     * @param string $memberofold     (array) Original roles whose the role belongs to
     * @param string $membersold      (array) Original roles that are members of the role
     * @param string $adminmembersold (array) Original roles that are admin members of the role
     *
     * @return int 0 if operation was successful
     */
    public function setRole(
        $rolename,
        $password,
        $superuser,
        $createdb,
        $createrole,
        $inherits,
        $login,
        $connlimit,
        $expiry,
        $memberof,
        $members,
        $adminmembers,
        $memberofold,
        $membersold,
        $adminmembersold
    ) {
        $enc = $this->_encryptPassword($rolename, $password);
        $this->fieldClean($rolename);
        $this->clean($enc);
        $this->clean($connlimit);
        $this->clean($expiry);
        $this->fieldArrayClean($memberof);
        $this->fieldArrayClean($members);
        $this->fieldArrayClean($adminmembers);

        $sql = "ALTER ROLE \"{$rolename}\"";
        if ($password != '') {
            $sql .= " WITH ENCRYPTED PASSWORD '{$enc}'";
        }

        $sql .= $superuser ? ' SUPERUSER' : ' NOSUPERUSER';
        $sql .= $createdb ? ' CREATEDB' : ' NOCREATEDB';
        $sql .= $createrole ? ' CREATEROLE' : ' NOCREATEROLE';
        $sql .= $inherits ? ' INHERIT' : ' NOINHERIT';
        $sql .= $login ? ' LOGIN' : ' NOLOGIN';
        if ($connlimit != '') {
            $sql .= " CONNECTION LIMIT {$connlimit}";
        } else {
            $sql .= ' CONNECTION LIMIT -1';
        }

        if ($expiry != '') {
            $sql .= " VALID UNTIL '{$expiry}'";
        } else {
            $sql .= " VALID UNTIL 'infinity'";
        }

        $status = $this->execute($sql);

        if ($status != 0) {
            return -1;
        }

        //memberof
        $old = explode(',', $memberofold);
        foreach ($memberof as $m) {
            if (!in_array($m, $old, true)) {
                $status = $this->grantRole($m, $rolename);
                if ($status != 0) {
                    return -1;
                }
            }
        }
        if ($memberofold) {
            foreach ($old as $o) {
                if (!in_array($o, $memberof, true)) {
                    $status = $this->revokeRole($o, $rolename, 0, 'CASCADE');
                    if ($status != 0) {
                        return -1;
                    }
                }
            }
        }

        //members
        $old = explode(',', $membersold);
        foreach ($members as $m) {
            if (!in_array($m, $old, true)) {
                $status = $this->grantRole($rolename, $m);
                if ($status != 0) {
                    return -1;
                }
            }
        }
        if ($membersold) {
            foreach ($old as $o) {
                if (!in_array($o, $members, true)) {
                    $status = $this->revokeRole($rolename, $o, 0, 'CASCADE');
                    if ($status != 0) {
                        return -1;
                    }
                }
            }
        }

        //adminmembers
        $old = explode(',', $adminmembersold);
        foreach ($adminmembers as $m) {
            if (!in_array($m, $old, true)) {
                $status = $this->grantRole($rolename, $m, 1);
                if ($status != 0) {
                    return -1;
                }
            }
        }
        if ($adminmembersold) {
            foreach ($old as $o) {
                if (!in_array($o, $adminmembers, true)) {
                    $status = $this->revokeRole($rolename, $o, 1, 'CASCADE');
                    if ($status != 0) {
                        return -1;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Grants membership in a role.
     *
     * @param string $role     The name of the target role
     * @param string $rolename The name of the role that will belong to the target role
     * @param int    $admin    (optional) Flag to grant the admin option
     *
     * @return int 0 if operation was successful
     */
    public function grantRole($role, $rolename, $admin = 0)
    {
        $this->fieldClean($role);
        $this->fieldClean($rolename);

        $sql = "GRANT \"{$role}\" TO \"{$rolename}\"";
        if ($admin == 1) {
            $sql .= ' WITH ADMIN OPTION';
        }

        return $this->execute($sql);
    }

    /**
     * Revokes membership in a role.
     *
     * @param string $role     The name of the target role
     * @param string $rolename The name of the role that will not belong to the target role
     * @param int    $admin    (optional) Flag to revoke only the admin option
     * @param string $type     (optional) Type of revoke: RESTRICT | CASCADE
     *
     * @return int 0 if operation was successful
     */
    public function revokeRole($role, $rolename, $admin = 0, $type = 'RESTRICT')
    {
        $this->fieldClean($role);
        $this->fieldClean($rolename);

        $sql = 'REVOKE ';
        if ($admin == 1) {
            $sql .= 'ADMIN OPTION FOR ';
        }

        $sql .= "\"{$role}\" FROM \"{$rolename}\" {$type}";

        return $this->execute($sql);
    }

    /**
     * Removes a role.
     *
     * @param string $rolename The name of the role to drop
     *
     * @return int 0 if operation was successful
     */
    public function dropRole($rolename)
    {
        $this->fieldClean($rolename);

        $sql = "DROP ROLE \"{$rolename}\"";

        return $this->execute($sql);
    }

    /**
     * Creates a new user.
     *
     * @param string $username   The username of the user to create
     * @param string $password   A password for the user
     * @param bool   $createdb   boolean Whether or not the user can create databases
     * @param bool   $createuser boolean Whether or not the user can create other users
     * @param string $expiry     string Format 'YYYY-MM-DD HH:MM:SS'.  '' means never expire
     * @param array  $groups     The groups to create the user in
     *
     * @return int 0 if operation was successful
     *
     * @internal param $group (array) The groups to create the user in
     */
    public function createUser($username, $password, $createdb, $createuser, $expiry, $groups)
    {
        $enc = $this->_encryptPassword($username, $password);
        $this->fieldClean($username);
        $this->clean($enc);
        $this->clean($expiry);
        $this->fieldArrayClean($groups);

        $sql = "CREATE USER \"{$username}\"";
        if ($password != '') {
            $sql .= " WITH ENCRYPTED PASSWORD '{$enc}'";
        }

        $sql .= $createdb ? ' CREATEDB' : ' NOCREATEDB';
        $sql .= $createuser ? ' CREATEUSER' : ' NOCREATEUSER';
        if (is_array($groups) && sizeof($groups) > 0) {
            $sql .= ' IN GROUP "'.join('", "', $groups).'"';
        }

        if ($expiry != '') {
            $sql .= " VALID UNTIL '{$expiry}'";
        } else {
            $sql .= " VALID UNTIL 'infinity'";
        }

        return $this->execute($sql);
    }

    /**
     * Adjusts a user's info and renames the user.
     *
     * @param string $username   The username of the user to modify
     * @param string $password   A new password for the user
     * @param bool   $createdb   boolean Whether or not the user can create databases
     * @param bool   $createuser boolean Whether or not the user can create other users
     * @param string $expiry     string Format 'YYYY-MM-DD HH:MM:SS'.  '' means never expire.
     * @param string $newname    The new name of the user
     *
     * @return bool|int 0 success
     */
    public function setRenameUser($username, $password, $createdb, $createuser, $expiry, $newname)
    {
        $status = $this->beginTransaction();
        if ($status != 0) {
            return -1;
        }

        if ($username != $newname) {
            $status = $this->renameUser($username, $newname);
            if ($status != 0) {
                $this->rollbackTransaction();

                return -3;
            }
            $username = $newname;
        }

        $status = $this->setUser($username, $password, $createdb, $createuser, $expiry);
        if ($status != 0) {
            $this->rollbackTransaction();

            return -2;
        }

        return $this->endTransaction();
    }

    /**
     * Renames a user.
     *
     * @param string $username The username of the user to rename
     * @param string $newname  The new name of the user
     *
     * @return int 0 if operation was successful
     */
    public function renameUser($username, $newname)
    {
        $this->fieldClean($username);
        $this->fieldClean($newname);

        $sql = "ALTER USER \"{$username}\" RENAME TO \"{$newname}\"";

        return $this->execute($sql);
    }

    // Tablespace functions

    /**
     * Adjusts a user's info.
     *
     * @param string $username   The username of the user to modify
     * @param string $password   A new password for the user
     * @param bool   $createdb   boolean Whether or not the user can create databases
     * @param bool   $createuser boolean Whether or not the user can create other users
     * @param string $expiry     string Format 'YYYY-MM-DD HH:MM:SS'.  '' means never expire.
     *
     * @return int 0 if operation was successful
     */
    public function setUser($username, $password, $createdb, $createuser, $expiry)
    {
        $enc = $this->_encryptPassword($username, $password);
        $this->fieldClean($username);
        $this->clean($enc);
        $this->clean($expiry);

        $sql = "ALTER USER \"{$username}\"";
        if ($password != '') {
            $sql .= " WITH ENCRYPTED PASSWORD '{$enc}'";
        }

        $sql .= $createdb ? ' CREATEDB' : ' NOCREATEDB';
        $sql .= $createuser ? ' CREATEUSER' : ' NOCREATEUSER';
        if ($expiry != '') {
            $sql .= " VALID UNTIL '{$expiry}'";
        } else {
            $sql .= " VALID UNTIL 'infinity'";
        }

        return $this->execute($sql);
    }

    /**
     * Removes a user.
     *
     * @param string $username The username of the user to drop
     *
     * @return int 0 if operation was successful
     */
    public function dropUser($username)
    {
        $this->fieldClean($username);

        $sql = "DROP USER \"{$username}\"";

        return $this->execute($sql);
    }

    /**
     * Changes a role's password.
     *
     * @param string $rolename The role name
     * @param string $password The new password
     *
     * @return int 0 if operation was successful
     */
    public function changePassword($rolename, $password)
    {
        $enc = $this->_encryptPassword($rolename, $password);
        $this->fieldClean($rolename);
        $this->clean($enc);

        $sql = "ALTER ROLE \"{$rolename}\" WITH ENCRYPTED PASSWORD '{$enc}'";

        return $this->execute($sql);
    }

    /**
     * Adds a group member.
     *
     * @param string $groname The name of the group
     * @param string $user    The name of the user to add to the group
     *
     * @return int 0 if operation was successful
     */
    public function addGroupMember($groname, $user)
    {
        $this->fieldClean($groname);
        $this->fieldClean($user);

        $sql = "ALTER GROUP \"{$groname}\" ADD USER \"{$user}\"";

        return $this->execute($sql);
    }

    /**
     * Returns all role names which the role belongs to.
     *
     * @param string $rolename The role name
     *
     * @return \PHPPgAdmin\ADORecordSet All role names
     */
    public function getMemberOf($rolename)
    {
        $this->clean($rolename);

        $sql = "
			SELECT rolname FROM pg_catalog.pg_roles R, pg_auth_members M
			WHERE R.oid=M.roleid
				AND member IN (
					SELECT oid FROM pg_catalog.pg_roles
					WHERE rolname='{$rolename}')
			ORDER BY rolname";

        return $this->selectSet($sql);
    }

    // Administration functions

    /**
     * Returns all role names that are members of a role.
     *
     * @param string $rolename The role name
     * @param string $admin    (optional) Find only admin members
     *
     * @return \PHPPgAdmin\ADORecordSet All role names
     */
    public function getMembers($rolename, $admin = 'f')
    {
        $this->clean($rolename);

        $sql = "
			SELECT rolname FROM pg_catalog.pg_roles R, pg_auth_members M
			WHERE R.oid=M.member AND admin_option='{$admin}'
				AND roleid IN (SELECT oid FROM pg_catalog.pg_roles
					WHERE rolname='{$rolename}')
			ORDER BY rolname";

        return $this->selectSet($sql);
    }

    /**
     * Removes a group member.
     *
     * @param string $groname The name of the group
     * @param string $user    The name of the user to remove from the group
     *
     * @return int 0 if operation was successful
     */
    public function dropGroupMember($groname, $user)
    {
        $this->fieldClean($groname);
        $this->fieldClean($user);

        $sql = "ALTER GROUP \"{$groname}\" DROP USER \"{$user}\"";

        return $this->execute($sql);
    }

    /**
     * Return users in a specific group.
     *
     * @param string $groname The name of the group
     *
     * @return \PHPPgAdmin\ADORecordSet All users in the group
     */
    public function getGroup($groname)
    {
        $this->clean($groname);

        $sql = "
			SELECT s.usename FROM pg_catalog.pg_user s, pg_catalog.pg_group g
			WHERE g.groname='{$groname}' AND s.usesysid = ANY (g.grolist)
			ORDER BY s.usename";

        return $this->selectSet($sql);
    }

    /**
     * Returns all groups in the database cluser.
     *
     * @return \PHPPgAdmin\ADORecordSet All groups
     */
    public function getGroups()
    {
        $sql = 'SELECT groname FROM pg_group ORDER BY groname';

        return $this->selectSet($sql);
    }

    /**
     * Creates a new group.
     *
     * @param string $groname The name of the group
     * @param array  $users   An array of users to add to the group
     *
     * @return int 0 if operation was successful
     */
    public function createGroup($groname, $users)
    {
        $this->fieldClean($groname);

        $sql = "CREATE GROUP \"{$groname}\"";

        if (is_array($users) && sizeof($users) > 0) {
            $this->fieldArrayClean($users);
            $sql .= ' WITH USER "'.join('", "', $users).'"';
        }

        return $this->execute($sql);
    }

    /**
     * Removes a group.
     *
     * @param string $groname The name of the group to drop
     *
     * @return int 0 if operation was successful
     */
    public function dropGroup($groname)
    {
        $this->fieldClean($groname);

        $sql = "DROP GROUP \"{$groname}\"";

        return $this->execute($sql);
    }

    /**
     * Grants a privilege to a user, group or public.
     *
     * @param string $mode        'GRANT' or 'REVOKE';
     * @param mixed  $type        The type of object
     * @param string $object      The name of the object
     * @param bool   $public      True to grant to public, false otherwise
     * @param mixed  $usernames   the array of usernames to grant privs to
     * @param mixed  $groupnames  the array of group names to grant privs to
     * @param mixed  $privileges  The array of privileges to grant (eg. ('SELECT', 'ALL PRIVILEGES', etc.) )
     * @param bool   $grantoption True if has grant option, false otherwise
     * @param bool   $cascade     True for cascade revoke, false otherwise
     * @param string $table       the column's table if type=column
     *
     * @return int 0 if operation was successful
     */
    public function setPrivileges(
        $mode,
        $type,
        $object,
        $public,
        $usernames,
        $groupnames,
        $privileges,
        $grantoption,
        $cascade,
        $table
    ) {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldArrayClean($usernames);
        $this->fieldArrayClean($groupnames);

        // Input checking
        if (!is_array($privileges) || sizeof($privileges) == 0) {
            return -3;
        }

        if (!is_array($usernames) || !is_array($groupnames) ||
            (!$public && sizeof($usernames) == 0 && sizeof($groupnames) == 0)) {
            return -4;
        }

        if ($mode != 'GRANT' && $mode != 'REVOKE') {
            return -5;
        }

        $sql = $mode;

        // Grant option
        if ($this->hasGrantOption() && $mode == 'REVOKE' && $grantoption) {
            $sql .= ' GRANT OPTION FOR';
        }

        if (in_array('ALL PRIVILEGES', $privileges, true)) {
            $sql .= ' ALL PRIVILEGES';
        } else {
            if ($type == 'column') {
                $this->fieldClean($object);
                $sql .= ' '.join(" (\"{$object}\"), ", $privileges);
            } else {
                $sql .= ' '.join(', ', $privileges);
            }
        }

        switch ($type) {
            case 'column':
                $sql .= " (\"{$object}\")";
                $object = $table;
            // no break
            case 'table':
            case 'view':
            case 'sequence':
                $this->fieldClean($object);
                $sql .= " ON \"{$f_schema}\".\"{$object}\"";

                break;
            case 'database':
                $this->fieldClean($object);
                $sql .= " ON DATABASE \"{$object}\"";

                break;
            case 'function':
                // Function comes in with $object as function OID
                $fn = $this->getFunction($object);
                $this->fieldClean($fn->fields['proname']);
                $sql .= " ON FUNCTION \"{$f_schema}\".\"{$fn->fields['proname']}\"({$fn->fields['proarguments']})";

                break;
            case 'language':
                $this->fieldClean($object);
                $sql .= " ON LANGUAGE \"{$object}\"";

                break;
            case 'schema':
                $this->fieldClean($object);
                $sql .= " ON SCHEMA \"{$object}\"";

                break;
            case 'tablespace':
                $this->fieldClean($object);
                $sql .= " ON TABLESPACE \"{$object}\"";

                break;
            default:
                return -1;
        }

        // Dump PUBLIC
        $first = true;
        $sql .= ($mode == 'GRANT') ? ' TO ' : ' FROM ';
        if ($public) {
            $sql .= 'PUBLIC';
            $first = false;
        }
        // Dump users
        foreach ($usernames as $v) {
            if ($first) {
                $sql .= "\"{$v}\"";
                $first = false;
            } else {
                $sql .= ", \"{$v}\"";
            }
        }
        // Dump groups
        foreach ($groupnames as $v) {
            if ($first) {
                $sql .= "GROUP \"{$v}\"";
                $first = false;
            } else {
                $sql .= ", GROUP \"{$v}\"";
            }
        }

        // Grant option
        if ($this->hasGrantOption() && $mode == 'GRANT' && $grantoption) {
            $sql .= ' WITH GRANT OPTION';
        }

        // Cascade revoke
        if ($this->hasGrantOption() && $mode == 'REVOKE' && $cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    abstract public function fieldClean(&$str);

    abstract public function beginTransaction();

    abstract public function rollbackTransaction();

    abstract public function endTransaction();

    abstract public function execute($sql);

    abstract public function setComment($obj_type, $obj_name, $table, $comment, $basetype = null);

    abstract public function selectSet($sql);

    abstract public function clean(&$str);

    abstract public function hasGrantOption();

    abstract public function getFunction($function_oid);
}
