<?php

/**
 * PHPPgAdmin v6.0.0-RC8
 */

namespace PHPPgAdmin\Traits;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Common trait for listing servers.
 */
trait ServersTrait
{
    /**
     * Get list of servers.
     *
     * @param bool  $recordset return as RecordSet suitable for HTMLTableController::printTable if true, otherwise just return an array
     * @param mixed $group     a group name to filter the returned servers using $this->conf[srv_groups]
     *
     * @return array|\PHPPgAdmin\ArrayRecordSet either an array or a Recordset suitable for HTMLTableController::printTable
     */
    public function getServers($recordset = false, $group = false)
    {
        $logins = isset($_SESSION['webdbLogin']) && is_array($_SESSION['webdbLogin']) ? $_SESSION['webdbLogin'] : [];
        $srvs   = [];

        if (($group !== false) && ($group !== 'all')) {
            if (isset($this->conf['srv_groups'][$group]['servers'])) {
                $group = array_fill_keys(explode(',', preg_replace(
                    '/\s/',
                    '',
                    $this->conf['srv_groups'][$group]['servers']
                )), 1);
            } else {
                $group = '';
            }
        }

        foreach ($this->conf['servers'] as $idx => $info) {
            $server_id = $info['host'].':'.$info['port'].':'.$info['sslmode'];
            if ($group === false || isset($group[$idx]) || ($group === 'all')) {
                $server_id  = $info['host'].':'.$info['port'].':'.$info['sslmode'];
                $server_sha = sha1($server_id);

                if (isset($logins[$server_sha])) {
                    $srvs[$server_sha] = $logins[$server_sha];
                } elseif (isset($logins[$server_id])) {
                    $srvs[$server_sha] = $logins[$server_id];
                } else {
                    $srvs[$server_sha] = $info;
                }

                $srvs[$server_sha]['id']     = $server_id;
                $srvs[$server_sha]['sha']    = $server_sha;
                $srvs[$server_sha]['action'] = Decorator::url(
                    '/redirect/server',
                    [
                        'server' => Decorator::field('sha'),
                    ]
                );
                if (isset($srvs[$server_sha]['username'])) {
                    $srvs[$server_sha]['icon']   = 'Server';
                    $srvs[$server_sha]['branch'] = Decorator::url(
                        '/src/views/alldb',
                        [
                            'action'  => 'tree',
                            'subject' => 'server',
                            'server'  => Decorator::field('sha'),
                        ]
                    );
                } else {
                    $srvs[$server_sha]['icon']   = 'DisconnectedServer';
                    $srvs[$server_sha]['branch'] = false;
                }
            }
        }

        uasort($srvs, function ($a, $b) {
            return strcmp($a['desc'], $b['desc']);
        });

        if ($recordset) {
            return new \PHPPgAdmin\ArrayRecordSet($srvs);
        }

        return $srvs;
    }

    /*
     * Output dropdown list to select server and
     * databases form the popups windows.
     * @param string $the_action an action identifying the purpose of this snipper sql|find|history
     */
    public function printConnection($the_action, $do_print = true)
    {
        $connection_html = '<table class="printconnection" style="width: 100%"><tr><td class="popup_select1">'.PHP_EOL;

        $conf_servers = $this->getServers();
        $server_id    = $this->misc->getServerId();
        $servers      = [];
        foreach ($conf_servers as $key => $info) {
            if (empty($info['username'])) {
                continue;
            }
            $info['selected'] = '';
            if ($this->getRequestParam('server') === $info['id'] || $this->getRequestParam('server') === $key) {
                $info['selected'] = ' selected="selected" ';
            }
            $servers[$key] = $info;
        }
        $connection_html .= '<input type="hidden" readonly="readonly" value="'.$the_action.'" id="the_action">';

        if (count($servers) === 1) {
            $connection_html .= '<input type="hidden" readonly="readonly" value="'.$server_id.'" name="server">';
        } else {
            $connection_html .= '<label>';
            $connection_html .= $this->misc->printHelp($this->lang['strserver'], 'pg.server', false);
            $connection_html .= ': </label>';
            $connection_html .= " <select name=\"server\" id='selectserver' >".PHP_EOL;
            foreach ($servers as $id => $server) {
                $connection_html .= '<option value="'.$id.'" '.$server['selected'].'>';
                $connection_html .= htmlspecialchars("{$server['desc']} ({$server['id']})");
                $connection_html .= '</option>'.PHP_EOL;
            }
            $connection_html .= '</select>'.PHP_EOL;
        }

        $connection_html .= '</td><td class="popup_select2" style="text-align: right">'.PHP_EOL;

        if (count($servers) === 1 &&
            isset($servers[$server_id]['useonlydefaultdb']) &&
            $servers[$server_id]['useonlydefaultdb'] === true
        ) {
            $connection_html .= '<input type="hidden" name="database" value="'.htmlspecialchars($servers[$server_id]['defaultdb']).'" />'.PHP_EOL;
        } else {
            // Get the list of all databases
            $data      = $this->misc->getDatabaseAccessor();
            $databases = $data->getDatabases();
            if ($databases->recordCount() > 0) {
                $connection_html .= '<label>';
                $connection_html .= $this->misc->printHelp($this->lang['strdatabase'], 'pg.database', false);
                $connection_html .= ": <select  id='selectdb'  name=\"database\" >".PHP_EOL;

                //if no database was selected, user should select one
                if (!isset($_REQUEST['database'])) {
                    $connection_html .= '<option value="">--</option>'.PHP_EOL;
                }

                while (!$databases->EOF) {
                    $dbname     = $databases->fields['datname'];
                    $dbselected = isset($_REQUEST['database']) && $dbname == $_REQUEST['database'] ? ' selected="selected"' : '';
                    $connection_html .= '<option value="'.htmlspecialchars($dbname).'" '.$dbselected.'>'.htmlspecialchars($dbname).'</option>'.PHP_EOL;

                    $databases->moveNext();
                }
                $connection_html .= '</select></label>'.PHP_EOL;
            } else {
                $server_info = $this->misc->getServerInfo();
                $connection_html .= '<input type="hidden" name="database" value="'.htmlspecialchars($server_info['defaultdb']).'" />'.PHP_EOL;
            }
        }

        $connection_html .= '</td></tr></table>'.PHP_EOL;

        if ($do_print) {
            echo $connection_html;
        } else {
            return $connection_html;
        }
    }
}
