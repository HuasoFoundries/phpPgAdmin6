<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Controller;

/**
 * AcinsertController controller class.
 */
class AcinsertController extends BaseController
{
    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if (isset($_POST['offset'])) {
            $offset = \sprintf(
                ' OFFSET %s',
                $_POST['offset']
            );
        } else {
            $_POST['offset'] = 0;
            $offset = ' OFFSET 0';
        }

        $fkeynames = [];

        foreach ($_POST['fkeynames'] as $k => $v) {
            $fkeynames[$k] = \html_entity_decode($v, \ENT_QUOTES);
        }

        $keyspos = \array_combine($fkeynames, $_POST['keys']);

        $f_schema = \html_entity_decode($_POST['f_schema'], \ENT_QUOTES);
        $data->fieldClean($f_schema);
        $f_table = \html_entity_decode($_POST['f_table'], \ENT_QUOTES);
        $data->fieldClean($f_table);
        $f_attname = $fkeynames[$_POST['fattpos'][0]];
        $data->fieldClean($f_attname);

        $q = \sprintf(
            'SELECT *
		FROM "%s"."%s"
		WHERE "%s"::text LIKE \'%s%\'
		ORDER BY "%s" LIMIT 12 %s;',
            $f_schema,
            $f_table,
            $f_attname,
            $_POST['fvalue'],
            $f_attname,
            $offset
        );

        $res = $data->selectSet($q);

        if (!$res->EOF) {
            echo '<table class="ac_values">';
            echo '<tr>';

            foreach (\array_keys($res->fields) as $h) {
                echo '<th>';

                if (\in_array($h, $fkeynames, true)) {
                    echo '<img src="' . $this->view->icon('ForeignKey') . '" alt="[referenced key]" />';
                }

                echo \htmlentities($h, \ENT_QUOTES, 'UTF-8'), '</th>';
            }
            echo '</tr>' . \PHP_EOL;
            $i = 0;

            while ((!$res->EOF) && (11 > $i)) {
                $j = 0;
                echo '<tr class="acline">';

                foreach ($res->fields as $n => $v) {
                    $finfo = $res->FetchField($j++);

                    if (\in_array($n, $fkeynames, true)) {
                        echo \sprintf(
                            '<td><a href="javascript:void(0)" class="fkval" name="%s">',
                            $keyspos[$n]
                        ),
                        $this->misc->printVal($v, $finfo->type, ['clip' => 'collapsed']),
                            '</a></td>';
                    } else {
                        echo '<td><a href="javascript:void(0)">',
                        $this->misc->printVal($v, $finfo->type, ['clip' => 'collapsed']),
                            '</a></td>';
                    }
                }
                echo '</tr>' . \PHP_EOL;
                ++$i;
                $res->MoveNext();
            }
            echo '</table>' . \PHP_EOL;

            $js = '<script type="text/javascript">' . \PHP_EOL;

            if ($_POST['offset']) {
                echo '<a href="javascript:void(0)" id="fkprev">&lt;&lt; Prev</a>';
                $js .= "fkl_hasprev=true;\n";
            } else {
                $js .= "fkl_hasprev=false;\n";
            }

            if (12 === $res->RecordCount()) {
                $js .= "fkl_hasnext=true;\n";
                echo '&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" id="fknext">Next &gt;&gt;</a>';
            } else {
                $js .= "fkl_hasnext=false;\n";
            }

            echo $js . '</script>';
        } else {
            \printf(\sprintf(
                '<p>%s</p>',
                $this->lang['strnofkref']
            ), \sprintf(
                '"%s"."%s"."%s"',
                $_POST['f_schema'],
                $_POST['f_table'],
                $fkeynames[$_POST['fattpos']]
            ));

            if ($_POST['offset']) {
                echo '<a href="javascript:void(0)" class="fkprev">Prev &lt;&lt;</a>';
            }
        }
    }
}
