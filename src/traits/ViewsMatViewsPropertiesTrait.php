<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Traits;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Common trait for dealing with views or materialized views.
 */
trait ViewsMatViewsPropertiesTrait
{
    public $href = '';

    public $misc;

    public $view_name;

    /**
     * Show view definition and virtual columns.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        $attPre = static function (&$rowdata) use ($data): void {
            $rowdata->fields['+type'] = $data->formatType($rowdata->fields['type'], $rowdata->fields['atttypmod']);
        };

        $this->printTrail($this->subject);
        $this->printTabs($this->subject, 'columns');
        $this->printMsg($msg);

        // Get view
        $vdata = $data->getView($_REQUEST[$this->subject]);
        // Get columns (using same method for getting a view)
        $attrs = $data->getTableAttributes($_REQUEST[$this->subject]);

        // Show comment if any
        if (null !== $vdata->fields['relcomment']) {
            echo '<p class="comment">', $this->misc->printVal($vdata->fields['relcomment']), '</p>' . \PHP_EOL;
        }

        $columns = [
            'column' => [
                'title' => $this->lang['strcolumn'],
                'field' => Decorator::field('attname'),
                'url' => "colproperties?subject=column&amp;{$this->misc->href}&amp;view=" . \urlencode($_REQUEST[$this->subject]) . '&amp;',
                'vars' => ['column' => 'attname'],
            ],
            'type' => [
                'title' => $this->lang['strtype'],
                'field' => Decorator::field('+type'),
            ],
            'default' => [
                'title' => $this->lang['strdefault'],
                'field' => Decorator::field('adsrc'),
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
            'alter' => [
                'content' => $this->lang['stralter'],
                'attr' => [
                    'href' => [
                        'url' => $this->view_name,
                        'urlvars' => [
                            'action' => 'properties',
                            $this->subject => $_REQUEST[$this->subject],
                            'column' => Decorator::field('attname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($attrs, $columns, $actions, "{$this->view_name}-{$this->view_name}", $this->lang['strnodata'], $attPre);

        echo '<br />' . \PHP_EOL;

        $navlinks = [
            'browse' => [
                'attr' => [
                    'href' => [
                        'url' => 'display',
                        'urlvars' => [
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                            'subject' => $this->subject,
                            'return' => $this->subject,
                        ],
                    ],
                ],
                'content' => $this->lang['strbrowse'],
            ],
            'select' => [
                'attr' => [
                    'href' => [
                        'url' => \str_replace('properties', 's', $this->view_name),
                        'urlvars' => [
                            'action' => 'confselectrows',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                        ],
                    ],
                ],
                'content' => $this->lang['strselect'],
            ],
            'drop' => [
                'attr' => [
                    'href' => [
                        'url' => \str_replace('properties', 's', $this->view_name),
                        'urlvars' => [
                            'action' => 'confirm_drop',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                        ],
                    ],
                ],
                'content' => $this->lang['strdrop'],
            ],
            'alter' => [
                'attr' => [
                    'href' => [
                        'url' => $this->view_name,
                        'urlvars' => [
                            'action' => 'confirm_alter',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                        ],
                    ],
                ],
                'content' => $this->lang['stralter'],
            ],
        ];
        //$this->prtrace($this->view_name);

        if ('materializedviewproperties' === $this->view_name) {
            $navlinks['refresh'] = [
                'attr' => [
                    'href' => [
                        'url' => $this->view_name,
                        'urlvars' => [
                            'action' => 'refresh',
                            'server' => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema' => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                        ],
                    ],
                ],
                'content' => $this->lang['strrefresh'],
            ];
        }

        $this->printNavLinks($navlinks, "{$this->view_name}-{$this->view_name}", \get_defined_vars());
    }

    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $reqvars = $this->misc->getRequestVars('column');
        $columns = $data->getTableAttributes($_REQUEST[$this->subject]);

        $attrs = [
            'text' => Decorator::field('attname'),
            'action' => Decorator::actionurl(
                'colproperties',
                $reqvars,
                [
                    $this->subject => $_REQUEST[$this->subject],
                    'column' => Decorator::field('attname'),
                ]
            ),
            'icon' => 'Column',
            'iconAction' => Decorator::url(
                'display',
                $reqvars,
                [
                    $this->subject => $_REQUEST[$this->subject],
                    'column' => Decorator::field('attname'),
                    'query' => Decorator::replace(
                        'SELECT "%column%", count(*) AS "count" FROM %view% GROUP BY "%column%" ORDER BY "%column%"',
                        [
                            '%column%' => Decorator::field('attname'),
                            '%view%' => $_REQUEST[$this->subject],
                        ]
                    ),
                ]
            ),
            'toolTip' => Decorator::field('comment'),
        ];

        return $this->printTree($columns, $attrs, 'viewcolumns');
    }

    /**
     * Allow the dumping of the data "in" a view
     * NOTE:: PostgreSQL doesn't currently support dumping the data in a view
     *        so I have disabled the data related parts for now. In the future
     *        we should allow it conditionally if it becomes supported.  This is
     *        a SMOP since it is based on pg_dump version not backend version.
     *
     * @param mixed $msg
     */
    public function doExport($msg = ''): void
    {
        $this->printTrail($this->subject);
        $this->printTabs($this->subject, 'export');
        $this->printMsg($msg);

        $subject = $this->subject;
        $object = $_REQUEST[$this->subject];

        echo $this->formHeader();
        // Data only
        // echo $this->dataOnly(false);

        // Structure only
        echo $this->structureOnly(true);

        // Structure and data
        // echo $this->structureAndData();

        echo $this->displayOrDownload();

        echo $this->formFooter($subject, $object);
    }

    /**
     * Show definition for a view or matview.
     *
     * @param mixed $msg
     */
    public function doDefinition($msg = ''): void
    {
        $data = $this->misc->getDatabaseAccessor();

        // Get view
        $vdata = $data->getView($_REQUEST[$this->subject]);

        $this->printTrail($this->subject);
        $this->printTabs($this->subject, 'definition');
        $this->printMsg($msg);

        if (0 < $vdata->recordCount()) {
            // Show comment if any
            if (null !== $vdata->fields['relcomment']) {
                echo '<p class="comment">', $this->misc->printVal($vdata->fields['relcomment']), '</p>' . \PHP_EOL;
            }

            echo '<table style="width: 100%">' . \PHP_EOL;
            echo "<tr><th class=\"data\">{$this->lang['strdefinition']}</th></tr>" . \PHP_EOL;
            echo '<tr><td class="data1">', $this->misc->printVal($vdata->fields['vwdefinition']), '</td></tr>' . \PHP_EOL;
            echo '</table>' . \PHP_EOL;
        } else {
            echo "<p>{$this->lang['strnodata']}</p>" . \PHP_EOL;
        }

        $this->printNavLinks(['alter' => [
            'attr' => [
                'href' => [
                    'url' => $this->view_name,
                    'urlvars' => [
                        'action' => 'edit',
                        'server' => $_REQUEST['server'],
                        'database' => $_REQUEST['database'],
                        'schema' => $_REQUEST['schema'],
                        $this->subject => $_REQUEST[$this->subject],
                    ],
                ],
            ],
            'content' => $this->lang['stralter'],
        ]], "{$this->view_name}-definition", \get_defined_vars());
    }
}
