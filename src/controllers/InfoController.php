<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class InfoController extends BaseController
{
    public $controller_title = 'strtables';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render(): void
    {
        $this->printHeader($this->headerTitle('', '', $_REQUEST['table'] . ' - ' . $this->lang['strinfo']));
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
        $this->shownull = ['null' => true];

        // Fetch info
        $referrers      = $data->getReferrers($_REQUEST['table']);
        $parents        = $data->getTableParents($_REQUEST['table']);
        $children       = $data->getTableChildren($_REQUEST['table']);
        $tablestatstups = $data->getStatsTableTuples($_REQUEST['table']);
        $tablestatsio   = $data->getStatsTableIO($_REQUEST['table']);
        $indexstatstups = $data->getStatsIndexTuples($_REQUEST['table']);
        $indexstatsio   = $data->getStatsIndexIO($_REQUEST['table']);

        // Check that there is some info
        if ((-99 === $referrers || (-99 !== $referrers && 0 === $referrers->recordCount()))
            && 0 === $parents->recordCount() && 0 === $children->recordCount()
            && (0 === $tablestatstups->recordCount() && 0 === $tablestatsio->recordCount()
                && 0 === $indexstatstups->recordCount() && 0 === $indexstatsio->recordCount())) {
            $this->printMsg($this->lang['strnoinfo']);

            return;
        }
        // Referring foreign tables
        if (-99 !== $referrers && 0 < $referrers->recordCount()) {
            $this->_printReferring($referrers);
        }

        // Parent tables
        if (0 < $parents->recordCount()) {
            $this->_printParents($parents);
        }

        // Child tables
        if (0 < $children->recordCount()) {
            $this->_printChildren($children);
        }

        // Row performance
        if (0 < $tablestatstups->recordCount()) {
            $this->_printTablestatstups($tablestatstups);
        }

        // I/O performance
        if (0 < $tablestatsio->recordCount()) {
            $this->_printTablestatsio($tablestatsio);
        }

        // Index row performance
        if (0 < $indexstatstups->recordCount()) {
            $this->_printIndexstatstups($indexstatstups);
        }

        // Index I/0 performance
        if (0 < $indexstatsio->recordCount()) {
            $this->_printIndexstatsio($indexstatsio);
        }
    }

    private function _printChildren($children): void
    {
        echo "<h3>{$this->lang['strchildtables']}</h3>" . \PHP_EOL;

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

    private function _printTablestatstups($tablestatstups): void
    {
        echo "<h3>{$this->lang['strrowperf']}</h3>" . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo "\t\t<th class=\"data\" colspan=\"2\">{$this->lang['strsequential']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\" colspan=\"2\">{$this->lang['strindex']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strrows2']}</th>" . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strscan']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strread']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strscan']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strfetch']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strinsert']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strupdate']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strdelete']}</th>" . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        $i = 0;

        while (!$tablestatstups->EOF) {
            $id = (0 === ($i % 2) ? '1' : '2');
            echo "\t<tr class=\"data{$id}\">" . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['seq_scan'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['seq_tup_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['idx_scan'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['idx_tup_fetch'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_ins'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_upd'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_del'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            $tablestatstups->movenext();
            ++$i;
        }

        echo '</table>' . \PHP_EOL;
    }

    private function _printTablestatsio($tablestatsio): void
    {
        echo "<h3>{$this->lang['strioperf']}</h3>" . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strheap']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strindex']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strtoast']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\" colspan=\"3\">{$this->lang['strtoastindex']}</th>" . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>" . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        $i = 0;

        while (!$tablestatsio->EOF) {
            $id = (0 === ($i % 2) ? '1' : '2');
            echo "\t<tr class=\"data{$id}\">" . \PHP_EOL;

            $total = $tablestatsio->fields['heap_blks_hit'] + $tablestatsio->fields['heap_blks_read'];

            if (0 < $total) {
                $percentage = \round(($tablestatsio->fields['heap_blks_hit'] / $total) * 100);
            } else {
                $percentage = 0;
            }

            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['heap_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['heap_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>" . \PHP_EOL;

            $total = $tablestatsio->fields['idx_blks_hit'] + $tablestatsio->fields['idx_blks_read'];

            if (0 < $total) {
                $percentage = \round(($tablestatsio->fields['idx_blks_hit'] / $total) * 100);
            } else {
                $percentage = 0;
            }

            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['idx_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['idx_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>" . \PHP_EOL;

            $total = $tablestatsio->fields['toast_blks_hit'] + $tablestatsio->fields['toast_blks_read'];

            if (0 < $total) {
                $percentage = \round(($tablestatsio->fields['toast_blks_hit'] / $total) * 100);
            } else {
                $percentage = 0;
            }

            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['toast_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['toast_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>" . \PHP_EOL;

            $total = $tablestatsio->fields['tidx_blks_hit'] + $tablestatsio->fields['tidx_blks_read'];

            if (0 < $total) {
                $percentage = \round(($tablestatsio->fields['tidx_blks_hit'] / $total) * 100);
            } else {
                $percentage = 0;
            }

            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['tidx_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['tidx_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            $tablestatsio->movenext();
            ++$i;
        }

        echo '</table>' . \PHP_EOL;
    }

    private function _printIndexstatstups($indexstatstups): void
    {
        echo "<h3>{$this->lang['stridxrowperf']}</h3>" . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strindex']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strscan']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strread']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strfetch']}</th>" . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        $i = 0;

        while (!$indexstatstups->EOF) {
            $id = (0 === ($i % 2) ? '1' : '2');
            echo "\t<tr class=\"data{$id}\">" . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['indexrelname']), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_scan'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_tup_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_tup_fetch'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            $indexstatstups->movenext();
            ++$i;
        }

        echo '</table>' . \PHP_EOL;
    }

    private function _printIndexstatsio($indexstatsio): void
    {
        echo "<h3>{$this->lang['stridxioperf']}</h3>" . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strindex']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strdisk']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strcache']}</th>" . \PHP_EOL;
        echo "\t\t<th class=\"data\">{$this->lang['strpercent']}</th>" . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        $i = 0;

        while (!$indexstatsio->EOF) {
            $id = (0 === ($i % 2) ? '1' : '2');
            echo "\t<tr class=\"data{$id}\">" . \PHP_EOL;
            $total = $indexstatsio->fields['idx_blks_hit'] + $indexstatsio->fields['idx_blks_read'];

            if (0 < $total) {
                $percentage = \round(($indexstatsio->fields['idx_blks_hit'] / $total) * 100);
            } else {
                $percentage = 0;
            }

            echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['indexrelname']), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['idx_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['idx_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>({$percentage}{$this->lang['strpercent']})</td>" . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            $indexstatsio->movenext();
            ++$i;
        }

        echo '</table>' . \PHP_EOL;
    }

    private function _printParents($parents): void
    {
        echo "<h3>{$this->lang['strparenttables']}</h3>" . \PHP_EOL;

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

    private function _printReferring($referrers): void
    {
        echo "<h3>{$this->lang['strreferringtables']}</h3>" . \PHP_EOL;

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
}
