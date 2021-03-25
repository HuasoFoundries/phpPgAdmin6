<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

/**
 * PrivilegesController controller class.
 */
class PrivilegesController extends BaseController
{
    public $table_place = 'privileges-privileges';

    public $controller_title = 'strprivileges';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            case 'save':
                if (isset($_REQUEST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doAlter($_REQUEST['mode']);
                }

                break;
            case 'alter':
                $this->formAlter($_REQUEST['mode']);

                break;

            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * Show permissions on a database, namespace, relation, language or function.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();
        $subject = $_REQUEST['subject'];

        $this->printTrail($subject);

        // @@@FIXME: This switch is just a temporary solution,
        // need a better way, maybe every type of object should
        // have a tab bar???

        if (\in_array($subject, [
            'server',
            'database',
            'schema',
            'table',
            'column',
            'view',
            'function',
        ], true)) {
            $this->printTabs($subject, 'privileges');
        } else {
            $this->printTitle($this->lang['strprivileges'], 'pg.privilege');
        }

        $this->printMsg($msg);

        if (!isset($data->privlist[$subject])) {
            $this->container->halt('No privileges defined for subject ' . $subject);

            return '';
        }

        $object = $_REQUEST[$subject . '_oid'] ?? $_REQUEST[$subject];

        // Get the privileges on the object, given its type
        if ('column' === $subject) {
            $privileges = $data->getPrivileges($object, 'column', $_REQUEST['table']);
        } else {
            $privileges = $data->getPrivileges($object, $subject);
        }

        if (0 < \count($privileges)) {
            echo '<table>' . \PHP_EOL;

            if ($data->hasRoles()) {
                echo \sprintf(
                    '<tr><th class="data">%s</th>',
                    $this->lang['strrole']
                );
            } else {
                echo \sprintf(
                    '<tr><th class="data">%s</th><th class="data">%s/%s</th>',
                    $this->lang['strtype'],
                    $this->lang['struser'],
                    $this->lang['strgroup']
                );
            }

            foreach ($data->privlist[$subject] as $v2) {
                // Skip over ALL PRIVILEGES
                if ('ALL PRIVILEGES' === $v2) {
                    continue;
                }

                echo \sprintf(
                    '<th class="data">%s</th>',
                    $v2
                ) . \PHP_EOL;
            }

            if ($data->hasGrantOption()) {
                echo \sprintf(
                    '<th class="data">%s</th>',
                    $this->lang['strgrantor']
                );
            }
            echo '</tr>' . \PHP_EOL;

            // Loop over privileges, outputting them
            $i = 0;

            foreach ($privileges as $v) {
                $id = (0 === ($i % 2) ? '1' : '2');
                echo \sprintf(
                    '<tr class="data%s">',
                    $id
                ) . \PHP_EOL;

                if (!$data->hasRoles()) {
                    echo '<td>', $this->misc->printVal($v[0]), '</td>' . \PHP_EOL;
                }

                echo '<td>', $this->misc->printVal($v[1]), '</td>' . \PHP_EOL;

                foreach ($data->privlist[$subject] as $v2) {
                    // Skip over ALL PRIVILEGES
                    if ('ALL PRIVILEGES' === $v2) {
                        continue;
                    }

                    echo '<td>';

                    if (\in_array($v2, $v[2], true)) {
                        echo $this->lang['stryes'];
                    } else {
                        echo $this->lang['strno'];
                    }

                    // If we have grant option for this, end mark
                    if ($data->hasGrantOption() && \in_array($v2, $v[4], true)) {
                        echo $this->lang['strasterisk'];
                    }

                    echo '</td>' . \PHP_EOL;
                }

                if ($data->hasGrantOption()) {
                    echo '<td>', $this->misc->printVal($v[3]), '</td>' . \PHP_EOL;
                }
                echo '</tr>' . \PHP_EOL;
                ++$i;
            }

            echo '</table>';
        } else {
            echo \sprintf(
                '<p>%s</p>',
                $this->lang['strnoprivileges']
            ) . \PHP_EOL;
        }
       return $this->printGrantLinks();
    }

    public function printGrantLinks() 
    {
        $data = $this->misc->getDatabaseAccessor();
        $subject = $_REQUEST['subject'];
        $alllabel = '';
        $alltxt = '';
        // Links for granting to a user or group
        switch ($subject) {
            case 'table':
            case 'view':
            case 'sequence':
            case 'function':
            case 'tablespace':
                $alllabel = \sprintf(
                    'showall%ss',
                    $subject
                );
                $allurl = \sprintf(
                    '%ss',
                    $subject
                );
                $alltxt = $this->lang[\sprintf(
                    'strshowall%ss',
                    $subject
                )];

                break;
            case 'schema':
                $alllabel = 'showallschemas';
                $allurl = 'schemas';
                $alltxt = $this->lang['strshowallschemas'];

                break;
            case 'database':
                $alllabel = 'showalldatabases';
                $allurl = 'alldb';
                $alltxt = $this->lang['strshowalldatabases'];

                break;
        }

        $object = $_REQUEST[$subject];

        if ('function' === $subject) {
            $objectoid = $_REQUEST[$subject . '_oid'];
            $urlvars = [
                'action' => 'alter',
                'server' => $_REQUEST['server'],
                'database' => $_REQUEST['database'],
                'schema' => $_REQUEST['schema'],
                $subject => $object,
                \sprintf(
                    '%s_oid',
                    $subject
                ) => $objectoid,
                'subject' => $subject,
            ];
        } elseif ('column' === $subject) {
            $urlvars = [
                'action' => 'alter',
                'server' => $_REQUEST['server'],
                'database' => $_REQUEST['database'],
                'schema' => $_REQUEST['schema'],
                $subject => $object,
                'subject' => $subject,
            ];

            if (isset($_REQUEST['table'])) {
                $urlvars['table'] = $_REQUEST['table'];
            } elseif (isset($_REQUEST['view'])) {
                $urlvars['view'] = $_REQUEST['view'];
            } else {
                $urlvars['matview'] = $_REQUEST['matview'];
            }
        } else {
            $urlvars = [
                'action' => 'alter',
                'server' => $_REQUEST['server'],
                'database' => $_REQUEST['database'],
                $subject => $object,
                'subject' => $subject,
            ];

            if (isset($_REQUEST['schema'])) {
                $urlvars['schema'] = $_REQUEST['schema'];
            }
        }

        $navlinks = [
            'grant' => [
                'attr' => [
                    'href' => [
                        'url' => 'privileges',
                        'urlvars' => \array_merge($urlvars, ['mode' => 'grant']),
                    ],
                ],
                'content' => $this->lang['strgrant'],
            ],
            'revoke' => [
                'attr' => [
                    'href' => [
                        'url' => 'privileges',
                        'urlvars' => \array_merge($urlvars, ['mode' => 'revoke']),
                    ],
                ],
                'content' => $this->lang['strrevoke'],
            ],
        ];

        if (isset($allurl)) {
            $navlinks[$alllabel] = [
                'attr' => [
                    'href' => [
                        'url' => $allurl,
                        'urlvars' => [
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                        ],
                    ],
                ],
                'content' => $alltxt,
            ];

            if (isset($_REQUEST['schema'])) {
                $navlinks[$alllabel]['attr']['href']['urlvars']['schema'] = $_REQUEST['schema'];
            }
        }

return  $this->printNavLinks($navlinks, $this->table_place, \get_defined_vars());
    }

    /**
     * Prints the form to grants permision on an object to a user.
     *
     * @param string $mode either grant or revoke
     * @param string $msg  The message
     */
    public function formAlter($mode, $msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'username', []);

        $this->coalesceArr($_REQUEST, 'groupname', []);

        $this->coalesceArr($_REQUEST, 'privilege', []);

        // Get users from the database
        $users = $data->getUsers();
        // Get groups from the database
        $groups = $data->getGroups();

        $this->printTrail($_REQUEST['subject']);

        $this->printTitle($this->lang['str' . $mode], 'pg.privilege.' . $mode);

        $this->printMsg($msg);

        echo '<form action="privileges" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data left">%s</th>',
            $this->lang['strusers']
        ) . \PHP_EOL;
        echo '<td class="data1"><select name="username[]" multiple="multiple" size="', \min(6, $users->RecordCount()), '">' . \PHP_EOL;

        while (!$users->EOF) {
            $uname = \htmlspecialchars($users->fields['usename']);
            echo \sprintf(
                '<option value="%s"',
                $uname
            ),
            \in_array($users->fields['usename'], $_REQUEST['username'], true) ? ' selected="selected"' : '', \sprintf(
                '>%s</option>',
                $uname
            ) . \PHP_EOL;
            $users->MoveNext();
        }
        echo '</select></td></tr>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data left">%s</th>',
            $this->lang['strgroups']
        ) . \PHP_EOL;
        echo '<td class="data1">' . \PHP_EOL;
        echo '<input type="checkbox" id="public" name="public"', (isset($_REQUEST['public']) ? ' checked="checked"' : ''), ' /><label for="public">PUBLIC</label>' . \PHP_EOL;
        // Only show groups if there are groups!
        if (0 < $groups->RecordCount()) {
            echo '<br /><select name="groupname[]" multiple="multiple" size="', \min(6, $groups->RecordCount()), '">' . \PHP_EOL;

            while (!$groups->EOF) {
                $gname = \htmlspecialchars($groups->fields['groname']);
                echo \sprintf(
                    '<option value="%s"',
                    $gname
                ),
                \in_array($groups->fields['groname'], $_REQUEST['groupname'], true) ? ' selected="selected"' : '', \sprintf(
                    '>%s</option>',
                    $gname
                ) . \PHP_EOL;
                $groups->MoveNext();
            }
            echo '</select>' . \PHP_EOL;
        }
        echo '</td></tr>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data left required">%s</th>',
            $this->lang['strprivileges']
        ) . \PHP_EOL;
        echo '<td class="data1">' . \PHP_EOL;

        foreach ($data->privlist[$_REQUEST['subject']] as $v) {
            $v = \htmlspecialchars($v);
            echo \sprintf(
                '<input type="checkbox" id="privilege[%s]" name="privilege[%s]"',
                $v,
                $v
            ),
            isset($_REQUEST['privilege'][$v]) ? ' checked="checked"' : '', \sprintf(
                ' /><label for="privilege[%s]">%s</label><br />',
                $v,
                $v
            ) . \PHP_EOL;
        }
        echo '</td></tr>' . \PHP_EOL;
        // Grant option
        if ($data->hasGrantOption()) {
            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['stroptions']
            ) . \PHP_EOL;
            echo '<td class="data1">' . \PHP_EOL;

            if ('grant' === $mode) {
                echo '<input type="checkbox" id="grantoption" name="grantoption"',
                isset($_REQUEST['grantoption']) ? ' checked="checked"' : '', ' /><label for="grantoption">GRANT OPTION</label>' . \PHP_EOL;
            } elseif ('revoke' === $mode) {
                echo '<input type="checkbox" id="grantoption" name="grantoption"',
                isset($_REQUEST['grantoption']) ? ' checked="checked"' : '', ' /><label for="grantoption">GRANT OPTION FOR</label><br />' . \PHP_EOL;
                echo '<input type="checkbox" id="cascade" name="cascade"',
                isset($_REQUEST['cascade']) ? ' checked="checked"' : '', ' /><label for="cascade">CASCADE</label><br />' . \PHP_EOL;
            }
            echo '</td></tr>' . \PHP_EOL;
        }
        echo '</table>' . \PHP_EOL;

        echo '<p><input type="hidden" name="action" value="save" />' . \PHP_EOL;
        echo '<input type="hidden" name="mode" value="', \htmlspecialchars($mode), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="subject" value="', \htmlspecialchars($_REQUEST['subject']), '" />' . \PHP_EOL;

        if (isset($_REQUEST[$_REQUEST['subject'] . '_oid'])) {
            echo '<input type="hidden" name="', \htmlspecialchars($_REQUEST['subject'] . '_oid'),
            '" value="', \htmlspecialchars($_REQUEST[$_REQUEST['subject'] . '_oid']), '" />' . \PHP_EOL;
        }

        echo '<input type="hidden" name="', \htmlspecialchars($_REQUEST['subject']),
        '" value="', \htmlspecialchars($_REQUEST[$_REQUEST['subject']]), '" />' . \PHP_EOL;

        if ('column' === $_REQUEST['subject']) {
            echo '<input type="hidden" name="table" value="',
            \htmlspecialchars($_REQUEST['table']), '" />' . \PHP_EOL;
        }

        echo $this->view->form;
        echo \sprintf(
            '<input type="submit" name="%s" value="%s" />%s',
            $mode,
            $this->lang['str' . $mode],
            \PHP_EOL
        );

        echo \sprintf(
            '<input type="submit" name="cancel" value="%s" /></p>',
            $this->lang['strcancel']
        );
        echo '</form>' . \PHP_EOL;
    }

    /**
     * Grant permissions on an object to a user.
     *
     * @param string $mode 'grant' or 'revoke'
     */
    public function doAlter($mode): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'username', []);

        $this->coalesceArr($_REQUEST, 'groupname', []);

        $this->coalesceArr($_REQUEST, 'privilege', []);

        // Determine whether object should be ref'd by name or oid.
        if (isset($_REQUEST[$_REQUEST['subject'] . '_oid'])) {
            $object = $_REQUEST[$_REQUEST['subject'] . '_oid'];
        } else {
            $object = $_REQUEST[$_REQUEST['subject']];
        }

        $table = $_REQUEST['table'] ?? null;

        $status = $data->setPrivileges(
            ('grant' === $mode) ? 'GRANT' : 'REVOKE',
            $_REQUEST['subject'],
            $object,
            isset($_REQUEST['public']),
            $_REQUEST['username'],
            $_REQUEST['groupname'],
            \array_keys($_REQUEST['privilege']),
            isset($_REQUEST['grantoption']),
            isset($_REQUEST['cascade']),
            $table
        );

        if (0 === $status) {
            $this->doDefault($this->lang['strgranted']);
        } elseif (-3 === $status || -4 === $status) {
            $this->formAlter($_REQUEST['mode'], $this->lang['strgrantbad']);
        } else {
            $this->formAlter($_REQUEST['mode'], $this->lang['strgrantfailed']);
        }
    }
}
