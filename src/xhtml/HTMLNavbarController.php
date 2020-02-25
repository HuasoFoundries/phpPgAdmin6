<?php

/**
 * PHPPgAdmin v6.0.0-RC8
 */

namespace PHPPgAdmin\XHtml;

/**
 * Class to render tables. Formerly part of Misc.php.
 */
class HTMLNavbarController extends HTMLController
{
    public $controller_name = 'HTMLNavbarController';

    private function _getCrumbs($trail)
    {
        $crumbs = [];
        foreach ($trail as $crumb_id => $crumb) {
            if (isset($crumb['url'])) {
                $crumbs[$crumb_id]['url'] = str_replace('&amp;', '&', $crumb['url']);
            }

            if (isset($crumb['title'])) {
                $crumbs[$crumb_id]['title']   = $crumb['title'];
                $crumbs[$crumb_id]['iconalt'] = $crumb['title'];
            } else {
                $crumbs[$crumb_id]['iconalt'] = 'Database Root';
            }

            if (isset($crumb['icon']) && $icon = $this->misc->icon($crumb['icon'])) {
                $crumbs[$crumb_id]['icon'] = $icon;
            }

            $crumbs[$crumb_id]['text'] = $crumb['text'];

            if (isset($crumb['help'])) {
                $crumbs[$crumb_id]['helpurl'] = str_replace('&amp;', '&', $this->misc->getHelpLink($crumb['help']));
            }
        }

        return $crumbs;
    }

    /**
     * @param array $viewVars
     */
    private function _getSearchPathsCrumbs($crumbs, array $viewVars)
    {
        $data = $this->misc->getDatabaseAccessor();
        $lang = $this->lang;
        if (isset($crumbs['database'])) {
            $search_path_crumbs = [];
            $dburl              = $crumbs['database']['url'];
            $search_paths       = $data->getSearchPath();
            foreach ($search_paths as $schema) {
                $url         = str_replace(['&amp;', 'redirect/database'], ['&', 'redirect/schema'], $dburl.'&schema='.$schema);
                $destination = $this->container->utils->getDestinationWithLastTab('database');
                //$this->dump(['url' => $url, 'destination' => $destination]);
                $search_path_crumbs[$schema] = [
                    'title'   => $lang['strschema'],
                    'text'    => $schema,
                    'icon'    => $this->misc->icon('Schema'),
                    'iconalt' => $lang['strschema'],
                    'url'     => $url,
                ];
            }
            $viewVars['search_paths'] = $search_path_crumbs;
        }

        return $viewVars;
    }

    /**
     * Display a bread crumb trail.
     *
     * @param array|string $trail an array of breadcrumb items, or a string to identify one of them
     * @param  $do_print true to echo, false to return html
     * @param null|string $from
     */
    public function printTrail($trail = [], $do_print = true, $from = null)
    {
        $from = $from ? $from : __METHOD__;

        $trail_html = $this->printTopbar(false, $from);

        if (is_string($trail)) {
            $subject = $trail;
            $trail   = $this->_getTrail($subject);
            // Trail hook's place
            $plugin_functions_parameters = [
                'trail'   => &$trail,
                'section' => $subject,
            ];
        }

        $crumbs = $this->_getCrumbs($trail);

        $viewVars = [
            'crumbs'          => $crumbs,
            'controller_name' => $this->controller_name,
        ];
        $viewVars = $this->_getSearchPathsCrumbs($crumbs, $viewVars);

        //\Kint::dump($viewVars);

        $trail_html .= $this->getContainer()->view->fetch('components/trail.twig', $viewVars);

        if ($do_print) {
            echo $trail_html;
        } else {
            return $trail_html;
        }
    }

    /**
     * Display navigation tabs.
     *
     * @param string      $alltabs   The name of current section (Ex: intro, server, ...),
     *                               or an array with tabs (Ex: sqledit::doFind function)
     * @param string      $activetab the name of the tab to be highlighted
     * @param bool        $do_print  true to print html, false to return html
     * @param null|string $from      whichi method is calling this one
     */
    public function printTabs($alltabs, $activetab, $do_print = true, $from = null)
    {
        $from = $from ? $from : __METHOD__;

        $this->misc = $this->misc;

        if (is_string($alltabs)) {
            $_SESSION['webdbLastTab'][$alltabs] = $activetab;
            $alltabs                            = $this->misc->getNavTabs($alltabs);
        }

        //$this->prtrace($tabs);
        $tabs_html = '';

        //Getting only visible tabs
        $tabs = [];
        if (count($alltabs) > 0) {
            foreach ($alltabs as $tab_id => $tab) {
                if (!isset($tab['hide']) || true !== $tab['hide']) {
                    $tabs[$tab_id]            = $tab;
                    $tabs[$tab_id]['active']  = ($tab_id == $activetab) ? ' active' : '';
                    $tabs[$tab_id]['tablink'] = str_replace(['&amp;', '.php'], ['&', ''], htmlentities($this->getActionUrl($tab, $_REQUEST, $from)));
                    //$this->prtrace('link for ' . $tab_id, $tabs[$tab_id]['tablink']);
                    if (isset($tab['icon']) && $icon = $this->misc->icon($tab['icon'])) {
                        $tabs[$tab_id]['iconurl'] = $icon;
                    }
                    if (isset($tab['help'])) {
                        $tabs[$tab_id]['helpurl'] = str_replace('&amp;', '&', $this->misc->getHelpLink($tab['help']));
                    }
                }
            }
        }

        //$this->prtrace($tabs);

        if (count($tabs) > 0) {
            $width = (int) (100 / count($tabs)).'%';

            $viewVars = [
                'width'           => $width,
                'tabs'            => $tabs,
                'controller_name' => $this->controller_name,
            ];

            $tabs_html = $this->getContainer()->view->fetch('components/tabs.twig', $viewVars);
        }

        if ($do_print) {
            echo $tabs_html;
        } else {
            return $tabs_html;
        }
    }

    /**
     * [printTopbar description].
     *
     * @param bool       $do_print true to print, false to return html
     * @param null|mixed $from     which method is calling this one
     *
     * @return null|string
     */
    private function printTopbar($do_print = true, $from = null): ?string
    {
        $from = $from ? $from : __METHOD__;

        $lang = $this->lang;

        $this->misc = $this->misc;
        $appName    = $this->misc->appName;
        $appVersion = $this->misc->appVersion;

        $server_info = $this->misc->getServerInfo();
        $server_id   = $this->misc->getServerId();
        $reqvars     = $this->misc->getRequestVars('table');

        $topbar_html = '<div class="topbar" data-controller="'.$this->controller_name.'"><table style="width: 100%"><tr><td>';

        if ($server_info && isset($server_info['platform'], $server_info['username'])) {
            // top left informations when connected
            $topbar_html .= sprintf(
                $lang['strtopbar'],
                '<span class="platform">'.htmlspecialchars($server_info['platform']).'</span>',
                '<span class="host">'.htmlspecialchars((empty($server_info['host'])) ? 'localhost' : $server_info['host']).'</span>',
                '<span class="port">'.htmlspecialchars($server_info['port']).'</span>',
                '<span class="username">'.htmlspecialchars($server_info['username']).'</span>'
            );

            $topbar_html .= '</td>';

            // top right informations when connected

            $toplinks = [
                'sql'     => [
                    'attr'    => [
                        'href'   => [
                            'url'     => SUBFOLDER.'/src/views/sqledit',
                            'urlvars' => array_merge($reqvars, [
                                'action' => 'sql',
                            ]),
                        ],
                        'target' => 'sqledit',
                        'id'     => 'toplink_sql',
                        'class'  => 'toplink_popup',
                    ],
                    'content' => $lang['strsql'],
                ],
                'history' => [
                    'attr'    => [
                        'href'  => [
                            'url'     => SUBFOLDER.'/src/views/history',
                            'urlvars' => array_merge($reqvars, [
                                'action' => 'pophistory',
                            ]),
                        ],
                        'id'    => 'toplink_history',
                        'class' => 'toplink_popup',
                    ],
                    'content' => $lang['strhistory'],
                ],
                'find'    => [
                    'attr'    => [
                        'href'   => [
                            'url'     => SUBFOLDER.'/src/views/sqledit',
                            'urlvars' => array_merge($reqvars, [
                                'action' => 'find',
                            ]),
                        ],
                        'target' => 'sqledit',
                        'id'     => 'toplink_find',
                        'class'  => 'toplink_popup',
                    ],
                    'content' => $lang['strfind'],
                ],
                'logout'  => [
                    'attr'    => [
                        'href' => [
                            'url'     => SUBFOLDER.'/src/views/servers',
                            'urlvars' => [
                                'action'       => 'logout',
                                'logoutServer' => sha1("{$server_info['host']}:{$server_info['port']}:{$server_info['sslmode']}"),
                            ],
                        ],
                        'id'   => 'toplink_logout',
                    ],
                    'content' => $lang['strlogout'],
                ],
            ];

            // Toplink hook's place
            $plugin_functions_parameters = [
                'toplinks' => &$toplinks,
            ];

            $topbar_html .= '<td style="text-align: right">';

            $topbar_html .= $this->printLinksList($toplinks, 'toplink', false, $from);

            $topbar_html .= '</td>';
        } else {
            $topbar_html .= "<span class=\"appname\">{$appName}</span> <span class=\"version\">{$appVersion}</span>";
        }

        $topbar_html .= '</tr></table></div>'.PHP_EOL;

        if ($do_print) {
            echo $topbar_html;
        } else {
            return $topbar_html;
        }
    }

    private function getHREFSubject(string $subject)
    {
        $vars = $this->misc->getSubjectParams($subject);
        ksort($vars['params']);

        return "{$vars['url']}?".http_build_query($vars['params'], '', '&amp;');
    }

    /**
     * Create a bread crumb trail of the object hierarchy.
     *
     * @param null|string $subject sunkect of the trail
     *
     * @return array the trail array
     */
    private function _getTrail($subject = null)
    {
        $lang = $this->lang;

        $appName = $this->misc->appName;

        $trail = [];

        $trail['root'] = [
            'text' => $appName,
            'url'  => SUBFOLDER.'/src/views/servers',
            'icon' => 'Introduction',
        ];

        if ('root' == $subject) {
            return $trail;
        }

        $server_info     = $this->misc->getServerInfo();
        $trail['server'] = [
            'title' => $lang['strserver'],
            'text'  => $server_info['desc'],
            'url'   => $this->getHREFSubject('server'),
            'help'  => 'pg.server',
            'icon'  => 'Server',
        ];

        if ('server' == $subject) {
            return $trail;
        }

        $database_rolename = [
            'database' => [
                'title'   => $lang['strdatabase'],
                'subject' => 'database',
                'help'    => 'pg.database',
                'icon'    => 'Database',
            ],
            'rolename' => [
                'title'   => $lang['strrole'],
                'subject' => 'role',
                'help'    => 'pg.role',
                'icon'    => 'Roles',
            ],
        ];

        $trail = $this->_getTrailsFromArray($trail, $database_rolename);

        if (in_array($subject, ['database', 'role'], true)) {
            return $trail;
        }

        $schema = [
            'schema' => [
                'title'   => $lang['strschema'],
                'subject' => 'schema',
                'help'    => 'pg.schema',
                'icon'    => 'Schema',
            ],
        ];

        $trail = $this->_getTrailsFromArray($trail, $schema);
        if ('schema' == $subject) {
            return $trail;
        }

        $table_view_matview_fts = [
            'table'   => [
                'title'   => $lang['strtable'],
                'subject' => 'table',
                'help'    => 'pg.table',
                'icon'    => 'Table',
            ],
            'view'    => [
                'title'   => $lang['strview'],
                'subject' => 'view',
                'help'    => 'pg.view',
                'icon'    => 'View',
            ],
            'matview' => [
                'title'   => 'M'.$lang['strview'],
                'subject' => 'matview',
                'help'    => 'pg.matview',
                'icon'    => 'MViews',
            ],
            'ftscfg'  => [
                'title'   => $lang['strftsconfig'],
                'subject' => 'ftscfg',
                'help'    => 'pg.ftscfg.example',
                'icon'    => 'Fts',
            ],
        ];

        $trail = $this->_getTrailsFromArray($trail, $table_view_matview_fts);

        if (in_array($subject, ['table', 'view', 'matview', 'ftscfg'], true)) {
            return $trail;
        }

        if (!is_null($subject)) {
            $trail = $this->_getLastTrailPart($subject, $trail);
        }

        //$this->prtrace($trail);

        return $trail;
    }

    /**
     * @param (mixed|string)[][] $trail
     * @param (mixed|string)[][] $the_array
     */
    private function _getTrailsFromArray(array $trail, array $the_array)
    {
        foreach ($the_array as $key => $value) {
            if (isset($_REQUEST[$key])) {
                $trail[$key] = [
                    'title' => $value['title'],
                    'text'  => $_REQUEST[$key],
                    'url'   => $this->getHREFSubject($value['subject']),
                    'help'  => $value['help'],
                    'icon'  => $value['icon'],
                ];

                break;
            }
        }

        return $trail;
    }

    private function _getLastTrailPart(string $subject, $trail)
    {
        $lang = $this->lang;

        switch ($subject) {
            case 'function':
                $trail[$subject] = [
                    'title' => $lang['str'.$subject],
                    'text'  => $_REQUEST[$subject],
                    'url'   => $this->getHREFSubject('function'),
                    'help'  => 'pg.function',
                    'icon'  => 'Function',
                ];

                break;
            case 'aggregate':
                $trail[$subject] = [
                    'title' => $lang['straggregate'],
                    'text'  => $_REQUEST['aggrname'],
                    'url'   => $this->getHREFSubject('aggregate'),
                    'help'  => 'pg.aggregate',
                    'icon'  => 'Aggregate',
                ];

                break;
            case 'column':
                $trail['column'] = [
                    'title' => $lang['strcolumn'],
                    'text'  => $_REQUEST['column'],
                    'icon'  => 'Column',
                    'url'   => $this->getHREFSubject('column'),
                ];

                break;
            default:
                if (isset($_REQUEST[$subject])) {
                    switch ($subject) {
                        case 'domain':
                            $icon = 'Domain';

                            break;
                        case 'sequence':
                            $icon = 'Sequence';

                            break;
                        case 'type':
                            $icon = 'Type';

                            break;
                        case 'operator':
                            $icon = 'Operator';

                            break;
                        case 'index':
                            $icon = 'Index';

                            break;
                        default:
                            $icon = null;

                            break;
                    }
                    $trail[$subject] = [
                        'title' => array_key_exists('str'.$subject, $lang) ? $lang['str'.$subject] : $subject,
                        'text'  => $_REQUEST[$subject],
                        'help'  => 'pg.'.$subject,
                        'icon'  => $icon,
                    ];
                }
        }

        return $trail;
    }
}
