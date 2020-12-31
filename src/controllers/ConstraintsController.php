<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use PHPPgAdmin\Traits\FormTrait;
use PHPPgAdmin\XHtml\XHtmlButton;
use PHPPgAdmin\XHtml\XHtmlOption;
use PHPPgAdmin\XHtml\XHtmlSelect;

/**
 * Base controller class.
 */
class ConstraintsController extends BaseController
{
    use FormTrait;

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->/* @scrutinizer ignore-call */doTree();
        }

        $this->printHeader(
            $this->lang['strtables'] . ' - ' . $_REQUEST['table'] . ' - ' . $this->lang['strconstraints'],
            '<script src="' . \containerInstance()->subFolder . '/assets/js/indexes.js" type="text/javascript"></script>',
            true,
            'header_select2.twig'
        );

        $onloadInitActions = [
            'add_unique_key',
            'save_add_unique_key',
            'add_primary_key',
            'save_add_primary_key',
            'add_foreign_key',
            'select_referenced_columns',
            'save_add_foreign_key',
        ];

        $onloadInit = false;

        if (\in_array($this->action, $onloadInitActions, true)) {
            $onloadInit = true;
        }
        $this->printBody(true, 'detailbody', $onloadInit);

        if (isset($_POST['cancel']) || ('drop' === $this->action && !isset($_POST['drop']))) {
            $this->action = 'default';
        }

        switch ($this->action) {
            case 'add_foreign_key':
                $this->formAddForeignKey();

                break;
            case 'select_referenced_columns':
                $this->_selectFKColumns();

                break;
            case 'save_add_foreign_key':
                $this->addForeignKey();

                break;
            case 'add_unique_key':
                $this->formPrimaryOrUniqueKey('unique');

                break;
            case 'add_primary_key':
                $this->formPrimaryOrUniqueKey('primary');

                break;
            case 'save_add_unique_key':
                $this->addPrimaryOrUniqueKey('unique');

                break;
            case 'save_add_primary_key':
                $this->addPrimaryOrUniqueKey('primary');

                break;
            case 'add_check':
                $this->addCheck(true);

                break;
            case 'save_add_check':
                $this->addCheck(false);

                break;
            case 'drop':
                $this->doDrop();

                break;
            case 'confirm_drop':
                $this->formDrop();

                break;

            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * List all the constraints on the table.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $cnPre = static function (&$rowdata) use ($data): void {
            if (null === $rowdata->fields['consrc']) {
                $atts = $data->getAttributeNames($_REQUEST['table'], \explode(' ', $rowdata->fields['indkey']));
                $rowdata->fields['+definition'] = ('u' === $rowdata->fields['contype'] ? 'UNIQUE (' : 'PRIMARY KEY (') . \implode(',', $atts) . ')';
            } else {
                $rowdata->fields['+definition'] = $rowdata->fields['consrc'];
            }
        };

        $this->printTrail('table');
        $this->printTabs('table', 'constraints');
        $this->printMsg($msg);

        $constraints = $data->getConstraints($_REQUEST['table']);

        $columns = [
            'constraint' => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('conname'),
            ],
            'definition' => [
                'title' => $this->lang['strdefinition'],
                'field' => Decorator::field('+definition'),
                'type' => 'pre',
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('constcomment'),
            ],
        ];

        $actions = [
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'constraints',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'table' => $_REQUEST['table'],
                            'constraint' => Decorator::field('conname'),
                            'type' => Decorator::field('contype'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($constraints, $columns, $actions, 'constraints-constraints', $this->lang['strnoconstraints'], $cnPre);

        $navlinks = [
            'addcheck' => [
                'attr' => [
                    'href' => [
                        'url' => 'constraints',
                        'urlvars' => [
                            'action' => 'add_check',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            'table' => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['straddcheck'],
            ],
            'adduniq' => [
                'attr' => [
                    'href' => [
                        'url' => 'constraints',
                        'urlvars' => [
                            'action' => 'add_unique_key',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            'table' => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['stradduniq'],
            ],
            'addpk' => [
                'attr' => [
                    'href' => [
                        'url' => 'constraints',
                        'urlvars' => [
                            'action' => 'add_primary_key',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            'table' => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['straddpk'],
            ],
            'addfk' => [
                'attr' => [
                    'href' => [
                        'url' => 'constraints',
                        'urlvars' => [
                            'action' => 'add_foreign_key',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            'table' => $_REQUEST['table'],
                        ],
                    ],
                ],
                'content' => $this->lang['straddfk'],
            ],
        ];
        $this->printNavLinks($navlinks, 'constraints-constraints', \get_defined_vars());
    }

    /**
     * Prints the first step to create an FK.
     *
     * @param string $msg The message
     */
    public function formAddForeignKey($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('table');
        $this->printTitle($this->lang['straddfk'], 'pg.constraint.foreign_key');
        $this->printMsg($msg);

        $attrs = $data->getTableAttributes($_REQUEST['table']);
        $tables = $data->getAllTables();

        $selColumns = new XHtmlSelect('TableColumnList', true, 10);
        $selColumns->set_style('width: 15em;');

        if (0 < $attrs->RecordCount()) {
            while (!$attrs->EOF) {
                $xmloption = new XHtmlOption($attrs->fields['attname']);
                $selColumns->add($xmloption);
                $attrs->MoveNext();
            }
        }

        $selIndex = new XHtmlSelect('IndexColumnList[]', true, 10);
        $selIndex->set_style('width: 15em;');
        $selIndex->set_attribute('id', 'IndexColumnList');
        $buttonAdd = new XHtmlButton('add', '>>');
        $buttonAdd->set_attribute('onclick', 'buttonPressed(this);');
        $buttonAdd->set_attribute('type', 'button');

        $buttonRemove = new XHtmlButton('remove', '<<');
        $buttonRemove->set_attribute('onclick', 'buttonPressed(this);');
        $buttonRemove->set_attribute('type', 'button');

        echo '<form onsubmit="doSelectAll();" name="formIndex" action="constraints" method="post">' . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data" colspan="3">%s</th></tr>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '<tr><td class="data1" colspan="3"><input type="text" name="name" size="32" maxlength="%s" /></td></tr>',
            $data->_maxNameLen
        ) . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data">%s</th><th class="data">&nbsp;</th><th class="data required">%s</th></tr>',
            $this->lang['strtablecolumnlist'],
            $this->lang['strfkcolumnlist']
        ) . \PHP_EOL;
        echo '<tr><td class="data1">' . $selColumns->fetch() . '</td>' . \PHP_EOL;
        echo '<td class="data1" style="text-align: center">' . $buttonRemove->fetch() . $buttonAdd->fetch() . '</td>' . \PHP_EOL;
        echo '<td class=data1>' . $selIndex->fetch() . '</td></tr>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data" colspan="3">%s</th></tr>',
            $this->lang['strfktarget']
        );
        echo '<tr>';
        echo '<td class="data1" colspan="3"><select class="select2" name="target">';

        while (!$tables->EOF) {
            $key = ['schemaname' => $tables->fields['nspname'], 'tablename' => $tables->fields['relname']];
            $key = \serialize($key);
            echo '<option value="', \htmlspecialchars($key), '">';

            if ($tables->fields['nspname'] !== $_REQUEST['schema']) {
                echo \htmlspecialchars($tables->fields['nspname']), '.';
            }
            echo \htmlspecialchars($tables->fields['relname']), '</option>' . \PHP_EOL;
            $tables->MoveNext();
        }
        echo '</select>' . \PHP_EOL;
        echo '</td></tr>';
        echo '</table>' . \PHP_EOL;

        echo $this->getFormInputsAndButtons(
            [
                ['name' => 'action', 'type' => 'hidden', 'value' => 'select_referenced_columns'],
                ['name' => 'table', 'type' => 'hidden', 'value' => \htmlspecialchars($_REQUEST['table'])],
            ],
            [
                ['type' => 'submit', 'name' => '', 'value' => $this->lang['stradd']],
                ['type' => 'submit', 'name' => 'cancel', 'value' => $this->lang['strcancel']],
            ]
        );

        echo \sprintf(
            '</form>%s',
            \PHP_EOL
        );
    }

    /**
     * Perform actual creation of the FOREIGN KEY constraint.
     *
     * @param string $msg optional message to display
     */
    public function addForeignKey($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'name', '');

        $this->coalesceArr($_POST, 'target', '');

        $this->coalesceArr($_POST, 'SourceColumnList', 'a:0:{}');

        $this->coalesceArr($_POST, 'IndexColumnList', []);

        // Unserialize target
        $_POST['target'] = \unserialize($_POST['target']);

        // Check that they've given at least one column
        $temp = \unserialize($_POST['SourceColumnList']);

        // If IndexColumnList or SourceColumnList are empty, return to screen to select referencing table columns
        if (!\is_array($_POST['IndexColumnList'])
            || 0 === \count($_POST['IndexColumnList'])
            || 0 === \count($temp)) {
            return $this->_selectFKColumns($this->lang['strfkneedscols']);
        }

        $status = $data->addForeignKey(
            $_POST['table'],
            $_POST['target']['schemaname'],
            $_POST['target']['tablename'],
            \unserialize($_POST['SourceColumnList']),
            $_POST['IndexColumnList'],
            $_POST['upd_action'],
            $_POST['del_action'],
            $_POST['match'],
            $_POST['deferrable'],
            $_POST['initially'],
            $_POST['name']
        );

        if (0 === $status) {
            return $this->doDefault($this->lang['strfkadded']);
        }

        return $this->_selectFKColumns($this->lang['strfkaddedbad']);
    }

    /**
     * Print form to add a PRIMARY KEY or UNIQUE constraint.
     *
     * @param string $type either primary or unique
     * @param string $msg  optional message
     */
    public function formPrimaryOrUniqueKey($type, $msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();
        $this->coalesceArr($_POST, 'name', '');

        $this->coalesceArr($_POST, 'tablespace', '');

        $this->printTrail('table');

        switch ($type) {
            case 'primary':
                $this->printTitle($this->lang['straddpk'], 'pg.constraint.primary_key');

                break;
            case 'unique':
                $this->printTitle($this->lang['stradduniq'], 'pg.constraint.unique_key');

                break;

            default:
                $this->doDefault($this->lang['strinvalidparam']);

                return;
        }

        $this->printMsg($msg);

        $attrs = $data->getTableAttributes($_REQUEST['table']);
        $tablespaces = null;
        // Fetch all tablespaces from the database
        if ($data->hasTablespaces()) {
            $tablespaces = $data->getTablespaces();
        }

        $selColumns = new XHtmlSelect('TableColumnList', true, 10);
        $selColumns->set_style('width: 15em;');

        if (0 < $attrs->RecordCount()) {
            while (!$attrs->EOF) {
                $new_option = new XHtmlOption($attrs->fields['attname']);
                $selColumns->add($new_option);
                $attrs->MoveNext();
            }
        }

        $selIndex = new XHtmlSelect('IndexColumnList[]', true, 10);
        $selIndex->set_style('width: 15em;');
        $selIndex->set_attribute('id', 'IndexColumnList');
        $buttonAdd = new XHtmlButton('add', '>>');
        $buttonAdd->set_attribute('onclick', 'buttonPressed(this);');
        $buttonAdd->set_attribute('type', 'button');

        $buttonRemove = new XHtmlButton('remove', '<<');
        $buttonRemove->set_attribute('onclick', 'buttonPressed(this);');
        $buttonRemove->set_attribute('type', 'button');

        echo '<form onsubmit="doSelectAll();" name="formIndex" action="constraints" method="post">' . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data" colspan="3">%s</th></tr>',
            $this->lang['strname']
        );
        echo '<tr>';
        echo '<td class="data1" colspan="3"><input type="text" name="name" value="', \htmlspecialchars($_POST['name']),
            \sprintf(
                '" size="32" maxlength="%s" /></td></tr>',
                $data->_maxNameLen
            );
        echo \sprintf(
            '<tr><th class="data">%s</th><th class="data">&nbsp;</th><th class="data required">%s</th></tr>',
            $this->lang['strtablecolumnlist'],
            $this->lang['strindexcolumnlist']
        ) . \PHP_EOL;
        echo '<tr><td class="data1">' . $selColumns->fetch() . '</td>' . \PHP_EOL;
        echo '<td class="data1" style="text-align: center">' . $buttonRemove->fetch() . $buttonAdd->fetch() . '</td>';
        echo '<td class=data1>' . $selIndex->fetch() . '</td></tr>' . \PHP_EOL;

        // Tablespace (if there are any)
        if ($data->hasTablespaces() && 0 < $tablespaces->RecordCount()) {
            echo \sprintf(
                '<tr><th class="data" colspan="3">%s</th></tr>',
                $this->lang['strtablespace']
            );
            echo '<tr><td class="data1" colspan="3"><select name="tablespace">' . \PHP_EOL;
            // Always offer the default (empty) option
            echo "\t\t\t\t<option value=\"\"",
            ('' === $_POST['tablespace']) ? ' selected="selected"' : '', '></option>' . \PHP_EOL;
            // Display all other tablespaces
            while (!$tablespaces->EOF) {
                $spcname = \htmlspecialchars($tablespaces->fields['spcname']);
                echo \sprintf(
                    '				<option value="%s"',
                    $spcname
                ),
                ($spcname === $_POST['tablespace']) ? ' selected="selected"' : '', \sprintf(
                    '>%s</option>',
                    $spcname
                ) . \PHP_EOL;
                $tablespaces->MoveNext();
            }
            echo '</select></td></tr>' . \PHP_EOL;
        }

        echo '</table>' . \PHP_EOL;

        echo $this->getFormInputsAndButtons(
            [
                ['name' => 'action', 'type' => 'hidden', 'value' => ('primary' === $type ? 'save_add_primary_key' : 'save_add_unique_key')],
                ['name' => 'table', 'type' => 'hidden', 'value' => \htmlspecialchars($_REQUEST['table'])],
            ],
            [
                ['type' => 'submit', 'name' => '', 'value' => $this->lang['stradd']],
                ['type' => 'submit', 'name' => 'cancel', 'value' => $this->lang['strcancel']],
            ]
        );

        echo \sprintf(
            '</form>%s',
            \PHP_EOL
        );
    }

    /**
     * Try to add a PRIMARY KEY or UNIQUE constraint.
     *
     * @param string $type either primary or unique
     */
    public function addPrimaryOrUniqueKey($type)
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'name', '');

        // Default tablespace to empty if it isn't set
        $this->coalesceArr($_POST, 'tablespace', '');

        if ('primary' === $type) {
            // Check that they've given at least one column
            if (!isset($_POST['IndexColumnList']) || !\is_array($_POST['IndexColumnList'])
                || 0 === \count($_POST['IndexColumnList'])
            ) {
                $this->formPrimaryOrUniqueKey($type, $this->lang['strpkneedscols']);
            } else {
                $status = $data->addPrimaryKey($_POST['table'], $_POST['IndexColumnList'], $_POST['name'], $_POST['tablespace']);

                if (0 === $status) {
                    return $this->doDefault($this->lang['strpkadded']);
                }

                return $this->formPrimaryOrUniqueKey($type, $this->lang['strpkaddedbad']);
            }
        } elseif ('unique' === $type) {
            // Check that they've given at least one column
            if (!isset($_POST['IndexColumnList']) || !\is_array($_POST['IndexColumnList'])
                || 0 === \count($_POST['IndexColumnList'])
            ) {
                $this->formPrimaryOrUniqueKey($type, $this->lang['struniqneedscols']);
            } else {
                $status = $data->addUniqueKey($_POST['table'], $_POST['IndexColumnList'], $_POST['name'], $_POST['tablespace']);

                if (0 === $status) {
                    return $this->doDefault($this->lang['struniqadded']);
                }

                return $this->formPrimaryOrUniqueKey($type, $this->lang['struniqaddedbad']);
            }
        } else {
            return $this->doDefault($this->lang['strinvalidparam']);
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
            $this->printTrail('table');
            $this->printTitle($this->lang['straddcheck'], 'pg.constraint.check');
            $this->printMsg($msg);

            echo '<form action="' . \containerInstance()->subFolder . '/src/views/constraints" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;
            echo \sprintf(
                '<tr><th class="data">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo \sprintf(
                '<th class="data required">%s</th></tr>',
                $this->lang['strdefinition']
            ) . \PHP_EOL;

            echo \sprintf(
                '<tr><td class="data1"><input name="name" size="24" maxlength="%s" value="',
                $data->_maxNameLen
            ),
            \htmlspecialchars($_POST['name']), '" /></td>' . \PHP_EOL;

            echo '<td class="data1">(<input name="definition" size="64" value="',
            \htmlspecialchars($_POST['definition']), '" />)</td></tr>' . \PHP_EOL;
            echo '</table>' . \PHP_EOL;

            echo $this->getFormInputsAndButtons(
                [
                    ['name' => 'action', 'type' => 'hidden', 'value' => 'save_add_check'],
                    ['name' => 'table', 'type' => 'hidden', 'value' => \htmlspecialchars($_REQUEST['table'])],
                ],
                [
                    ['type' => 'submit', 'name' => '', 'value' => $this->lang['stradd']],
                    ['type' => 'submit', 'name' => 'cancel', 'value' => $this->lang['strcancel']],
                ]
            );

            echo \sprintf(
                '</form>%s',
                \PHP_EOL
            );
        } else {
            if ('' === \trim($_POST['definition'])) {
                $this->addCheck(true, $this->lang['strcheckneedsdefinition']);
            } else {
                $status = $data->addCheckConstraint(
                    $_POST['table'],
                    $_POST['definition'],
                    $_POST['name']
                );

                if (0 === $status) {
                    return $this->doDefault($this->lang['strcheckadded']);
                }

                return $this->addCheck(true, $this->lang['strcheckaddedbad']);
            }
        }
    }

    /**
     * Prints the drop form.
     */
    public function formDrop(): void
    {
        $this->printTrail('constraint');
        $this->printTitle($this->lang['strdrop'], 'pg.constraint.drop');

        echo '<p>', \sprintf(
            $this->lang['strconfdropconstraint'],
            $this->misc->printVal($_REQUEST['constraint']),
            $this->misc->printVal($_REQUEST['table'])
        ), '</p>' . \PHP_EOL;

        echo \sprintf(
            '<form action="constraints" method="post">%s',
            \PHP_EOL
        );

        echo $this->getFormInputsAndButtons(
            [
                ['name' => 'action', 'value' => 'drop', 'type' => 'hidden'],
                ['name' => 'table', 'value' => \htmlspecialchars($_REQUEST['table']), 'type' => 'hidden'],
                ['name' => 'constraint', 'value' => \htmlspecialchars($_REQUEST['constraint']), 'type' => 'hidden'],
                ['name' => 'type', 'value' => \htmlspecialchars($_REQUEST['type']), 'type' => 'hidden'],
            ],
            [
                ['type' => 'submit', 'name' => 'drop', 'value' => $this->lang['strdrop']],
                ['type' => 'submit', 'name' => 'cancel', 'value' => $this->lang['strcancel']],
            ],
            [
                ['type' => 'checkbox', 'name' => 'cascade', 'id' => 'cascade', 'checked' => false, 'labeltext' => $this->lang['strcascade']],
            ]
        );

        echo \sprintf(
            '</form>%s',
            \PHP_EOL
        );
    }

    /**
     * Try to perform actual drop.
     */
    public function doDrop()
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->dropConstraint($_POST['constraint'], $_POST['table'], $_POST['type'], isset($_POST['cascade']));

        if (0 === $status) {
            return $this->doDefault($this->lang['strconstraintdropped']);
        }

        return $this->doDefault($this->lang['strconstraintdroppedbad']);
    }

    /**
     * Prints second screen of FK creation, where you select which columns
     * to use in the referencing table.
     *
     * @param string $msg optional message to display
     */
    private function _selectFKColumns($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'name', '');

        $this->coalesceArr($_POST, 'target', '');

        // Check that they've given at least one source column
        if (!isset($_REQUEST['SourceColumnList']) && (!isset($_POST['IndexColumnList']) ||
            !\is_array($_POST['IndexColumnList']) ||
            0 === \count($_POST['IndexColumnList']))) {
            return $this->formAddForeignKey($this->lang['strfkneedscols']);
        }
        // Copy the IndexColumnList variable from stage 1
        if (isset($_REQUEST['IndexColumnList']) && !isset($_REQUEST['SourceColumnList'])) {
            $_REQUEST['SourceColumnList'] = \serialize($_REQUEST['IndexColumnList']);
        }

        // Initialise variables
        $this->coalesceArr($_POST, 'upd_action', null);

        $this->coalesceArr($_POST, 'del_action', null);

        $this->coalesceArr($_POST, 'match', null);

        $this->coalesceArr($_POST, 'deferrable', null);

        $this->coalesceArr($_POST, 'initially', null);

        $_REQUEST['target'] = \unserialize($_REQUEST['target']);

        $this->printTrail('table');
        $this->printTitle($this->lang['straddfk'], 'pg.constraint.foreign_key');
        $this->printMsg($msg);

        // Unserialize target and fetch appropriate table. This is a bit messy
        // because the table could be in another schema.
        $data->setSchema($_REQUEST['target']['schemaname']);
        $attrs = $data->getTableAttributes($_REQUEST['target']['tablename']);
        $data->setSchema($_REQUEST['schema']);

        $selColumns = new XHtmlSelect('TableColumnList', true, 10);
        $selColumns->set_style('width: 15em;');

        if (0 < $attrs->RecordCount()) {
            while (!$attrs->EOF) {
                $xmloption = new XHtmlOption($attrs->fields['attname']);
                $selColumns->add($xmloption);
                $attrs->MoveNext();
            }
        }

        $selIndex = new XHtmlSelect('IndexColumnList[]', true, 10);
        $selIndex->set_style('width: 15em;');
        $selIndex->set_attribute('id', 'IndexColumnList');
        $buttonAdd = new XHtmlButton('add', '>>');
        $buttonAdd->set_attribute('onclick', 'buttonPressed(this);');
        $buttonAdd->set_attribute('type', 'button');

        $buttonRemove = new XHtmlButton('remove', '<<');
        $buttonRemove->set_attribute('onclick', 'buttonPressed(this);');
        $buttonRemove->set_attribute('type', 'button');

        echo '<form onsubmit="doSelectAll();" name="formIndex" action="constraints" method="post">' . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data" colspan="3">%s</th></tr>',
            $this->lang['strfktarget']
        );
        echo \sprintf(
            '<tr><th class="data">%s</th><th class="data">&nbsp;</th><th class=data>%s</th></tr>',
            $this->lang['strtablecolumnlist'],
            $this->lang['strfkcolumnlist']
        ) . \PHP_EOL;
        echo '<tr><td class="data1">' . $selColumns->fetch() . '</td>' . \PHP_EOL;
        echo '<td class="data1" style="text-align: center">' . $buttonRemove->fetch() . $buttonAdd->fetch() . '</td>';
        echo '<td class="data1">' . $selIndex->fetch() . '</td></tr>' . \PHP_EOL;
        echo \sprintf(
            '<tr><th class="data" colspan="3">%s</th></tr>',
            $this->lang['stractions']
        );
        echo '<tr>';
        echo '<td class="data1" colspan="3">' . \PHP_EOL;
        // ON SELECT actions
        echo \sprintf(
            '%s <select name="upd_action">',
            $this->lang['stronupdate']
        );

        foreach ($data->fkactions as $v) {
            echo \sprintf(
                '<option value="%s"',
                $v
            ), ($_POST['upd_action'] === $v) ? ' selected="selected"' : '', \sprintf(
                '>%s</option>',
                $v
            ) . \PHP_EOL;
        }

        echo '</select><br />' . \PHP_EOL;

        // ON DELETE actions
        echo \sprintf(
            '%s <select name="del_action">',
            $this->lang['strondelete']
        );

        foreach ($data->fkactions as $v) {
            echo \sprintf(
                '<option value="%s"',
                $v
            ), ($_POST['del_action'] === $v) ? ' selected="selected"' : '', \sprintf(
                '>%s</option>',
                $v
            ) . \PHP_EOL;
        }

        echo '</select><br />' . \PHP_EOL;

        // MATCH options
        echo '<select name="match">';

        foreach ($data->fkmatches as $v) {
            echo \sprintf(
                '<option value="%s"',
                $v
            ), ($_POST['match'] === $v) ? ' selected="selected"' : '', \sprintf(
                '>%s</option>',
                $v
            ) . \PHP_EOL;
        }

        echo '</select><br />' . \PHP_EOL;

        // DEFERRABLE options
        echo '<select name="deferrable">';

        foreach ($data->fkdeferrable as $v) {
            echo \sprintf(
                '<option value="%s"',
                $v
            ), ($_POST['deferrable'] === $v) ? ' selected="selected"' : '', \sprintf(
                '>%s</option>',
                $v
            ) . \PHP_EOL;
        }

        echo '</select><br />' . \PHP_EOL;

        // INITIALLY options
        echo '<select name="initially">';

        foreach ($data->fkinitial as $v) {
            echo \sprintf(
                '<option value="%s"',
                $v
            ), ($_POST['initially'] === $v) ? ' selected="selected"' : '', \sprintf(
                '>%s</option>',
                $v
            ) . \PHP_EOL;
        }

        echo '</select>' . \PHP_EOL;
        echo '</td></tr>' . \PHP_EOL;
        echo '</table>' . \PHP_EOL;

        echo '<p>';

        echo '<input type="hidden" name="name" value="', \htmlspecialchars($_REQUEST['name']), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="target" value="', \htmlspecialchars(\serialize($_REQUEST['target'])), '" />' . \PHP_EOL;
        echo '<input type="hidden" name="SourceColumnList" value="', \htmlspecialchars($_REQUEST['SourceColumnList']), '" />' . \PHP_EOL;

        echo $this->getActionTableAndButtons(
            'save_add_foreign_key',
            \htmlspecialchars($_REQUEST['table']),
            $this->lang['stradd'],
            $this->lang['strcancel']
        );

        echo \sprintf(
            '</p>%s</form>%s',
            \PHP_EOL,
            \PHP_EOL
        );
    }
}
