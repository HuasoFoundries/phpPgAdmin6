<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
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
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $attPre = function (&$rowdata) use ($data) {
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
            echo '<p class="comment">', $this->misc->printVal($vdata->fields['relcomment']), "</p>\n";
        }

        $columns = [
            'column'  => [
                'title' => $this->lang['strcolumn'],
                'field' => Decorator::field('attname'),
                'url'   => "colproperties?subject=column&amp;{$this->misc->href}&amp;view=".urlencode($_REQUEST[$this->subject]).'&amp;',
                'vars'  => ['column' => 'attname'],
            ],
            'type'    => [
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
                'attr'    => [
                    'href' => [
                        'url'     => $this->view_name,
                        'urlvars' => [
                            'action'       => 'properties',
                            $this->subject => $_REQUEST[$this->subject],
                            'column'       => Decorator::field('attname'),
                        ],
                    ],
                ],
            ],
        ];

        echo $this->printTable($attrs, $columns, $actions, "{$this->view_name}-{$this->view_name}", null, $attPre);

        echo "<br />\n";

        $navlinks = [
            'browse' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'display',
                        'urlvars' => [
                            'server'       => $_REQUEST['server'],
                            'database'     => $_REQUEST['database'],
                            'schema'       => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                            'subject'      => $this->subject,
                            'return'       => $this->subject,
                        ],
                    ],
                ],
                'content' => $this->lang['strbrowse'],
            ],
            'select' => [
                'attr'    => [
                    'href' => [
                        'url'     => str_replace('properties', 's', $this->view_name),
                        'urlvars' => [
                            'action'       => 'confselectrows',
                            'server'       => $_REQUEST['server'],
                            'database'     => $_REQUEST['database'],
                            'schema'       => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                        ],
                    ],
                ],
                'content' => $this->lang['strselect'],
            ],
            'drop'   => [
                'attr'    => [
                    'href' => [
                        'url'     => str_replace('properties', 's', $this->view_name),
                        'urlvars' => [
                            'action'       => 'confirm_drop',
                            'server'       => $_REQUEST['server'],
                            'database'     => $_REQUEST['database'],
                            'schema'       => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                        ],
                    ],
                ],
                'content' => $this->lang['strdrop'],
            ],
            'alter'  => [
                'attr'    => [
                    'href' => [
                        'url'     => $this->view_name,
                        'urlvars' => [
                            'action'       => 'confirm_alter',
                            'server'       => $_REQUEST['server'],
                            'database'     => $_REQUEST['database'],
                            'schema'       => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                        ],
                    ],
                ],
                'content' => $this->lang['stralter'],
            ],
        ];
        $this->prtrace($this->view_name);
        if ($this->view_name === 'materializedviewproperties') {
            $navlinks['refresh'] = [
                'attr'    => [
                    'href' => [
                        'url'     => $this->view_name,
                        'urlvars' => [
                            'action'       => 'refresh',
                            'server'       => $_REQUEST['server'],
                            'database'     => $_REQUEST['database'],
                            'schema'       => $_REQUEST['schema'],
                            $this->subject => $_REQUEST[$this->subject],
                        ],
                    ],
                ],
                'content' => $this->lang['strrefresh'],
            ];
        }

        $this->printNavLinks($navlinks, "{$this->view_name}-{$this->view_name}", get_defined_vars());
    }

    public function doTree()
    {
        $data = $this->misc->getDatabaseAccessor();

        $reqvars = $this->misc->getRequestVars('column');
        $columns = $data->getTableAttributes($_REQUEST[$this->subject]);

        $attrs = [
            'text'       => Decorator::field('attname'),
            'action'     => Decorator::actionurl(
                'colproperties',
                $reqvars,
                [
                    $this->subject => $_REQUEST[$this->subject],
                    'column'       => Decorator::field('attname'),
                ]
            ),
            'icon'       => 'Column',
            'iconAction' => Decorator::url(
                'display',
                $reqvars,
                [
                    $this->subject => $_REQUEST[$this->subject],
                    'column'       => Decorator::field('attname'),
                    'query'        => Decorator::replace(
                        'SELECT "%column%", count(*) AS "count" FROM %view% GROUP BY "%column%" ORDER BY "%column%"',
                        [
                            '%column%' => Decorator::field('attname'),
                            '%view%'   => $_REQUEST[$this->subject],
                        ]
                    ),
                ]
            ),
            'toolTip'    => Decorator::field('comment'),
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
    public function doExport($msg = '')
    {
        $this->printTrail($this->subject);
        $this->printTabs($this->subject, 'export');
        $this->printMsg($msg);

        $subject = $this->subject;
        $object  = $_REQUEST[$this->subject];

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
    public function doDefinition($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        // Get view
        $vdata = $data->getView($_REQUEST[$this->subject]);

        $this->printTrail($this->subject);
        $this->printTabs($this->subject, 'definition');
        $this->printMsg($msg);

        if ($vdata->RecordCount() > 0) {
            // Show comment if any
            if (null !== $vdata->fields['relcomment']) {
                echo '<p class="comment">', $this->misc->printVal($vdata->fields['relcomment']), "</p>\n";
            }

            echo "<table style=\"width: 100%\">\n";
            echo "<tr><th class=\"data\">{$this->lang['strdefinition']}</th></tr>\n";
            echo '<tr><td class="data1">', $this->misc->printVal($vdata->fields['vwdefinition']), "</td></tr>\n";
            echo "</table>\n";
        } else {
            echo "<p>{$this->lang['strnodata']}</p>\n";
        }

        $this->printNavLinks(['alter' => [
            'attr'    => [
                'href' => [
                    'url'     => $this->view_name,
                    'urlvars' => [
                        'action'       => 'edit',
                        'server'       => $_REQUEST['server'],
                        'database'     => $_REQUEST['database'],
                        'schema'       => $_REQUEST['schema'],
                        $this->subject => $_REQUEST[$this->subject],
                    ],
                ],
            ],
            'content' => $this->lang['stralter'],
        ]], "{$this->view_name}-definition", get_defined_vars());
    }

    /**
     * Actually creates the new wizard view in the database.
     *
     * @param bool $is_materialized true if it's a materialized view, false by default
     *
     * @return mixed either a sucess message, a redirection, an error message and who knows
     */
    public function doSaveCreateWiz($is_materialized = false)
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check that they've given a name and fields they want to select

        if (!strlen($_POST['formView'])) {
            return $this->doSetParamsCreate($this->lang['strviewneedsname']);
        }
        if (!isset($_POST['formFields']) || !count($_POST['formFields'])) {
            return $this->doSetParamsCreate($this->lang['strviewneedsfields']);
        }
        $selFields = '';

        if (!empty($_POST['dblFldMeth'])) {
            $tmpHsh = [];
        }

        foreach ($_POST['formFields'] as $curField) {
            $arrTmp = unserialize($curField);
            $data->fieldArrayClean($arrTmp);
            $field_arr = [$arrTmp['schemaname'], $arrTmp['tablename'], $arrTmp['fieldname']];

            $field_element = '"'.implode('"."', $field_arr).'"';
            if (empty($_POST['dblFldMeth'])) {
                // no doublon control
                $selFields .= $field_element.', ';
            // doublon control
            } elseif (empty($tmpHsh[$arrTmp['fieldname']])) {
                // field does not exist
                $selFields .= $field_element.', ';
                $tmpHsh[$arrTmp['fieldname']] = 1;
            } elseif ('rename' == $_POST['dblFldMeth']) {
                // field exist and must be renamed
                ++$tmpHsh[$arrTmp['fieldname']];
                $selFields .= $field_element.'  AS  "'.implode('_', $field_arr).'_'.$tmpHsh[$arrTmp['fieldname']].'", ';
            } //  field already exist, just ignore this one
        }

        $selFields = substr($selFields, 0, -2);
        unset($arrTmp, $tmpHsh);
        $linkFields = '';
        $count      = 0;

        // If we have links, out put the JOIN ... ON statements
        if (is_array($_POST['formLink'])) {
            // Filter out invalid/blank entries for our links
            $arrLinks = [];
            foreach ($_POST['formLink'] as $curLink) {
                if (strlen($curLink['leftlink']) && strlen($curLink['rightlink']) && strlen($curLink['operator'])) {
                    $arrLinks[] = $curLink;
                }
            }
            // We must perform some magic to make sure that we have a valid join order
            $count       = sizeof($arrLinks);
            $arrJoined   = [];
            $arrUsedTbls = [];
        }
        // If we have at least one join condition, output it

        $j = 0;
        $this->prtrace('arrLinks ', $arrLinks);
        while ($j < $count) {
            foreach ($arrLinks as $curLink) {
                $arrLeftLink  = unserialize($curLink['leftlink']);
                $arrRightLink = unserialize($curLink['rightlink']);
                $data->fieldArrayClean($arrLeftLink);
                $data->fieldArrayClean($arrRightLink);

                $tbl1 = "\"{$arrLeftLink['schemaname']}\".\"{$arrLeftLink['tablename']}\"";
                $tbl2 = "\"{$arrRightLink['schemaname']}\".\"{$arrRightLink['tablename']}\"";

                if (!((!in_array($curLink, $arrJoined, true) && in_array($tbl1, $arrUsedTbls, true)) || !count($arrJoined))) {
                    continue;
                }
                // Make sure for multi-column foreign keys that we use a table alias tables joined to more than once
                // This can (and should be) more optimized for multi-column foreign keys
                $adj_tbl2 = in_array($tbl2, $arrUsedTbls, true) ? "${tbl2} AS alias_ppa_".time() : $tbl2;

                $clause1 = "{$curLink['operator']} ${adj_tbl2} ON ({$tbl1}.\"{$arrLeftLink['fieldname']}\" = {$tbl2}.\"{$arrRightLink['fieldname']}\") ";
                $clause2 = "${tbl1} {$curLink['operator']} ${adj_tbl2} ON ({$tbl1}.\"{$arrLeftLink['fieldname']}\" = {$tbl2}.\"{$arrRightLink['fieldname']}\") ";

                $linkFields .= strlen($linkFields) ? $clause1 : $clause2;

                $arrJoined[] = $curLink;
                if (!in_array($tbl1, $arrUsedTbls, true)) {
                    $arrUsedTbls[] = $tbl1;
                }

                if (!in_array($tbl2, $arrUsedTbls, true)) {
                    $arrUsedTbls[] = $tbl2;
                }
            }
            ++$j;
        }

        //if linkFields has no length then either _POST['formLink'] was not set, or there were no join conditions
        //just select from all seleted tables - a cartesian join do a
        if (!strlen($linkFields)) {
            foreach ($_POST['formTables'] as $curTable) {
                $arrTmp = unserialize($curTable);
                $data->fieldArrayClean($arrTmp);
                $linkFields .= (strlen($linkFields) ? ', ' : ' ')."\"{$arrTmp['schemaname']}\".\"{$arrTmp['tablename']}\"";
            }
        }

        $addConditions = '';
        if (is_array($_POST['formCondition'])) {
            foreach ($_POST['formCondition'] as $curCondition) {
                if (strlen($curCondition['field']) && strlen($curCondition['txt'])) {
                    $arrTmp = unserialize($curCondition['field']);
                    $data->fieldArrayClean($arrTmp);
                    $condition = " \"{$arrTmp['schemaname']}\".\"{$arrTmp['tablename']}\".\"{$arrTmp['fieldname']}\" {$curCondition['operator']} '{$curCondition['txt']}' ";
                    $addConditions .= (strlen($addConditions) ? ' AND ' : ' ').$condition;
                }
            }
        }

        $viewQuery = "SELECT ${selFields} FROM ${linkFields} ";

        //add where from additional conditions
        if (strlen($addConditions)) {
            $viewQuery .= ' WHERE '.$addConditions;
        }

        try {
            $status = $data->createView($_POST['formView'], $viewQuery, false, $_POST['formComment'], $is_materialized);
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);

                return $this->doDefault($this->lang['strviewcreated']);
            }

            return $this->doSetParamsCreate($this->lang['strviewcreatedbad']);
        } catch (\PHPPgAdmin\ADOdbException $e) {
            return $this->halt($e->getMessage());
        }
    }
}
