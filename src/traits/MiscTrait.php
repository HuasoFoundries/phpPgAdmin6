<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
 */

namespace PHPPgAdmin\Traits;

use PHPPgAdmin\Decorators\Decorator;

/**
 * @file
 * A trait to deal with nav tabs
 */

/**
 * A trait to deal with nav tabs.
 *
 * @package PHPPgAdmin
 */
trait MiscTrait
{
    public function getSubjectParams($subject)
    {
        $plugin_manager = $this->plugin_manager;

        $vars = [];

        switch ($subject) {
            case 'root':
                $vars = [
                    'params' => [
                        'subject' => 'root',
                    ],
                ];

                break;
            case 'server':
                $vars = ['params' => [
                    'server'  => $_REQUEST['server'],
                    'subject' => 'server',
                ]];

                break;
            case 'role':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'role',
                    'action'   => 'properties',
                    'rolename' => $_REQUEST['rolename'],
                ]];

                break;
            case 'database':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'database',
                    'database' => $_REQUEST['database'],
                ]];

                break;
            case 'schema':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'schema',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                ]];

                break;
            case 'table':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'table',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'table'    => $_REQUEST['table'],
                ]];

                break;
            case 'selectrows':
                $vars = [
                    'url'    => 'tables',
                    'params' => [
                        'server'   => $_REQUEST['server'],
                        'subject'  => 'table',
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'table'    => $_REQUEST['table'],
                        'action'   => 'confselectrows',
                    ], ];

                break;
            case 'view':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'view',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'view'     => $_REQUEST['view'],
                ]];

                break;
            case 'matview':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'matview',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'matview'  => $_REQUEST['matview'],
                ]];

                break;
            case 'fulltext':
            case 'ftscfg':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'fulltext',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'action'   => 'viewconfig',
                    'ftscfg'   => $_REQUEST['ftscfg'],
                ]];

                break;
            case 'function':
                $vars = ['params' => [
                    'server'       => $_REQUEST['server'],
                    'subject'      => 'function',
                    'database'     => $_REQUEST['database'],
                    'schema'       => $_REQUEST['schema'],
                    'function'     => $_REQUEST['function'],
                    'function_oid' => $_REQUEST['function_oid'],
                ]];

                break;
            case 'aggregate':
                $vars = ['params' => [
                    'server'   => $_REQUEST['server'],
                    'subject'  => 'aggregate',
                    'action'   => 'properties',
                    'database' => $_REQUEST['database'],
                    'schema'   => $_REQUEST['schema'],
                    'aggrname' => $_REQUEST['aggrname'],
                    'aggrtype' => $_REQUEST['aggrtype'],
                ]];

                break;
            case 'column':
                if (isset($_REQUEST['table'])) {
                    $vars = ['params' => [
                        'server'   => $_REQUEST['server'],
                        'subject'  => 'column',
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'table'    => $_REQUEST['table'],
                        'column'   => $_REQUEST['column'],
                    ]];
                } elseif (isset($_REQUEST['view'])) {
                    $vars = ['params' => [
                        'server'   => $_REQUEST['server'],
                        'subject'  => 'column',
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'view'     => $_REQUEST['view'],
                        'column'   => $_REQUEST['column'],
                    ]];
                } elseif (isset($_REQUEST['matview'])) {
                    $vars = ['params' => [
                        'server'   => $_REQUEST['server'],
                        'subject'  => 'column',
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'matview'  => $_REQUEST['matview'],
                        'column'   => $_REQUEST['column'],
                    ]];
                }

                break;
            case 'plugin':
                $vars = [
                    'url'    => 'plugin',
                    'params' => [
                        'server'  => $_REQUEST['server'],
                        'subject' => 'plugin',
                        'plugin'  => $_REQUEST['plugin'],
                    ], ];

                if (!is_null($plugin_manager->getPlugin($_REQUEST['plugin']))) {
                    $vars['params'] = array_merge($vars['params'], $plugin_manager->getPlugin($_REQUEST['plugin'])->get_subject_params());
                }

                break;
            default:
                return false;
        }

        if (!isset($vars['url'])) {
            $vars['url'] = SUBFOLDER.'/redirect';
        }
        if ($vars['url'] == SUBFOLDER.'/redirect' && isset($vars['params']['subject'])) {
            $vars['url'] = SUBFOLDER.'/redirect/'.$vars['params']['subject'];
            unset($vars['params']['subject']);
        }

        return $vars;
    }

    /**
     * Render a value into HTML using formatting rules specified
     * by a type name and parameters.
     *
     * @param null|string $str    The string to change
     * @param string      $type   Field type (optional), this may be an internal PostgreSQL type, or:
     *                            yesno    - same as bool, but renders as 'Yes' or 'No'.
     *                            pre      - render in a <pre> block.
     *                            nbsp     - replace all spaces with &nbsp;'s
     *                            verbatim - render exactly as supplied, no escaping what-so-ever.
     *                            callback - render using a callback function supplied in the 'function' param.
     * @param array       $params Type parameters (optional), known parameters:
     *                            null     - string to display if $str is null, or set to TRUE to use a default 'NULL' string,
     *                            otherwise nothing is rendered.
     *                            clip     - if true, clip the value to a fixed length, and append an ellipsis...
     *                            cliplen  - the maximum length when clip is enabled (defaults to $conf['max_chars'])
     *                            ellipsis - the string to append to a clipped value (defaults to $lang['strellipsis'])
     *                            tag      - an HTML element name to surround the value.
     *                            class    - a class attribute to apply to any surrounding HTML element.
     *                            align    - an align attribute ('left','right','center' etc.)
     *                            true     - (type='bool') the representation of true.
     *                            false    - (type='bool') the representation of false.
     *                            function - (type='callback') a function name, accepts args ($str, $params) and returns a rendering.
     *                            lineno   - prefix each line with a line number.
     *                            map      - an associative array.
     *
     * @return string The HTML rendered value
     */
    public function printVal($str, $type = null, $params = [])
    {
        $lang = $this->lang;
        $data = $this->getDatabaseAccessor();

        // Shortcircuit for a NULL value
        if (is_null($str)) {
            return isset($params['null'])
            ? ($params['null'] === true ? '<i>NULL</i>' : $params['null'])
            : '';
        }

        if (isset($params['map'], $params['map'][$str])) {
            $str = $params['map'][$str];
        }

        // Clip the value if the 'clip' parameter is true.
        if (isset($params['clip']) && $params['clip'] === true) {
            $maxlen   = isset($params['cliplen']) && is_integer($params['cliplen']) ? $params['cliplen'] : $this->conf['max_chars'];
            $ellipsis = isset($params['ellipsis']) ? $params['ellipsis'] : $lang['strellipsis'];
            if (strlen($str) > $maxlen) {
                $str = substr($str, 0, $maxlen - 1).$ellipsis;
            }
        }

        $out   = '';
        $class = '';

        switch ($type) {
            case 'int2':
            case 'int4':
            case 'int8':
            case 'float4':
            case 'float8':
            case 'money':
            case 'numeric':
            case 'oid':
            case 'xid':
            case 'cid':
            case 'tid':
                $align = 'right';
                $out   = nl2br(htmlspecialchars(\PHPPgAdmin\Traits\HelperTrait::br2ln($str)));

                break;
            case 'yesno':
                if (!isset($params['true'])) {
                    $params['true'] = $lang['stryes'];
                }

                if (!isset($params['false'])) {
                    $params['false'] = $lang['strno'];
                }

            // no break - fall through to boolean case.
            case 'bool':
            case 'boolean':
                if (is_bool($str)) {
                    $str = $str ? 't' : 'f';
                }

                switch ($str) {
                    case 't':
                        $out   = (isset($params['true']) ? $params['true'] : $lang['strtrue']);
                        $align = 'center';

                        break;
                    case 'f':
                        $out   = (isset($params['false']) ? $params['false'] : $lang['strfalse']);
                        $align = 'center';

                        break;
                    default:
                        $out = htmlspecialchars($str);
                }

                break;
            case 'bytea':
                $tag   = 'div';
                $class = 'pre';
                $out   = $data->escapeBytea($str);

                break;
            case 'errormsg':
                $tag   = 'pre';
                $class = 'error';
                $out   = htmlspecialchars($str);

                break;
            case 'pre':
                $tag = 'pre';
                $out = htmlspecialchars($str);

                break;
            case 'prenoescape':
                $tag = 'pre';
                $out = $str;

                break;
            case 'nbsp':
                $out = nl2br(str_replace(' ', '&nbsp;', \PHPPgAdmin\Traits\HelperTrait::br2ln($str)));

                break;
            case 'verbatim':
                $out = $str;

                break;
            case 'callback':
                $out = $params['function']($str, $params);

                break;
            case 'prettysize':
                if ($str == -1) {
                    $out = $lang['strnoaccess'];
                } else {
                    $out = \PHPPgAdmin\Traits\HelperTrait::formatSizeUnits($str, $lang);
                }

                break;
            default:
                // If the string contains at least one instance of >1 space in a row, a tab
                // character, a space at the start of a line, or a space at the start of
                // the whole string then render within a pre-formatted element (<pre>).
                if (preg_match('/(^ |  |\t|\n )/m', $str)) {
                    $tag   = 'pre';
                    $class = 'data';
                    $out   = htmlspecialchars($str);
                } else {
                    //$tag = 'span';
                    $out = nl2br(htmlspecialchars(\PHPPgAdmin\Traits\HelperTrait::br2ln($str)));
                }
        }

        if (isset($params['class'])) {
            $class = $params['class'];
        }

        if (isset($params['align'])) {
            $align = $params['align'];
        }

        if (!isset($tag) && (!empty($class) || isset($align))) {
            $tag = 'div';
        }

        if (isset($tag)) {
            $alignattr = isset($align) ? " style=\"text-align: {$align}\"" : '';
            $classattr = !empty($class) ? " class=\"{$class}\"" : '';
            $out       = "<{$tag}{$alignattr}{$classattr}>{$out}</{$tag}>";
        }

        // Add line numbers if 'lineno' param is true
        if (isset($params['lineno']) && $params['lineno'] === true) {
            $lines = explode("\n", $str);
            $num   = count($lines);
            if ($num > 0) {
                $temp = "<table>\n<tr><td class=\"{$class}\" style=\"vertical-align: top; padding-right: 10px;\"><pre class=\"{$class}\">";
                for ($i = 1; $i <= $num; ++$i) {
                    $temp .= $i."\n";
                }
                $temp .= "</pre></td><td class=\"{$class}\" style=\"vertical-align: top;\">{$out}</td></tr></table>\n";
                $out = $temp;
            }
            unset($lines);
        }

        return $out;
    }

    /**
     * Retrieve the tab info for a specific tab bar.
     *
     * @param string $section the name of the tab bar
     *
     * @return array array of tabs
     */
    public function getNavTabs($section)
    {
        $data           = $this->getDatabaseAccessor();
        $lang           = $this->lang;
        $plugin_manager = $this->plugin_manager;

        $hide_advanced = ($this->conf['show_advanced'] === false);
        $tabs          = [];

        switch ($section) {
            case 'root':
                $tabs = [
                    'intro'   => [
                        'title' => $lang['strintroduction'],
                        'url'   => 'intro',
                        'icon'  => 'Introduction',
                    ],
                    'servers' => [
                        'title' => $lang['strservers'],
                        'url'   => 'servers',
                        'icon'  => 'Servers',
                    ],
                ];

                break;
            case 'server':
                $hide_users = true;
                $hide_roles = false;
                if ($data) {
                    $hide_users = !$data->isSuperUser();
                }

                $tabs = [
                    'databases' => [
                        'title'   => $lang['strdatabases'],
                        'url'     => 'alldb',
                        'urlvars' => ['subject' => 'server'],
                        'help'    => 'pg.database',
                        'icon'    => 'Databases',
                    ],
                ];
                if ($data && $data->hasRoles()) {
                    $tabs = array_merge($tabs, [
                        'users' => [
                            'title'   => $lang['strusers'],
                            'url'     => 'users',
                            'urlvars' => ['subject' => 'server'],
                            'hide'    => $hide_roles,
                            'help'    => 'pg.user',
                            'icon'    => 'Users',
                        ],
                        'roles' => [
                            'title'   => $lang['strroles'],
                            'url'     => 'roles',
                            'urlvars' => ['subject' => 'server'],
                            'hide'    => $hide_roles,
                            'help'    => 'pg.role',
                            'icon'    => 'Roles',
                        ],
                    ]);
                } else {
                    $tabs = array_merge($tabs, [
                        'users'  => [
                            'title'   => $lang['strusers'],
                            'url'     => 'users',
                            'urlvars' => ['subject' => 'server'],
                            'hide'    => $hide_users,
                            'help'    => 'pg.user',
                            'icon'    => 'Users',
                        ],
                        'groups' => [
                            'title'   => $lang['strgroups'],
                            'url'     => 'groups',
                            'urlvars' => ['subject' => 'server'],
                            'hide'    => $hide_users,
                            'help'    => 'pg.group',
                            'icon'    => 'UserGroups',
                        ],
                    ]);
                }

                $tabs = array_merge($tabs, [
                    'account'     => [
                        'title'   => $lang['straccount'],
                        'url'     => ($data && $data->hasRoles()) ? 'roles' : 'users',
                        'urlvars' => ['subject' => 'server', 'action' => 'account'],
                        'hide'    => !$hide_users,
                        'help'    => 'pg.role',
                        'icon'    => 'User',
                    ],
                    'tablespaces' => [
                        'title'   => $lang['strtablespaces'],
                        'url'     => 'tablespaces',
                        'urlvars' => ['subject' => 'server'],
                        'hide'    => !$data || !$data->hasTablespaces(),
                        'help'    => 'pg.tablespace',
                        'icon'    => 'Tablespaces',
                    ],
                    'export'      => [
                        'title'   => $lang['strexport'],
                        'url'     => 'alldb',
                        'urlvars' => ['subject' => 'server', 'action' => 'export'],
                        'hide'    => !$this->isDumpEnabled(),
                        'icon'    => 'Export',
                    ],
                ]);

                break;
            case 'database':
                $tabs = [
                    'schemas'    => [
                        'title'   => $lang['strschemas'],
                        'url'     => 'schemas',
                        'urlvars' => ['subject' => 'database'],
                        'help'    => 'pg.schema',
                        'icon'    => 'Schemas',
                    ],
                    'sql'        => [
                        'title'   => $lang['strsql'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'sql', 'new' => 1],
                        'help'    => 'pg.sql',
                        'tree'    => false,
                        'icon'    => 'SqlEditor',
                    ],
                    'find'       => [
                        'title'   => $lang['strfind'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'find'],
                        'tree'    => false,
                        'icon'    => 'Search',
                    ],
                    'variables'  => [
                        'title'   => $lang['strvariables'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'variables'],
                        'help'    => 'pg.variable',
                        'tree'    => false,
                        'icon'    => 'Variables',
                    ],
                    'processes'  => [
                        'title'   => $lang['strprocesses'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'processes'],
                        'help'    => 'pg.process',
                        'tree'    => false,
                        'icon'    => 'Processes',
                    ],
                    'locks'      => [
                        'title'   => $lang['strlocks'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'locks'],
                        'help'    => 'pg.locks',
                        'tree'    => false,
                        'icon'    => 'Key',
                    ],
                    'admin'      => [
                        'title'   => $lang['stradmin'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'admin'],
                        'tree'    => false,
                        'icon'    => 'Admin',
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'database'],
                        'hide'    => !isset($data->privlist['database']),
                        'help'    => 'pg.privilege',
                        'tree'    => false,
                        'icon'    => 'Privileges',
                    ],
                    'languages'  => [
                        'title'   => $lang['strlanguages'],
                        'url'     => 'languages',
                        'urlvars' => ['subject' => 'database'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.language',
                        'icon'    => 'Languages',
                    ],
                    'casts'      => [
                        'title'   => $lang['strcasts'],
                        'url'     => 'casts',
                        'urlvars' => ['subject' => 'database'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.cast',
                        'icon'    => 'Casts',
                    ],
                    'export'     => [
                        'title'   => $lang['strexport'],
                        'url'     => 'database',
                        'urlvars' => ['subject' => 'database', 'action' => 'export'],
                        'hide'    => !$this->isDumpEnabled(),
                        'tree'    => false,
                        'icon'    => 'Export',
                    ],
                ];

                break;
            case 'schema':
                $tabs = [
                    'tables'      => [
                        'title'   => $lang['strtables'],
                        'url'     => 'tables',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.table',
                        'icon'    => 'Tables',
                    ],
                    'views'       => [
                        'title'   => $lang['strviews'],
                        'url'     => 'views',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.view',
                        'icon'    => 'Views',
                    ],
                    'matviews'    => [
                        'title'   => 'M '.$lang['strviews'],
                        'url'     => 'materializedviews',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.matview',
                        'icon'    => 'MViews',
                    ],
                    'sequences'   => [
                        'title'   => $lang['strsequences'],
                        'url'     => 'sequences',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.sequence',
                        'icon'    => 'Sequences',
                    ],
                    'functions'   => [
                        'title'   => $lang['strfunctions'],
                        'url'     => 'functions',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.function',
                        'icon'    => 'Functions',
                    ],
                    'fulltext'    => [
                        'title'   => $lang['strfulltext'],
                        'url'     => 'fulltext',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.fts',
                        'tree'    => true,
                        'icon'    => 'Fts',
                    ],
                    'domains'     => [
                        'title'   => $lang['strdomains'],
                        'url'     => 'domains',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.domain',
                        'icon'    => 'Domains',
                    ],
                    'aggregates'  => [
                        'title'   => $lang['straggregates'],
                        'url'     => 'aggregates',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.aggregate',
                        'icon'    => 'Aggregates',
                    ],
                    'types'       => [
                        'title'   => $lang['strtypes'],
                        'url'     => 'types',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.type',
                        'icon'    => 'Types',
                    ],
                    'operators'   => [
                        'title'   => $lang['stroperators'],
                        'url'     => 'operators',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.operator',
                        'icon'    => 'Operators',
                    ],
                    'opclasses'   => [
                        'title'   => $lang['stropclasses'],
                        'url'     => 'opclasses',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.opclass',
                        'icon'    => 'OperatorClasses',
                    ],
                    'conversions' => [
                        'title'   => $lang['strconversions'],
                        'url'     => 'conversions',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => $hide_advanced,
                        'help'    => 'pg.conversion',
                        'icon'    => 'Conversions',
                    ],
                    'privileges'  => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'schema'],
                        'help'    => 'pg.privilege',
                        'tree'    => false,
                        'icon'    => 'Privileges',
                    ],
                    'export'      => [
                        'title'   => $lang['strexport'],
                        'url'     => 'schemas',
                        'urlvars' => ['subject' => 'schema', 'action' => 'export'],
                        'hide'    => !$this->isDumpEnabled(),
                        'tree'    => false,
                        'icon'    => 'Export',
                    ],
                ];
                if (!$data->hasFTS()) {
                    unset($tabs['fulltext']);
                }

                break;
            case 'table':
                $tabs = [
                    'columns'     => [
                        'title'   => $lang['strcolumns'],
                        'url'     => 'tblproperties',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'icon'    => 'Columns',
                        'branch'  => true,
                    ],
                    'browse'      => [
                        'title'   => $lang['strbrowse'],
                        'icon'    => 'Columns',
                        'url'     => 'display',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'return'  => 'table',
                        'branch'  => true,
                    ],
                    'select'      => [
                        'title'   => $lang['strselect'],
                        'icon'    => 'Search',
                        'url'     => 'tables',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'confselectrows'],
                        'help'    => 'pg.sql.select',
                    ],
                    'insert'      => [
                        'title'   => $lang['strinsert'],
                        'url'     => 'tables',
                        'urlvars' => [
                            'action' => 'confinsertrow',
                            'table'  => Decorator::field('table'),
                        ],
                        'help'    => 'pg.sql.insert',
                        'icon'    => 'Operator',
                    ],
                    'indexes'     => [
                        'title'   => $lang['strindexes'],
                        'url'     => 'indexes',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.index',
                        'icon'    => 'Indexes',
                        'branch'  => true,
                    ],
                    'constraints' => [
                        'title'   => $lang['strconstraints'],
                        'url'     => 'constraints',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.constraint',
                        'icon'    => 'Constraints',
                        'branch'  => true,
                    ],
                    'triggers'    => [
                        'title'   => $lang['strtriggers'],
                        'url'     => 'triggers',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.trigger',
                        'icon'    => 'Triggers',
                        'branch'  => true,
                    ],
                    'rules'       => [
                        'title'   => $lang['strrules'],
                        'url'     => 'rules',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.rule',
                        'icon'    => 'Rules',
                        'branch'  => true,
                    ],
                    'admin'       => [
                        'title'   => $lang['stradmin'],
                        'url'     => 'tables',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'admin'],
                        'icon'    => 'Admin',
                    ],
                    'info'        => [
                        'title'   => $lang['strinfo'],
                        'url'     => 'info',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'icon'    => 'Statistics',
                    ],
                    'privileges'  => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                        'help'    => 'pg.privilege',
                        'icon'    => 'Privileges',
                    ],
                    'import'      => [
                        'title'   => $lang['strimport'],
                        'url'     => 'tblproperties',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'import'],
                        'icon'    => 'Import',
                        'hide'    => false,
                    ],
                    'export'      => [
                        'title'   => $lang['strexport'],
                        'url'     => 'tblproperties',
                        'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'export'],
                        'icon'    => 'Export',
                        'hide'    => false,
                    ],
                ];

                break;
            case 'view':
                $tabs = [
                    'columns'    => [
                        'title'   => $lang['strcolumns'],
                        'url'     => 'viewproperties',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                        'icon'    => 'Columns',
                        'branch'  => true,
                    ],
                    'browse'     => [
                        'title'   => $lang['strbrowse'],
                        'icon'    => 'Columns',
                        'url'     => 'display',
                        'urlvars' => [
                            'action'  => 'confselectrows',
                            'return'  => 'schema',
                            'subject' => 'view',
                            'view'    => Decorator::field('view'),
                        ],
                        'branch'  => true,
                    ],
                    'select'     => [
                        'title'   => $lang['strselect'],
                        'icon'    => 'Search',
                        'url'     => 'views',
                        'urlvars' => ['action' => 'confselectrows', 'view' => Decorator::field('view')],
                        'help'    => 'pg.sql.select',
                    ],
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'viewproperties',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view'), 'action' => 'definition'],
                        'icon'    => 'Definition',
                    ],
                    'rules'      => [
                        'title'   => $lang['strrules'],
                        'url'     => 'rules',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                        'help'    => 'pg.rule',
                        'icon'    => 'Rules',
                        'branch'  => true,
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                        'help'    => 'pg.privilege',
                        'icon'    => 'Privileges',
                    ],
                    'export'     => [
                        'title'   => $lang['strexport'],
                        'url'     => 'viewproperties',
                        'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view'), 'action' => 'export'],
                        'icon'    => 'Export',
                        'hide'    => false,
                    ],
                ];

                break;
            case 'matview':
                $tabs = [
                    'columns'    => [
                        'title'   => $lang['strcolumns'],
                        'url'     => 'materializedviewproperties',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                        'icon'    => 'Columns',
                        'branch'  => true,
                    ],
                    'browse'     => [
                        'title'   => $lang['strbrowse'],
                        'icon'    => 'Columns',
                        'url'     => 'display',
                        'urlvars' => [
                            'action'  => 'confselectrows',
                            'return'  => 'schema',
                            'subject' => 'matview',
                            'matview' => Decorator::field('matview'),
                        ],
                        'branch'  => true,
                    ],
                    'select'     => [
                        'title'   => $lang['strselect'],
                        'icon'    => 'Search',
                        'url'     => 'materializedviews',
                        'urlvars' => ['action' => 'confselectrows', 'matview' => Decorator::field('matview')],
                        'help'    => 'pg.sql.select',
                    ],
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'materializedviewproperties',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview'), 'action' => 'definition'],
                        'icon'    => 'Definition',
                    ],
                    'indexes'    => [
                        'title'   => $lang['strindexes'],
                        'url'     => 'indexes',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                        'help'    => 'pg.index',
                        'icon'    => 'Indexes',
                        'branch'  => true,
                    ],
                    /*'constraints' => [
                    'title' => $lang['strconstraints'],
                    'url' => 'constraints',
                    'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                    'help' => 'pg.constraint',
                    'icon' => 'Constraints',
                    'branch' => true,
                     */

                    'rules'      => [
                        'title'   => $lang['strrules'],
                        'url'     => 'rules',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                        'help'    => 'pg.rule',
                        'icon'    => 'Rules',
                        'branch'  => true,
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                        'help'    => 'pg.privilege',
                        'icon'    => 'Privileges',
                    ],
                    'export'     => [
                        'title'   => $lang['strexport'],
                        'url'     => 'materializedviewproperties',
                        'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview'), 'action' => 'export'],
                        'icon'    => 'Export',
                        'hide'    => false,
                    ],
                ];

                break;
            case 'function':
                $tabs = [
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'functions',
                        'urlvars' => [
                            'subject'      => 'function',
                            'function'     => Decorator::field('function'),
                            'function_oid' => Decorator::field('function_oid'),
                            'action'       => 'properties',
                        ],
                        'icon'    => 'Definition',
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => [
                            'subject'      => 'function',
                            'function'     => Decorator::field('function'),
                            'function_oid' => Decorator::field('function_oid'),
                        ],
                        'icon'    => 'Privileges',
                    ],
                ];

                break;
            case 'aggregate':
                $tabs = [
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'aggregates',
                        'urlvars' => [
                            'subject'  => 'aggregate',
                            'aggrname' => Decorator::field('aggrname'),
                            'aggrtype' => Decorator::field('aggrtype'),
                            'action'   => 'properties',
                        ],
                        'icon'    => 'Definition',
                    ],
                ];

                break;
            case 'role':
                $tabs = [
                    'definition' => [
                        'title'   => $lang['strdefinition'],
                        'url'     => 'roles',
                        'urlvars' => [
                            'subject'  => 'role',
                            'rolename' => Decorator::field('rolename'),
                            'action'   => 'properties',
                        ],
                        'icon'    => 'Definition',
                    ],
                ];

                break;
            case 'popup':
                $tabs = [
                    'sql'  => [
                        'title'   => $lang['strsql'],
                        'url'     => \SUBFOLDER.'/src/views/sqledit',
                        'urlvars' => ['action' => 'sql', 'subject' => 'schema'],
                        'help'    => 'pg.sql',
                        'icon'    => 'SqlEditor',
                    ],
                    'find' => [
                        'title'   => $lang['strfind'],
                        'url'     => \SUBFOLDER.'/src/views/sqledit',
                        'urlvars' => ['action' => 'find', 'subject' => 'schema'],
                        'icon'    => 'Search',
                    ],
                ];

                break;
            case 'column':
                $tabs = [
                    'properties' => [
                        'title'   => $lang['strcolprop'],
                        'url'     => 'colproperties',
                        'urlvars' => [
                            'subject' => 'column',
                            'table'   => Decorator::field('table'),
                            'view'    => Decorator::field('view'),
                            'column'  => Decorator::field('column'),
                        ],
                        'icon'    => 'Column',
                    ],
                    'privileges' => [
                        'title'   => $lang['strprivileges'],
                        'url'     => 'privileges',
                        'urlvars' => [
                            'subject' => 'column',
                            'table'   => Decorator::field('table'),
                            'view'    => Decorator::field('view'),
                            'column'  => Decorator::field('column'),
                        ],
                        'help'    => 'pg.privilege',
                        'icon'    => 'Privileges',
                    ],
                ];
                if (empty($tabs['properties']['urlvars']['table'])) {
                    unset($tabs['properties']['urlvars']['table']);
                }
                if (empty($tabs['privileges']['urlvars']['table'])) {
                    unset($tabs['privileges']['urlvars']['table']);
                }

                break;
            case 'fulltext':
                $tabs = [
                    'ftsconfigs' => [
                        'title'   => $lang['strftstabconfigs'],
                        'url'     => 'fulltext',
                        'urlvars' => ['subject' => 'schema'],
                        'hide'    => !$data->hasFTS(),
                        'help'    => 'pg.ftscfg',
                        'tree'    => true,
                        'icon'    => 'FtsCfg',
                    ],
                    'ftsdicts'   => [
                        'title'   => $lang['strftstabdicts'],
                        'url'     => 'fulltext',
                        'urlvars' => ['subject' => 'schema', 'action' => 'viewdicts'],
                        'hide'    => !$data->hasFTS(),
                        'help'    => 'pg.ftsdict',
                        'tree'    => true,
                        'icon'    => 'FtsDict',
                    ],
                    'ftsparsers' => [
                        'title'   => $lang['strftstabparsers'],
                        'url'     => 'fulltext',
                        'urlvars' => ['subject' => 'schema', 'action' => 'viewparsers'],
                        'hide'    => !$data->hasFTS(),
                        'help'    => 'pg.ftsparser',
                        'tree'    => true,
                        'icon'    => 'FtsParser',
                    ],
                ];

                break;
        }

        // Tabs hook's place
        $plugin_functions_parameters = [
            'tabs'    => &$tabs,
            'section' => $section,
        ];
        $plugin_manager->doHook('tabs', $plugin_functions_parameters);

        return $tabs;
    }

    /**
     * Get the URL for the last active tab of a particular tab bar.
     *
     * @param string $section
     *
     * @return null|mixed
     */
    public function getLastTabURL($section)
    {
        //$data = $this->getDatabaseAccessor();

        $tabs = $this->getNavTabs($section);
        if (isset($_SESSION['webdbLastTab'][$section], $tabs[$_SESSION['webdbLastTab'][$section]])) {
            $tab = $tabs[$_SESSION['webdbLastTab'][$section]];
        } else {
            $tab = reset($tabs);
        }
        $this->prtrace(['section' => $section, 'tabs' => $tabs, 'tab' => $tab]);

        return isset($tab['url']) ? $tab : null;
    }

    abstract public function getDatabaseAccessor($database = '', $server_id = null);

    abstract public function isDumpEnabled($all = false);

    abstract public function prtrace();
}
