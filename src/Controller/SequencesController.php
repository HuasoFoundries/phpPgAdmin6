<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;
use Slim\Http\Response;

/**
 * Base controller class.
 */
class SequencesController extends BaseController
{
    public $controller_title = 'strsequences';

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

        // Print header

        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            case 'create':
                $this->doCreateSequence();

                break;
            case 'save_create_sequence':
                if (null !== $this->getPostParam('create')) {
                    $this->doSaveCreateSequence();
                } else {
                    $this->doDefault();
                }

                break;
            case 'properties':
                $this->doProperties();

                break;
            case 'drop':
                if (null !== $this->getPostParam('drop')) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'restart':
                $this->doRestart();

                break;
            case 'reset':
                $this->doReset();

                break;
            case 'nextval':
                $this->doNextval();

                break;
            case 'setval':
                if (isset($_POST['setval'])) {
                    $this->doSaveSetval();
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_setval':
                $this->doSetval();

                break;
            case 'alter':
                if (null !== $this->getPostParam('alter')) {
                    $this->doSaveAlter();
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_alter':
                $this->doAlter();

                break;

            default:
                $this->doDefault();

                break;
        }

        // Print footer
        return $this->printFooter();
    }

    /**
     * Display list of all sequences in the database/schema.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'sequences');
        $this->printMsg($msg);

        // Get all sequences
        $sequences = $data->getSequences();

        $columns = [
            'sequence' => [
                'title' => $this->lang['strsequence'],
                'field' => Decorator::field('seqname'),
                'url' => \sprintf(
                    'sequences?action=properties&amp;%s&amp;',
                    $this->misc->href
                ),
                'vars' => ['sequence' => 'seqname'],
            ],
            'owner' => [
                'title' => $this->lang['strowner'],
                'field' => Decorator::field('seqowner'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
            'comment' => [
                'title' => $this->lang['strcomment'],
                'field' => Decorator::field('seqcomment'),
            ],
        ];

        $actions = [
            'multiactions' => [
                'keycols' => ['sequence' => 'seqname'],
                'url' => 'sequences',
            ],
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => 'sequences',
                        'urlvars' => [
                            'action' => 'confirm_alter',
                            'subject' => 'sequence',
                            'sequence' => Decorator::field('seqname'),
                        ],
                    ],
                ],
            ],
            'drop' => [
                'content' => $this->lang['strdrop'],
                'attr' => [
                    'href' => [
                        'url' => 'sequences',
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'sequence' => Decorator::field('seqname'),
                        ],
                    ],
                ],
                'multiaction' => 'confirm_drop',
            ],
            'privileges' => [
                'content' => $this->lang['strprivileges'],
                'attr' => [
                    'href' => [
                        'url' => 'privileges',
                        'urlvars' => [
                            'subject' => 'sequence',
                            'sequence' => Decorator::field('seqname'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($sequences)) {
            echo $this->printTable($sequences, $columns, $actions, 'sequences-sequences', $this->lang['strnosequences']);
        }

        return $this->printNavLinks(['create' => [
            'attr' => [
                'href' => [
                    'url' => 'sequences',
                    'urlvars' => [
                        'action' => 'create',
                        'server' => $_REQUEST['server'],
                        'database' => $_REQUEST['database'],
                        'schema' => $_REQUEST['schema'],
                    ],
                ],
            ],
            'content' => $this->lang['strcreatesequence'],
        ]], 'sequences-sequences', \get_defined_vars());
    }

    /**
     * Generate XML for the browser tree.
     *
     * @return Response|string
     */
    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $sequences = $data->getSequences();
        $reqvars = $this->misc->getRequestVars('sequence');

        $attrs = [
            'text' => Decorator::field('seqname'),
            'icon' => 'Sequence',
            'toolTip' => Decorator::field('seqcomment'),
            'iconAction' => Decorator::url('sequences', $reqvars, ['action' => 'properties', 'sequence' => Decorator::field('seqname')]),
            'action' => Decorator::url('sequences', $reqvars, ['action' => 'properties', 'sequence' => Decorator::field('seqname')]),
        ];

        return $this->printTree($sequences, $attrs, 'sequences');
    }

    /**
     * Display the properties of a sequence.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();
        $this->printTrail('sequence');
        $this->printTitle($this->lang['strproperties'], 'pg.sequence');
        $this->printMsg($msg);

        // Fetch the sequence information
        $sequence = $data->getSequence($_REQUEST['sequence']);

        if (\is_object($sequence) && 0 < $sequence->RecordCount()) {
            $sequence->fields['is_cycled'] = $data->phpBool($sequence->fields['is_cycled']);
            $sequence->fields['is_called'] = $data->phpBool($sequence->fields['is_called']);

            // Show comment if any
            if (null !== $sequence->fields['seqcomment']) {
                echo '<p class="comment">', $this->misc->printVal($sequence->fields['seqcomment']), '</p>' . \PHP_EOL;
            }

            echo '<table border="0">';
            echo \sprintf(
                '<tr><th class="data">%s</th>',
                $this->lang['strname']
            );

            if ($data->hasAlterSequenceStart()) {
                echo \sprintf(
                    '<th class="data">%s</th>',
                    $this->lang['strstartvalue']
                );
            }
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strlastvalue']
            );
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strincrementby']
            );
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strmaxvalue']
            );
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strminvalue']
            );
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strcachevalue']
            );
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strlogcount']
            );
            echo \sprintf(
                '<th class="data">%s</th>',
                $this->lang['strcancycle']
            );
            echo \sprintf(
                '<th class="data">%s</th></tr>',
                $this->lang['striscalled']
            );
            echo '<tr>';
            echo '<td class="data1">', $this->misc->printVal($sequence->fields['seqname']), '</td>';

            if ($data->hasAlterSequenceStart()) {
                echo '<td class="data1">', $this->misc->printVal($sequence->fields['start_value']), '</td>';
            }
            echo '<td class="data1">', $this->misc->printVal($sequence->fields['last_value']), '</td>';
            echo '<td class="data1">', $this->misc->printVal($sequence->fields['increment_by']), '</td>';
            echo '<td class="data1">', $this->misc->printVal($sequence->fields['max_value']), '</td>';
            echo '<td class="data1">', $this->misc->printVal($sequence->fields['min_value']), '</td>';
            echo '<td class="data1">', $this->misc->printVal($sequence->fields['cache_value']), '</td>';
            echo '<td class="data1">', $this->misc->printVal($sequence->fields['log_cnt']), '</td>';
            echo '<td class="data1">', ($sequence->fields['is_cycled'] ? $this->lang['stryes'] : $this->lang['strno']), '</td>';
            echo '<td class="data1">', ($sequence->fields['is_called'] ? $this->lang['stryes'] : $this->lang['strno']), '</td>';
            echo '</tr>';
            echo '</table>';

            $navlinks = [
                'alter' => [
                    'attr' => [
                        'href' => [
                            'url' => 'sequences',
                            'urlvars' => [
                                'action' => 'confirm_alter',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                                'sequence' => $sequence->fields['seqname'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['stralter'],
                ],
                'setval' => [
                    'attr' => [
                        'href' => [
                            'url' => 'sequences',
                            'urlvars' => [
                                'action' => 'confirm_setval',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                                'sequence' => $sequence->fields['seqname'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strsetval'],
                ],
                'nextval' => [
                    'attr' => [
                        'href' => [
                            'url' => 'sequences',
                            'urlvars' => [
                                'action' => 'nextval',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                                'sequence' => $sequence->fields['seqname'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strnextval'],
                ],
                'restart' => [
                    'attr' => [
                        'href' => [
                            'url' => 'sequences',
                            'urlvars' => [
                                'action' => 'restart',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                                'sequence' => $sequence->fields['seqname'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strrestart'],
                ],
                'reset' => [
                    'attr' => [
                        'href' => [
                            'url' => 'sequences',
                            'urlvars' => [
                                'action' => 'reset',
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                                'sequence' => $sequence->fields['seqname'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strreset'],
                ],
                'showall' => [
                    'attr' => [
                        'href' => [
                            'url' => 'sequences',
                            'urlvars' => [
                                'server' => $_REQUEST['server'],
                                'database' => $_REQUEST['database'],
                                'schema' => $_REQUEST['schema'],
                            ],
                        ],
                    ],
                    'content' => $this->lang['strshowallsequences'],
                ],
            ];

            if (!$data->hasAlterSequenceStart()) {
                unset($navlinks['restart']);
            }

            return $this->printNavLinks($navlinks, 'sequences-properties', \get_defined_vars());
        }
        echo \sprintf(
            '<p>%s</p>',
            $this->lang['strnodata']
        ) . \PHP_EOL;
    }

    /**
     * Drop a sequence.
     *
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doDrop($confirm, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['sequence']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($this->lang['strspecifysequencetodrop']);
        }

        if ($confirm) {
            $this->printTrail('sequence');
            $this->printTitle($this->lang['strdrop'], 'pg.sequence.drop');
            $this->printMsg($msg);
            echo '<form action="sequences" method="post">' . \PHP_EOL;
            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = \unserialize(\htmlspecialchars_decode($v, \ENT_QUOTES));
                    echo '<p>', \sprintf(
                        $this->lang['strconfdropsequence'],
                        $this->misc->printVal($a['sequence'])
                    ), '</p>' . \PHP_EOL;
                    \printf('<input type="hidden" name="sequence[]" value="%s" />', \htmlspecialchars($a['sequence']));
                }
            } else {
                echo '<p>', \sprintf(
                    $this->lang['strconfdropsequence'],
                    $this->misc->printVal($_REQUEST['sequence'])
                ), '</p>' . \PHP_EOL;
                echo '<input type="hidden" name="sequence" value="', \htmlspecialchars($_REQUEST['sequence']), '" />' . \PHP_EOL;
            }
            echo \sprintf(
                '<p><input type="checkbox" id="cascade" name="cascade" /> <label for="cascade">%s</label></p>',
                $this->lang['strcascade']
            ) . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="drop" />' . \PHP_EOL;
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
        } elseif (\is_array($_POST['sequence'])) {
            $msg = '';
            $status = $data->beginTransaction();

            if (0 === $status) {
                foreach ($_POST['sequence'] as $s) {
                    $status = $data->dropSequence($s, isset($_POST['cascade']));

                    if (0 === $status) {
                        $msg .= \sprintf(
                            '%s: %s<br />',
                            \htmlentities($s, \ENT_QUOTES, 'UTF-8'),
                            $this->lang['strsequencedropped']
                        );
                    } else {
                        $data->endTransaction();
                        $this->doDefault(\sprintf(
                            '%s%s: %s<br />',
                            $msg,
                            \htmlentities($s, \ENT_QUOTES, 'UTF-8'),
                            $this->lang['strsequencedroppedbad']
                        ));

                        return;
                    }
                }
            }

            if (0 === $data->endTransaction()) {
                // Everything went fine, back to the Default page....
                $this->view->setReloadBrowser(true);
                $this->doDefault($msg);
            } else {
                $this->doDefault($this->lang['strsequencedroppedbad']);
            }
        } else {
            $status = $data->dropSequence($_POST['sequence'], isset($_POST['cascade']));

            if (0 === $status) {
                $this->view->setReloadBrowser(true);
                $this->doDefault($this->lang['strsequencedropped']);
            } else {
                $this->doDrop(true, $this->lang['strsequencedroppedbad']);
            }
        }
    }

    /**
     * Displays a screen where they can enter a new sequence.
     *
     * @param mixed $msg
     */
    public function doCreateSequence($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'formSequenceName', '');

        $this->coalesceArr($_POST, 'formIncrement', '');

        $this->coalesceArr($_POST, 'formMinValue', '');

        $this->coalesceArr($_POST, 'formMaxValue', '');

        $this->coalesceArr($_POST, 'formStartValue', '');

        $this->coalesceArr($_POST, 'formCacheValue', '');

        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreatesequence'], 'pg.sequence.create');
        $this->printMsg($msg);

        echo '<form action="sequences" method="post">' . \PHP_EOL;
        echo '<table>' . \PHP_EOL;

        echo \sprintf(
            '<tr><th class="data left required">%s</th>',
            $this->lang['strname']
        ) . \PHP_EOL;
        echo \sprintf(
            '<td class="data1"><input name="formSequenceName" size="32" maxlength="%s" value="',
            $data->_maxNameLen
        ),
            \htmlspecialchars($_POST['formSequenceName']),
            '" /></td></tr>' . \PHP_EOL;

        echo \sprintf(
            '<tr><th class="data left">%s</th>',
            $this->lang['strincrementby']
        ) . \PHP_EOL;
        echo '<td class="data1"><input name="formIncrement" size="5" value="',
            \htmlspecialchars($_POST['formIncrement']),
            '" /> </td></tr>' . \PHP_EOL;

        echo \sprintf(
            '<tr><th class="data left">%s</th>',
            $this->lang['strminvalue']
        ) . \PHP_EOL;
        echo '<td class="data1"><input name="formMinValue" size="5" value="',
            \htmlspecialchars($_POST['formMinValue']),
            '" /></td></tr>' . \PHP_EOL;

        echo \sprintf(
            '<tr><th class="data left">%s</th>',
            $this->lang['strmaxvalue']
        ) . \PHP_EOL;
        echo '<td class="data1"><input name="formMaxValue" size="5" value="',
            \htmlspecialchars($_POST['formMaxValue']),
            '" /></td></tr>' . \PHP_EOL;

        echo \sprintf(
            '<tr><th class="data left">%s</th>',
            $this->lang['strstartvalue']
        ) . \PHP_EOL;
        echo '<td class="data1"><input name="formStartValue" size="5" value="',
            \htmlspecialchars($_POST['formStartValue']),
            '" /></td></tr>' . \PHP_EOL;

        echo \sprintf(
            '<tr><th class="data left">%s</th>',
            $this->lang['strcachevalue']
        ) . \PHP_EOL;
        echo '<td class="data1"><input name="formCacheValue" size="5" value="',
            \htmlspecialchars($_POST['formCacheValue']),
            '" /></td></tr>' . \PHP_EOL;

        echo \sprintf(
            '<tr><th class="data left"><label for="formCycledValue">%s</label></th>',
            $this->lang['strcancycle']
        ) . \PHP_EOL;
        echo '<td class="data1"><input type="checkbox" id="formCycledValue" name="formCycledValue" ',
            (isset($_POST['formCycledValue']) ? ' checked="checked"' : ''),
            ' /></td></tr>' . \PHP_EOL;

        echo '</table>' . \PHP_EOL;
        echo '<p><input type="hidden" name="action" value="save_create_sequence" />' . \PHP_EOL;
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
     * Actually creates the new sequence in the database.
     */
    public function doSaveCreateSequence(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name and at least one column
        if ('' === $_POST['formSequenceName']) {
            $this->doCreateSequence($this->lang['strsequenceneedsname']);
        } else {
            $status = $data->createSequence(
                $_POST['formSequenceName'],
                $_POST['formIncrement'],
                $_POST['formMinValue'],
                $_POST['formMaxValue'],
                $_POST['formStartValue'],
                $_POST['formCacheValue'],
                isset($_POST['formCycledValue'])
            );

            if (0 === $status) {
                $this->doDefault($this->lang['strsequencecreated']);
            } else {
                $this->doCreateSequence($this->lang['strsequencecreatedbad']);
            }
        }
    }

    /**
     * Restarts a sequence.
     */
    public function doRestart(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->restartSequence($_REQUEST['sequence']);

        if (0 === $status) {
            $this->doProperties($this->lang['strsequencerestart']);
        } else {
            $this->doProperties($this->lang['strsequencerestartbad']);
        }
    }

    /**
     * Resets a sequence.
     */
    public function doReset(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->resetSequence($_REQUEST['sequence']);

        if (0 === $status) {
            $this->doProperties($this->lang['strsequencereset']);
        } else {
            $this->doProperties($this->lang['strsequenceresetbad']);
        }
    }

    /**
     * Set Nextval of a sequence.
     */
    public function doNextval(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->nextvalSequence($_REQUEST['sequence']);

        if (0 === $status) {
            $this->doProperties($this->lang['strsequencenextval']);
        } else {
            $this->doProperties($this->lang['strsequencenextvalbad']);
        }
    }

    /**
     * Function to save after 'setval'ing a sequence.
     */
    public function doSaveSetval(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $status = $data->setvalSequence($_POST['sequence'], $_POST['nextvalue']);

        if (0 === $status) {
            $this->doProperties($this->lang['strsequencesetval']);
        } else {
            $this->doProperties($this->lang['strsequencesetvalbad']);
        }
    }

    /**
     * Function to allow 'setval'ing of a sequence.
     *
     * @param mixed $msg
     */
    public function doSetval($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('sequence');
        $this->printTitle($this->lang['strsetval'], 'pg.sequence');
        $this->printMsg($msg);

        // Fetch the sequence information
        $sequence = $data->getSequence($_REQUEST['sequence']);

        if (\is_object($sequence) && 0 < $sequence->RecordCount()) {
            echo '<form action="sequences" method="post">' . \PHP_EOL;
            echo '<table border="0">';
            echo \sprintf(
                '<tr><th class="data left required">%s</th>',
                $this->lang['strlastvalue']
            ) . \PHP_EOL;
            echo '<td class="data1">';
            echo \sprintf(
                '<input name="nextvalue" size="32" maxlength="%s" value="',
                $data->_maxNameLen
            ),
                $this->misc->printVal($sequence->fields['last_value']),
                '" /></td></tr>' . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="setval" />' . \PHP_EOL;
            echo '<input type="hidden" name="sequence" value="', \htmlspecialchars($_REQUEST['sequence']), '" />' . \PHP_EOL;
            echo $this->view->form;
            echo \sprintf(
                '<input type="submit" name="setval" value="%s" />',
                $this->lang['strsetval']
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
     * Function to save after altering a sequence.
     */
    public function doSaveAlter(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_POST, 'owner', null);

        $this->coalesceArr($_POST, 'newschema', null);

        $this->coalesceArr($_POST, 'formIncrement', null);

        $this->coalesceArr($_POST, 'formMinValue', null);

        $this->coalesceArr($_POST, 'formMaxValue', null);

        $this->coalesceArr($_POST, 'formStartValue', null);

        $this->coalesceArr($_POST, 'formRestartValue', null);

        $this->coalesceArr($_POST, 'formCacheValue', null);

        $this->coalesceArr($_POST, 'formCycledValue', null);

        $status = $data->alterSequence(
            $_POST['sequence'],
            $_POST['name'],
            $_POST['comment'],
            $_POST['owner'],
            $_POST['newschema'],
            $_POST['formIncrement'],
            $_POST['formMinValue'],
            $_POST['formMaxValue'],
            $_POST['formRestartValue'],
            $_POST['formCacheValue'],
            isset($_POST['formCycledValue']),
            $_POST['formStartValue']
        );

        if (0 === $status) {
            if ($_POST['sequence'] !== $_POST['name']) {
                // Jump them to the new view name
                $_REQUEST['sequence'] = $_POST['name'];
                // Force a browser reload
                $this->view->setReloadBrowser(true);
            }

            if (!empty($_POST['newschema']) && ($_POST['newschema'] !== $data->_schema)) {
                // Jump them to the new sequence schema
                $this->misc->setCurrentSchema($_POST['newschema']);
                $this->view->setReloadBrowser(true);
            }
            $this->doProperties($this->lang['strsequencealtered']);
        } else {
            $this->doProperties($this->lang['strsequencealteredbad']);
        }
    }

    /**
     * Function to allow altering of a sequence.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('sequence');
        $this->printTitle($this->lang['stralter'], 'pg.sequence.alter');
        $this->printMsg($msg);

        // Fetch the sequence information
        $sequence = $data->getSequence($_REQUEST['sequence']);

        if (\is_object($sequence) && 0 < $sequence->RecordCount()) {
            $this->coalesceArr($_POST, 'name', $_REQUEST['sequence']);

            $this->coalesceArr($_POST, 'comment', $sequence->fields['seqcomment']);

            $this->coalesceArr($_POST, 'owner', $sequence->fields['seqowner']);

            $this->coalesceArr($_POST, 'newschema', $sequence->fields['nspname']);

            // Handle Checkbox Value
            $sequence->fields['is_cycled'] = $data->phpBool($sequence->fields['is_cycled']);

            if ($sequence->fields['is_cycled']) {
                $_POST['formCycledValue'] = 'on';
            }

            echo '<form action="sequences" method="post">' . \PHP_EOL;
            echo '<table>' . \PHP_EOL;

            echo \sprintf(
                '<tr><th class="data left required">%s</th>',
                $this->lang['strname']
            ) . \PHP_EOL;
            echo '<td class="data1">';
            echo \sprintf(
                '<input name="name" size="32" maxlength="%s" value="',
                $data->_maxNameLen
            ),
                \htmlspecialchars($_POST['name']),
                '" /></td></tr>' . \PHP_EOL;

            if ($data->isSuperUser()) {
                // Fetch all users
                $users = $data->getUsers();

                echo \sprintf(
                    '<tr><th class="data left required">%s</th>',
                    $this->lang['strowner']
                ) . \PHP_EOL;
                echo '<td class="data1"><select name="owner">';

                while (!$users->EOF) {
                    $uname = $users->fields['usename'];
                    echo '<option value="', \htmlspecialchars($uname), '"',
                        ($uname === $_POST['owner']) ? ' selected="selected"' : '',
                        '>',
                        \htmlspecialchars($uname),
                        '</option>' . \PHP_EOL;
                    $users->MoveNext();
                }
                echo '</select></td></tr>' . \PHP_EOL;
            }

            if ($data->hasAlterSequenceSchema()) {
                $schemas = $data->getSchemas();
                echo \sprintf(
                    '<tr><th class="data left required">%s</th>',
                    $this->lang['strschema']
                ) . \PHP_EOL;
                echo '<td class="data1"><select name="newschema">';

                while (!$schemas->EOF) {
                    $schema = $schemas->fields['nspname'];
                    echo '<option value="', \htmlspecialchars($schema), '"',
                        ($schema === $_POST['newschema']) ? ' selected="selected"' : '',
                        '>',
                        \htmlspecialchars($schema),
                        '</option>' . \PHP_EOL;
                    $schemas->MoveNext();
                }
                echo '</select></td></tr>' . \PHP_EOL;
            }

            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strcomment']
            ) . \PHP_EOL;
            echo '<td class="data1">';
            echo '<textarea rows="3" cols="32" name="comment">',
                \htmlspecialchars($_POST['comment']),
                '</textarea></td></tr>' . \PHP_EOL;

            if ($data->hasAlterSequenceStart()) {
                echo \sprintf(
                    '<tr><th class="data left">%s</th>',
                    $this->lang['strstartvalue']
                ) . \PHP_EOL;
                echo '<td class="data1"><input name="formStartValue" size="5" value="',
                    \htmlspecialchars($sequence->fields['start_value']),
                    '" /></td></tr>' . \PHP_EOL;
            }

            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strrestartvalue']
            ) . \PHP_EOL;
            echo '<td class="data1"><input name="formRestartValue" size="5" value="',
                \htmlspecialchars($sequence->fields['last_value']),
                '" /></td></tr>' . \PHP_EOL;

            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strincrementby']
            ) . \PHP_EOL;
            echo '<td class="data1"><input name="formIncrement" size="5" value="',
                \htmlspecialchars($sequence->fields['increment_by']),
                '" /> </td></tr>' . \PHP_EOL;

            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strmaxvalue']
            ) . \PHP_EOL;
            echo '<td class="data1"><input name="formMaxValue" size="5" value="',
                \htmlspecialchars($sequence->fields['max_value']),
                '" /></td></tr>' . \PHP_EOL;

            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strminvalue']
            ) . \PHP_EOL;
            echo '<td class="data1"><input name="formMinValue" size="5" value="',
                \htmlspecialchars($sequence->fields['min_value']),
                '" /></td></tr>' . \PHP_EOL;

            echo \sprintf(
                '<tr><th class="data left">%s</th>',
                $this->lang['strcachevalue']
            ) . \PHP_EOL;
            echo '<td class="data1"><input name="formCacheValue" size="5" value="',
                \htmlspecialchars($sequence->fields['cache_value']),
                '" /></td></tr>' . \PHP_EOL;

            echo \sprintf(
                '<tr><th class="data left"><label for="formCycledValue">%s</label></th>',
                $this->lang['strcancycle']
            ) . \PHP_EOL;
            echo '<td class="data1"><input type="checkbox" id="formCycledValue" name="formCycledValue" ',
                (isset($_POST['formCycledValue']) ? ' checked="checked"' : ''),
                ' /></td></tr>' . \PHP_EOL;

            echo '</table>' . \PHP_EOL;
            echo '<p><input type="hidden" name="action" value="alter" />' . \PHP_EOL;
            echo $this->view->form;
            echo '<input type="hidden" name="sequence" value="', \htmlspecialchars($_REQUEST['sequence']), '" />' . \PHP_EOL;
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
}
