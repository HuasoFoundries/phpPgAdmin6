<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use PHPPgAdmin\XHtml\HTMLController;
use Slim\Http\Response;

/**
 * Base controller class.
 */
class FulltextController extends BaseController
{
    public $controller_title = 'strschemas';

    /**
     * Default method to render the controller according to the action parameter.
     *
     * @return null|Response|string
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        if ('subtree' === $this->action) {
            return $this->doSubTree($_REQUEST['what']);
        }

        $this->printHeader();
        $this->printBody();

        if (null !== $this->getPostParam('cancel')) {
            $this->action = $_POST['prev_action'] ?? '';
        }

        switch ($this->action) {
            case 'createconfig':
                if (null !== $this->getPostParam('create')) {
                    $this->doSaveCreateConfig();
                } else {
                    $this->doCreateConfig();
                }

                break;
            case 'alterconfig':
                if (null !== $this->getPostParam('alter')) {
                    $this->doSaveAlterConfig();
                } else {
                    $this->doAlterConfig();
                }

                break;
            case 'dropconfig':
                if (null !== $this->getPostParam('drop')) {
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
                if (null !== $this->getPostParam('create')) {
                    $this->doSaveCreateDict();
                } else {
                    $this->doCreateDict();
                }

                break;
            case 'alterdict':
                if (null !== $this->getPostParam('alter')) {
                    $this->doSaveAlterDict();
                } else {
                    $this->doAlterDict();
                }

                break;
            case 'dropdict':
                if (null !== $this->getPostParam('drop')) {
                    $this->doDropDict(false);
                } else {
                    $this->doDropDict(true);
                }

                break;
            case 'dropmapping':
                if (null !== $this->getPostParam('drop')) {
                    $this->doDropMapping(false);
                } else {
                    $this->doDropMapping(true);
                }

                break;
            case 'altermapping':
                if (null !== $this->getPostParam('alter')) {
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
                'url' => \sprintf(
                    'fulltext?action=viewconfig&amp;%s&amp;',
                    $this->misc->href
                ),
                'vars' => ['ftscfg' => 'name'],
            ],
            'schema' => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('schema'),
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
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'dropconfig',
                            'ftscfg' => Decorator::field('name'),
                        ],
                    ],
                ],
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'alterconfig',
                            'ftscfg' => Decorator::field('name'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($cfgs)) {
            echo $this->printTable($cfgs, $columns, $actions, 'fulltext-fulltext', $this->lang['strftsnoconfigs']);
        }

        $navlinks = [
            'createconf' => [
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'createconfig',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strftscreateconfig'],
            ],
        ];

        $this->printNavLinks($navlinks, 'fulltext-fulltext', \get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     *
     * @return Response|string
     */
    public function doTree()
    {
        $tabs = $this->misc->getNavTabs('fulltext');
        $items = $this->adjustTabsForTree($tabs);

        $reqvars = $this->misc->getRequestVars('ftscfg');

        $attrs = [
            'text' => Decorator::field('title'),
            'icon' => Decorator::field('icon'),
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
                    'what' => Decorator::field('icon'), // IZ: yeah, it's ugly, but I do not want to change navigation tabs arrays
                ]
            ),
        ];

        return $this->printTree($items, $attrs, 'fts');
    }

    /**
     * @param mixed $what
     *
     * @return null|Response|string
     */
    public function doSubTree($what)
    {
        $data = $this->misc->getDatabaseAccessor();

        switch ($what) {
            case 'FtsCfg':
                $items = $data->getFtsConfigurations(false);
                $urlvars = ['action' => 'viewconfig', 'ftscfg' => Decorator::field('name')];

                break;
            case 'FtsDict':
                $items = $data->getFtsDictionaries(false);
                $urlvars = ['action' => 'viewdicts'];

                break;
            case 'FtsParser':
                $items = $data->getFtsParsers(false);
                $urlvars = ['action' => 'viewparsers'];

                break;

            default:
                return;
        }

        $reqvars = $this->misc->getRequestVars('ftscfg');

        $attrs = [
            'text' => Decorator::field('name'),
            'icon' => $what,
            'toolTip' => Decorator::field('comment'),
            'action' => Decorator::actionurl(
                'fulltext',
                $reqvars,
                $urlvars
            ),
            'branch' => Decorator::ifempty(
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

        return $this->printTree($items, $attrs, \mb_strtolower($what));
    }

    public function doDropConfig(bool $confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('ftscfg');
            $this->printTitle($this->lang['strdrop'], 'pg.ftscfg.drop');

            echo '<p>', \sprintf(
                $this->lang['strconfdropftsconfig'],
                $this->misc->printVal($_REQUEST['ftscfg'])
            ), '</p>' . \PHP_EOL;

            echo '<form action="fulltext" method="post">' . \PHP_EOL;
            echo \sprintf(
                '<p><input type="checkbox" id="cascade" name="cascade" /> <label for="cascade">%s</label></p>',
                $this->lang['strcascade']
            ) . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="dropconfig" />' . \PHP_EOL;
            echo '<input type="hidden" name="database" value="', \htmlspecialchars($_REQUEST['database']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="ftscfg" value="', \htmlspecialchars($_REQUEST['ftscfg']), '" />' . \PHP_EOL;
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
            $status = $data->dropFtsConfiguration($_POST['ftscfg'], isset($_POST['cascade']));

            if (0 === $status) {
                $this->view->setReloadBrowser(true);
                $this->doDefault($this->lang['strftsconfigdropped']);
            } else {
                $this->doDefault($this->lang['strftsconfigdroppedbad']);
            }
        }
    }

    public function doDropDict(bool $confirm): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('ftscfg'); // TODO: change to smth related to dictionary
            $this->printTitle($this->lang['strdrop'], 'pg.ftsdict.drop');

            echo '<p>', \sprintf(
                $this->lang['strconfdropftsdict'],
                $this->misc->printVal($_REQUEST['ftsdict'])
            ), '</p>' . \PHP_EOL;

            echo '<form action="fulltext" method="post">' . \PHP_EOL;
            echo \sprintf(
                '<p><input type="checkbox" id="cascade" name="cascade" /> <label for="cascade">%s</label></p>',
                $this->lang['strcascade']
            ) . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="dropdict" />' . \PHP_EOL;
            echo '<input type="hidden" name="database" value="', \htmlspecialchars($_REQUEST['database']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="ftsdict" value="', \htmlspecialchars($_REQUEST['ftsdict']), '" />' . \PHP_EOL;
            //echo "<input type=\"hidden\" name=\"ftscfg\" value=\"", htmlspecialchars($_REQUEST['ftscfg']), "\" />".PHP_EOL;
            echo '<input type="hidden" name="prev_action" value="viewdicts" /></p>' . \PHP_EOL;
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
            $status = $data->dropFtsDictionary($_POST['ftsdict'], isset($_POST['cascade']));

            if (0 === $status) {
                $this->view->setReloadBrowser(true);
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

        $this->coalesceArr($_POST, 'formName', '');

        $this->coalesceArr($_POST, 'formParser', '');

        $this->coalesceArr($_POST, 'formTemplate', '');

        $this->coalesceArr($_POST, 'formWithMap', '');

        $this->coalesceArr($_POST, 'formComment', '');

        // Fetch all FTS configurations from the database
        $ftscfgs = $data->getFtsConfigurations();
        // Fetch all FTS parsers from the database
        $ftsparsers = $data->getFtsParsers();

        $this->printTrail('schema');
        $this->printTitle($this->lang['strftscreateconfig'], 'pg.ftscfg.create');
        $this->printMsg($msg);

        echo '<form action="fulltext" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        // conf name
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data1"><input name="formName" size="32" maxlength="%s" value="',
            $data->_maxNameLen
        ),
            \htmlspecialchars($_POST['formName']),
            "\" /></td>\n\t</tr>" . \PHP_EOL;

        // Template
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strftstemplate']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\">";

        $tpls = [];
        $tplsel = '';

        while (!$ftscfgs->EOF) {
            $data->fieldClean($ftscfgs->fields['schema']);
            $data->fieldClean($ftscfgs->fields['name']);
            $tplname = $ftscfgs->fields['schema'] . '.' . $ftscfgs->fields['name'];
            $tpls[$tplname] = \serialize([
                'name' => $ftscfgs->fields['name'],
                'schema' => $ftscfgs->fields['schema'],
            ]);

            if ($_POST['formTemplate'] === $tpls[$tplname]) {
                $tplsel = \htmlspecialchars($tpls[$tplname]);
            }
            $ftscfgs->MoveNext();
        }
        echo HTMLController::printCombo($tpls, 'formTemplate', true, $tplsel, false);
        echo "\n\t\t</td>\n\t</tr>" . \PHP_EOL;

        // Parser
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strftsparser']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\">" . \PHP_EOL;
        $ftsparsers_ = [];
        $ftsparsel = '';

        while (!$ftsparsers->EOF) {
            $data->fieldClean($ftsparsers->fields['schema']);
            $data->fieldClean($ftsparsers->fields['name']);
            $parsername = $ftsparsers->fields['schema'] . '.' . $ftsparsers->fields['name'];

            $ftsparsers_[$parsername] = \serialize([
                'parser' => $ftsparsers->fields['name'],
                'schema' => $ftsparsers->fields['schema'],
            ]);

            if ($_POST['formParser'] === $ftsparsers_[$parsername]) {
                $ftsparsel = \htmlspecialchars($ftsparsers_[$parsername]);
            }
            $ftsparsers->MoveNext();
        }
        echo HTMLController::printCombo($ftsparsers_, 'formParser', true, $ftsparsel, false);
        echo "\n\t\t</td>\n\t</tr>" . \PHP_EOL;

        // Comment
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strcomment']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
            \htmlspecialchars($_POST['formComment']),
            "</textarea></td>\n\t</tr>" . \PHP_EOL;

        echo '</table>' . \PHP_EOL;
        echo '<p>' . \PHP_EOL;
        echo '<input type="hidden" name="action" value="createconfig" />' . \PHP_EOL;
        echo '<input type="hidden" name="database" value="', \htmlspecialchars($_REQUEST['database']), '" />' . \PHP_EOL;
        echo $this->view->form;
        echo \sprintf(
            '<input type="submit" name="create" value="%s" />',
            $this->lang['strcreate']
        ) . \PHP_EOL;
        echo \sprintf(
            '<input type="submit" name="cancel" value="%s" />',
            $this->lang['strcancel']
        ) . \PHP_EOL;
        echo '</p>' . \PHP_EOL;
        echo '</form>' . \PHP_EOL;
    }

    /**
     * Actually creates the new FTS configuration in the database.
     */
    public function doSaveCreateConfig()
    {
        $data = $this->misc->getDatabaseAccessor();

        $err = '';
        // Check that they've given a name
        if ('' === $_POST['formName']) {
            $err .= \sprintf(
                '%s<br />',
                $this->lang['strftsconfigneedsname']
            );
        }

        if (('' !== $_POST['formParser']) && ('' !== $_POST['formTemplate'])) {
            $err .= \sprintf(
                '%s<br />',
                $this->lang['strftscantparsercopy']
            );
        }

        if ('' !== $err) {
            return $this->doCreateConfig($err);
        }

        $formParser = '' !== $_POST['formParser'] ? \unserialize($_POST['formParser']) : '';

        $formTemplate = '' !== $_POST['formTemplate'] ? \unserialize($_POST['formTemplate']) : '';

        $status = $data->createFtsConfiguration($_POST['formName'], $formParser, $formTemplate, $_POST['formComment']);

        if (0 === $status) {
            $this->view->setReloadBrowser(true);
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

        if (0 < $ftscfg->RecordCount()) {
            $this->coalesceArr($_POST, 'formComment', $ftscfg->fields['comment']);

            $this->coalesceArr($_POST, 'ftscfg', $_REQUEST['ftscfg']);

            $this->coalesceArr($_POST, 'formName', $_REQUEST['ftscfg']);

            $this->coalesceArr($_POST, 'formParser', '');

            // Fetch all FTS parsers from the database
            $ftsparsers = $data->getFtsParsers();

            echo '<form action="fulltext" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;

            echo "\t<tr>" . \PHP_EOL;
            echo \sprintf(
                '		<th class="data left required">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">";
            echo \sprintf(
                '			<input name="formName" size="32" maxlength="%s" value="',
                $data->_maxNameLen
            ),
                \htmlspecialchars($_POST['formName']),
                '" />' . \PHP_EOL;
            echo "\t\t</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;

            // Comment
            echo "\t<tr>" . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strcomment']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><textarea cols=\"32\" rows=\"3\"name=\"formComment\">", \htmlspecialchars($_POST['formComment']), '</textarea></td>' . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="alterconfig" />' . \PHP_EOL;
            echo '<input type="hidden" name="ftscfg" value="', \htmlspecialchars($_POST['ftscfg']), '" />' . \PHP_EOL;
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
     * Save the form submission containing changes to a FTS configuration.
     */
    public function doSaveAlterConfig(): void
    {
        $data = $this->misc->getDatabaseAccessor();
        $status = $data->updateFtsConfiguration($_POST['ftscfg'], $_POST['formComment'], $_POST['formName']);

        if (0 === $status) {
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
            'schema' => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('schema'),
            ],
            'name' => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('name'),
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('comment'),
            ],
        ];

        $actions = [];

        if (self::isRecordset($parsers)) {
            echo $this->printTable($parsers, $columns, $actions, 'fulltext-viewparsers', $this->lang['strftsnoparsers']);
        }

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
            'schema' => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('schema'),
            ],
            'name' => [
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
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'dropdict',
                            'ftsdict' => Decorator::field('name'),
                        ],
                    ],
                ],
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'alterdict',
                            'ftsdict' => Decorator::field('name'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($dicts)) {
            echo $this->printTable($dicts, $columns, $actions, 'fulltext-viewdicts', $this->lang['strftsnodicts']);
        }

        $navlinks = [
            'createdict' => [
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'createdict',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['strftscreatedict'],
            ],
        ];

        $this->printNavLinks($navlinks, 'fulltext-viewdicts', \get_defined_vars());
    }

    /**
     * View details of FTS configuration given.
     *
     * @param mixed $ftscfg
     * @param mixed $msg
     */
    public function doViewConfig($ftscfg, $msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('ftscfg');
        $this->printTabs('schema', 'fulltext');
        $this->printTabs('fulltext', 'ftsconfigs');
        $this->printMsg($msg);

        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['strftsconfigmap']
        ) . \PHP_EOL;

        $map = $data->getFtsConfigurationMap($ftscfg);

        $columns = [
            'name' => [
                'title' => $this->lang['strftsmapping'],
                'field' => Decorator::field('name'),
            ],
            'dictionaries' => [
                'title' => $this->lang['strftsdicts'],
                'field' => Decorator::field('dictionaries'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('description'),
            ],
        ];

        $actions = [
            'drop' => [
                'multiaction' => 'dropmapping',
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'dropmapping',
                            'mapping' => Decorator::field('name'),
                            'ftscfg' => Decorator::field('cfgname'),
                        ],
                    ],
                ],
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'altermapping',
                            'mapping' => Decorator::field('name'),
                            'ftscfg' => Decorator::field('cfgname'),
                        ],
                    ],
                ],
            ],
            'multiactions' => [
                'keycols' => ['mapping' => 'name'],
                'url' => 'fulltext',
                'default' => null,
                'vars' => ['ftscfg' => $ftscfg],
            ],
        ];

        if (self::isRecordset($map)) {
            echo $this->printTable($map, $columns, $actions, 'fulltext-viewconfig', $this->lang['strftsemptymap']);
        }

        $navlinks = [
            'addmapping' => [
                'attr' => [
                    'href' => [
                        'url' => 'fulltext',
                        'urlvars' => [
                            'action' => 'addmapping',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            'ftscfg' => $ftscfg,
                        ],
                    ],
                ],
                'content' => $this->lang['strftsaddmapping'],
            ],
        ];

        $this->printNavLinks($navlinks, 'fulltext-viewconfig', \get_defined_vars());
    }

    /**
     * Displays a screen where one can enter a details of a new FTS dictionary.
     *
     * @param mixed $msg
     */
    public function doCreateDict($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'formName', '');

        $this->coalesceArr($_POST, 'formIsTemplate', false);

        $this->coalesceArr($_POST, 'formTemplate', '');

        $this->coalesceArr($_POST, 'formLexize', '');

        $this->coalesceArr($_POST, 'formInit', '');

        $this->coalesceArr($_POST, 'formOption', '');

        $this->coalesceArr($_POST, 'formComment', '');

        // Fetch all FTS dictionaries from the database
        $ftstpls = $data->getFtsDictionaryTemplates();

        $this->printTrail('schema');
        // TODO: create doc links
        $this->printTitle($this->lang['strftscreatedict'], 'pg.ftsdict.create');
        $this->printMsg($msg);

        echo '<form action="fulltext" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '	<tr>
		<th class="data left required">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<td class="data1"><input name="formName" size="32" maxlength="%s" value="',
            $data->_maxNameLen
        ),
            \htmlspecialchars($_POST['formName']),
            '" />&nbsp;',
            '<input type="checkbox" name="formIsTemplate" id="formIsTemplate"',
            $_POST['formIsTemplate'] ? ' checked="checked" ' : '',
            " />\n",
            \sprintf(
                '<label for="formIsTemplate">%s</label></td>
	</tr>',
                $this->lang['strftscreatedicttemplate']
            ) . \PHP_EOL;

        // Template
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strftstemplate']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\">";
        $tpls = [];
        $tplsel = '';

        while (!$ftstpls->EOF) {
            $data->fieldClean($ftstpls->fields['schema']);
            $data->fieldClean($ftstpls->fields['name']);
            $tplname = $ftstpls->fields['schema'] . '.' . $ftstpls->fields['name'];
            $tpls[$tplname] = \serialize([
                'name' => $ftstpls->fields['name'],
                'schema' => $ftstpls->fields['schema'],
            ]);

            if ($_POST['formTemplate'] === $tpls[$tplname]) {
                $tplsel = \htmlspecialchars($tpls[$tplname]);
            }
            $ftstpls->MoveNext();
        }
        echo HTMLController::printCombo($tpls, 'formTemplate', true, $tplsel, false);
        echo "\n\t\t</td>\n\t</tr>" . \PHP_EOL;

        // TODO: what about maxlengths?
        // Lexize
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strftslexize']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input name=\"formLexize\" size=\"32\" maxlength=\"1000\" value=\"",
            \htmlspecialchars($_POST['formLexize']),
            '" ',
            isset($_POST['formIsTemplate']) ? '' : ' disabled="disabled" ',
            "/></td>\n\t</tr>" . \PHP_EOL;

        // Init
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strftsinit']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input name=\"formInit\" size=\"32\" maxlength=\"1000\" value=\"",
            \htmlspecialchars($_POST['formInit']),
            '"',
            $_POST['formIsTemplate'] ? '' : ' disabled="disabled" ',
            "/></td>\n\t</tr>" . \PHP_EOL;

        // Option
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strftsoptionsvalues']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><input name=\"formOption\" size=\"32\" maxlength=\"1000\" value=\"",
            \htmlspecialchars($_POST['formOption']),
            "\" /></td>\n\t</tr>" . \PHP_EOL;

        // Comment
        echo \sprintf(
            '	<tr>
		<th class="data left">%s</th>',
            $this->lang['strcomment']
        ) . \PHP_EOL;
        echo "\t\t<td class=\"data1\"><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
            \htmlspecialchars($_POST['formComment']),
            "</textarea></td>\n\t</tr>" . \PHP_EOL;

        echo '</table>' . \PHP_EOL;
        echo '<p>' . \PHP_EOL;
        echo '<input type="hidden" name="action" value="createdict" />' . \PHP_EOL;
        echo '<input type="hidden" name="database" value="', \htmlspecialchars($_REQUEST['database']), '" />' . \PHP_EOL;
        echo $this->view->form;
        echo \sprintf(
            '<input type="submit" name="create" value="%s" />',
            $this->lang['strcreate']
        ) . \PHP_EOL;
        echo \sprintf(
            '<input type="submit" name="cancel" value="%s" />',
            $this->lang['strcancel']
        ) . \PHP_EOL;
        echo '</p>' . \PHP_EOL;
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
			</script>" . \PHP_EOL;
    }

    /**
     * Actually creates the new FTS dictionary in the database.
     */
    public function doSaveCreateDict(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name
        if ('' === $_POST['formName']) {
            $this->doCreateDict($this->lang['strftsdictneedsname']);
        } else {
            $this->coalesceArr($_POST, 'formIsTemplate', false);

            $formTemplate = isset($_POST['formTemplate']) ? \unserialize($_POST['formTemplate']) : '';

            $this->coalesceArr($_POST, 'formLexize', '');

            $this->coalesceArr($_POST, 'formInit', '');

            $this->coalesceArr($_POST, 'formOption', '');

            $status = $data->createFtsDictionary(
                $_POST['formName'],
                $_POST['formIsTemplate'],
                $formTemplate,
                $_POST['formLexize'],
                $_POST['formInit'],
                $_POST['formOption'],
                $_POST['formComment']
            );

            if (0 === $status) {
                $this->view->setReloadBrowser(true);
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

        if (0 < $ftsdict->RecordCount()) {
            $this->coalesceArr($_POST, 'formComment', $ftsdict->fields['comment']);

            $this->coalesceArr($_POST, 'ftsdict', $_REQUEST['ftsdict']);

            $this->coalesceArr($_POST, 'formName', $_REQUEST['ftsdict']);

            echo '<form action="fulltext" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;

            echo "\t<tr>" . \PHP_EOL;
            echo \sprintf(
                '		<th class="data left required">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">";
            echo \sprintf(
                '			<input name="formName" size="32" maxlength="%s" value="',
                $data->_maxNameLen
            ),
                \htmlspecialchars($_POST['formName']),
                '" />' . \PHP_EOL;
            echo "\t\t</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;

            // Comment
            echo "\t<tr>" . \PHP_EOL;
            echo \sprintf(
                '		<th class="data">%s</th>',
                $this->lang['strcomment']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\"><textarea cols=\"32\" rows=\"3\"name=\"formComment\">", \htmlspecialchars($_POST['formComment']), '</textarea></td>' . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="alterdict" />' . \PHP_EOL;
            echo '<input type="hidden" name="ftsdict" value="', \htmlspecialchars($_POST['ftsdict']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="prev_action" value="viewdicts" /></p>' . \PHP_EOL;
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
     * Save the form submission containing changes to a FTS dictionary.
     */
    public function doSaveAlterDict(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->updateFtsDictionary($_POST['ftsdict'], $_POST['formComment'], $_POST['formName']);

        if (0 === $status) {
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
    public function doDropMapping($confirm): void
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
            $this->printTrail('ftscfg');
            // TODO: proper breadcrumbs
            $this->printTitle($this->lang['strdrop'], 'pg.ftscfg.alter');
            echo '<form action="fulltext" method="post">' . \PHP_EOL;
            // Case of multiaction drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    echo '<p>', \sprintf(
                        $this->lang['strconfdropftsmapping'],
                        $this->misc->printVal($a['mapping']),
                        $this->misc->printVal($_REQUEST['ftscfg'])
                    ), '</p>' . \PHP_EOL;
                    \printf('<input type="hidden" name="mapping[]" value="%s" />', \htmlspecialchars($a['mapping']));
                }
            } else {
                echo '<p>', \sprintf(
                    $this->lang['strconfdropftsmapping'],
                    $this->misc->printVal($_REQUEST['mapping']),
                    $this->misc->printVal($_REQUEST['ftscfg'])
                ), '</p>' . \PHP_EOL;
                echo '<input type="hidden" name="mapping" value="', \htmlspecialchars($_REQUEST['mapping']), '" />' . \PHP_EOL;
            }
            echo \sprintf(
                '<input type="hidden" name="ftscfg" value="%s" />',
                $_REQUEST['ftscfg']
            ) . \PHP_EOL;
            echo '<input type="hidden" name="action" value="dropmapping" />' . \PHP_EOL;
            echo '<input type="hidden" name="prev_action" value="viewconfig" /></p>' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="drop" value="%s" />',
                $this->lang['strdrop']
            ) . \PHP_EOL;
            echo \sprintf(
                '<input type="submit" name="cancel" value="%s" />',
                $this->lang['strcancel']
            ) . \PHP_EOL;
            echo '</form>' . \PHP_EOL;
        } elseif (\is_array($_REQUEST['mapping'])) {
            $status = $data->changeFtsMapping($_REQUEST['ftscfg'], $_REQUEST['mapping'], 'drop');

            if (0 !== $status) {
                $this->doViewConfig($_REQUEST['ftscfg'], $this->lang['strftsmappingdroppedbad']);

                return;
            }
            $this->doViewConfig($_REQUEST['ftscfg'], $this->lang['strftsmappingdropped']);
        } else {
            $status = $data->changeFtsMapping($_REQUEST['ftscfg'], [$_REQUEST['mapping']], 'drop');

            if (0 === $status) {
                $this->doViewConfig($_REQUEST['ftscfg'], $this->lang['strftsmappingdropped']);
            } else {
                $this->doViewConfig($_REQUEST['ftscfg'], $this->lang['strftsmappingdroppedbad']);
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

        if (0 < $ftsdicts->RecordCount()) {
            $this->coalesceArr($_POST, 'formMapping', $_REQUEST['mapping']);

            $this->coalesceArr($_POST, 'formDictionary', '');

            $this->coalesceArr($_POST, 'ftscfg', $_REQUEST['ftscfg']);

            echo '<form action="fulltext" method="post">' . \PHP_EOL;

            echo '<table>' . \PHP_EOL;
            echo "\t<tr>" . \PHP_EOL;
            echo \sprintf(
                '		<th class="data left required">%s</th>',
                $this->lang['strftsmapping']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">";

            $ma_mappings = [];
            // Case of multiaction drop
            if (isset($_REQUEST['ma'])) {
                $ma_mappings_names = [];

                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    \printf('<input type="hidden" name="formMapping[]" value="%s" />', \htmlspecialchars($a['mapping']));
                    $ma_mappings[] = $data->getFtsMappingByName($_POST['ftscfg'], $a['mapping']);
                    $ma_mappings_names[] = $a['mapping'];
                }
                echo \implode(', ', $ma_mappings_names);
            } else {
                $mapping = $data->getFtsMappingByName($_POST['ftscfg'], $_POST['formMapping']);
                echo $mapping->fields['name'];
                echo '<input type="hidden" name="formMapping" value="', \htmlspecialchars($_POST['formMapping']), '" />' . \PHP_EOL;
            }

            echo "\t\t</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;

            // Dictionary
            echo "\t<tr>" . \PHP_EOL;
            echo \sprintf(
                '		<th class="data left required">%s</th>',
                $this->lang['strftsdict']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<select name=\"formDictionary\">" . \PHP_EOL;

            while (!$ftsdicts->EOF) {
                $ftsdict = \htmlspecialchars($ftsdicts->fields['name']);
                echo \sprintf(
                    '				<option value="%s"',
                    $ftsdict
                ),
                    ($ftsdict === $_POST['formDictionary']
                        || $ftsdict === $mapping->fields['dictionaries']
                        || $ftsdict === $ma_mappings[0]->fields['dictionaries']) ? ' selected="selected"' : '',
                    \sprintf(
                        '>%s</option>',
                        $ftsdict
                    ) . \PHP_EOL;
                $ftsdicts->MoveNext();
            }

            echo "\t\t</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;

            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="altermapping" />' . \PHP_EOL;
            echo '<input type="hidden" name="ftscfg" value="', \htmlspecialchars($_POST['ftscfg']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="prev_action" value="viewconfig" /></p>' . \PHP_EOL;

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
                $this->lang['strftsnodictionaries']
            ) . \PHP_EOL;
        }
    }

    /**
     * Save the form submission containing changes to a FTS mapping.
     */
    public function doSaveAlterMapping(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $mappingArray = (\is_array($_POST['formMapping']) ? $_POST['formMapping'] : [$_POST['formMapping']]);
        $status = $data->changeFtsMapping($_POST['ftscfg'], $mappingArray, 'alter', $_POST['formDictionary']);

        if (0 === $status) {
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

        if (0 < $ftsdicts->RecordCount()) {
            $this->coalesceArr($_POST, 'formMapping', '');

            $this->coalesceArr($_POST, 'formDictionary', '');

            $this->coalesceArr($_POST, 'ftscfg', $_REQUEST['ftscfg']);

            $mappings = $data->getFtsMappings($_POST['ftscfg']);

            echo '<form action="fulltext" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;
            echo "\t<tr>" . \PHP_EOL;
            echo \sprintf(
                '		<th class="data left required">%s</th>',
                $this->lang['strftsmapping']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<select name=\"formMapping\">" . \PHP_EOL;

            while (!$mappings->EOF) {
                $mapping = \htmlspecialchars($mappings->fields['name']);
                $mapping_desc = \htmlspecialchars($mappings->fields['description']);
                echo \sprintf(
                    '				<option value="%s"',
                    $mapping
                ),
                    $mapping === $_POST['formMapping'] ? ' selected="selected"' : '',
                    \sprintf(
                        '>%s',
                        $mapping
                    ),
                    '' !== $mapping_desc ? \sprintf(
                        ' - %s',
                        $mapping_desc
                    ) : '',
                    '</option>' . \PHP_EOL;
                $mappings->MoveNext();
            }
            echo "\t\t</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;

            // Dictionary
            echo "\t<tr>" . \PHP_EOL;
            echo \sprintf(
                '		<th class="data left required">%s</th>',
                $this->lang['strftsdict']
            ) . \PHP_EOL;
            echo "\t\t<td class=\"data1\">";
            echo "\t\t\t<select name=\"formDictionary\">" . \PHP_EOL;

            while (!$ftsdicts->EOF) {
                $ftsdict = \htmlspecialchars($ftsdicts->fields['name']);
                echo \sprintf(
                    '				<option value="%s"',
                    $ftsdict
                ),
                    $ftsdict === $_POST['formDictionary'] ? ' selected="selected"' : '',
                    \sprintf(
                        '>%s</option>',
                        $ftsdict
                    ) . \PHP_EOL;
                $ftsdicts->MoveNext();
            }

            echo "\t\t</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;

            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="addmapping" />' . \PHP_EOL;
            echo '<input type="hidden" name="ftscfg" value="', \htmlspecialchars($_POST['ftscfg']), '" />' . \PHP_EOL;
            echo '<input type="hidden" name="prev_action" value="viewconfig" /></p>' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="add" value="%s" />',
                $this->lang['stradd']
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
                $this->lang['strftsnodictionaries']
            ) . \PHP_EOL;
        }
    }

    /**
     * Save the form submission containing parameters of a new FTS mapping.
     */
    public function doSaveAddMapping(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $mappingArray = (\is_array($_POST['formMapping']) ? $_POST['formMapping'] : [$_POST['formMapping']]);
        $status = $data->changeFtsMapping($_POST['ftscfg'], $mappingArray, 'add', $_POST['formDictionary']);

        if (0 === $status) {
            $this->doViewConfig($_POST['ftscfg'], $this->lang['strftsmappingadded']);
        } else {
            $this->doAddMapping($this->lang['strftsmappingaddedbad']);
        }
    }
}
