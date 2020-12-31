<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\ArrayRecordSet;
use PHPPgAdmin\Decorators\Decorator;
use PHPPgAdmin\Traits\ServersTrait;

/**
 * Base controller class.
 */
class ServersController extends BaseController
{
    use ServersTrait;

    public $table_place = 'servers-servers';

    public $section = 'servers';

    public $query = '';

    public $subject = '';

    public $start_time;

    public $duration;

    public $controller_title = 'strservers';

    protected $no_db_connection = true;

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if ('tree' === $this->action) {
            return $this->doTree();
        }

        $msg = $this->msg;

        $server_html = $this->printHeader($this->headerTitle(), null, false);
        $server_html .= $this->printBody(false,'flexbox_body',false,true);
        $server_html .= $this->printTrail('root', false);

        \ob_start();

        switch ($this->action) {
            case 'logout':
                $this->doLogout();

                break;

            default:
                $this->doDefault($msg);

                break;
        }

        $server_html .= \ob_get_clean();

        $server_html .= $this->printFooter(false);

        if (null === \requestInstance()->getAttribute('route')) {
            echo $server_html;
        } else {
            $body = \responseInstance()->getBody();
            $body->write($server_html);

            return \responseInstance();
        }
    }

    public function doDefault($msg = ''): void
    {
        $this->printTabs('root', 'servers');
        $this->printMsg($msg);
        $group = $_GET['group'] ?? false;

        $groups = $this->getServersGroups(true, $group);
        $columns = [
            'group' => [
                'title' => $this->lang['strgroup'],
                'field' => Decorator::field('desc'),
                'url' => 'servers?',
                'vars' => ['group' => 'id'],
            ],
        ];
        $actions = [];

        if ((false !== $group) &&
            (isset($this->conf['srv_groups'][$group])) &&
            (0 < $groups->RecordCount())
        ) {
            $this->printTitle(\sprintf($this->lang['strgroupgroups'], \htmlentities($this->conf['srv_groups'][$group]['desc'], \ENT_QUOTES, 'UTF-8')));
            echo $this->printTable($groups, $columns, $actions, $this->table_place);
        }

        $servers = $this->getServers(true, $group);

        $columns = [
            'server' => [
                'title' => $this->lang['strserver'],
                'field' => Decorator::field('desc'),
                'url' => \containerInstance()->subFolder . '/redirect/server?',
                'vars' => ['server' => 'sha'],
            ],
            'host' => [
                'title' => $this->lang['strhost'],
                'field' => Decorator::field('host'),
            ],
            'port' => [
                'title' => $this->lang['strport'],
                'field' => Decorator::field('port'),
            ],
            'username' => [
                'title' => $this->lang['strusername'],
                'field' => Decorator::field('username'),
            ],
            'actions' => [
                'title' => $this->lang['stractions'],
            ],
        ];

        $actions = [
            'logout' => [
                'content' => $this->lang['strlogout'],
                'attr' => [
                    'href' => [
                        'url' => 'servers',
                        'urlvars' => [
                            'action' => 'logout',
                            'logoutServer' => Decorator::field('sha'),
                        ],
                    ],
                ],
            ],
        ];
        //\sha1("{$server_info['host']}:{$server_info['port']}:{$server_info['sslmode']}")
        $svPre = static function (&$rowdata) use ($actions) {
            $actions['logout']['disable'] = empty($rowdata->fields['username']);

            return $actions;
        };

        if ((false !== $group) &&
            isset($this->conf['srv_groups'][$group])
        ) {
            $this->printTitle(\sprintf($this->lang['strgroupservers'], \htmlentities($this->conf['srv_groups'][$group]['desc'], \ENT_QUOTES, 'UTF-8')), null);
            $actions['logout']['attr']['href']['urlvars']['group'] = $group;
        }
        echo $this->printTable($servers, $columns, $actions, $this->table_place, $this->lang['strnoobjects'], $svPre);
    }

    /**
     * @return \Slim\Http\Response|string
     */
    public function doTree()
    {
        $nodes = [];
        $group_id = $_GET['group'] ?? false;

        // root with srv_groups
        if (isset($this->conf['srv_groups']) && 0 < \count($this->conf['srv_groups'])
            && false === $group_id) {
            $nodes = $this->getServersGroups(true);
        } elseif (isset($this->conf['srv_groups']) && false !== $group_id) {
            // group subtree
            if ('all' !== $group_id) {
                $nodes = $this->getServersGroups(false, $group_id);
            }

            $nodes = \array_merge($nodes, $this->getServers(false, $group_id));
            $nodes = new ArrayRecordSet($nodes);
        } else {
            // no srv_group
            $nodes = $this->getServers(true, false);
        }

        //$reqvars = $this->misc->getRequestVars('server');

        $attrs = [
            'text' => Decorator::field('desc'),
            // Show different icons for logged in/out
            'icon' => Decorator::field('icon'),
            'toolTip' => Decorator::field('id'),
            'action' => Decorator::field('action'),
            // Only create a branch url if the user has
            // logged into the server.
            'branch' => Decorator::field('branch'),
        ];

        return $this->printTree($nodes, $attrs, $this->section);
    }

    public function doLogout(): void
    {
        $server_info = $this->misc->getServerInfo($_REQUEST['logoutServer']);
        $this->misc->setServerInfo(null, null, $_REQUEST['logoutServer']);

        unset($_SESSION['sharedUsername'], $_SESSION['sharedPassword']);

        $this->view->setReloadBrowser(true);

        echo \sprintf(
            $this->lang['strlogoutmsg'],
            $server_info['desc']
        );
    }

    /**
     * Get list of server groups.
     *
     * @param bool  $recordset return as RecordSet suitable for HTMLTableController::printTable if true, otherwise just return an array
     * @param mixed $group_id  a group name to filter the returned servers using $this->conf[srv_groups]
     *
     * @return array|ArrayRecordSet either an array or a Recordset suitable for HTMLTableController::printTable
     */
    private function getServersGroups($recordset = false, $group_id = false)
    {
        $grps = [];

        if (isset($this->conf['srv_groups'])) {
            foreach ($this->conf['srv_groups'] as $i => $group) {
                if (((false === $group_id) && (!isset($group['parents']))) ||
                    (false !== $group_id) &&
                    isset($group['parents']) &&
                    \in_array(
                        $group_id,
                        \explode(
                            ',',
                            \preg_replace('/\s/', '', $group['parents'])
                        ),
                        true
                    )
                ) {
                    $grps[$i] = [
                        'id' => $i,
                        'desc' => $group['desc'],
                        'icon' => 'Servers',
                        'action' => Decorator::url(
                            'servers',
                            [
                                'group' => Decorator::field('id'),
                            ]
                        ),
                        'branch' => Decorator::url(
                            'servers',
                            [
                                'action' => 'tree',
                                'group' => $i,
                            ]
                        ),
                    ];
                }
            }

            if (false === $group_id) {
                $grps['all'] = [
                    'id' => 'all',
                    'desc' => $this->lang['strallservers'],
                    'icon' => 'Servers',
                    'action' => Decorator::url(
                        'servers',
                        [
                            'group' => Decorator::field('id'),
                        ]
                    ),
                    'branch' => Decorator::url(
                        'servers',
                        [
                            'action' => 'tree',
                            'group' => 'all',
                        ]
                    ),
                ];
            }
        }

        if ($recordset) {
            return new ArrayRecordSet($grps);
        }

        return $grps;
    }
}
