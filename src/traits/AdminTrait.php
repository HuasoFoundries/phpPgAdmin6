<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
 */

namespace PHPPgAdmin\Traits;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Common trait for admin features.
 */
trait AdminTrait
{
    /**
     * Show confirmation of cluster.
     *
     * @param mixed $type
     */
    public function confirmCluster($type)
    {
        $this->script = ('database' == $type) ? 'database' : 'tables';

        $script = $this->script;

        if (('table' == $type) && empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletocluster']);

            return;
        }

        if (isset($_REQUEST['ma'])) {
            $this->printTrail('schema');
            $this->printTitle($this->lang['strclusterindex'], 'pg.index.cluster');

            echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;
            foreach ($_REQUEST['ma'] as $v) {
                $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                echo '<p>', sprintf($this->lang['strconfclustertable'], $this->misc->printVal($a['table'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table[]" value="', htmlspecialchars($a['table']), '" />'.PHP_EOL;
            } //  END if multi cluster
        } else {
            $this->printTrail($type);
            $this->printTitle($this->lang['strclusterindex'], 'pg.index.cluster');

            echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;

            if ('table' == $type) {
                echo '<p>', sprintf($this->lang['strconfclustertable'], $this->misc->printVal($_REQUEST['object'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['object']), '" />'.PHP_EOL;
            } else {
                echo '<p>', sprintf($this->lang['strconfclusterdatabase'], $this->misc->printVal($_REQUEST['object'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table" value="" />'.PHP_EOL;
            }
        }
        echo '<input type="hidden" name="action" value="cluster" />'.PHP_EOL;

        echo $this->misc->form;

        echo "<input type=\"submit\" name=\"cluster\" value=\"{$this->lang['strcluster']}\" />\n"; //TODO
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
        echo "</form>\n"; //  END single cluster
    }

    /**
     * Show confirmation of reindex.
     *
     * @param mixed $type
     */
    public function confirmReindex($type)
    {
        $this->script = ('database' == $type) ? 'database' : 'tables';
        $script       = $this->script;
        $this->misc   = $this->misc;
        $data         = $this->misc->getDatabaseAccessor();

        if (('table' == $type) && empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletoreindex']);

            return;
        }

        if (isset($_REQUEST['ma'])) {
            $this->printTrail('schema');
            $this->printTitle($this->lang['strreindex'], 'pg.reindex');

            echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;
            foreach ($_REQUEST['ma'] as $v) {
                $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                echo '<p>', sprintf($this->lang['strconfreindextable'], $this->misc->printVal($a['table'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table[]" value="', htmlspecialchars($a['table']), '" />'.PHP_EOL;
            } //  END if multi reindex
        } else {
            $this->printTrail($type);
            $this->printTitle($this->lang['strreindex'], 'pg.reindex');

            echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;

            if ('table' == $type) {
                echo '<p>', sprintf($this->lang['strconfreindextable'], $this->misc->printVal($_REQUEST['object'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['object']), '" />'.PHP_EOL;
            } else {
                echo '<p>', sprintf($this->lang['strconfreindexdatabase'], $this->misc->printVal($_REQUEST['object'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table" value="" />'.PHP_EOL;
            }
        }
        echo '<input type="hidden" name="action" value="reindex" />'.PHP_EOL;

        if ($data->hasForceReindex()) {
            echo "<p><input type=\"checkbox\" id=\"reindex_force\" name=\"reindex_force\" /><label for=\"reindex_force\">{$this->lang['strforce']}</label></p>".PHP_EOL;
        }

        echo $this->misc->form;

        echo "<input type=\"submit\" name=\"reindex\" value=\"{$this->lang['strreindex']}\" />\n"; //TODO
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
        echo "</form>\n"; //  END single reindex
    }

    /**
     * Show confirmation of analyze.
     *
     * @param mixed $type
     */
    public function confirmAnalyze($type)
    {
        $this->script = ('database' == $type) ? 'database' : 'tables';

        $script = $this->script;

        if (('table' == $type) && empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletoanalyze']);

            return;
        }

        if (isset($_REQUEST['ma'])) {
            $this->printTrail('schema');
            $this->printTitle($this->lang['stranalyze'], 'pg.analyze');

            echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;
            foreach ($_REQUEST['ma'] as $v) {
                $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                //\Kint::dump($a);
                echo '<p>', sprintf($this->lang['strconfanalyzetable'], $this->misc->printVal($a['table'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table[]" value="', htmlspecialchars($a['table']), '" />'.PHP_EOL;
            } //  END if multi analyze
        } else {
            $this->printTrail($type);
            $this->printTitle($this->lang['stranalyze'], 'pg.analyze');

            echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;

            if ('table' == $type) {
                echo '<p>', sprintf($this->lang['strconfanalyzetable'], $this->misc->printVal($_REQUEST['object'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['object']), '" />'.PHP_EOL;
            } else {
                echo '<p>', sprintf($this->lang['strconfanalyzedatabase'], $this->misc->printVal($_REQUEST['object'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table" value="" />'.PHP_EOL;
            }
        }
        echo '<input type="hidden" name="action" value="analyze" />'.PHP_EOL;
        echo $this->misc->form;

        echo "<input type=\"submit\" name=\"analyze\" value=\"{$this->lang['stranalyze']}\" />\n"; //TODO
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
        echo "</form>\n"; //  END single analyze
    }

    /**
     * Show confirmation of vacuum.
     *
     * @param mixed $type
     */
    public function confirmVacuum($type)
    {
        $script = ('database' == $type) ? 'database' : 'tables';

        if (('table' == $type) && empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletovacuum']);

            return;
        }

        if (isset($_REQUEST['ma'])) {
            $this->printTrail('schema');
            $this->printTitle($this->lang['strvacuum'], 'pg.vacuum');

            echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;
            foreach ($_REQUEST['ma'] as $v) {
                $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                echo '<p>', sprintf($this->lang['strconfvacuumtable'], $this->misc->printVal($a['table'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table[]" value="', htmlspecialchars($a['table']), '" />'.PHP_EOL;
            }
        } else {
            // END if multi vacuum
            $this->printTrail($type);
            $this->printTitle($this->lang['strvacuum'], 'pg.vacuum');

            echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;

            if ('table' == $type) {
                echo '<p>', sprintf($this->lang['strconfvacuumtable'], $this->misc->printVal($_REQUEST['object'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['object']), '" />'.PHP_EOL;
            } else {
                echo '<p>', sprintf($this->lang['strconfvacuumdatabase'], $this->misc->printVal($_REQUEST['object'])), '</p>'.PHP_EOL;
                echo '<input type="hidden" name="table" value="" />'.PHP_EOL;
            }
        }
        echo '<input type="hidden" name="action" value="vacuum" />'.PHP_EOL;
        echo $this->misc->form;
        echo "<p><input type=\"checkbox\" id=\"vacuum_full\" name=\"vacuum_full\" /> <label for=\"vacuum_full\">{$this->lang['strfull']}</label></p>".PHP_EOL;
        echo "<p><input type=\"checkbox\" id=\"vacuum_analyze\" name=\"vacuum_analyze\" /> <label for=\"vacuum_analyze\">{$this->lang['stranalyze']}</label></p>".PHP_EOL;
        echo "<p><input type=\"checkbox\" id=\"vacuum_freeze\" name=\"vacuum_freeze\" /> <label for=\"vacuum_freeze\">{$this->lang['strfreeze']}</label></p>".PHP_EOL;
        echo "<input type=\"submit\" name=\"vacuum\" value=\"{$this->lang['strvacuum']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
        echo "</form>\n"; //  END single vacuum
    }

    /**
     * Add or Edit autovacuum params.
     *
     * @param mixed $type
     * @param mixed $msg
     */
    public function confirmEditAutovacuum($type, $msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['table'])) {
            $this->doAdmin($type, $this->lang['strspecifyeditvacuumtable']);

            return;
        }

        $script = ('database' == $type) ? 'database' : 'tables';

        $this->printTrail($type);
        $this->printTitle(sprintf($this->lang['streditvacuumtable'], $this->misc->printVal($_REQUEST['table'])));
        $this->printMsg(sprintf($msg, $this->misc->printVal($_REQUEST['table'])));

        if (empty($_REQUEST['table'])) {
            $this->doAdmin($type, $this->lang['strspecifyeditvacuumtable']);

            return;
        }

        $old_val  = $data->getTableAutovacuum($_REQUEST['table']);
        $defaults = $data->getAutovacuum();
        $old_val  = $old_val->fields;

        if (isset($old_val['autovacuum_enabled']) and ('off' == $old_val['autovacuum_enabled'])) {
            $enabled  = '';
            $disabled = 'checked="checked"';
        } else {
            $enabled  = 'checked="checked"';
            $disabled = '';
        }

        if (!isset($old_val['autovacuum_vacuum_threshold'])) {
            $old_val['autovacuum_vacuum_threshold'] = '';
        }

        if (!isset($old_val['autovacuum_vacuum_scale_factor'])) {
            $old_val['autovacuum_vacuum_scale_factor'] = '';
        }

        if (!isset($old_val['autovacuum_analyze_threshold'])) {
            $old_val['autovacuum_analyze_threshold'] = '';
        }

        if (!isset($old_val['autovacuum_analyze_scale_factor'])) {
            $old_val['autovacuum_analyze_scale_factor'] = '';
        }

        if (!isset($old_val['autovacuum_vacuum_cost_delay'])) {
            $old_val['autovacuum_vacuum_cost_delay'] = '';
        }

        if (!isset($old_val['autovacuum_vacuum_cost_limit'])) {
            $old_val['autovacuum_vacuum_cost_limit'] = '';
        }

        echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;
        echo $this->misc->form;
        echo '<input type="hidden" name="action" value="editautovac" />'.PHP_EOL;
        echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;

        echo "<br />\n<br />\n<table>".PHP_EOL;
        echo "\t<tr><td>&nbsp;</td>".PHP_EOL;
        echo "<th class=\"data\">{$this->lang['strnewvalues']}</th><th class=\"data\">{$this->lang['strdefaultvalues']}</th></tr>".PHP_EOL;
        echo "\t<tr><th class=\"data left\">{$this->lang['strenable']}</th>".PHP_EOL;
        echo '<td class="data1">'.PHP_EOL;
        echo "<label for=\"on\">on</label><input type=\"radio\" name=\"autovacuum_enabled\" id=\"on\" value=\"on\" {$enabled} />".PHP_EOL;
        echo "<label for=\"off\">off</label><input type=\"radio\" name=\"autovacuum_enabled\" id=\"off\" value=\"off\" {$disabled} /></td>".PHP_EOL;
        echo "<th class=\"data left\">{$defaults['autovacuum']}</th></tr>".PHP_EOL;
        echo "\t<tr><th class=\"data left\">{$this->lang['strvacuumbasethreshold']}</th>".PHP_EOL;
        echo "<td class=\"data1\"><input type=\"text\" name=\"autovacuum_vacuum_threshold\" value=\"{$old_val['autovacuum_vacuum_threshold']}\" /></td>".PHP_EOL;
        echo "<th class=\"data left\">{$defaults['autovacuum_vacuum_threshold']}</th></tr>".PHP_EOL;
        echo "\t<tr><th class=\"data left\">{$this->lang['strvacuumscalefactor']}</th>".PHP_EOL;
        echo "<td class=\"data1\"><input type=\"text\" name=\"autovacuum_vacuum_scale_factor\" value=\"{$old_val['autovacuum_vacuum_scale_factor']}\" /></td>".PHP_EOL;
        echo "<th class=\"data left\">{$defaults['autovacuum_vacuum_scale_factor']}</th></tr>".PHP_EOL;
        echo "\t<tr><th class=\"data left\">{$this->lang['stranalybasethreshold']}</th>".PHP_EOL;
        echo "<td class=\"data1\"><input type=\"text\" name=\"autovacuum_analyze_threshold\" value=\"{$old_val['autovacuum_analyze_threshold']}\" /></td>".PHP_EOL;
        echo "<th class=\"data left\">{$defaults['autovacuum_analyze_threshold']}</th></tr>".PHP_EOL;
        echo "\t<tr><th class=\"data left\">{$this->lang['stranalyzescalefactor']}</th>".PHP_EOL;
        echo "<td class=\"data1\"><input type=\"text\" name=\"autovacuum_analyze_scale_factor\" value=\"{$old_val['autovacuum_analyze_scale_factor']}\" /></td>".PHP_EOL;
        echo "<th class=\"data left\">{$defaults['autovacuum_analyze_scale_factor']}</th></tr>".PHP_EOL;
        echo "\t<tr><th class=\"data left\">{$this->lang['strvacuumcostdelay']}</th>".PHP_EOL;
        echo "<td class=\"data1\"><input type=\"text\" name=\"autovacuum_vacuum_cost_delay\" value=\"{$old_val['autovacuum_vacuum_cost_delay']}\" /></td>".PHP_EOL;
        echo "<th class=\"data left\">{$defaults['autovacuum_vacuum_cost_delay']}</th></tr>".PHP_EOL;
        echo "\t<tr><th class=\"data left\">{$this->lang['strvacuumcostlimit']}</th>".PHP_EOL;
        echo "<td class=\"datat1\"><input type=\"text\" name=\"autovacuum_vacuum_cost_limit\" value=\"{$old_val['autovacuum_vacuum_cost_limit']}\" /></td>".PHP_EOL;
        echo "<th class=\"data left\">{$defaults['autovacuum_vacuum_cost_limit']}</th></tr>".PHP_EOL;
        echo '</table>'.PHP_EOL;
        echo '<br />';
        echo '<br />';
        echo "<input type=\"submit\" name=\"save\" value=\"{$this->lang['strsave']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>".PHP_EOL;

        echo '</form>'.PHP_EOL;
    }

    /**
     * Confirm drop autovacuum params for a table.
     *
     * @param mixed $type
     */
    public function confirmDropAutovacuum($type)
    {
        $script = ('database' == $type) ? 'database' : 'tables';

        if (empty($_REQUEST['table'])) {
            $this->doAdmin($type, $this->lang['strspecifydelvacuumtable']);

            return;
        }

        $this->printTrail($type);
        $this->printTabs($type, 'admin');

        printf(
            "<p>{$this->lang['strdelvacuumtable']}</p>\n",
            $this->misc->printVal("\"{$_GET['schema']}\".\"{$_GET['table']}\"")
        );

        echo "<form style=\"float: left\" action=\"{$script}\" method=\"post\">".PHP_EOL;
        echo '<input type="hidden" name="action" value="delautovac" />'.PHP_EOL;
        echo $this->misc->form;
        echo '<input type="hidden" name="table" value="', htmlspecialchars($_REQUEST['table']), '" />'.PHP_EOL;
        echo '<input type="hidden" name="rel" value="', htmlspecialchars(serialize([$_REQUEST['schema'], $_REQUEST['table']])), '" />'.PHP_EOL;
        echo "<input type=\"submit\" name=\"yes\" value=\"{$this->lang['stryes']}\" />".PHP_EOL;
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" />".PHP_EOL;
        echo '</form>'.PHP_EOL;
    }

    /**
     * perform cluster.
     *
     * @param mixed $type
     */
    public function doCluster($type)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (('table' == $type) && empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletocluster']);

            return;
        }

        $msg = '';
        //If multi table cluster
        if ('table' == $type) {
            // cluster one or more table
            if (is_array($_REQUEST['table'])) {
                foreach ($_REQUEST['table'] as $o) {
                    list($status, $sql) = $data->clusterIndex($o);
                    $msg .= sprintf('%s<br />', $sql);
                    if (0 == $status) {
                        $msg .= sprintf('%s: %s<br />', htmlentities($o, ENT_QUOTES, 'UTF-8'), $this->lang['strclusteredgood']);
                    } else {
                        $this->doDefault(sprintf('%s %s%s: %s<br />', $type, $msg, htmlentities($o, ENT_QUOTES, 'UTF-8'), $this->lang['strclusteredbad']));

                        return;
                    }
                }
                // Everything went fine, back to the Default page....
                $this->doDefault($msg);
            } else {
                list($status, $sql) = $data->clusterIndex($_REQUEST['object']);
                $msg .= sprintf('%s<br />', $sql);
                if (0 == $status) {
                    $this->doAdmin($type, $msg.$this->lang['strclusteredgood']);
                } else {
                    $this->doAdmin($type, $msg.$this->lang['strclusteredbad']);
                }
            }
        } else {
            // Cluster all tables in database
            list($status, $sql) = $data->clusterIndex();
            $msg .= sprintf('%s<br />', $sql);
            if (0 == $status) {
                $this->doAdmin($type, $msg.$this->lang['strclusteredgood']);
            } else {
                $this->doAdmin($type, $msg.$this->lang['strclusteredbad']);
            }
        }
    }

    /**
     * perform reindex.
     *
     * @param mixed $type
     */
    public function doReindex($type)
    {
        $this->misc = $this->misc;
        $data       = $this->misc->getDatabaseAccessor();

        if (('table' == $type) && empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletoreindex']);

            return;
        }

        //If multi table reindex
        if (('table' == $type) && is_array($_REQUEST['table'])) {
            $msg = '';
            foreach ($_REQUEST['table'] as $o) {
                $status = $data->reindex(strtoupper($type), $o, isset($_REQUEST['reindex_force']));
                if (0 == $status) {
                    $msg .= sprintf('%s: %s<br />', htmlentities($o, ENT_QUOTES, 'UTF-8'), $this->lang['strreindexgood']);
                } else {
                    $this->doDefault(sprintf('%s %s%s: %s<br />', $type, $msg, htmlentities($o, ENT_QUOTES, 'UTF-8'), $this->lang['strreindexbad']));

                    return;
                }
            }
            // Everything went fine, back to the Default page....
            $this->misc->setReloadBrowser(true);
            $this->doDefault($msg);
        } else {
            $status = $data->reindex(strtoupper($type), $_REQUEST['object'], isset($_REQUEST['reindex_force']));
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doAdmin($type, $this->lang['strreindexgood']);
            } else {
                $this->doAdmin($type, $this->lang['strreindexbad']);
            }
        }
    }

    /**
     * perform analyze.
     *
     * @param mixed $type
     */
    public function doAnalyze($type)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (('table' == $type) && empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletoanalyze']);

            return;
        }

        //If multi table analyze
        if (('table' == $type) && is_array($_REQUEST['table'])) {
            $msg = '';
            foreach ($_REQUEST['table'] as $o) {
                $status = $data->analyzeDB($o);
                if (0 == $status) {
                    $msg .= sprintf('%s: %s<br />', htmlentities($o, ENT_QUOTES, 'UTF-8'), $this->lang['stranalyzegood']);
                } else {
                    $this->doDefault(sprintf('%s %s%s: %s<br />', $type, $msg, htmlentities($o, ENT_QUOTES, 'UTF-8'), $this->lang['stranalyzebad']));

                    return;
                }
            }
            // Everything went fine, back to the Default page....
            $this->misc->setReloadBrowser(true);
            $this->doDefault($msg);
        } else {
            //we must pass table here. When empty, analyze the whole db
            $status = $data->analyzeDB($_REQUEST['table']);
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doAdmin($type, $this->lang['stranalyzegood']);
            } else {
                $this->doAdmin($type, $this->lang['stranalyzebad']);
            }
        }
    }

    /**
     * perform actual vacuum.
     *
     * @param mixed $type
     */
    public function doVacuum($type)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (('table' == $type) && empty($_REQUEST['table']) && empty($_REQUEST['ma'])) {
            $this->doDefault($this->lang['strspecifytabletovacuum']);

            return;
        }

        //If multi drop
        if (is_array($_REQUEST['table'])) {
            $msg = '';
            foreach ($_REQUEST['table'] as $t) {
                $status = $data->vacuumDB($t, isset($_REQUEST['vacuum_analyze']), isset($_REQUEST['vacuum_full']), isset($_REQUEST['vacuum_freeze']));
                if (0 == $status) {
                    $msg .= sprintf('%s: %s<br />', htmlentities($t, ENT_QUOTES, 'UTF-8'), $this->lang['strvacuumgood']);
                } else {
                    $this->doDefault(sprintf('%s %s%s: %s<br />', $type, $msg, htmlentities($t, ENT_QUOTES, 'UTF-8'), $this->lang['strvacuumbad']));

                    return;
                }
            }
            // Everything went fine, back to the Default page....
            $this->misc->setReloadBrowser(true);
            $this->doDefault($msg);
        } else {
            //we must pass table here. When empty, vacuum the whole db
            $status = $data->vacuumDB($_REQUEST['table'], isset($_REQUEST['vacuum_analyze']), isset($_REQUEST['vacuum_full']), isset($_REQUEST['vacuum_freeze']));
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doAdmin($type, $this->lang['strvacuumgood']);
            } else {
                $this->doAdmin($type, $this->lang['strvacuumbad']);
            }
        }
    }

    /**
     * persist changes in autovacuum settings.
     *
     * @param mixed $type
     * @param mixed $confirm
     * @param mixed $msg
     */
    public function doEditAutovacuum($type)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['table'])) {
            $this->doAdmin($type, $this->lang['strspecifyeditvacuumtable']);

            return;
        }

        $status = $data->saveAutovacuum(
            $_REQUEST['table'],
            $_POST['autovacuum_enabled'],
            $_POST['autovacuum_vacuum_threshold'],
            $_POST['autovacuum_vacuum_scale_factor'],
            $_POST['autovacuum_analyze_threshold'],
            $_POST['autovacuum_analyze_scale_factor'],
            $_POST['autovacuum_vacuum_cost_delay'],
            $_POST['autovacuum_vacuum_cost_limit']
        );

        if (0 == $status) {
            $this->doAdmin($type, sprintf($this->lang['strsetvacuumtablesaved'], $_REQUEST['table']));
        } else {
            $this->confirmEditAutovacuum($type, $this->lang['strsetvacuumtablefail']);
        }
    }

    /**
     * drop autovacuum settings.
     *
     * @param mixed $type
     */
    public function doDropAutovacuum($type)
    {
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['table'])) {
            $this->doAdmin($type, $this->lang['strspecifydelvacuumtable']);

            return;
        }

        $status = $data->dropAutovacuum($_POST['table']);

        if (0 == $status) {
            $this->doAdmin($type, sprintf($this->lang['strvacuumtablereset'], $this->misc->printVal($_POST['table'])));
        } else {
            $this->doAdmin($type, sprintf($this->lang['strdelvacuumtablefail'], $this->misc->printVal($_POST['table'])));
        }
    }

    /**
     * Database/table administration and tuning tasks.
     *
     * Release: admin
     *
     * @param mixed $type
     * @param mixed $msg
     */
    public function doAdmin($type, $msg = '')
    {
        $this->script = ('database' == $type) ? 'database' : 'tables';

        $script = $this->script;

        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail($type);
        $this->printTabs($type, 'admin');
        $this->printMsg($msg);

        if ('database' == $type) {
            printf("<p>{$this->lang['stradminondatabase']}</p>\n", $this->misc->printVal($_REQUEST['object']));
        } else {
            printf("<p>{$this->lang['stradminontable']}</p>\n", $this->misc->printVal($_REQUEST['object']));
        }

        echo '<table style="width: 50%">'.PHP_EOL;
        echo '<tr>'.PHP_EOL;
        echo '<th class="data">';
        $this->misc->printHelp($this->lang['strvacuum'], 'pg.admin.vacuum').'</th>'.PHP_EOL;
        echo '</th>';
        echo '<th class="data">';
        $this->misc->printHelp($this->lang['stranalyze'], 'pg.admin.analyze');
        echo '</th>';

        $table_hidden_inputs = ($type === 'table') ?
        sprintf('<input type="hidden" name="table" value="%s" />%s<input type="hidden" name="subject" value="table" />', htmlspecialchars($_REQUEST['object']), PHP_EOL, PHP_EOL) : '';

        list($recluster_help, $reclusterconf) = $this->_getReclusterConf($data, $type, $table_hidden_inputs);

        echo $recluster_help;

        echo '<th class="data">';
        $this->misc->printHelp($this->lang['strreindex'], 'pg.index.reindex');
        echo '</th>';
        echo '</tr>';

        // Vacuum
        echo '<tr class="row1">'.PHP_EOL;
        echo '<td style="text-align: center; vertical-align: bottom">'.PHP_EOL;
        echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;

        echo '<p><input type="hidden" name="action" value="confirm_vacuum" />'.PHP_EOL;
        echo $this->misc->form;
        echo $table_hidden_inputs;
        echo "<input type=\"submit\" value=\"{$this->lang['strvacuum']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;
        echo '</td>'.PHP_EOL;

        // Analyze
        echo '<td style="text-align: center; vertical-align: bottom">'.PHP_EOL;
        echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;
        echo '<p><input type="hidden" name="action" value="confirm_analyze" />'.PHP_EOL;
        echo $this->misc->form;
        echo $table_hidden_inputs;
        echo "<input type=\"submit\" value=\"{$this->lang['stranalyze']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;
        echo '</td>'.PHP_EOL;

        // Cluster
        echo $reclusterconf;

        // Reindex
        echo '<td style="text-align: center; vertical-align: bottom">'.PHP_EOL;
        echo '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;
        echo '<p><input type="hidden" name="action" value="confirm_reindex" />'.PHP_EOL;
        echo $this->misc->form;
        echo $table_hidden_inputs;
        echo "<input type=\"submit\" value=\"{$this->lang['strreindex']}\" /></p>".PHP_EOL;
        echo '</form>'.PHP_EOL;
        echo '</td>'.PHP_EOL;
        echo '</tr>'.PHP_EOL;
        echo '</table>'.PHP_EOL;

        // Autovacuum
        $this->_printAutoVacuumConf($data, $type);
    }

    public function adminActions($action, $type)
    {
        if ('database' == $type) {
            $_REQUEST['object'] = $_REQUEST['database'];
        } else {
            // $_REQUEST['table'] is no set if we are in the schema page
            $_REQUEST['object'] = (isset($_REQUEST['table']) ? $_REQUEST['table'] : '');
            if (is_array($_REQUEST['object'])) {
                return false;
            }
        }

        if (isset($_POST['cancel'])) {
            $action = 'admin';
        }

        switch ($action) {
            case 'admin':
                $this->doAdmin($type);

                break;
            case 'confirm_cluster':
                $this->confirmCluster($type);

                break;
            case 'confirm_reindex':
                $this->confirmReindex($type);

                break;
            case 'confirm_analyze':
                $this->confirmAnalyze($type);

                break;
            case 'confirm_vacuum':
                $this->confirmVacuum($type);

                break;
            case 'confeditautovac':
                $this->confirmEditAutovacuum($type);

                break;
            case 'confdelautovac':
                $this->confirmDropAutovacuum($type);

                break;
            case 'cluster':
                $this->doCluster($type);

                break;
            case 'reindex':
                $this->doReindex($type);

                break;
            case 'analyze':
                $this->doAnalyze($type);

                break;
            case 'vacuum':
                $this->doVacuum($type);

                break;
            case 'editautovac':
                $this->doEditAutovacuum($type);

                break;
            case 'delautovac':
                $this->doDropAutovacuum($type);

                break;
            default:
                return false;
        }

        return true;
    }

    private function _getReclusterConf($data, $type, $table_hidden_inputs)
    {
        if (!$data->hasRecluster()) {
            return ['', ''];
        }
        $script         = $this->script;
        $recluster_help = sprintf('<th class="data">%s</th>', $this->misc->printHelp($this->lang['strclusterindex'], 'pg.index.cluster', false));

        $disabled      = '';
        $reclusterconf = '<td style="text-align: center; vertical-align: bottom">'.PHP_EOL;
        $reclusterconf .= '<form action="'.\SUBFOLDER."/src/views/{$script}\" method=\"post\">".PHP_EOL;
        $reclusterconf .= $this->misc->form;
        $reclusterconf .= $table_hidden_inputs;
        if ('table' == $type && !$data->alreadyClustered($_REQUEST['object'])) {
            $disabled = 'disabled="disabled" ';
            $reclusterconf .= "{$this->lang['strnoclusteravailable']}<br />";
        }
        $reclusterconf .= '<p><input type="hidden" name="action" value="confirm_cluster" />'.PHP_EOL;
        $reclusterconf .= "<input type=\"submit\" value=\"{$this->lang['strclusterindex']}\" ${disabled}/></p>".PHP_EOL;
        $reclusterconf .= '</form>'.PHP_EOL;
        $reclusterconf .= '</td>'.PHP_EOL;

        return [$recluster_help, $reclusterconf];
    }

    private function _printAutoVacuumConf($data, $type)
    {
        if (!$data->hasAutovacuum()) {
            return;
        }
        $script = $this->script;
        // get defaults values for autovacuum
        $defaults = $data->getAutovacuum();
        // Fetch the autovacuum properties from the database or table if != ''
        if ('table' == $type) {
            $autovac = $data->getTableAutovacuum($_REQUEST['table']);
        } else {
            $autovac = $data->getTableAutovacuum();
        }

        echo "<br /><br /><h2>{$this->lang['strvacuumpertable']}</h2>";
        echo '<p>'.(('on' == $defaults['autovacuum']) ? $this->lang['strturnedon'] : $this->lang['strturnedoff']).'</p>';
        echo "<p class=\"message\">{$this->lang['strnotdefaultinred']}</p>";

        $enlight = function ($f, $p) {
            if (isset($f[$p[0]]) and ($f[$p[0]] != $p[1])) {
                return '<span style="color:#F33;font-weight:bold">'.htmlspecialchars($f[$p[0]]).'</span>';
            }

            return htmlspecialchars($p[1]);
        };

        $columns = [
            'namespace'                       => [
                'title' => $this->lang['strschema'],
                'field' => Decorator::field('nspname'),
                'url'   => \SUBFOLDER."/redirect/schema?{$this->misc->href}&amp;",
                'vars'  => ['schema' => 'nspname'],
            ],
            'relname'                         => [
                'title' => $this->lang['strtable'],
                'field' => Decorator::field('relname'),
                'url'   => \SUBFOLDER."/redirect/table?{$this->misc->href}&amp;",
                'vars'  => ['table' => 'relname', 'schema' => 'nspname'],
            ],
            'autovacuum_enabled'              => [
                'title' => $this->lang['strenabled'],
                'field' => Decorator::callback($enlight, ['autovacuum_enabled', $defaults['autovacuum']]),
                'type'  => 'verbatim',
            ],
            'autovacuum_vacuum_threshold'     => [
                'title' => $this->lang['strvacuumbasethreshold'],
                'field' => Decorator::callback($enlight, ['autovacuum_vacuum_threshold', $defaults['autovacuum_vacuum_threshold']]),
                'type'  => 'verbatim',
            ],
            'autovacuum_vacuum_scale_factor'  => [
                'title' => $this->lang['strvacuumscalefactor'],
                'field' => Decorator::callback($enlight, ['autovacuum_vacuum_scale_factor', $defaults['autovacuum_vacuum_scale_factor']]),
                'type'  => 'verbatim',
            ],
            'autovacuum_analyze_threshold'    => [
                'title' => $this->lang['stranalybasethreshold'],
                'field' => Decorator::callback($enlight, ['autovacuum_analyze_threshold', $defaults['autovacuum_analyze_threshold']]),
                'type'  => 'verbatim',
            ],
            'autovacuum_analyze_scale_factor' => [
                'title' => $this->lang['stranalyzescalefactor'],
                'field' => Decorator::callback($enlight, ['autovacuum_analyze_scale_factor', $defaults['autovacuum_analyze_scale_factor']]),
                'type'  => 'verbatim',
            ],
            'autovacuum_vacuum_cost_delay'    => [
                'title' => $this->lang['strvacuumcostdelay'],
                'field' => Decorator::concat(Decorator::callback($enlight, ['autovacuum_vacuum_cost_delay', $defaults['autovacuum_vacuum_cost_delay']]), 'ms'),
                'type'  => 'verbatim',
            ],
            'autovacuum_vacuum_cost_limit'    => [
                'title' => $this->lang['strvacuumcostlimit'],
                'field' => Decorator::callback($enlight, ['autovacuum_vacuum_cost_limit', $defaults['autovacuum_vacuum_cost_limit']]),
                'type'  => 'verbatim',
            ],
        ];

        // Maybe we need to check permissions here?
        $columns['actions'] = ['title' => $this->lang['stractions']];

        $actions = [
            'edit'   => [
                'content' => $this->lang['stredit'],
                'attr'    => [
                    'href' => [
                        'url'     => $script,
                        'urlvars' => [
                            'subject' => $type,
                            'action'  => 'confeditautovac',
                            'schema'  => Decorator::field('nspname'),
                            'table'   => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
            'delete' => [
                'content' => $this->lang['strdelete'],
                'attr'    => [
                    'href' => [
                        'url'     => $script,
                        'urlvars' => [
                            'subject' => $type,
                            'action'  => 'confdelautovac',
                            'schema'  => Decorator::field('nspname'),
                            'table'   => Decorator::field('relname'),
                        ],
                    ],
                ],
            ],
        ];

        if ('table' == $type) {
            unset($actions['edit']['vars']['schema'],
                $actions['delete']['vars']['schema'],
                $columns['namespace'],
                $columns['relname']
            );
        }

        echo $this->printTable($autovac, $columns, $actions, 'admin-admin', $this->lang['strnovacuumconf']);

        if (('table' == $type) and (0 == $autovac->RecordCount())) {
            echo '<br />';

            echo '<a href="'.\SUBFOLDER."/src/views/tables?action=confeditautovac&amp;{$this->misc->href}&amp;table=";
            echo htmlspecialchars($_REQUEST['table']);
            echo "\">{$this->lang['straddvacuumtable']}</a>";
        }
    }

    abstract public function doDefault($msg = '');

    abstract public function printTrail($trail = [], $do_print = true);

    abstract public function printTitle($title, $help = null, $do_print = true);

    abstract public function printMsg($msg, $do_print = true);

    abstract public function printTabs($tabs, $activetab, $do_print = true);

    abstract public function printTable(&$tabledata, &$columns, &$actions, $place, $nodata = '', $pre_fn = null);
}
