<?php

/**
 * PHPPgAdmin 6.1.0
 */

namespace PHPPgAdmin\Traits;

/**
 * Common trait for exporting tables, views or materialized views.
 */
trait InsertEditRowTrait
{
    public $href = '';

    public $conf;

    public $misc;

    /**
     * Returns an array representing FKs definition for a table, sorted by fields
     * or by constraint.
     *
     * @param string $table The table to retrieve FK contraints from
     *
     * @return array{byconstr:array, byfield:array, code:string}|bool the array of FK definition:
     *
     * @example
     * ```
     * $fk = [
     *     'byconstr' => [
     *         'constrain id' => [
     *             'confrelid' => 'foreign relation oid',
     *             'f_schema'  => 'foreign schema name',
     *             'f_table'   => 'foreign table name',
     *             'pattnums'  => 'array of parent\'s fields nums',
     *             'pattnames' => 'array of parent\'s fields names',
     *             'fattnames' => 'array of foreign attributes names',
     *         ],
     *     ],
     *     'byfield'  => [
     *         'attribute num' => 'array (constraint id, ...)',
     *     ],
     *     'code'     => 'HTML/js code to include in the page for auto-completion',
     * ];
     * ```
     */
    public function getAutocompleteFKProperties($table)
    {
        $data = $this->misc->getDatabaseAccessor();

        $fksprops = [
            'byconstr' => [],
            'byfield' => [],
            'code' => '',
        ];

        $constrs = $data->getConstraintsWithFields($table);

        if (!$constrs->EOF) {
            //$conrelid = $constrs->fields['conrelid'];
            while (!$constrs->EOF) {
                if ('f' === $constrs->fields['contype']) {
                    if (!isset($fksprops['byconstr'][$constrs->fields['conid']])) {
                        $fksprops['byconstr'][$constrs->fields['conid']] = [
                            'confrelid' => $constrs->fields['confrelid'],
                            'f_table' => $constrs->fields['f_table'],
                            'f_schema' => $constrs->fields['f_schema'],
                            'pattnums' => [],
                            'pattnames' => [],
                            'fattnames' => [],
                        ];
                    }

                    $fksprops['byconstr'][$constrs->fields['conid']]['pattnums'][] = $constrs->fields['p_attnum'];
                    $fksprops['byconstr'][$constrs->fields['conid']]['pattnames'][] = $constrs->fields['p_field'];
                    $fksprops['byconstr'][$constrs->fields['conid']]['fattnames'][] = $constrs->fields['f_field'];

                    if (!isset($fksprops['byfield'][$constrs->fields['p_attnum']])) {
                        $fksprops['byfield'][$constrs->fields['p_attnum']] = [];
                    }

                    $fksprops['byfield'][$constrs->fields['p_attnum']][] = $constrs->fields['conid'];
                }
                $constrs->moveNext();
            }

            $fksprops['code'] = '<script type="text/javascript">' . \PHP_EOL;
            $fksprops['code'] .= "var constrs = {};\n";

            foreach ($fksprops['byconstr'] as $conid => $props) {
                $fksprops['code'] .= "constrs.constr_{$conid} = {\n";
                $fksprops['code'] .= 'pattnums: [' . \implode(',', $props['pattnums']) . "],\n";
                $fksprops['code'] .= "f_table:'" . \addslashes(\htmlentities($props['f_table'], \ENT_QUOTES, 'UTF-8')) . "',\n";
                $fksprops['code'] .= "f_schema:'" . \addslashes(\htmlentities($props['f_schema'], \ENT_QUOTES, 'UTF-8')) . "',\n";
                $_ = '';

                foreach ($props['pattnames'] as $n) {
                    $_ .= ",'" . \htmlentities($n, \ENT_QUOTES, 'UTF-8') . "'";
                }
                $fksprops['code'] .= 'pattnames: [' . \mb_substr($_, 1) . "],\n";

                $_ = '';

                foreach ($props['fattnames'] as $n) {
                    $_ .= ",'" . \htmlentities($n, \ENT_QUOTES, 'UTF-8') . "'";
                }

                $fksprops['code'] .= 'fattnames: [' . \mb_substr($_, 1) . "]\n";
                $fksprops['code'] .= "};\n";
            }

            $fksprops['code'] .= "var attrs = {};\n";

            foreach ($fksprops['byfield'] as $attnum => $cstrs) {
                $fksprops['code'] .= "attrs.attr_{$attnum} = [" . \implode(',', $fksprops['byfield'][$attnum]) . "];\n";
            }

            $fksprops['code'] .= "var table='" . \addslashes(\htmlentities($table, \ENT_QUOTES, 'UTF-8')) . "';";
            $fksprops['code'] .= "var server='" . \htmlentities($_REQUEST['server'], \ENT_QUOTES, 'UTF-8') . "';";
            $fksprops['code'] .= "var database='" . \addslashes(\htmlentities($_REQUEST['database'], \ENT_QUOTES, 'UTF-8')) . "';";
            $fksprops['code'] .= "var subfolder='" . \containerInstance()->subFolder . "';";
            $fksprops['code'] .= '</script>' . \PHP_EOL;

            $fksprops['code'] .= '<div id="fkbg"></div>';
            $fksprops['code'] .= '<div id="fklist"></div>';
            $fksprops['code'] .= '<script src="' . \containerInstance()->subFolder . '/assets/js/ac_insert_row.js" type="text/javascript"></script>';
        } else {
            /* we have no foreign keys on this table */
            return false;
        }

        return $fksprops;
    }

    private function _getFKProps()
    {
        if (('disable' !== $this->conf['autocomplete'])) {
            $fksprops = $this->getAutocompleteFKProperties($_REQUEST['table']);

            if (false !== $fksprops) {
                echo $fksprops['code'];
            }
        } else {
            $fksprops = false;
        }

        return $fksprops;
    }
}
