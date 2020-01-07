<?php

/**
 * PHPPgAdmin v6.0.0-RC1
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class DomainsController extends BaseController
{
    public $controller_title = 'strdomains';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }

        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            case 'add_check':
                $this->addCheck(true);

                break;
            case 'save_add_check':
                if (isset($_POST['cancel'])) {
                    $this->doProperties();
                } else {
                    $this->addCheck(false);
                }

                break;
            case 'drop_con':
                if (isset($_POST['drop'])) {
                    $this->doDropConstraint(false);
                } else {
                    $this->doProperties();
                }

                break;
            case 'confirm_drop_con':
                $this->doDropConstraint(true);

                break;
            case 'save_create':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'create':
                $this->doCreate();

                break;
            case 'drop':
                if (isset($_POST['drop'])) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'save_alter':
                if (isset($_POST['alter'])) {
                    $this->doSaveAlter();
                } else {
                    $this->doProperties();
                }

                break;
            case 'alter':
                $this->doAlter();

                break;
            case 'properties':
                $this->doProperties();

                break;
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * Show default list of domains in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'domains');
        $this->printMsg($msg);

        $domains = $data->getDomains();

        $columns = [
            'domain'  => [
                'title' => $this->lang['strdomain'],
                'field' => Decorator::field('domname'),
                'url'   => "domains?action=properties&amp;{$this->misc->href}&amp;",
                'vars'  => ['domain' => 'domname'],
            ],
            'type'    => [
                'title' => $this->lang['strtype'],
                'field' => Decorator::field('domtype'),
            ],
            'notnull' => [
                'title'  => $this->lang['strnotnull'],
                'field'  => Decorator::field('domnotnull'),
                'type'   => 'bool',
                'params' => ['true' => 'NOT NULL', 'false' => ''],
            ],
            'default' => [
                'title' => $this->lang['strdefault'],
                'field' => Decorator::field('domdef'),
            ],
            'owner'   => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('domowner'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('domcomment'),
            ],
        ];

        $actions = [
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'domains',
                        'urlvars' => [
                            'action' => 'alter',
                            'domain' => Decorator::field('domname'),
                        ],
                    ],
                ],
            ],
            'drop'  => [
                'content' => $this->lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'domains',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'domain' => Decorator::field('domname'),
                        ],
                    ],
                ],
            ],
        ];

        if (!$data->hasAlterDomains()) {
            unset($actions['alter']);
        }

        echo $this->printTable($domains, $columns, $actions, 'domains-domains', $this->lang['strnodomains']);

        $navlinks = [
            'create' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'domains',
                        'urlvars' => [
                            'action'   => 'create',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strcreatedomain'],
            ],
        ];
        $this->printNavLinks($navlinks, 'domains-domains', get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $domains = $data->getDomains();

        $reqvars = $this->misc->getRequestVars('domain');

        $attrs = [
            'text'    => Decorator::field('domname'),
            'icon'    => 'Domain',
            'toolTip' => Decorator::field('domcomment'),
            'action'  => Decorator::actionurl(
                'domains',
                $reqvars,
                [
                    'action' => 'properties',
                    'domain' => Decorator::field('domname'),
                ]
            ),
        ];

        return $this->printTree($domains, $attrs, 'domains');
    }

    /**
     * Function to save after altering a domain.
     */
    public function doSaveAlter()
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->alterDomain(
            $_POST['domain'],
            $_POST['domdefault'],
            isset($_POST['domnotnull']),
            $_POST['domowner']
        );
        if (0 == $status) {
            $this->doProperties($this->lang['strdomainaltered']);
        } else {
            $this->doAlter($this->lang['strdomainalteredbad']);
        }
    }

    /**
     * Allow altering a domain.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('domain');
        $this->printTitle($this->lang['stralter'], 'pg.domain.alter');
        $this->printMsg($msg);

        // Fetch domain info
        $domaindata = $data->getDomain($_REQUEST['domain']);
        // Fetch all users
        $users = $data->getUsers();

        if ($domaindata->recordCount() > 0) {
            if (!isset($_POST['domname'])) {
                $_POST['domtype']                 = $domaindata->fields['domtype'];
                $_POST['domdefault']              = $domaindata->fields['domdef'];
                $domaindata->fields['domnotnull'] = $data->phpBool($domaindata->fields['domnotnull']);
                if ($domaindata->fields['domnotnull']) {
                    $_POST['domnotnull'] = 'on';
                }

                $_POST['domowner'] = $domaindata->fields['domowner'];
            }

            // Display domain info
            echo '<form action="'.\SUBFOLDER.'/src/views/domains" method="post">'.PHP_EOL;
            echo '<table>'.PHP_EOL;
            echo "<tr><th class=\"data left required\" style=\"width: 70px\">{$this->lang['strname']}</th>".PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domname']), '</td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left required\">{$this->lang['strtype']}</th>".PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domtype']), '</td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left\"><label for=\"domnotnull\">{$this->lang['strnotnull']}</label></th>".PHP_EOL;
            echo '<td class="data1"><input type="checkbox" id="domnotnull" name="domnotnull"', (isset($_POST['domnotnull']) ? ' checked="checked"' : ''), ' /></td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left\">{$this->lang['strdefault']}</th>".PHP_EOL;
            echo '<td class="data1"><input name="domdefault" size="32" value="',
            htmlspecialchars($_POST['domdefault']), '" /></td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left required\">{$this->lang['strowner']}</th>".PHP_EOL;
            echo '<td class="data1"><select name="domowner">';
            while (!$users->EOF) {
                $uname = $users->fields['usename'];
                echo '<option value="', htmlspecialchars($uname), '"',
                ($uname == $_POST['domowner']) ? ' selected="selected"' : '', '>', htmlspecialchars($uname), '</option>'.PHP_EOL;
                $users->moveNext();
            }
            echo '</select></td></tr>'.PHP_EOL;
            echo '</table>'.PHP_EOL;
            echo '<p><input type="hidden" name="action" value="save_alter" />'.PHP_EOL;
            echo '<input type="hidden" name="domain" value="', htmlspecialchars($_REQUEST['domain']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            echo "<p>{$this->lang['strnodata']}</p>".PHP_EOL;
        }
    }

    /**
     * Confirm and then actually add a CHECK constraint.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function addCheck($confirm, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'name', '');

        $this->coalesceArr($_POST, 'definition', '');

        if ($confirm) {
            $this->printTrail('domain');
            $this->printTitle($this->lang['straddcheck'], 'pg.constraint.check');
            $this->printMsg($msg);

            echo '<form action="'.\SUBFOLDER.'/src/views/domains" method="post">'.PHP_EOL;
            echo '<table>'.PHP_EOL;
            echo "<tr><th class=\"data\">{$this->lang['strname']}</th>".PHP_EOL;
            echo "<th class=\"data required\">{$this->lang['strdefinition']}</th></tr>".PHP_EOL;

            echo "<tr><td class=\"data1\"><input name=\"name\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['name']), '" /></td>'.PHP_EOL;

            echo '<td class="data1">(<input name="definition" size="32" value="',
            htmlspecialchars($_POST['definition']), '" />)</td></tr>'.PHP_EOL;
            echo '</table>'.PHP_EOL;

            echo '<p><input type="hidden" name="action" value="save_add_check" />'.PHP_EOL;
            echo '<input type="hidden" name="domain" value="', htmlspecialchars($_REQUEST['domain']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"add\" value=\"{$this->lang['stradd']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            if ('' == trim($_POST['definition'])) {
                $this->addCheck(true, $this->lang['strcheckneedsdefinition']);
            } else {
                $status = $data->addDomainCheckConstraint(
                    $_POST['domain'],
                    $_POST['definition'],
                    $_POST['name']
                );
                if (0 == $status) {
                    $this->doProperties($this->lang['strcheckadded']);
                } else {
                    $this->addCheck(true, $this->lang['strcheckaddedbad']);
                }
            }
        }
    }

    /**
     * Show confirmation of drop constraint and perform actual drop.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doDropConstraint($confirm, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('domain');
            $this->printTitle($this->lang['strdrop'], 'pg.constraint.drop');
            $this->printMsg($msg);

            echo '<p>', sprintf(
                $this->lang['strconfdropconstraint'],
                $this->misc->printVal($_REQUEST['constraint']),
                $this->misc->printVal($_REQUEST['domain'])
            ), '</p>'.PHP_EOL;
            echo '<form action="'.\SUBFOLDER.'/src/views/domains" method="post">'.PHP_EOL;
            echo '<input type="hidden" name="action" value="drop_con" />'.PHP_EOL;
            echo '<input type="hidden" name="domain" value="', htmlspecialchars($_REQUEST['domain']), '" />'.PHP_EOL;
            echo '<input type="hidden" name="constraint" value="', htmlspecialchars($_REQUEST['constraint']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>".PHP_EOL;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->dropDomainConstraint($_POST['domain'], $_POST['constraint'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doProperties($this->lang['strconstraintdropped']);
            } else {
                $this->doDropConstraint(true, $this->lang['strconstraintdroppedbad']);
            }
        }
    }

    /**
     * Show properties for a domain.  Allow manipulating constraints as well.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('domain');
        $this->printTitle($this->lang['strproperties'], 'pg.domain');
        $this->printMsg($msg);

        $domaindata = $data->getDomain($_REQUEST['domain']);

        if ($domaindata->recordCount() > 0) {
            // Show comment if any
            if (null !== $domaindata->fields['domcomment']) {
                echo '<p class="comment">', $this->misc->printVal($domaindata->fields['domcomment']), '</p>'.PHP_EOL;
            }

            // Display domain info
            $domaindata->fields['domnotnull'] = $data->phpBool($domaindata->fields['domnotnull']);
            echo '<table>'.PHP_EOL;
            echo "<tr><th class=\"data left\" style=\"width: 70px\">{$this->lang['strname']}</th>".PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domname']), '</td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left\">{$this->lang['strtype']}</th>".PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domtype']), '</td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left\">{$this->lang['strnotnull']}</th>".PHP_EOL;
            echo '<td class="data1">', ($domaindata->fields['domnotnull'] ? 'NOT NULL' : ''), '</td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left\">{$this->lang['strdefault']}</th>".PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domdef']), '</td></tr>'.PHP_EOL;
            echo "<tr><th class=\"data left\">{$this->lang['strowner']}</th>".PHP_EOL;
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domowner']), '</td></tr>'.PHP_EOL;
            echo '</table>'.PHP_EOL;

            // Display domain constraints
            echo "<h3>{$this->lang['strconstraints']}</h3>".PHP_EOL;
            if ($data->hasDomainConstraints()) {
                $domaincons = $data->getDomainConstraints($_REQUEST['domain']);

                $columns = [
                    'name'       => [
                        'title' => $this->lang['strname'],
                        'field' => Decorator::field('conname'),
                    ],
                    'definition' => [
                        'title' => $this->lang['strdefinition'],
                        'field' => Decorator::field('consrc'),
                    ],
                    'actions'    => [
                        'title' => $this->lang['stractions'],
                    ],
                ];

                $actions = [
                    'drop' => [
                        'content' => $this->lang['strdrop'],
                        'attr'    => [
                            'href' => [
                                'url'     => 'domains',
                                'urlvars' => [
                                    'action'     => 'confirm_drop_con',
                                    'domain'     => $_REQUEST['domain'],
                                    'constraint' => Decorator::field('conname'),
                                    'type'       => Decorator::field('contype'),
                                ],
                            ],
                        ],
                    ],
                ];

                echo $this->printTable($domaincons, $columns, $actions, 'domains-properties', $this->lang['strnodata']);
            }
        } else {
            echo "<p>{$this->lang['strnodata']}</p>".PHP_EOL;
        }

        $navlinks = [
            'drop' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'domains',
                        'urlvars' => [
                            'action'   => 'confirm_drop',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'domain'   => $_REQUEST['domain'],
                        ],
                    ],
                ],
                'content' => $this->lang['strdrop'],
            ],
        ];
        if ($data->hasAlterDomains()) {
            $navlinks['addcheck'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'domains',
                        'urlvars' => [
                            'action'   => 'add_check',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'domain'   => $_REQUEST['domain'],
                        ],
                    ],
                ],
                'content' => $this->lang['straddcheck'],
            ];
            $navlinks['alter'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'domains',
                        'urlvars' => [
                            'action'   => 'alter',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'domain'   => $_REQUEST['domain'],
                        ],
                    ],
                ],
                'content' => $this->lang['stralter'],
            ];
        }

        $this->printNavLinks($navlinks, 'domains-properties', get_defined_vars());
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
            $this->printTrail('domain');
            $this->printTitle($this->lang['strdrop'], 'pg.domain.drop');

            echo '<p>', sprintf($this->lang['strconfdropdomain'], $this->misc->printVal($_REQUEST['domain'])), '</p>'.PHP_EOL;
            echo '<form action="'.\SUBFOLDER.'/src/views/domains" method="post">'.PHP_EOL;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /><label for=\"cascade\">{$this->lang['strcascade']}</label></p>".PHP_EOL;
            echo '<p><input type="hidden" name="action" value="drop" />'.PHP_EOL;
            echo '<input type="hidden" name="domain" value="', htmlspecialchars($_REQUEST['domain']), '" />'.PHP_EOL;
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />".PHP_EOL;
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
            echo '</form>'.PHP_EOL;
        } else {
            $status = $data->dropDomain($_POST['domain'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($this->lang['strdomaindropped']);
            } else {
                $this->doDefault($this->lang['strdomaindroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where they can enter a new domain.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'domname', '');

        $this->coalesceArr($_POST, 'domtype', '');

        $this->coalesceArr($_POST, 'domlength', '');

        $this->coalesceArr($_POST, 'domarray', '');

        $this->coalesceArr($_POST, 'domdefault', '');

        $this->coalesceArr($_POST, 'domcheck', '');

        $types = $data->getTypes(true);

        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreatedomain'], 'pg.domain.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER.'/src/views/domains" method="post">'.PHP_EOL;
        echo '<table>'.PHP_EOL;
        echo "<tr><th class=\"data left required\" style=\"width: 70px\">{$this->lang['strname']}</th>".PHP_EOL;
        echo "<td class=\"data1\"><input name=\"domname\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_POST['domname']), '" /></td></tr>'.PHP_EOL;
        echo "<tr><th class=\"data left required\">{$this->lang['strtype']}</th>".PHP_EOL;
        echo '<td class="data1">'.PHP_EOL;
        // Output return type list
        echo '<select name="domtype">'.PHP_EOL;
        while (!$types->EOF) {
            echo '<option value="', htmlspecialchars($types->fields['typname']), '"',
            ($types->fields['typname'] == $_POST['domtype']) ? ' selected="selected"' : '', '>',
            $this->misc->printVal($types->fields['typname']), '</option>'.PHP_EOL;
            $types->moveNext();
        }
        echo '</select>'.PHP_EOL;

        // Type length
        echo '<input type="text" size="4" name="domlength" value="', htmlspecialchars($_POST['domlength']), '" />';

        // Output array type selector
        echo '<select name="domarray">'.PHP_EOL;
        echo '<option value=""', ('' == $_POST['domarray']) ? ' selected="selected"' : '', '></option>'.PHP_EOL;
        echo '<option value="[]"', ('[]' == $_POST['domarray']) ? ' selected="selected"' : '', '>[ ]</option>'.PHP_EOL;
        echo '</select></td></tr>'.PHP_EOL;

        echo "<tr><th class=\"data left\"><label for=\"domnotnull\">{$this->lang['strnotnull']}</label></th>".PHP_EOL;
        echo '<td class="data1"><input type="checkbox" id="domnotnull" name="domnotnull"',
        (isset($_POST['domnotnull']) ? ' checked="checked"' : ''), ' /></td></tr>'.PHP_EOL;
        echo "<tr><th class=\"data left\">{$this->lang['strdefault']}</th>".PHP_EOL;
        echo '<td class="data1"><input name="domdefault" size="32" value="',
        htmlspecialchars($_POST['domdefault']), '" /></td></tr>'.PHP_EOL;
        if ($data->hasDomainConstraints()) {
            echo "<tr><th class=\"data left\">{$this->lang['strconstraints']}</th>".PHP_EOL;
            echo '<td class="data1">CHECK (<input name="domcheck" size="32" value="',
            htmlspecialchars($_POST['domcheck']), '" />)</td></tr>'.PHP_EOL;
        }
        echo '</table>'.PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create" />'.PHP_EOL;
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;
    }

    /**
     * Actually creates the new domain in the database.
     */
    public function doSaveCreate()
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'domcheck', '');

        // Check that they've given a name and a definition
        if ('' == $_POST['domname']) {
            $this->doCreate($this->lang['strdomainneedsname']);
        } else {
            $status = $data->createDomain(
                $_POST['domname'],
                $_POST['domtype'],
                $_POST['domlength'],
                '' != $_POST['domarray'],
                isset($_POST['domnotnull']),
                $_POST['domdefault'],
                $_POST['domcheck']
            );
            if (0 == $status) {
                $this->doDefault($this->lang['strdomaincreated']);
            } else {
                $this->doCreate($this->lang['strdomaincreatedbad']);
            }
        }
    }
}
