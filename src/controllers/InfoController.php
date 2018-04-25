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
class InfoController extends BaseController
{
    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->printHeader($this->lang['strtables'].' - '.$_REQUEST['table'].' - '.$this->lang['strinfo']);
        $this->printBody();

        switch ($this->action) {
            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

    /**
     * List all the information on the table.
     *
     * @param string $msg
     *
     * @return string|void
     */
    public function doDefault($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('table');
        $this->printTabs('table', 'info');
        $this->printMsg($msg);

        // common params for printVal
        $shownull = ['null' => true];

        // Fetch info
        $referrers      = $data->getReferrers($_REQUEST['table']);
        $parents        = $data->getTableParents($_REQUEST['table']);
        $children       = $data->getTableChildren($_REQUEST['table']);
        $tablestatstups = $data->getStatsTableTuples($_REQUEST['table']);
        $tablestatsio   = $data->getStatsTableIO($_REQUEST['table']);
        $indexstatstups = $data->getStatsIndexTuples($_REQUEST['table']);
        $indexstatsio   = $data->getStatsIndexIO($_REQUEST['table']);

        // Check that there is some info
        if (($referrers === -99 || ($referrers !== -99 && 0 == $referrers->recordCount()))
            && 0 == $parents->recordCount() && 0 == $children->recordCount()
            && (0 == $tablestatstups->recordCount() && 0 == $tablestatsio->recordCount()
                && 0 == $indexstatstups->recordCount() && 0 == $indexstatsio->recordCount())) {
            $this->printMsg($this->lang['strnoinfo']);
        } else {
            // Referring foreign tables
            if ($referrers !== -99 && $referrers->recordCount() > 0) {
                echo "<h3>{$this->lang['strreferringtables']}</h3>\n";

                $columns = [
                    'schema'     => [
                        'title' => $this->lang['strschema'],
                        'field' => Decorator::field('nspname'),
                    ],
                    'table'      => [
                        'title' => $this->lang['strtable'],
                        'field' => Decorator::field('relname'),
                    ],
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
                    'properties' => [
                        'content' => $this->lang['strproperties'],
                        'attr'    => [
                            'href' => [
                                'url'     => 'constraints',
                                'urlvars' => [
                                    'schema' => Decorator::field('nspname'),
                                    'table'  => Decorator::field('relname'),
                                ],
                            ],
                        ],
                    ],
                ];

                echo $this->printTable($referrers, $columns, $actions, 'info-referrers', $this->lang['strnodata']);
            }

            // Parent tables
            if ($parents->recordCount() > 0) {
                echo "<h3>{$this->lang['strparenttables']}</h3>\n";

                $columns = [
                    'schema'  => [
                        'title' => $this->lang['strschema'],
                        'field' => Decorator::field('nspname'),
                    ],
                    'table'   => [
                        'title' => $this->lang['strtable'],
                        'field' => Decorator::field('relname'),
                    ],
                    'actions' => [
                        'title' => $this->lang['stractions'],
                    ],
                ];

                $actions = [
                    'properties' => [
                        'content' => $this->lang['strproperties'],
                        'attr'    => [
                            'href' => [
                                'url'     => 'tblproperties',
                                'urlvars' => [
                                    'schema' => Decorator::field('nspname'),
                                    'table'  => Decorator::field('relname'),
                                ],
                            ],
                        ],
                    ],
                ];

                echo $this->printTable($parents, $columns, $actions, 'info-parents', $this->lang['strnodata']);
            }

            // Child tables
            if ($children->recordCount() > 0) {
                echo "<h3>{$this->lang['strchildtables']}</h3>\n";

                $columns = [
                    'schema'  => [
                        'title' => $this->lang['strschema'],
                        'field' => Decorator::field('nspname'),
                    ],
                    'table'   => [
                        'title' => $this->lang['strtable'],
                        'field' => Decorator::field('relname'),
                    ],
                    'actions' => [
                        'title' => $this->lang['stractions'],
                    ],
                ];

                $actions = [
                    'properties' => [
                        'content' => $this->lang['strproperties'],
                        'attr'    => [
                            'href' => [
                                'url'     => 'tblproperties',
                                'urlvars' => [
                                    'schema' => Decorator::field('nspname'),
                                    'table'  => Decorator::field('relname'),
                                ],
                            ],
                        ],
                    ],
                ];

                echo $this->printTable($children, $columns, $actions, 'info-children', $this->lang['strnodata']);
            }

            // Row performance
            if ($tablestatstups->recordCount() > 0) {
                echo "<h3>{$this->lang['strrowperf']}</h3>\n";

                echo "<table>\n";
                echo "\t<tr>\n";
                echo "\t\t<th class=\"data\" colspan=\"2\">{$this->lang['strsequential']}</th>\n";
                echo "\t\t<th class=\"data\" colspan=\"2\">{$this->lang['strindex']}</th>\n";
                echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strrows2']}</th>\n";
                echo "\t</tr>\n";
                echo "\t<tr>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strscan']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strread']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strscan']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strfetch']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strinsert']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strupdate']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strdelete']}</th>\n";
                echo "\t</tr>\n";
                $i = 0;

                while (!$tablestatstups->EOF) {
                    $id = (0 == ($i % 2) ? '1' : '2');
                    echo "\t<tr class=\"data{$id}\">\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['seq_scan'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['seq_tup_read'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['idx_scan'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['idx_tup_fetch'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_ins'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_upd'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_del'], 'int4', $shownull), "</td>\n";
                    echo "\t</tr>\n";
                    $tablestatstups->movenext();
                    ++$i;
                }

                echo "</table>\n";
            }

            // I/O performance
            if ($tablestatsio->recordCount() > 0) {
                echo "<h3>{$this->lang['strioperf']}</h3>\n";

                echo "<table>\n";
                echo "\t<tr>\n";
                echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strheap']}</th>\n";
                echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strindex']}</th>\n";
                echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strtoast']}</th>\n";
                echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strtoastindex']}</th>\n";
                echo "\t</tr>\n";
                echo "\t<tr>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>\n";
                echo "\t</tr>\n";
                $i = 0;

                while (!$tablestatsio->EOF) {
                    $id = (0 == ($i % 2) ? '1' : '2');
                    echo "\t<tr class=\"data{$id}\">\n";

                    $total = $tablestatsio->fields['heap_blks_hit'] + $tablestatsio->fields['heap_blks_read'];
                    if ($total > 0) {
                        $percentage = round(($tablestatsio->fields['heap_blks_hit'] / $total) * 100);
                    } else {
                        $percentage = 0;
                    }

                    echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['heap_blks_read'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['heap_blks_hit'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>\n";

                    $total = $tablestatsio->fields['idx_blks_hit'] + $tablestatsio->fields['idx_blks_read'];
                    if ($total > 0) {
                        $percentage = round(($tablestatsio->fields['idx_blks_hit'] / $total) * 100);
                    } else {
                        $percentage = 0;
                    }

                    echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['idx_blks_read'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['idx_blks_hit'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>\n";

                    $total = $tablestatsio->fields['toast_blks_hit'] + $tablestatsio->fields['toast_blks_read'];
                    if ($total > 0) {
                        $percentage = round(($tablestatsio->fields['toast_blks_hit'] / $total) * 100);
                    } else {
                        $percentage = 0;
                    }

                    echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['toast_blks_read'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['toast_blks_hit'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>\n";

                    $total = $tablestatsio->fields['tidx_blks_hit'] + $tablestatsio->fields['tidx_blks_read'];
                    if ($total > 0) {
                        $percentage = round(($tablestatsio->fields['tidx_blks_hit'] / $total) * 100);
                    } else {
                        $percentage = 0;
                    }

                    echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['tidx_blks_read'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['tidx_blks_hit'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>\n";
                    echo "\t</tr>\n";
                    $tablestatsio->movenext();
                    ++$i;
                }

                echo "</table>\n";
            }

            // Index row performance
            if ($indexstatstups->recordCount() > 0) {
                echo "<h3>{$this->lang['stridxrowperf']}</h3>\n";

                echo "<table>\n";
                echo "\t<tr>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strindex']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strscan']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strread']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strfetch']}</th>\n";
                echo "\t</tr>\n";
                $i = 0;

                while (!$indexstatstups->EOF) {
                    $id = (0 == ($i % 2) ? '1' : '2');
                    echo "\t<tr class=\"data{$id}\">\n";
                    echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['indexrelname']), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_scan'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_tup_read'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_tup_fetch'], 'int4', $shownull), "</td>\n";
                    echo "\t</tr>\n";
                    $indexstatstups->movenext();
                    ++$i;
                }

                echo "</table>\n";
            }

            // Index I/0 performance
            if ($indexstatsio->recordCount() > 0) {
                echo "<h3>{$this->lang['stridxioperf']}</h3>\n";

                echo "<table>\n";
                echo "\t<tr>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strindex']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>\n";
                echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>\n";
                echo "\t</tr>\n";
                $i = 0;

                while (!$indexstatsio->EOF) {
                    $id = (0 == ($i % 2) ? '1' : '2');
                    echo "\t<tr class=\"data{$id}\">\n";
                    $total = $indexstatsio->fields['idx_blks_hit'] + $indexstatsio->fields['idx_blks_read'];
                    if ($total > 0) {
                        $percentage = round(($indexstatsio->fields['idx_blks_hit'] / $total) * 100);
                    } else {
                        $percentage = 0;
                    }

                    echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['indexrelname']), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['idx_blks_read'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['idx_blks_hit'], 'int4', $shownull), "</td>\n";
                    echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>\n";
                    echo "\t</tr>\n";
                    $indexstatsio->movenext();
                    ++$i;
                }

                echo "</table>\n";
            }
        }
    }
}
