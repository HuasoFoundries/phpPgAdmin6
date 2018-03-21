<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class ServersController extends BaseController
{
    public $controller_name = 'ServersController';
    public $table_place     = 'servers-servers';
    public $section         = 'servers';
    public $query           = '';
    public $subject         = '';
    public $start_time;
    public $duration;

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang = $this->lang;

        $action = $this->action;

        if ('tree' == $action) {
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

        if (null === $this->container->requestobj->getAttribute('route')) {
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

        $this->printTabs('root', 'servers');
        $this->printMsg($msg);
        $group = isset($_GET['group']) ? $_GET['group'] : false;

        $groups  = $this->misc->getServersGroups(true, $group);
        $columns = [
            'group' => [
                'title' => $lang['strgroup'],
                'field' => Decorator::field('desc'),
                'url'   => 'servers?',
                'vars'  => ['group' => 'id'],
            ],
        ];
        $actions = [];
        if ((false !== $group) && (isset($this->conf['srv_groups'][$group])) && ($groups->recordCount() > 0)) {
            $this->printTitle(sprintf($lang['strgroupgroups'], htmlentities($this->conf['srv_groups'][$group]['desc'], ENT_QUOTES, 'UTF-8')));
        }
        $this->printTable($groups, $columns, $actions, $this->table_place);
        $servers = $this->misc->getServers(true, $group);

        $columns = [
            'server'   => [
                'title' => $lang['strserver'],
                'field' => Decorator::field('desc'),
                'url'   => \SUBFOLDER . '/redirect/server?',
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
                        'url'     => 'servers',
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

        if ((false !== $group) and isset($this->conf['srv_groups'][$group])) {
            $this->printTitle(sprintf($lang['strgroupservers'], htmlentities($this->conf['srv_groups'][$group]['desc'], ENT_QUOTES, 'UTF-8')), null);
            $actions['logout']['attr']['href']['urlvars']['group'] = $group;
        }
        echo $this->printTable($servers, $columns, $actions, $this->table_place, $lang['strnoobjects'], $svPre);
    }

    public function doTree()
    {
        $nodes    = [];
        $group_id = isset($_GET['group']) ? $_GET['group'] : false;

        // root with srv_groups
        if (isset($this->conf['srv_groups']) and count($this->conf['srv_groups']) > 0
            and false === $group_id) {
            $nodes = $this->misc->getServersGroups(true);
        } elseif (isset($this->conf['srv_groups']) and false !== $group_id) {
            // group subtree
            if ('all' !== $group_id) {
                $nodes = $this->misc->getServersGroups(false, $group_id);
            }

            $nodes = array_merge($nodes, $this->misc->getServers(false, $group_id));
            $nodes = new \PHPPgAdmin\ArrayRecordSet($nodes);
        } else {
            // no srv_group
            $nodes = $this->misc->getServers(true, false);
        }

        $reqvars = $this->misc->getRequestVars('server');

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
        $this->misc     = $this->misc;
        $conf           = $this->conf;
        $data           = $this->misc->getDatabaseAccessor();

        $plugin_manager->do_hook('logout', $_REQUEST['logoutServer']);

        $server_info = $this->misc->getServerInfo($_REQUEST['logoutServer']);
        $this->misc->setServerInfo(null, null, $_REQUEST['logoutServer']);

        unset($_SESSION['sharedUsername'], $_SESSION['sharedPassword']);

        $this->misc->setReloadBrowser(true);

        echo sprintf($lang['strlogoutmsg'], $server_info['desc']);
    }
}
