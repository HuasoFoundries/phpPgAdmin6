<?php

/**
 * PHPPgAdmin v6.0.0-beta.40
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
    public $controller_name = 'DomainsController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang = $this->lang;

        $action = $this->action;
        if ('tree' == $action) {
            return $this->doTree();
        }

        $this->printHeader($lang['strdomains']);
        $this->printBody();

        switch ($action) {
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
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'domains');
        $this->printMsg($msg);

        $domains = $data->getDomains();

        $columns = [
            'domain'  => [
                'title' => $lang['strdomain'],
                'field' => Decorator::field('domname'),
                'url'   => "domains?action=properties&amp;{$this->misc->href}&amp;",
                'vars'  => ['domain' => 'domname'],
            ],
            'type'    => [
                'title' => $lang['strtype'],
                'field' => Decorator::field('domtype'),
            ],
            'notnull' => [
                'title'  => $lang['strnotnull'],
                'field'  => Decorator::field('domnotnull'),
                'type'   => 'bool',
                'params' => ['true' => 'NOT NULL', 'false' => ''],
            ],
            'default' => [
                'title' => $lang['strdefault'],
                'field' => Decorator::field('domdef'),
            ],
            'owner'   => [
                'title' => $lang['strowner'],
                'field' => Decorator::field('domowner'),
            ],
            'actions' => [
                'title' => $lang['stractions'],
            ],
            'comment' => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('domcomment'),
            ],
        ];

        $actions = [
            'alter' => [
                'content' => $lang['stralter'],
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
                'content' => $lang['strdrop'],
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

        echo $this->printTable($domains, $columns, $actions, 'domains-domains', $lang['strnodomains']);

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
                'content' => $lang['strcreatedomain'],
            ],
        ];
        $this->printNavLinks($navlinks, 'domains-domains', get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $lang = $this->lang;
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
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->alterDomain(
            $_POST['domain'],
            $_POST['domdefault'],
            isset($_POST['domnotnull']),
            $_POST['domowner']
        );
        if (0 == $status) {
            $this->doProperties($lang['strdomainaltered']);
        } else {
            $this->doAlter($lang['strdomainalteredbad']);
        }
    }

    /**
     * Allow altering a domain.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('domain');
        $this->printTitle($lang['stralter'], 'pg.domain.alter');
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
            echo '<form action="'.\SUBFOLDER."/src/views/domains\" method=\"post\">\n";
            echo "<table>\n";
            echo "<tr><th class=\"data left required\" style=\"width: 70px\">{$lang['strname']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domname']), "</td></tr>\n";
            echo "<tr><th class=\"data left required\">{$lang['strtype']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domtype']), "</td></tr>\n";
            echo "<tr><th class=\"data left\"><label for=\"domnotnull\">{$lang['strnotnull']}</label></th>\n";
            echo '<td class="data1"><input type="checkbox" id="domnotnull" name="domnotnull"', (isset($_POST['domnotnull']) ? ' checked="checked"' : ''), " /></td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strdefault']}</th>\n";
            echo '<td class="data1"><input name="domdefault" size="32" value="',
            htmlspecialchars($_POST['domdefault']), "\" /></td></tr>\n";
            echo "<tr><th class=\"data left required\">{$lang['strowner']}</th>\n";
            echo '<td class="data1"><select name="domowner">';
            while (!$users->EOF) {
                $uname = $users->fields['usename'];
                echo '<option value="', htmlspecialchars($uname), '"',
                ($uname == $_POST['domowner']) ? ' selected="selected"' : '', '>', htmlspecialchars($uname), "</option>\n";
                $users->moveNext();
            }
            echo "</select></td></tr>\n";
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"save_alter\" />\n";
            echo '<input type="hidden" name="domain" value="', htmlspecialchars($_REQUEST['domain']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            echo "<p>{$lang['strnodata']}</p>\n";
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
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['name'])) {
            $_POST['name'] = '';
        }

        if (!isset($_POST['definition'])) {
            $_POST['definition'] = '';
        }

        if ($confirm) {
            $this->printTrail('domain');
            $this->printTitle($lang['straddcheck'], 'pg.constraint.check');
            $this->printMsg($msg);

            echo '<form action="'.\SUBFOLDER."/src/views/domains\" method=\"post\">\n";
            echo "<table>\n";
            echo "<tr><th class=\"data\">{$lang['strname']}</th>\n";
            echo "<th class=\"data required\">{$lang['strdefinition']}</th></tr>\n";

            echo "<tr><td class=\"data1\"><input name=\"name\" size=\"16\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['name']), "\" /></td>\n";

            echo '<td class="data1">(<input name="definition" size="32" value="',
            htmlspecialchars($_POST['definition']), "\" />)</td></tr>\n";
            echo "</table>\n";

            echo "<p><input type=\"hidden\" name=\"action\" value=\"save_add_check\" />\n";
            echo '<input type="hidden" name="domain" value="', htmlspecialchars($_REQUEST['domain']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"add\" value=\"{$lang['stradd']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            if ('' == trim($_POST['definition'])) {
                $this->addCheck(true, $lang['strcheckneedsdefinition']);
            } else {
                $status = $data->addDomainCheckConstraint(
                    $_POST['domain'],
                    $_POST['definition'],
                    $_POST['name']
                );
                if (0 == $status) {
                    $this->doProperties($lang['strcheckadded']);
                } else {
                    $this->addCheck(true, $lang['strcheckaddedbad']);
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
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('domain');
            $this->printTitle($lang['strdrop'], 'pg.constraint.drop');
            $this->printMsg($msg);

            echo '<p>', sprintf(
                $lang['strconfdropconstraint'],
                $this->misc->printVal($_REQUEST['constraint']),
                $this->misc->printVal($_REQUEST['domain'])
            ), "</p>\n";
            echo '<form action="'.\SUBFOLDER."/src/views/domains\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"drop_con\" />\n";
            echo '<input type="hidden" name="domain" value="', htmlspecialchars($_REQUEST['domain']), "\" />\n";
            echo '<input type="hidden" name="constraint" value="', htmlspecialchars($_REQUEST['constraint']), "\" />\n";
            echo $this->misc->form;
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$lang['strcascade']}</label></p>\n";
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            echo "</form>\n";
        } else {
            $status = $data->dropDomainConstraint($_POST['domain'], $_POST['constraint'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doProperties($lang['strconstraintdropped']);
            } else {
                $this->doDropConstraint(true, $lang['strconstraintdroppedbad']);
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
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('domain');
        $this->printTitle($lang['strproperties'], 'pg.domain');
        $this->printMsg($msg);

        $domaindata = $data->getDomain($_REQUEST['domain']);

        if ($domaindata->recordCount() > 0) {
            // Show comment if any
            if (null !== $domaindata->fields['domcomment']) {
                echo '<p class="comment">', $this->misc->printVal($domaindata->fields['domcomment']), "</p>\n";
            }

            // Display domain info
            $domaindata->fields['domnotnull'] = $data->phpBool($domaindata->fields['domnotnull']);
            echo "<table>\n";
            echo "<tr><th class=\"data left\" style=\"width: 70px\">{$lang['strname']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domname']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strtype']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domtype']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strnotnull']}</th>\n";
            echo '<td class="data1">', ($domaindata->fields['domnotnull'] ? 'NOT NULL' : ''), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strdefault']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domdef']), "</td></tr>\n";
            echo "<tr><th class=\"data left\">{$lang['strowner']}</th>\n";
            echo '<td class="data1">', $this->misc->printVal($domaindata->fields['domowner']), "</td></tr>\n";
            echo "</table>\n";

            // Display domain constraints
            echo "<h3>{$lang['strconstraints']}</h3>\n";
            if ($data->hasDomainConstraints()) {
                $domaincons = $data->getDomainConstraints($_REQUEST['domain']);

                $columns = [
                    'name'       => [
                        'title' => $lang['strname'],
                        'field' => Decorator::field('conname'),
                    ],
                    'definition' => [
                        'title' => $lang['strdefinition'],
                        'field' => Decorator::field('consrc'),
                    ],
                    'actions'    => [
                        'title' => $lang['stractions'],
                    ],
                ];

                $actions = [
                    'drop' => [
                        'content' => $lang['strdrop'],
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

                echo $this->printTable($domaincons, $columns, $actions, 'domains-properties', $lang['strnodata']);
            }
        } else {
            echo "<p>{$lang['strnodata']}</p>\n";
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
                'content' => $lang['strdrop'],
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
                'content' => $lang['straddcheck'],
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
                'content' => $lang['stralter'],
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
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('domain');
            $this->printTitle($lang['strdrop'], 'pg.domain.drop');

            echo '<p>', sprintf($lang['strconfdropdomain'], $this->misc->printVal($_REQUEST['domain'])), "</p>\n";
            echo '<form action="'.\SUBFOLDER."/src/views/domains\" method=\"post\">\n";
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /><label for=\"cascade\">{$lang['strcascade']}</label></p>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo '<input type="hidden" name="domain" value="', htmlspecialchars($_REQUEST['domain']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            $status = $data->dropDomain($_POST['domain'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->doDefault($lang['strdomaindropped']);
            } else {
                $this->doDefault($lang['strdomaindroppedbad']);
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
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['domname'])) {
            $_POST['domname'] = '';
        }

        if (!isset($_POST['domtype'])) {
            $_POST['domtype'] = '';
        }

        if (!isset($_POST['domlength'])) {
            $_POST['domlength'] = '';
        }

        if (!isset($_POST['domarray'])) {
            $_POST['domarray'] = '';
        }

        if (!isset($_POST['domdefault'])) {
            $_POST['domdefault'] = '';
        }

        if (!isset($_POST['domcheck'])) {
            $_POST['domcheck'] = '';
        }

        $types = $data->getTypes(true);

        $this->printTrail('schema');
        $this->printTitle($lang['strcreatedomain'], 'pg.domain.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/domains\" method=\"post\">\n";
        echo "<table>\n";
        echo "<tr><th class=\"data left required\" style=\"width: 70px\">{$lang['strname']}</th>\n";
        echo "<td class=\"data1\"><input name=\"domname\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_POST['domname']), "\" /></td></tr>\n";
        echo "<tr><th class=\"data left required\">{$lang['strtype']}</th>\n";
        echo "<td class=\"data1\">\n";
        // Output return type list
        echo "<select name=\"domtype\">\n";
        while (!$types->EOF) {
            echo '<option value="', htmlspecialchars($types->fields['typname']), '"',
            ($types->fields['typname'] == $_POST['domtype']) ? ' selected="selected"' : '', '>',
            $this->misc->printVal($types->fields['typname']), "</option>\n";
            $types->moveNext();
        }
        echo "</select>\n";

        // Type length
        echo '<input type="text" size="4" name="domlength" value="', htmlspecialchars($_POST['domlength']), '" />';

        // Output array type selector
        echo "<select name=\"domarray\">\n";
        echo '<option value=""', ('' == $_POST['domarray']) ? ' selected="selected"' : '', "></option>\n";
        echo '<option value="[]"', ('[]' == $_POST['domarray']) ? ' selected="selected"' : '', ">[ ]</option>\n";
        echo "</select></td></tr>\n";

        echo "<tr><th class=\"data left\"><label for=\"domnotnull\">{$lang['strnotnull']}</label></th>\n";
        echo '<td class="data1"><input type="checkbox" id="domnotnull" name="domnotnull"',
        (isset($_POST['domnotnull']) ? ' checked="checked"' : ''), " /></td></tr>\n";
        echo "<tr><th class=\"data left\">{$lang['strdefault']}</th>\n";
        echo '<td class="data1"><input name="domdefault" size="32" value="',
        htmlspecialchars($_POST['domdefault']), "\" /></td></tr>\n";
        if ($data->hasDomainConstraints()) {
            echo "<tr><th class=\"data left\">{$lang['strconstraints']}</th>\n";
            echo '<td class="data1">CHECK (<input name="domcheck" size="32" value="',
            htmlspecialchars($_POST['domcheck']), "\" />)</td></tr>\n";
        }
        echo "</table>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
    }

    /**
     * Actually creates the new domain in the database.
     */
    public function doSaveCreate()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['domcheck'])) {
            $_POST['domcheck'] = '';
        }

        // Check that they've given a name and a definition
        if ('' == $_POST['domname']) {
            $this->doCreate($lang['strdomainneedsname']);
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
                $this->doDefault($lang['strdomaincreated']);
            } else {
                $this->doCreate($lang['strdomaincreatedbad']);
            }
        }
    }
}
