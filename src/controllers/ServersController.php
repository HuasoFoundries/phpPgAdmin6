<?php

namespace PHPPgAdmin\Controller;

use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class ServersController extends BaseController
{
    public $_name       = 'ServersController';
    public $table_place = 'servers-servers';
    public $section     = 'servers';
    public $query       = '';
    public $subject     = '';
    public $start_time  = null;
    public $duration    = null;

    public function render()
    {
        $conf = $this->conf;
        $misc = $this->misc;
        $lang = $this->lang;

        $action = $this->action;

        if ($action == 'tree') {
            return $this->doTree();
        }

        $msg = $this->msg;

        $server_html = $this->printHeader($this->lang['strservers'], null, false);
        $server_html .= $this->printBody(false);
        $server_html .= $this->printTrail('root', false);

        ob_start();
        switch ($action) {
            case 'logout':
                $this->doLogout();

                break;
            default:
                $this->doDefault($msg);
                break;
        }

        $server_html .= ob_get_clean();

        $server_html .= $this->printFooter(false);

        if ($this->container->requestobj->getAttribute('route') === null) {
            echo $server_html;
        } else {
            $body = $this->container->responseobj->getBody();
            $body->write($server_html);
            return $this->container->responseobj;
        }

    }

    public function doDefault($msg = '')
    {

        $lang = $this->lang;
        $conf = $this->conf;
        $misc = $this->misc;
        $data = $misc->getDatabaseAccessor();

        $this->printTabs('root', 'servers');
        $this->printMsg($msg);
        $group = isset($_GET['group']) ? $_GET['group'] : false;

        $groups  = $misc->getServersGroups(true, $group);
        $columns = [
            'group' => [
                'title' => $lang['strgroup'],
                'field' => Decorator::field('desc'),
                'url'   => 'servers.php?',
                'vars'  => ['group' => 'id'],
            ],
        ];
        $actions = [];
        if (($group !== false) && (isset($conf['srv_groups'][$group])) && ($groups->recordCount() > 0)) {
            $this->printTitle(sprintf($lang['strgroupgroups'], htmlentities($conf['srv_groups'][$group]['desc'], ENT_QUOTES, 'UTF-8')));
        }
        $this->printTable($groups, $columns, $actions, $this->table_place);
        $servers = $misc->getServers(true, $group);

        $columns = [
            'server'   => [
                'title' => $lang['strserver'],
                'field' => Decorator::field('desc'),
                'url'   => SUBFOLDER . '/redirect/server?',
                'vars'  => ['server' => 'id'],
            ],
            'host'     => [
                'title' => $lang['strhost'],
                'field' => Decorator::field('host'),
            ],
            'port'     => [
                'title' => $lang['strport'],
                'field' => Decorator::field('port'),
            ],
            'username' => [
                'title' => $lang['strusername'],
                'field' => Decorator::field('username'),
            ],
            'actions'  => [
                'title' => $lang['stractions'],
            ],
        ];

        $actions = [
            'logout' => [
                'content' => $lang['strlogout'],
                'attr'    => [
                    'href' => [
                        'url'     => 'servers.php',
                        'urlvars' => [
                            'action'       => 'logout',
                            'logoutServer' => Decorator::field('id'),
                        ],
                    ],
                ],
            ],
        ];

        $svPre = function (&$rowdata) use ($actions) {
            $actions['logout']['disable'] = empty($rowdata->fields['username']);
            return $actions;
        };

        if (($group !== false) and isset($conf['srv_groups'][$group])) {
            $this->printTitle(sprintf($lang['strgroupservers'], htmlentities($conf['srv_groups'][$group]['desc'], ENT_QUOTES, 'UTF-8')), null);
            $actions['logout']['attr']['href']['urlvars']['group'] = $group;
        }
        echo $this->printTable($servers, $columns, $actions, $this->table_place, $lang['strnoobjects'], $svPre);

    }

    public function doTree()
    {

        $conf = $this->conf;
        $misc = $this->misc;

        $nodes    = [];
        $group_id = isset($_GET['group']) ? $_GET['group'] : false;

        /* root with srv_groups */
        if (isset($conf['srv_groups']) and count($conf['srv_groups']) > 0
            and $group_id === false) {
            $nodes = $misc->getServersGroups(true);
        } else if (isset($conf['srv_groups']) and $group_id !== false) {
            /* group subtree */
            if ($group_id !== 'all') {
                $nodes = $misc->getServersGroups(false, $group_id);
            }

            $nodes = array_merge($nodes, $misc->getServers(false, $group_id));
            $nodes = new \PHPPgAdmin\ArrayRecordSet($nodes);
        } else {
            /* no srv_group */
            $nodes = $misc->getServers(true, false);
        }

        $reqvars = $misc->getRequestVars('server');

        //$this->prtrace($reqvars);

        $attrs = [
            'text'    => Decorator::field('desc'),
            // Show different icons for logged in/out
            'icon'    => Decorator::field('icon'),
            'toolTip' => Decorator::field('id'),
            'action'  => Decorator::field('action'),
            // Only create a branch url if the user has
            // logged into the server.
            'branch'  => Decorator::field('branch'),
        ];
        /*$this->prtrace([
        'nodes'   => $nodes,
        'attrs'   => $attrs,
        'section' => $this->section,
        ]);*/
        return $this->printTree($nodes, $attrs, $this->section);

    }

    public function doLogout()
    {

        $plugin_manager = $this->plugin_manager;
        $lang           = $this->lang;
        $misc           = $this->misc;
        $conf           = $this->conf;
        $data           = $misc->getDatabaseAccessor();

        $plugin_manager->do_hook('logout', $_REQUEST['logoutServer']);

        $server_info = $misc->getServerInfo($_REQUEST['logoutServer']);
        $misc->setServerInfo(null, null, $_REQUEST['logoutServer']);

        unset($_SESSION['sharedUsername'], $_SESSION['sharedPassword']);

        $misc->setReloadBrowser(true);

        echo sprintf($lang['strlogoutmsg'], $server_info['desc']);

    }

}
