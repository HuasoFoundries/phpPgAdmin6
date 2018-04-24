<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class FulltextController extends BaseController
{
    public $controller_name = 'FulltextController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' == $this->action) {
            return $this->doTree();
        }
        if ('subtree' == $this->action) {
            return $this->doSubTree($_REQUEST['what']);
        }

        $this->printHeader($this->lang['strschemas']);
        $this->printBody();

        if (isset($_POST['cancel'])) {
            if (isset($_POST['prev_action'])) {
                $this->action = $_POST['prev_action'];
            } else {
                $this->action = '';
            }
        }

        switch ($this->action) {
            case 'createconfig':
                if (isset($_POST['create'])) {
                    $this->doSaveCreateConfig();
                } else {
                    $this->doCreateConfig();
                }

                break;
            case 'alterconfig':
                if (isset($_POST['alter'])) {
                    $this->doSaveAlterConfig();
                } else {
                    $this->doAlterConfig();
                }

                break;
            case 'dropconfig':
                if (isset($_POST['drop'])) {
                    $this->doDropConfig(false);
                } else {
                    $this->doDropConfig(true);
                }

                break;
            case 'viewconfig':
                $this->doViewConfig($_REQUEST['ftscfg']);

                break;
            case 'viewparsers':
                $this->doViewParsers();

                break;
            case 'viewdicts':
                $this->doViewDicts();

                break;
            case 'createdict':
                if (isset($_POST['create'])) {
                    $this->doSaveCreateDict();
                } else {
                    $this->doCreateDict();
                }

                break;
            case 'alterdict':
                if (isset($_POST['alter'])) {
                    $this->doSaveAlterDict();
                } else {
                    $this->doAlterDict();
                }

                break;
            case 'dropdict':
                if (isset($_POST['drop'])) {
                    $this->doDropDict(false);
                } else {
                    $this->doDropDict(true);
                }

                break;
            case 'dropmapping':
                if (isset($_POST['drop'])) {
                    $this->doDropMapping(false);
                } else {
                    $this->doDropMapping(true);
                }

                break;
            case 'altermapping':
                if (isset($_POST['alter'])) {
                    $this->doSaveAlterMapping();
                } else {
                    $this->doAlterMapping();
                }

                break;
            case 'addmapping':
                if (isset($_POST['add'])) {
                    $this->doSaveAddMapping();
                } else {
                    $this->doAddMapping();
                }

                break;
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'fulltext');
        $this->printTabs('fulltext', 'ftsconfigs');
        $this->printMsg($msg);

        $cfgs = $data->getFtsConfigurations(false);

        $columns = [
            'configuration' => [
                'title' => $this->lang['strftsconfig'],
                'field' => Decorator::field('name'),
                'url'   => "fulltext?action=viewconfig&amp;{$this->misc->href}&amp;",
                'vars'  => ['ftscfg' => 'name'],
            ],
            'schema'        => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('schema'),
            ],
            'actions'       => [
                'title' => $this->lang['stractions'],
            ],
            'comment'       => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('comment'),
            ],
        ];

        $actions = [
            'drop'  => [
                'content' => $this->lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action' => 'dropconfig',
                            'ftscfg' => Decorator::field('name'),
                        ],
                    ],
                ],
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action' => 'alterconfig',
                            'ftscfg' => Decorator::field('name'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($cfgs, $columns, $actions, 'fulltext-fulltext', $this->lang['strftsnoconfigs']);

        $navlinks = [
            'createconf' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action'   => 'createconfig',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strftscreateconfig'],
            ],
        ];

        $this->printNavLinks($navlinks, 'fulltext-fulltext', get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     */
    public function doTree()
    {
        $tabs  = $this->misc->getNavTabs('fulltext');
        $items = $this->adjustTabsForTree($tabs);

        $reqvars = $this->misc->getRequestVars('ftscfg');

        $attrs = [
            'text'   => Decorator::field('title'),
            'icon'   => Decorator::field('icon'),
            'action' => Decorator::actionurl(
                'fulltext',
                $reqvars,
                Decorator::field('urlvars')
            ),
            'branch' => Decorator::url(
                'fulltext',
                $reqvars,
                [
                    'action' => 'subtree',
                    'what'   => Decorator::field('icon'), // IZ: yeah, it's ugly, but I do not want to change navigation tabs arrays
                ]
            ),
        ];

        return $this->printTree($items, $attrs, 'fts');
    }

    public function doSubTree($what)
    {
        $data = $this->misc->getDatabaseAccessor();

        switch ($what) {
            case 'FtsCfg':
                $items   = $data->getFtsConfigurations(false);
                $urlvars = ['action' => 'viewconfig', 'ftscfg' => Decorator::field('name')];

                break;
            case 'FtsDict':
                $items   = $data->getFtsDictionaries(false);
                $urlvars = ['action' => 'viewdicts'];

                break;
            case 'FtsParser':
                $items   = $data->getFtsParsers(false);
                $urlvars = ['action' => 'viewparsers'];

                break;
            default:
                return;
        }

        $reqvars = $this->misc->getRequestVars('ftscfg');

        $attrs = [
            'text'    => Decorator::field('name'),
            'icon'    => $what,
            'toolTip' => Decorator::field('comment'),
            'action'  => Decorator::actionurl(
                'fulltext',
                $reqvars,
                $urlvars
            ),
            'branch'  => Decorator::ifempty(
                Decorator::field('branch'),
                '',
                Decorator::url(
                    'fulltext',
                    $reqvars,
                    [
                        'action' => 'subtree',
                        'ftscfg' => Decorator::field('name'),
                    ]
                )
            ),
        ];

        return $this->printTree($items, $attrs, strtolower($what));
    }

    public function doDropConfig($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('ftscfg');
            $this->printTitle($this->lang['strdrop'], 'pg.ftscfg.drop');

            echo '<p>', sprintf($this->lang['strconfdropftsconfig'], $this->misc->printVal($_REQUEST['ftscfg'])), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"dropconfig\" />\n";
            echo '<input type="hidden" name="database" value="', htmlspecialchars($_REQUEST['database']), "\" />\n";
            echo '<input type="hidden" name="ftscfg" value="', htmlspecialchars($_REQUEST['ftscfg']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            $status = $data->dropFtsConfiguration($_POST['ftscfg'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doDefault($this->lang['strftsconfigdropped']);
            } else {
                $this->doDefault($this->lang['strftsconfigdroppedbad']);
            }
        }
    }

    public function doDropDict($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('ftscfg'); // TODO: change to smth related to dictionary
            $this->printTitle($this->lang['strdrop'], 'pg.ftsdict.drop');

            echo '<p>', sprintf($this->lang['strconfdropftsdict'], $this->misc->printVal($_REQUEST['ftsdict'])), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"dropdict\" />\n";
            echo '<input type="hidden" name="database" value="', htmlspecialchars($_REQUEST['database']), "\" />\n";
            echo '<input type="hidden" name="ftsdict" value="', htmlspecialchars($_REQUEST['ftsdict']), "\" />\n";
            //echo "<input type=\"hidden\" name=\"ftscfg\" value=\"", htmlspecialchars($_REQUEST['ftscfg']), "\" />\n";
            echo "<input type=\"hidden\" name=\"prev_action\" value=\"viewdicts\" /></p>\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            $status = $data->dropFtsDictionary($_POST['ftsdict'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doViewDicts($this->lang['strftsdictdropped']);
            } else {
                $this->doViewDicts($this->lang['strftsdictdroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where one can enter a new FTS configuration.
     *
     * @param mixed $msg
     */
    public function doCreateConfig($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        if (!isset($_POST['formName'])) {
            $_POST['formName'] = '';
        }

        if (!isset($_POST['formParser'])) {
            $_POST['formParser'] = '';
        }

        if (!isset($_POST['formTemplate'])) {
            $_POST['formTemplate'] = '';
        }

        if (!isset($_POST['formWithMap'])) {
            $_POST['formWithMap'] = '';
        }

        if (!isset($_POST['formComment'])) {
            $_POST['formComment'] = '';
        }

        // Fetch all FTS configurations from the database
        $ftscfgs = $data->getFtsConfigurations();
        // Fetch all FTS parsers from the database
        $ftsparsers = $data->getFtsParsers();

        $this->printTrail('schema');
        $this->printTitle($this->lang['strftscreateconfig'], 'pg.ftscfg.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";
        echo "<table>\n";
        // conf name
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>\n";
        echo "\t\t<td class=\"data1\"><input name=\"formName\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_POST['formName']), "\" /></td>\n\t</tr>\n";

        // Template
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strftstemplate']}</th>\n";
        echo "\t\t<td class=\"data1\">";

        $tpls   = [];
        $tplsel = '';
        while (!$ftscfgs->EOF) {
            $data->fieldClean($ftscfgs->fields['schema']);
            $data->fieldClean($ftscfgs->fields['name']);
            $tplname        = $ftscfgs->fields['schema'].'.'.$ftscfgs->fields['name'];
            $tpls[$tplname] = serialize([
                'name'   => $ftscfgs->fields['name'],
                'schema' => $ftscfgs->fields['schema'],
            ]);
            if ($_POST['formTemplate'] == $tpls[$tplname]) {
                $tplsel = htmlspecialchars($tpls[$tplname]);
            }
            $ftscfgs->moveNext();
        }
        echo \PHPPgAdmin\XHtml\HTMLController::printCombo($tpls, 'formTemplate', true, $tplsel, false);
        echo "\n\t\t</td>\n\t</tr>\n";

        // Parser
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strftsparser']}</th>\n";
        echo "\t\t<td class=\"data1\">\n";
        $ftsparsers_ = [];
        $ftsparsel   = '';
        while (!$ftsparsers->EOF) {
            $data->fieldClean($ftsparsers->fields['schema']);
            $data->fieldClean($ftsparsers->fields['name']);
            $parsername = $ftsparsers->fields['schema'].'.'.$ftsparsers->fields['name'];

            $ftsparsers_[$parsername] = serialize([
                'parser' => $ftsparsers->fields['name'],
                'schema' => $ftsparsers->fields['schema'],
            ]);
            if ($_POST['formParser'] == $ftsparsers_[$parsername]) {
                $ftsparsel = htmlspecialchars($ftsparsers_[$parsername]);
            }
            $ftsparsers->moveNext();
        }
        echo \PHPPgAdmin\XHtml\HTMLController::printCombo($ftsparsers_, 'formParser', true, $ftsparsel, false);
        echo "\n\t\t</td>\n\t</tr>\n";

        // Comment
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>\n";
        echo "\t\t<td class=\"data1\"><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
        htmlspecialchars($_POST['formComment']), "</textarea></td>\n\t</tr>\n";

        echo "</table>\n";
        echo "<p>\n";
        echo "<input type=\"hidden\" name=\"action\" value=\"createconfig\" />\n";
        echo '<input type="hidden" name="database" value="', htmlspecialchars($_REQUEST['database']), "\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" name=\"create\" value=\"{$this->lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />\n";
        echo "</p>\n";
        echo "</form>\n";
    }

    /**
     * Actually creates the new FTS configuration in the database.
     */
    public function doSaveCreateConfig()
    {
        $data = $this->misc->getDatabaseAccessor();

        $err = '';
        // Check that they've given a name
        if ('' == $_POST['formName']) {
            $err .= "{$this->lang['strftsconfigneedsname']}<br />";
        }

        if (('' != $_POST['formParser']) && ('' != $_POST['formTemplate'])) {
            $err .= "{$this->lang['strftscantparsercopy']}<br />";
        }

        if ($err !== '') {
            return $this->doCreateConfig($err);
        }

        if ('' != $_POST['formParser']) {
            $formParser = unserialize($_POST['formParser']);
        } else {
            $formParser = '';
        }

        if ('' != $_POST['formTemplate']) {
            $formTemplate = unserialize($_POST['formTemplate']);
        } else {
            $formTemplate = '';
        }

        $status = $data->createFtsConfiguration($_POST['formName'], $formParser, $formTemplate, $_POST['formComment']);
        if (0 == $status) {
            $this->misc->setReloadBrowser(true);
            $this->doDefault($this->lang['strftsconfigcreated']);
        } else {
            $this->doCreateConfig($this->lang['strftsconfigcreatedbad']);
        }
    }

    /**
     * Display a form to permit editing FTS configuration properies.
     *
     * @param mixed $msg
     */
    public function doAlterConfig($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('ftscfg');
        $this->printTitle($this->lang['stralter'], 'pg.ftscfg.alter');
        $this->printMsg($msg);

        $ftscfg = $data->getFtsConfigurationByName($_REQUEST['ftscfg']);
        if ($ftscfg->recordCount() > 0) {
            if (!isset($_POST['formComment'])) {
                $_POST['formComment'] = $ftscfg->fields['comment'];
            }

            if (!isset($_POST['ftscfg'])) {
                $_POST['ftscfg'] = $_REQUEST['ftscfg'];
            }

            if (!isset($_POST['formName'])) {
                $_POST['formName'] = $_REQUEST['ftscfg'];
            }

            if (!isset($_POST['formParser'])) {
                $_POST['formParser'] = '';
            }

            // Fetch all FTS parsers from the database
            $ftsparsers = $data->getFtsParsers();

            echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";
            echo "<table>\n";

            echo "\t<tr>\n";
            echo "\t\t<th class=\"data left required\">{$this->lang['strname']}</th>\n";
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<input name=\"formName\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['formName']), "\" />\n";
            echo "\t\t</td>\n";
            echo "\t</tr>\n";

            // Comment
            echo "\t<tr>\n";
            echo "\t\t<th class=\"data\">{$this->lang['strcomment']}</th>\n";
            echo "\t\t<td class=\"data1\"><textarea cols=\"32\" rows=\"3\"name=\"formComment\">", htmlspecialchars($_POST['formComment']), "</textarea></td>\n";
            echo "\t</tr>\n";
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"alterconfig\" />\n";
            echo '<input type="hidden" name="ftscfg" value="', htmlspecialchars($_POST['ftscfg']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            echo "<p>{$this->lang['strnodata']}</p>\n";
        }
    }

    /**
     * Save the form submission containing changes to a FTS configuration.
     */
    public function doSaveAlterConfig()
    {
        $data   = $this->misc->getDatabaseAccessor();
        $status = $data->updateFtsConfiguration($_POST['ftscfg'], $_POST['formComment'], $_POST['formName']);
        if (0 == $status) {
            $this->doDefault($this->lang['strftsconfigaltered']);
        } else {
            $this->doAlterConfig($this->lang['strftsconfigalteredbad']);
        }
    }

    /**
     * View list of FTS parsers.
     *
     * @param mixed $msg
     */
    public function doViewParsers($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'fulltext');
        $this->printTabs('fulltext', 'ftsparsers');
        $this->printMsg($msg);

        $parsers = $data->getFtsParsers(false);

        $columns = [
            'schema'  => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('schema'),
            ],
            'name'    => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('name'),
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('comment'),
            ],
        ];

        $actions = [];

        echo $this->printTable($parsers, $columns, $actions, 'fulltext-viewparsers', $this->lang['strftsnoparsers']);

        //TODO: navlink to "create parser"
    }

    /**
     * View list of FTS dictionaries.
     *
     * @param mixed $msg
     */
    public function doViewDicts($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'fulltext');
        $this->printTabs('fulltext', 'ftsdicts');
        $this->printMsg($msg);

        $dicts = $data->getFtsDictionaries(false);

        $columns = [
            'schema'  => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('schema'),
            ],
            'name'    => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('name'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('comment'),
            ],
        ];

        $actions = [
            'drop'  => [
                'content' => $this->lang['strdrop'],
                'attr'    => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action'  => 'dropdict',
                            'ftsdict' => Decorator::field('name'),
                        ],
                    ],
                ],
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action'  => 'alterdict',
                            'ftsdict' => Decorator::field('name'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($dicts, $columns, $actions, 'fulltext-viewdicts', $this->lang['strftsnodicts']);

        $navlinks = [
            'createdict' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action'   => 'createdict',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strftscreatedict'],
            ],
        ];

        $this->printNavLinks($navlinks, 'fulltext-viewdicts', get_defined_vars());
    }

    /**
     * View details of FTS configuration given.
     *
     * @param mixed $ftscfg
     * @param mixed $msg
     */
    public function doViewConfig($ftscfg, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('ftscfg');
        $this->printTabs('schema', 'fulltext');
        $this->printTabs('fulltext', 'ftsconfigs');
        $this->printMsg($msg);

        echo "<h3>{$this->lang['strftsconfigmap']}</h3>\n";

        $map = $data->getFtsConfigurationMap($ftscfg);

        $columns = [
            'name'         => [
                'title' => $this->lang['strftsmapping'],
                'field' => Decorator::field('name'),
            ],
            'dictionaries' => [
                'title' => $this->lang['strftsdicts'],
                'field' => Decorator::field('dictionaries'),
            ],
            'actions'      => [
                'title' => $this->lang['stractions'],
            ],
            'comment'      => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('description'),
            ],
        ];

        $actions = [
            'drop'         => [
                'multiaction' => 'dropmapping',
                'content'     => $this->lang['strdrop'],
                'attr'        => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action'  => 'dropmapping',
                            'mapping' => Decorator::field('name'),
                            'ftscfg'  => Decorator::field('cfgname'),
                        ],
                    ],
                ],
            ],
            'alter'        => [
                'content' => $this->lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action'  => 'altermapping',
                            'mapping' => Decorator::field('name'),
                            'ftscfg'  => Decorator::field('cfgname'),
                        ],
                    ],
                ],
            ],
            'multiactions' => [
                'keycols' => ['mapping' => 'name'],
                'url'     => 'fulltext',
                'default' => null,
                'vars'    => ['ftscfg' => $ftscfg],
            ],
        ];

        echo $this->printTable($map, $columns, $actions, 'fulltext-viewconfig', $this->lang['strftsemptymap']);

        $navlinks = [
            'addmapping' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'fulltext',
                        'urlvars' => [
                            'action'   => 'addmapping',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'ftscfg'   => $ftscfg,
                        ],
                    ],
                ],
                'content' => $this->lang['strftsaddmapping'],
            ],
        ];

        $this->printNavLinks($navlinks, 'fulltext-viewconfig', get_defined_vars());
    }

    /**
     * Displays a screen where one can enter a details of a new FTS dictionary.
     *
     * @param mixed $msg
     */
    public function doCreateDict($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $server_info = $this->misc->getServerInfo();

        if (!isset($_POST['formName'])) {
            $_POST['formName'] = '';
        }

        if (!isset($_POST['formIsTemplate'])) {
            $_POST['formIsTemplate'] = false;
        }

        if (!isset($_POST['formTemplate'])) {
            $_POST['formTemplate'] = '';
        }

        if (!isset($_POST['formLexize'])) {
            $_POST['formLexize'] = '';
        }

        if (!isset($_POST['formInit'])) {
            $_POST['formInit'] = '';
        }

        if (!isset($_POST['formOption'])) {
            $_POST['formOption'] = '';
        }

        if (!isset($_POST['formComment'])) {
            $_POST['formComment'] = '';
        }

        // Fetch all FTS dictionaries from the database
        $ftstpls = $data->getFtsDictionaryTemplates();

        $this->printTrail('schema');
        // TODO: create doc links
        $this->printTitle($this->lang['strftscreatedict'], 'pg.ftsdict.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";
        echo "<table>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>\n";
        echo "\t\t<td class=\"data1\"><input name=\"formName\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_POST['formName']), '" />&nbsp;',
        '<input type="checkbox" name="formIsTemplate" id="formIsTemplate"', $_POST['formIsTemplate'] ? ' checked="checked" ' : '', " />\n",
            "<label for=\"formIsTemplate\">{$this->lang['strftscreatedicttemplate']}</label></td>\n\t</tr>\n";

        // Template
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strftstemplate']}</th>\n";
        echo "\t\t<td class=\"data1\">";
        $tpls   = [];
        $tplsel = '';
        while (!$ftstpls->EOF) {
            $data->fieldClean($ftstpls->fields['schema']);
            $data->fieldClean($ftstpls->fields['name']);
            $tplname        = $ftstpls->fields['schema'].'.'.$ftstpls->fields['name'];
            $tpls[$tplname] = serialize([
                'name'   => $ftstpls->fields['name'],
                'schema' => $ftstpls->fields['schema'],
            ]);
            if ($_POST['formTemplate'] == $tpls[$tplname]) {
                $tplsel = htmlspecialchars($tpls[$tplname]);
            }
            $ftstpls->moveNext();
        }
        echo \PHPPgAdmin\XHtml\HTMLController::printCombo($tpls, 'formTemplate', true, $tplsel, false);
        echo "\n\t\t</td>\n\t</tr>\n";

        // TODO: what about maxlengths?
        // Lexize
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strftslexize']}</th>\n";
        echo "\t\t<td class=\"data1\"><input name=\"formLexize\" size=\"32\" maxlength=\"1000\" value=\"",
        htmlspecialchars($_POST['formLexize']), '" ', isset($_POST['formIsTemplate']) ? '' : ' disabled="disabled" ',
            "/></td>\n\t</tr>\n";

        // Init
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strftsinit']}</th>\n";
        echo "\t\t<td class=\"data1\"><input name=\"formInit\" size=\"32\" maxlength=\"1000\" value=\"",
        htmlspecialchars($_POST['formInit']), '"', @$_POST['formIsTemplate'] ? '' : ' disabled="disabled" ',
            "/></td>\n\t</tr>\n";

        // Option
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strftsoptionsvalues']}</th>\n";
        echo "\t\t<td class=\"data1\"><input name=\"formOption\" size=\"32\" maxlength=\"1000\" value=\"",
        htmlspecialchars($_POST['formOption']), "\" /></td>\n\t</tr>\n";

        // Comment
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>\n";
        echo "\t\t<td class=\"data1\"><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
        htmlspecialchars($_POST['formComment']), "</textarea></td>\n\t</tr>\n";

        echo "</table>\n";
        echo "<p>\n";
        echo "<input type=\"hidden\" name=\"action\" value=\"createdict\" />\n";
        echo '<input type="hidden" name="database" value="', htmlspecialchars($_REQUEST['database']), "\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" name=\"create\" value=\"{$this->lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />\n";
        echo "</p>\n";
        echo "</form>\n",
            "<script type=\"text/javascript\">
				function templateOpts() {
					isTpl = document.getElementsByName('formIsTemplate')[0].checked;
					document.getElementsByName('formTemplate')[0].disabled = isTpl;
					document.getElementsByName('formOption')[0].disabled = isTpl;
					document.getElementsByName('formLexize')[0].disabled = !isTpl;
					document.getElementsByName('formInit')[0].disabled = !isTpl;
				}

				document.getElementsByName('formIsTemplate')[0].onchange = templateOpts;

				templateOpts();
			</script>\n";
    }

    /**
     * Actually creates the new FTS dictionary in the database.
     */
    public function doSaveCreateDict()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name
        if ('' == $_POST['formName']) {
            $this->doCreateDict($this->lang['strftsdictneedsname']);
        } else {
            if (!isset($_POST['formIsTemplate'])) {
                $_POST['formIsTemplate'] = false;
            }

            if (isset($_POST['formTemplate'])) {
                $formTemplate = unserialize($_POST['formTemplate']);
            } else {
                $formTemplate = '';
            }

            if (!isset($_POST['formLexize'])) {
                $_POST['formLexize'] = '';
            }

            if (!isset($_POST['formInit'])) {
                $_POST['formInit'] = '';
            }

            if (!isset($_POST['formOption'])) {
                $_POST['formOption'] = '';
            }

            $status = $data->createFtsDictionary(
                $_POST['formName'],
                $_POST['formIsTemplate'],
                $formTemplate,
                $_POST['formLexize'],
                $_POST['formInit'],
                $_POST['formOption'],
                $_POST['formComment']
            );

            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doViewDicts($this->lang['strftsdictcreated']);
            } else {
                $this->doCreateDict($this->lang['strftsdictcreatedbad']);
            }
        }
    }

    /**
     * Display a form to permit editing FTS dictionary properies.
     *
     * @param mixed $msg
     */
    public function doAlterDict($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('ftscfg'); // TODO: change to smth related to dictionary
        $this->printTitle($this->lang['stralter'], 'pg.ftsdict.alter');
        $this->printMsg($msg);

        $ftsdict = $data->getFtsDictionaryByName($_REQUEST['ftsdict']);
        if ($ftsdict->recordCount() > 0) {
            if (!isset($_POST['formComment'])) {
                $_POST['formComment'] = $ftsdict->fields['comment'];
            }

            if (!isset($_POST['ftsdict'])) {
                $_POST['ftsdict'] = $_REQUEST['ftsdict'];
            }

            if (!isset($_POST['formName'])) {
                $_POST['formName'] = $_REQUEST['ftsdict'];
            }

            echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";
            echo "<table>\n";

            echo "\t<tr>\n";
            echo "\t\t<th class=\"data left required\">{$this->lang['strname']}</th>\n";
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<input name=\"formName\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_POST['formName']), "\" />\n";
            echo "\t\t</td>\n";
            echo "\t</tr>\n";

            // Comment
            echo "\t<tr>\n";
            echo "\t\t<th class=\"data\">{$this->lang['strcomment']}</th>\n";
            echo "\t\t<td class=\"data1\"><textarea cols=\"32\" rows=\"3\"name=\"formComment\">", htmlspecialchars($_POST['formComment']), "</textarea></td>\n";
            echo "\t</tr>\n";
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"alterdict\" />\n";
            echo '<input type="hidden" name="ftsdict" value="', htmlspecialchars($_POST['ftsdict']), "\" />\n";
            echo "<input type=\"hidden\" name=\"prev_action\" value=\"viewdicts\" /></p>\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            echo "<p>{$this->lang['strnodata']}</p>\n";
        }
    }

    /**
     * Save the form submission containing changes to a FTS dictionary.
     */
    public function doSaveAlterDict()
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->updateFtsDictionary($_POST['ftsdict'], $_POST['formComment'], $_POST['formName']);
        if (0 == $status) {
            $this->doViewDicts($this->lang['strftsdictaltered']);
        } else {
            $this->doAlterDict($this->lang['strftsdictalteredbad']);
        }
    }

    /**
     * Show confirmation of drop and perform actual drop of FTS mapping.
     *
     * @param mixed $confirm
     */
    public function doDropMapping($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['mapping']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strftsspecifymappingtodrop']);

            return;
        }

        if (empty($_REQUEST['ftscfg'])) {
            $this->doDefault($this->lang['strftsspecifyconfigtoalter']);

            return;
        }

        if ($confirm) {
            $this->printTrail('ftscfg'); // TODO: proper breadcrumbs
            $this->printTitle($this->lang['strdrop'], 'pg.ftscfg.alter');

            echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";

            // Case of multiaction drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                    echo '<p>', sprintf($this->lang['strconfdropftsmapping'], $this->misc->printVal($a['mapping']), $this->misc->printVal($_REQUEST['ftscfg'])), "</p>\n";
                    printf('<input type="hidden" name="mapping[]" value="%s" />', htmlspecialchars($a['mapping']));
                }
            } else {
                echo '<p>', sprintf($this->lang['strconfdropftsmapping'], $this->misc->printVal($_REQUEST['mapping']), $this->misc->printVal($_REQUEST['ftscfg'])), "</p>\n";
                echo '<input type="hidden" name="mapping" value="', htmlspecialchars($_REQUEST['mapping']), "\" />\n";
            }

            echo "<input type=\"hidden\" name=\"ftscfg\" value=\"{$_REQUEST['ftscfg']}\" />\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"dropmapping\" />\n";
            echo "<input type=\"hidden\" name=\"prev_action\" value=\"viewconfig\" /></p>\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />\n";
            echo "</form>\n";
        } else {
            // Case of multiaction drop
            if (is_array($_REQUEST['mapping'])) {
                $status = $data->changeFtsMapping($_REQUEST['ftscfg'], $_REQUEST['mapping'], 'drop');
                if (0 != $status) {
                    $this->doViewConfig($_REQUEST['ftscfg'], $this->lang['strftsmappingdroppedbad']);

                    return;
                }
                $this->doViewConfig($_REQUEST['ftscfg'], $this->lang['strftsmappingdropped']);
            } else {
                $status = $data->changeFtsMapping($_REQUEST['ftscfg'], [$_REQUEST['mapping']], 'drop');
                if (0 == $status) {
                    $this->doViewConfig($_REQUEST['ftscfg'], $this->lang['strftsmappingdropped']);
                } else {
                    $this->doViewConfig($_REQUEST['ftscfg'], $this->lang['strftsmappingdroppedbad']);
                }
            }
        }
    }

    public function doAlterMapping($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();
        $this->printTrail('ftscfg');
        $this->printTitle($this->lang['stralter'], 'pg.ftscfg.alter');
        $this->printMsg($msg);

        $ftsdicts = $data->getFtsDictionaries();
        if ($ftsdicts->recordCount() > 0) {
            if (!isset($_POST['formMapping'])) {
                $_POST['formMapping'] = @$_REQUEST['mapping'];
            }

            if (!isset($_POST['formDictionary'])) {
                $_POST['formDictionary'] = '';
            }

            if (!isset($_POST['ftscfg'])) {
                $_POST['ftscfg'] = $_REQUEST['ftscfg'];
            }

            echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";

            echo "<table>\n";
            echo "\t<tr>\n";
            echo "\t\t<th class=\"data left required\">{$this->lang['strftsmapping']}</th>\n";
            echo "\t\t<td class=\"data1\">";

            // Case of multiaction drop
            if (isset($_REQUEST['ma'])) {
                $ma_mappings       = [];
                $ma_mappings_names = [];
                foreach ($_REQUEST['ma'] as $v) {
                    $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                    printf('<input type="hidden" name="formMapping[]" value="%s" />', htmlspecialchars($a['mapping']));
                    $ma_mappings[]       = $data->getFtsMappingByName($_POST['ftscfg'], $a['mapping']);
                    $ma_mappings_names[] = $a['mapping'];
                }
                echo implode(', ', $ma_mappings_names);
            } else {
                $mapping = $data->getFtsMappingByName($_POST['ftscfg'], $_POST['formMapping']);
                echo $mapping->fields['name'];
                echo '<input type="hidden" name="formMapping" value="', htmlspecialchars($_POST['formMapping']), "\" />\n";
            }

            echo "\t\t</td>\n";
            echo "\t</tr>\n";

            // Dictionary
            echo "\t<tr>\n";
            echo "\t\t<th class=\"data left required\">{$this->lang['strftsdict']}</th>\n";
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<select name=\"formDictionary\">\n";
            while (!$ftsdicts->EOF) {
                $ftsdict = htmlspecialchars($ftsdicts->fields['name']);
                echo "\t\t\t\t<option value=\"{$ftsdict}\"",
                ($ftsdict == $_POST['formDictionary'] || $ftsdict == @$mapping->fields['dictionaries'] || $ftsdict == @$ma_mappings[0]->fields['dictionaries']) ? ' selected="selected"' : '', ">{$ftsdict}</option>\n";
                $ftsdicts->moveNext();
            }

            echo "\t\t</td>\n";
            echo "\t</tr>\n";

            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"altermapping\" />\n";
            echo '<input type="hidden" name="ftscfg" value="', htmlspecialchars($_POST['ftscfg']), "\" />\n";
            echo "<input type=\"hidden\" name=\"prev_action\" value=\"viewconfig\" /></p>\n";

            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            echo "<p>{$this->lang['strftsnodictionaries']}</p>\n";
        }
    }

    /**
     * Save the form submission containing changes to a FTS mapping.
     */
    public function doSaveAlterMapping()
    {
        $data = $this->misc->getDatabaseAccessor();

        $mappingArray = (is_array($_POST['formMapping']) ? $_POST['formMapping'] : [$_POST['formMapping']]);
        $status       = $data->changeFtsMapping($_POST['ftscfg'], $mappingArray, 'alter', $_POST['formDictionary']);
        if (0 == $status) {
            $this->doViewConfig($_POST['ftscfg'], $this->lang['strftsmappingaltered']);
        } else {
            $this->doAlterMapping($this->lang['strftsmappingalteredbad']);
        }
    }

    /**
     * Show the form to enter parameters of a new FTS mapping.
     *
     * @param mixed $msg
     */
    public function doAddMapping($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('ftscfg');
        $this->printTitle($this->lang['stralter'], 'pg.ftscfg.alter');
        $this->printMsg($msg);

        $ftsdicts = $data->getFtsDictionaries();
        if ($ftsdicts->recordCount() > 0) {
            if (!isset($_POST['formMapping'])) {
                $_POST['formMapping'] = '';
            }

            if (!isset($_POST['formDictionary'])) {
                $_POST['formDictionary'] = '';
            }

            if (!isset($_POST['ftscfg'])) {
                $_POST['ftscfg'] = $_REQUEST['ftscfg'];
            }

            $mappings = $data->getFtsMappings($_POST['ftscfg']);

            echo '<form action="'.\SUBFOLDER."/src/views/fulltext\" method=\"post\">\n";
            echo "<table>\n";
            echo "\t<tr>\n";
            echo "\t\t<th class=\"data left required\">{$this->lang['strftsmapping']}</th>\n";
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<select name=\"formMapping\">\n";
            while (!$mappings->EOF) {
                $mapping      = htmlspecialchars($mappings->fields['name']);
                $mapping_desc = htmlspecialchars($mappings->fields['description']);
                echo "\t\t\t\t<option value=\"{$mapping}\"",
                $mapping == $_POST['formMapping'] ? ' selected="selected"' : '', ">{$mapping}", $mapping_desc ? " - {$mapping_desc}" : '', "</option>\n";
                $mappings->moveNext();
            }
            echo "\t\t</td>\n";
            echo "\t</tr>\n";

            // Dictionary
            echo "\t<tr>\n";
            echo "\t\t<th class=\"data left required\">{$this->lang['strftsdict']}</th>\n";
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<select name=\"formDictionary\">\n";
            while (!$ftsdicts->EOF) {
                $ftsdict = htmlspecialchars($ftsdicts->fields['name']);
                echo "\t\t\t\t<option value=\"{$ftsdict}\"",
                $ftsdict == $_POST['formDictionary'] ? ' selected="selected"' : '', ">{$ftsdict}</option>\n";
                $ftsdicts->moveNext();
            }

            echo "\t\t</td>\n";
            echo "\t</tr>\n";

            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"addmapping\" />\n";
            echo '<input type="hidden" name="ftscfg" value="', htmlspecialchars($_POST['ftscfg']), "\" />\n";
            echo "<input type=\"hidden\" name=\"prev_action\" value=\"viewconfig\" /></p>\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"add\" value=\"{$this->lang['stradd']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            echo "<p>{$this->lang['strftsnodictionaries']}</p>\n";
        }
    }

    /**
     * Save the form submission containing parameters of a new FTS mapping.
     */
    public function doSaveAddMapping()
    {
        $data = $this->misc->getDatabaseAccessor();

        $mappingArray = (is_array($_POST['formMapping']) ? $_POST['formMapping'] : [$_POST['formMapping']]);
        $status       = $data->changeFtsMapping($_POST['ftscfg'], $mappingArray, 'add', $_POST['formDictionary']);
        if (0 == $status) {
            $this->doViewConfig($_POST['ftscfg'], $this->lang['strftsmappingadded']);
        } else {
            $this->doAddMapping($this->lang['strftsmappingaddedbad']);
        }
    }
}
