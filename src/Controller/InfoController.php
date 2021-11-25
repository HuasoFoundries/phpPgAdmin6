<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class InfoController extends BaseController
{
    /**
     * @var array<string, bool>|mixed
     */
    public $shownull;

    public $controller_title = 'strtables';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->printHeader($this->headerTitle('', '', $_REQUEST['table'] . ' - ' . $this->lang['strinfo']));
        $this->printBody();
        $this->doDefault();

        $this->printFooter();
    }

    /**
     * List all the information on the table.
     *
     * @param string $msg
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
        $referrers = $data->getReferrers($_REQUEST['table']);
        $parents = $data->getTableParents($_REQUEST['table']);
        $children = $data->getTableChildren($_REQUEST['table']);
        $tablestatstups = $data->getStatsTableTuples($_REQUEST['table']);
        $tablestatsio = $data->getStatsTableIO($_REQUEST['table']);
        $indexstatstups = $data->getStatsIndexTuples($_REQUEST['table']);
        $indexstatsio = $data->getStatsIndexIO($_REQUEST['table']);

        // Check that there is some info
        if ((-99 === $referrers || (-99 !== $referrers && 0 === $referrers->RecordCount()))
            && 0 === $parents->RecordCount() && 0 === $children->RecordCount()
            && (0 === $tablestatstups->RecordCount() && 0 === $tablestatsio->RecordCount()
                && 0 === $indexstatstups->RecordCount() && 0 === $indexstatsio->RecordCount())) {
            $this->printMsg($this->lang['strnoinfo']);

            return;
        }
        // Referring foreign tables
        if (-99 !== $referrers && 0 < $referrers->RecordCount()) {
            $this->_printReferring($referrers);
        }

        // Parent tables
        if (0 < $parents->RecordCount()) {
            $this->_printParents($parents);
        }

        // Child tables
        if (0 < $children->RecordCount()) {
            $this->_printChildren($children);
        }

        // Row performance
        if (0 < $tablestatstups->RecordCount()) {
            $this->_printTablestatstups($tablestatstups);
        }

        // I/O performance
        if (0 < $tablestatsio->RecordCount()) {
            $this->_printTablestatsio($tablestatsio);
        }

        // Index row performance
        if (0 < $indexstatstups->RecordCount()) {
            $this->_printIndexstatstups($indexstatstups);
        }

        // Index I/0 performance
        if (0 < $indexstatsio->RecordCount()) {
            $this->_printIndexstatsio($indexstatsio);
        }

        return '';
    }

    /**
     * @param int|\PHPPgAdmin\Core\ADORecordSet|string $children
     */
    private function _printChildren($children): void
    {
        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['strchildtables']
        ) . \PHP_EOL;

        $columns = [
            'schema' => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('nspname'),
            ],
            'table' => [
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
                'attr' => [
                    'href' => [
                        'url' => 'tblproperties',
                        'urlvars' => [
                            'schema' => Decorator::field('nspname'),
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($children)) {
            echo $this->printTable($children, $columns, $actions, 'info-children', $this->lang['strnodata']);
        }
    }

    /**
     * @param int|\PHPPgAdmin\Core\ADORecordSet|string $tablestatstups
     */
    private function _printTablestatstups($tablestatstups): void
    {
        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['strrowperf']
        ) . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo \sprintf(
            '		<th class="data" colspan="2">%s</th>',
            $this->lang['strsequential']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data" colspan="2">%s</th>',
            $this->lang['strindex']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data" colspan="3">%s</th>',
            $this->lang['strrows2']
        ) . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strscan']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strread']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strscan']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strfetch']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strinsert']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strupdate']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strdelete']
        ) . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        $i = 0;

        while (!$tablestatstups->EOF) {
            $id = (0 === ($i % 2) ? '1' : '2');
            echo \sprintf(
                '	<tr class="data%s">',
                $id
            ) . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['seq_scan'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['seq_tup_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['idx_scan'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['idx_tup_fetch'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_ins'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_upd'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatstups->fields['n_tup_del'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            $tablestatstups->MoveNext();
            ++$i;
        }

        echo '</table>' . \PHP_EOL;
    }

    /**
     * @param int|\PHPPgAdmin\Core\ADORecordSet|string $tablestatsio
     */
    private function _printTablestatsio($tablestatsio): void
    {
        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['strioperf']
        ) . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo \sprintf(
            '		<th class="data" colspan="3">%s</th>',
            $this->lang['strheap']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data" colspan="3">%s</th>',
            $this->lang['strindex']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data" colspan="3">%s</th>',
            $this->lang['strtoast']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data" colspan="3">%s</th>',
            $this->lang['strtoastindex']
        ) . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strdisk']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strcache']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strpercent']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strdisk']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strcache']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strpercent']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strdisk']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strcache']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strpercent']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strdisk']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strcache']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strpercent']
        ) . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        $i = 0;

        while (!$tablestatsio->EOF) {
            $id = (0 === ($i % 2) ? '1' : '2');
            echo \sprintf(
                '	<tr class="data%s">',
                $id
            ) . \PHP_EOL;

            $total = $tablestatsio->fields['heap_blks_hit'] + $tablestatsio->fields['heap_blks_read'];

            $percentage = 0 < $total ? \round(($tablestatsio->fields['heap_blks_hit'] / $total) * 100) : 0;

            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['heap_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['heap_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo \sprintf(
                '		<td>(%s%s)</td>',
                $percentage,
                $this->lang['strpercent']
            ) . \PHP_EOL;

            $total = $tablestatsio->fields['idx_blks_hit'] + $tablestatsio->fields['idx_blks_read'];

            $percentage = 0 < $total ? \round(($tablestatsio->fields['idx_blks_hit'] / $total) * 100) : 0;

            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['idx_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['idx_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo \sprintf(
                '		<td>(%s%s)</td>',
                $percentage,
                $this->lang['strpercent']
            ) . \PHP_EOL;

            $total = $tablestatsio->fields['toast_blks_hit'] + $tablestatsio->fields['toast_blks_read'];

            $percentage = 0 < $total ? \round(($tablestatsio->fields['toast_blks_hit'] / $total) * 100) : 0;

            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['toast_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['toast_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo \sprintf(
                '		<td>(%s%s)</td>',
                $percentage,
                $this->lang['strpercent']
            ) . \PHP_EOL;

            $total = $tablestatsio->fields['tidx_blks_hit'] + $tablestatsio->fields['tidx_blks_read'];

            $percentage = 0 < $total ? \round(($tablestatsio->fields['tidx_blks_hit'] / $total) * 100) : 0;

            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['tidx_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($tablestatsio->fields['tidx_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo \sprintf(
                '		<td>(%s%s)</td>',
                $percentage,
                $this->lang['strpercent']
            ) . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            $tablestatsio->MoveNext();
            ++$i;
        }

        echo '</table>' . \PHP_EOL;
    }

    /**
     * @param int|\PHPPgAdmin\Core\ADORecordSet|string $indexstatstups
     */
    private function _printIndexstatstups($indexstatstups): void
    {
        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['stridxrowperf']
        ) . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strindex']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strscan']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strread']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strfetch']
        ) . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        $i = 0;

        while (!$indexstatstups->EOF) {
            $id = (0 === ($i % 2) ? '1' : '2');
            echo \sprintf(
                '	<tr class="data%s">',
                $id
            ) . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['indexrelname']), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_scan'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_tup_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatstups->fields['idx_tup_fetch'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            $indexstatstups->MoveNext();
            ++$i;
        }

        echo '</table>' . \PHP_EOL;
    }

    /**
     * @param int|\PHPPgAdmin\Core\ADORecordSet|string $indexstatsio
     */
    private function _printIndexstatsio($indexstatsio): void
    {
        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['stridxioperf']
        ) . \PHP_EOL;

        echo '<table>' . \PHP_EOL;
        echo "\t<tr>" . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strindex']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strdisk']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strcache']
        ) . \PHP_EOL;
        echo \sprintf(
            '		<th class="data">%s</th>',
            $this->lang['strpercent']
        ) . \PHP_EOL;
        echo "\t</tr>" . \PHP_EOL;
        $i = 0;

        while (!$indexstatsio->EOF) {
            $id = (0 === ($i % 2) ? '1' : '2');
            echo \sprintf(
                '	<tr class="data%s">',
                $id
            ) . \PHP_EOL;
            $total = $indexstatsio->fields['idx_blks_hit'] + $indexstatsio->fields['idx_blks_read'];

            $percentage = 0 < $total ? \round(($indexstatsio->fields['idx_blks_hit'] / $total) * 100) : 0;

            echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['indexrelname']), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['idx_blks_read'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo "\t\t<td>", $this->misc->printVal($indexstatsio->fields['idx_blks_hit'], 'int4', $this->shownull), '</td>' . \PHP_EOL;
            echo \sprintf(
                '		<td>(%s%s)</td>',
                $percentage,
                $this->lang['strpercent']
            ) . \PHP_EOL;
            echo "\t</tr>" . \PHP_EOL;
            $indexstatsio->MoveNext();
            ++$i;
        }

        echo '</table>' . \PHP_EOL;
    }

    /**
     * @param int|\PHPPgAdmin\Core\ADORecordSet|string $parents
     */
    private function _printParents($parents): void
    {
        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['strparenttables']
        ) . \PHP_EOL;

        $columns = [
            'schema' => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('nspname'),
            ],
            'table' => [
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
                'attr' => [
                    'href' => [
                        'url' => 'tblproperties',
                        'urlvars' => [
                            'schema' => Decorator::field('nspname'),
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($parents)) {
            echo $this->printTable($parents, $columns, $actions, 'info-parents', $this->lang['strnodata']);
        }
    }

    /**
     * @param int|\PHPPgAdmin\Core\ADORecordSet|string $referrers
     */
    private function _printReferring($referrers): void
    {
        echo \sprintf(
            '<h3>%s</h3>',
            $this->lang['strreferringtables']
        ) . \PHP_EOL;

        $columns = [
            'schema' => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('nspname'),
            ],
            'table' => [
                'title' => $this->lang['strtable'],
                'field' => Decorator::field('relname'),
            ],
            'name' => [
                'title' => $this->lang['strname'],
                'field' => Decorator::field('conname'),
            ],
            'definition' => [
                'title' => $this->lang['strdefinition'],
                'field' => Decorator::field('consrc'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
        ];

        $actions = [
            'properties' => [
                'content' => $this->lang['strproperties'],
                'attr' => [
                    'href' => [
                        'url' => 'constraints',
                        'urlvars' => [
                            'schema' => Decorator::field('nspname'),
                            'table' => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
        ];

        if (self::isRecordset($referrers)) {
            echo $this->printTable($referrers, $columns, $actions, 'info-referrers', $this->lang['strnodata']);
        }
    }
}
