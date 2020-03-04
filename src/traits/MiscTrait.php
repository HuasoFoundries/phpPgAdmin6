<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
 */

namespace PHPPgAdmin\Traits;

use PHPPgAdmin\Decorators\Decorator;

/**
 * @file
 * A trait to deal with nav tabs
 */

/**
 * A trait to deal with nav tabs.
 */
trait MiscTrait
{
    public function getSubjectParams($subject)
    {
        $vars = [];
        $common_params = [];

        if (\array_key_exists('server', $_REQUEST)) {
            $common_params['server'] = $_REQUEST['server'];
        }

        if (\array_key_exists('database', $_REQUEST)) {
            $common_params['database'] = $_REQUEST['database'];
        }

        if (\array_key_exists('schema', $_REQUEST)) {
            $common_params['schema'] = $_REQUEST['schema'];
        }

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
                    'subject' => 'server',
                    'server' => $_REQUEST['server'],
                ]];

                break;
            case 'role':
                $vars = ['params' => [
                    'subject' => 'role',
                    'server' => $_REQUEST['server'],
                    'action' => 'properties',
                    'rolename' => $_REQUEST['rolename'],
                ]];

                break;
            case 'database':
                $vars = ['params' => \array_merge($common_params, [
                    'subject' => 'database',
                ])];

                break;
            case 'schema':
                $vars = ['params' => \array_merge($common_params, [
                    'subject' => 'schema',
                ])];

                break;
            case 'table':
                $vars = ['params' => \array_merge($common_params, [
                    'subject' => 'table',

                    'table' => $_REQUEST['table'],
                ])];

                break;
            case 'selectrows':
                $vars = [
                    'url' => 'tables',
                    'params' => \array_merge($common_params, [
                        'subject' => 'table',
                        'table' => $_REQUEST['table'],
                        'action' => 'confselectrows',
                    ]), ];

                break;
            case 'view':
                $vars = ['params' => \array_merge($common_params, [
                    'subject' => 'view',
                    'view' => $_REQUEST['view'],
                ])];

                break;
            case 'matview':
                $vars = ['params' => \array_merge($common_params, [
                    'subject' => 'matview',
                    'matview' => $_REQUEST['matview'],
                ])];

                break;
            case 'fulltext':
            case 'ftscfg':
                $vars = ['params' => \array_merge($common_params, [
                    'subject' => 'fulltext',
                    'action' => 'viewconfig',
                    'ftscfg' => $_REQUEST['ftscfg'],
                ])];

                break;
            case 'function':
                $vars = ['params' => \array_merge($common_params, [
                    'subject' => 'function',
                    'function' => $_REQUEST['function'],
                    'function_oid' => $_REQUEST['function_oid'],
                ])];

                break;
            case 'aggregate':
                $vars = ['params' => \array_merge($common_params, [
                    'subject' => 'aggregate',
                    'action' => 'properties',
                    'aggrname' => $_REQUEST['aggrname'],
                    'aggrtype' => $_REQUEST['aggrtype'],
                ])];

                break;
            case 'column':
                if (isset($_REQUEST['table'])) {
                    $vars = ['params' => \array_merge($common_params, [
                        'subject' => 'column',

                        'table' => $_REQUEST['table'],
                        'column' => $_REQUEST['column'],
                    ])];
                } elseif (isset($_REQUEST['view'])) {
                    $vars = ['params' => \array_merge($common_params, [
                        'subject' => 'column',

                        'view' => $_REQUEST['view'],
                        'column' => $_REQUEST['column'],
                    ])];
                } elseif (isset($_REQUEST['matview'])) {
                    $vars = ['params' => \array_merge($common_params, [
                        'subject' => 'column',

                        'matview' => $_REQUEST['matview'],
                        'column' => $_REQUEST['column'],
                    ])];
                }

                break;

            default:
                return false;
        }

        if (!isset($vars['url'])) {
            $vars['url'] = SUBFOLDER . '/redirect';
        }

        if (SUBFOLDER . '/redirect' === $vars['url'] && isset($vars['params']['subject'])) {
            $vars['url'] = SUBFOLDER . '/redirect/' . $vars['params']['subject'];
            unset($vars['params']['subject']);
        }

        return $vars;
    }

    public function maybeClipStr($str, $params)
    {
        if (isset($params['map'], $params['map'][$str])) {
            $str = $params['map'][$str];
        }
        // Clip the value if the 'clip' parameter is true.
        if (!isset($params['clip']) || true !== $params['clip']) {
            return $str;
        }
        $maxlen = isset($params['cliplen']) && \is_int($params['cliplen']) ? $params['cliplen'] : $this->conf['max_chars'];
        $ellipsis = $params['ellipsis'] ?? $this->lang['strellipsis'];

        if (\mb_strlen($str) > $maxlen) {
            $str = \mb_substr($str, 0, $maxlen - 1) . $ellipsis;
        }

        return $str;
    }

    public function printBoolean($type, &$str, $params)
    {
        $lang = $this->lang;

        if ('yesno' === $type) {
            $this->coalesceArr($params, 'true', $lang['stryes']);
            $this->coalesceArr($params, 'false', $lang['strno']);
        }

        if (\is_bool($str)) {
            $str = $str ? 't' : 'f';
        }

        switch ($str) {
            case 't':
                $out = ($params['true'] ?? $lang['strtrue']);
                $align = 'center';

                break;
            case 'f':
                $out = ($params['false'] ?? $lang['strfalse']);
                $align = 'center';

                break;

            default:
                $align = null;
                $out = \htmlspecialchars($str);
        }

        return [$str, $align, $out];
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

        if (null === $this->_data) {
            $data = $this->getDatabaseAccessor();
        } else {
            $data = $this->_data;
        }

        // Shortcircuit for a NULL value
        if (null === $str) {
            return isset($params['null'])
            ? (true === $params['null'] ? '<i>NULL</i>' : $params['null'])
            : '';
        }

        $str = $this->maybeClipStr($str, $params);

        $out = '';
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
                $out = \nl2br(\htmlspecialchars(\PHPPgAdmin\Traits\HelperTrait::br2ln($str)));

                break;
            case 'yesno':
            case 'bool':
            case 'boolean':
                [$str, $align, $out] = $this->printBoolean($type, $str, $params);

                break;
            case 'bytea':
                $tag = 'div';
                $class = 'pre';
                $out = $data->escapeBytea($str);

                break;
            case 'errormsg':
                $tag = 'pre';
                $class = 'error';
                $out = \htmlspecialchars($str);

                break;
            case 'pre':
                $tag = 'pre';
                $out = \htmlspecialchars($str);

                break;
            case 'prenoescape':
                $tag = 'pre';
                $out = $str;

                break;
            case 'nbsp':
                $out = \nl2br(\str_replace(' ', '&nbsp;', \PHPPgAdmin\Traits\HelperTrait::br2ln($str)));

                break;
            case 'verbatim':
                $out = $str;

                break;
            case 'callback':
                $out = $params['function']($str, $params);

                break;
            case 'prettysize':
                $out = \PHPPgAdmin\Traits\HelperTrait::formatSizeUnits($str, $lang);

                break;

            default:
                // If the string contains at least one instance of >1 space in a row, a tab
                // character, a space at the start of a line, or a space at the start of
                // the whole string then render within a pre-formatted element (<pre>).
                if (\preg_match('/(^ |  |\t|\n )/m', $str)) {
                    $tag = 'pre';
                    $class = 'data';
                    $out = \htmlspecialchars($str);
                } else {
                    //$tag = 'span';
                    $out = \nl2br(\htmlspecialchars(\PHPPgAdmin\Traits\HelperTrait::br2ln($str)));
                }
        }

        $this->adjustClassAlignTag($class, $align, $tag, $out, $params);

        return $out;
    }

    public function adjustClassAlignTag(&$class, &$align, &$tag, &$out, $params): void
    {
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
            $out = "<{$tag}{$alignattr}{$classattr}>{$out}</{$tag}>";
        }
    }

    /**
     * Gets the tabs for root view.
     *
     * @param \PHPPgAdmin\Database\ADOdbBase $data The database accesor instance
     *
     * @return array The tabs for root view
     */
    public function getTabsRoot($data)
    {
        $lang = $this->lang;

        return [
            'intro' => [
                'title' => $lang['strintroduction'],
                'url' => 'intro',
                'icon' => 'Introduction',
            ],
            'servers' => [
                'title' => $lang['strservers'],
                'url' => 'servers',
                'icon' => 'Servers',
            ],
        ];
    }

    /**
     * Gets the tabs for server view.
     *
     * @param \PHPPgAdmin\Database\ADOdbBase $data The database accesor instance
     *
     * @return array The tabs for server view
     */
    public function getTabsServer($data)
    {
        $lang = $this->lang;
        $hide_users = true;
        $hide_roles = false;

        if ($data) {
            $hide_users = !$data->isSuperUser();
        }

        $tabs = [
            'databases' => [
                'title' => $lang['strdatabases'],
                'url' => 'alldb',
                'urlvars' => ['subject' => 'server'],
                'help' => 'pg.database',
                'icon' => 'Databases',
            ],
            'users' => [
                'title' => $lang['strusers'],
                'url' => 'users',
                'urlvars' => ['subject' => 'server'],
                'hide' => $hide_roles,
                'help' => 'pg.user',
                'icon' => 'Users',
            ],
        ];

        if ($data && $data->hasRoles()) {
            $tabs = \array_merge($tabs, [
                'roles' => [
                    'title' => $lang['strroles'],
                    'url' => 'roles',
                    'urlvars' => ['subject' => 'server'],
                    'hide' => $hide_roles,
                    'help' => 'pg.role',
                    'icon' => 'Roles',
                ],
            ]);
        } else {
            $tabs = \array_merge($tabs, [
                'groups' => [
                    'title' => $lang['strgroups'],
                    'url' => 'groups',
                    'urlvars' => ['subject' => 'server'],
                    'hide' => $hide_users,
                    'help' => 'pg.group',
                    'icon' => 'UserGroups',
                ],
            ]);
        }

        return \array_merge($tabs, [
            'account' => [
                'title' => $lang['straccount'],
                'url' => ($data && $data->hasRoles()) ? 'roles' : 'users',
                'urlvars' => ['subject' => 'server', 'action' => 'account'],
                'hide' => !$hide_users,
                'help' => 'pg.role',
                'icon' => 'User',
            ],
            'tablespaces' => [
                'title' => $lang['strtablespaces'],
                'url' => 'tablespaces',
                'urlvars' => ['subject' => 'server'],
                'hide' => !$data || !$data->hasTablespaces(),
                'help' => 'pg.tablespace',
                'icon' => 'Tablespaces',
            ],
            'export' => [
                'title' => $lang['strexport'],
                'url' => 'alldb',
                'urlvars' => ['subject' => 'server', 'action' => 'export'],
                'hide' => !$this->isDumpEnabled(),
                'icon' => 'Export',
            ],
        ]);
    }

    /**
     * Gets the tabs for database view.
     *
     * @param \PHPPgAdmin\Database\ADOdbBase $data The database accesor instance
     *
     * @return array The tabs for database view
     */
    public function getTabsDatabase($data)
    {
        $lang = $this->lang;
        $hide_advanced = (false === $this->conf['show_advanced']);

        return [
            'schemas' => [
                'title' => $lang['strschemas'],
                'url' => 'schemas',
                'urlvars' => ['subject' => 'database'],
                'help' => 'pg.schema',
                'icon' => 'Schemas',
            ],
            'sql' => [
                'title' => $lang['strsql'],
                'url' => 'database',
                'urlvars' => ['subject' => 'database', 'action' => 'sql', 'new' => 1],
                'help' => 'pg.sql',
                'tree' => false,
                'icon' => 'SqlEditor',
            ],
            'find' => [
                'title' => $lang['strfind'],
                'url' => 'database',
                'urlvars' => ['subject' => 'database', 'action' => 'find'],
                'tree' => false,
                'icon' => 'Search',
            ],
            'variables' => [
                'title' => $lang['strvariables'],
                'url' => 'database',
                'urlvars' => ['subject' => 'database', 'action' => 'variables'],
                'help' => 'pg.variable',
                'tree' => false,
                'icon' => 'Variables',
            ],
            'processes' => [
                'title' => $lang['strprocesses'],
                'url' => 'database',
                'urlvars' => ['subject' => 'database', 'action' => 'processes'],
                'help' => 'pg.process',
                'tree' => false,
                'icon' => 'Processes',
            ],
            'locks' => [
                'title' => $lang['strlocks'],
                'url' => 'database',
                'urlvars' => ['subject' => 'database', 'action' => 'locks'],
                'help' => 'pg.locks',
                'tree' => false,
                'icon' => 'Key',
            ],
            'admin' => [
                'title' => $lang['stradmin'],
                'url' => 'database',
                'urlvars' => ['subject' => 'database', 'action' => 'admin'],
                'tree' => false,
                'icon' => 'Admin',
            ],
            'privileges' => [
                'title' => $lang['strprivileges'],
                'url' => 'privileges',
                'urlvars' => ['subject' => 'database'],
                'hide' => !isset($data->privlist['database']),
                'help' => 'pg.privilege',
                'tree' => false,
                'icon' => 'Privileges',
            ],
            'languages' => [
                'title' => $lang['strlanguages'],
                'url' => 'languages',
                'urlvars' => ['subject' => 'database'],
                'hide' => $hide_advanced,
                'help' => 'pg.language',
                'icon' => 'Languages',
            ],
            'casts' => [
                'title' => $lang['strcasts'],
                'url' => 'casts',
                'urlvars' => ['subject' => 'database'],
                'hide' => $hide_advanced,
                'help' => 'pg.cast',
                'icon' => 'Casts',
            ],
            'export' => [
                'title' => $lang['strexport'],
                'url' => 'database',
                'urlvars' => ['subject' => 'database', 'action' => 'export'],
                'hide' => !$this->isDumpEnabled(),
                'tree' => false,
                'icon' => 'Export',
            ],
        ];
    }

    public function getTabsSchema($data)
    {
        $lang = $this->lang;
        $hide_advanced = (false === $this->conf['show_advanced']);
        $tabs = [
            'tables' => [
                'title' => $lang['strtables'],
                'url' => 'tables',
                'urlvars' => ['subject' => 'schema'],
                'help' => 'pg.table',
                'icon' => 'Tables',
            ],
            'views' => [
                'title' => $lang['strviews'],
                'url' => 'views',
                'urlvars' => ['subject' => 'schema'],
                'help' => 'pg.view',
                'icon' => 'Views',
            ],
            'matviews' => [
                'title' => 'M ' . $lang['strviews'],
                'url' => 'materializedviews',
                'urlvars' => ['subject' => 'schema'],
                'help' => 'pg.matview',
                'icon' => 'MViews',
            ],
            'sequences' => [
                'title' => $lang['strsequences'],
                'url' => 'sequences',
                'urlvars' => ['subject' => 'schema'],
                'help' => 'pg.sequence',
                'icon' => 'Sequences',
            ],
            'functions' => [
                'title' => $lang['strfunctions'],
                'url' => 'functions',
                'urlvars' => ['subject' => 'schema'],
                'help' => 'pg.function',
                'icon' => 'Functions',
            ],
            'fulltext' => [
                'title' => $lang['strfulltext'],
                'url' => 'fulltext',
                'urlvars' => ['subject' => 'schema'],
                'help' => 'pg.fts',
                'tree' => true,
                'icon' => 'Fts',
            ],
            'domains' => [
                'title' => $lang['strdomains'],
                'url' => 'domains',
                'urlvars' => ['subject' => 'schema'],
                'help' => 'pg.domain',
                'icon' => 'Domains',
            ],
            'aggregates' => [
                'title' => $lang['straggregates'],
                'url' => 'aggregates',
                'urlvars' => ['subject' => 'schema'],
                'hide' => $hide_advanced,
                'help' => 'pg.aggregate',
                'icon' => 'Aggregates',
            ],
            'types' => [
                'title' => $lang['strtypes'],
                'url' => 'types',
                'urlvars' => ['subject' => 'schema'],
                'hide' => $hide_advanced,
                'help' => 'pg.type',
                'icon' => 'Types',
            ],
            'operators' => [
                'title' => $lang['stroperators'],
                'url' => 'operators',
                'urlvars' => ['subject' => 'schema'],
                'hide' => $hide_advanced,
                'help' => 'pg.operator',
                'icon' => 'Operators',
            ],
            'opclasses' => [
                'title' => $lang['stropclasses'],
                'url' => 'opclasses',
                'urlvars' => ['subject' => 'schema'],
                'hide' => $hide_advanced,
                'help' => 'pg.opclass',
                'icon' => 'OperatorClasses',
            ],
            'conversions' => [
                'title' => $lang['strconversions'],
                'url' => 'conversions',
                'urlvars' => ['subject' => 'schema'],
                'hide' => $hide_advanced,
                'help' => 'pg.conversion',
                'icon' => 'Conversions',
            ],
            'privileges' => [
                'title' => $lang['strprivileges'],
                'url' => 'privileges',
                'urlvars' => ['subject' => 'schema'],
                'help' => 'pg.privilege',
                'tree' => false,
                'icon' => 'Privileges',
            ],
            'export' => [
                'title' => $lang['strexport'],
                'url' => 'schemas',
                'urlvars' => ['subject' => 'schema', 'action' => 'export'],
                'hide' => !$this->isDumpEnabled(),
                'tree' => false,
                'icon' => 'Export',
            ],
        ];

        if (!$data->hasFTS()) {
            unset($tabs['fulltext']);
        }

        return $tabs;
    }

    public function getTabsTable($data)
    {
        $lang = $this->lang;

        return [
            'columns' => [
                'title' => $lang['strcolumns'],
                'url' => 'tblproperties',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                'icon' => 'Columns',
                'branch' => true,
            ],
            'browse' => [
                'title' => $lang['strbrowse'],
                'icon' => 'Columns',
                'url' => 'display',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                'return' => 'table',
                'branch' => true,
            ],
            'select' => [
                'title' => $lang['strselect'],
                'icon' => 'Search',
                'url' => 'tables',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'confselectrows'],
                'help' => 'pg.sql.select',
            ],
            'insert' => [
                'title' => $lang['strinsert'],
                'url' => 'tables',
                'urlvars' => [
                    'action' => 'confinsertrow',
                    'table' => Decorator::field('table'),
                ],
                'help' => 'pg.sql.insert',
                'icon' => 'Operator',
            ],
            'indexes' => [
                'title' => $lang['strindexes'],
                'url' => 'indexes',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                'help' => 'pg.index',
                'icon' => 'Indexes',
                'branch' => true,
            ],
            'constraints' => [
                'title' => $lang['strconstraints'],
                'url' => 'constraints',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                'help' => 'pg.constraint',
                'icon' => 'Constraints',
                'branch' => true,
            ],
            'triggers' => [
                'title' => $lang['strtriggers'],
                'url' => 'triggers',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                'help' => 'pg.trigger',
                'icon' => 'Triggers',
                'branch' => true,
            ],
            'rules' => [
                'title' => $lang['strrules'],
                'url' => 'rules',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                'help' => 'pg.rule',
                'icon' => 'Rules',
                'branch' => true,
            ],
            'admin' => [
                'title' => $lang['stradmin'],
                'url' => 'tables',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'admin'],
                'icon' => 'Admin',
            ],
            'info' => [
                'title' => $lang['strinfo'],
                'url' => 'info',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                'icon' => 'Statistics',
            ],
            'privileges' => [
                'title' => $lang['strprivileges'],
                'url' => 'privileges',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table')],
                'help' => 'pg.privilege',
                'icon' => 'Privileges',
            ],
            'import' => [
                'title' => $lang['strimport'],
                'url' => 'tblproperties',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'import'],
                'icon' => 'Import',
                'hide' => false,
            ],
            'export' => [
                'title' => $lang['strexport'],
                'url' => 'tblproperties',
                'urlvars' => ['subject' => 'table', 'table' => Decorator::field('table'), 'action' => 'export'],
                'icon' => 'Export',
                'hide' => false,
            ],
        ];
    }

    public function getTabsView($data)
    {
        $lang = $this->lang;

        return [
            'columns' => [
                'title' => $lang['strcolumns'],
                'url' => 'viewproperties',
                'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                'icon' => 'Columns',
                'branch' => true,
            ],
            'browse' => [
                'title' => $lang['strbrowse'],
                'icon' => 'Columns',
                'url' => 'display',
                'urlvars' => [
                    'action' => 'confselectrows',
                    'return' => 'schema',
                    'subject' => 'view',
                    'view' => Decorator::field('view'),
                ],
                'branch' => true,
            ],
            'select' => [
                'title' => $lang['strselect'],
                'icon' => 'Search',
                'url' => 'views',
                'urlvars' => ['action' => 'confselectrows', 'view' => Decorator::field('view')],
                'help' => 'pg.sql.select',
            ],
            'definition' => [
                'title' => $lang['strdefinition'],
                'url' => 'viewproperties',
                'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view'), 'action' => 'definition'],
                'icon' => 'Definition',
            ],
            'rules' => [
                'title' => $lang['strrules'],
                'url' => 'rules',
                'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                'help' => 'pg.rule',
                'icon' => 'Rules',
                'branch' => true,
            ],
            'privileges' => [
                'title' => $lang['strprivileges'],
                'url' => 'privileges',
                'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view')],
                'help' => 'pg.privilege',
                'icon' => 'Privileges',
            ],
            'export' => [
                'title' => $lang['strexport'],
                'url' => 'viewproperties',
                'urlvars' => ['subject' => 'view', 'view' => Decorator::field('view'), 'action' => 'export'],
                'icon' => 'Export',
                'hide' => false,
            ],
        ];
    }

    public function getTabsMatview($data)
    {
        $lang = $this->lang;

        return [
            'columns' => [
                'title' => $lang['strcolumns'],
                'url' => 'materializedviewproperties',
                'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                'icon' => 'Columns',
                'branch' => true,
            ],
            'browse' => [
                'title' => $lang['strbrowse'],
                'icon' => 'Columns',
                'url' => 'display',
                'urlvars' => [
                    'action' => 'confselectrows',
                    'return' => 'schema',
                    'subject' => 'matview',
                    'matview' => Decorator::field('matview'),
                ],
                'branch' => true,
            ],
            'select' => [
                'title' => $lang['strselect'],
                'icon' => 'Search',
                'url' => 'materializedviews',
                'urlvars' => ['action' => 'confselectrows', 'matview' => Decorator::field('matview')],
                'help' => 'pg.sql.select',
            ],
            'definition' => [
                'title' => $lang['strdefinition'],
                'url' => 'materializedviewproperties',
                'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview'), 'action' => 'definition'],
                'icon' => 'Definition',
            ],
            'indexes' => [
                'title' => $lang['strindexes'],
                'url' => 'indexes',
                'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                'help' => 'pg.index',
                'icon' => 'Indexes',
                'branch' => true,
            ],
            /*'constraints' => [
            'title' => $lang['strconstraints'],
            'url' => 'constraints',
            'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
            'help' => 'pg.constraint',
            'icon' => 'Constraints',
            'branch' => true,
             */

            'rules' => [
                'title' => $lang['strrules'],
                'url' => 'rules',
                'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                'help' => 'pg.rule',
                'icon' => 'Rules',
                'branch' => true,
            ],
            'privileges' => [
                'title' => $lang['strprivileges'],
                'url' => 'privileges',
                'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview')],
                'help' => 'pg.privilege',
                'icon' => 'Privileges',
            ],
            'export' => [
                'title' => $lang['strexport'],
                'url' => 'materializedviewproperties',
                'urlvars' => ['subject' => 'matview', 'matview' => Decorator::field('matview'), 'action' => 'export'],
                'icon' => 'Export',
                'hide' => false,
            ],
        ];
    }

    public function getTabsFunction($data)
    {
        $lang = $this->lang;

        return [
            'definition' => [
                'title' => $lang['strdefinition'],
                'url' => 'functions',
                'urlvars' => [
                    'subject' => 'function',
                    'function' => Decorator::field('function'),
                    'function_oid' => Decorator::field('function_oid'),
                    'action' => 'properties',
                ],
                'icon' => 'Definition',
            ],
            'privileges' => [
                'title' => $lang['strprivileges'],
                'url' => 'privileges',
                'urlvars' => [
                    'subject' => 'function',
                    'function' => Decorator::field('function'),
                    'function_oid' => Decorator::field('function_oid'),
                ],
                'icon' => 'Privileges',
            ],
            'show' => [
                'title' => $lang['strshow'] . ' ' . $lang['strdefinition'],
                'url' => 'functions',
                'urlvars' => [
                    'subject' => 'function',
                    'function' => Decorator::field('function'),
                    'function_oid' => Decorator::field('function_oid'),
                    'action' => 'show',
                ],
                'icon' => 'Search',
            ],
        ];
    }

    public function getTabsAggregate($data)
    {
        $lang = $this->lang;

        return [
            'definition' => [
                'title' => $lang['strdefinition'],
                'url' => 'aggregates',
                'urlvars' => [
                    'subject' => 'aggregate',
                    'aggrname' => Decorator::field('aggrname'),
                    'aggrtype' => Decorator::field('aggrtype'),
                    'action' => 'properties',
                ],
                'icon' => 'Definition',
            ],
        ];
    }

    public function getTabsRole($data)
    {
        $lang = $this->lang;

        return [
            'definition' => [
                'title' => $lang['strdefinition'],
                'url' => 'roles',
                'urlvars' => [
                    'subject' => 'role',
                    'rolename' => Decorator::field('rolename'),
                    'action' => 'properties',
                ],
                'icon' => 'Definition',
            ],
        ];
    }

    public function getTabsPopup($data)
    {
        $lang = $this->lang;

        return [
            'sql' => [
                'title' => $lang['strsql'],
                'url' => \SUBFOLDER . '/src/views/sqledit',
                'urlvars' => ['action' => 'sql', 'subject' => 'schema'],
                'help' => 'pg.sql',
                'icon' => 'SqlEditor',
            ],
            'find' => [
                'title' => $lang['strfind'],
                'url' => \SUBFOLDER . '/src/views/sqledit',
                'urlvars' => ['action' => 'find', 'subject' => 'schema'],
                'icon' => 'Search',
            ],
        ];
    }

    public function getTabsColumn($data)
    {
        $lang = $this->lang;
        $tabs = [
            'properties' => [
                'title' => $lang['strcolprop'],
                'url' => 'colproperties',
                'urlvars' => [
                    'subject' => 'column',
                    'table' => Decorator::field('table'),
                    'view' => Decorator::field('view'),
                    'column' => Decorator::field('column'),
                ],
                'icon' => 'Column',
            ],
            'privileges' => [
                'title' => $lang['strprivileges'],
                'url' => 'privileges',
                'urlvars' => [
                    'subject' => 'column',
                    'table' => Decorator::field('table'),
                    'view' => Decorator::field('view'),
                    'column' => Decorator::field('column'),
                ],
                'help' => 'pg.privilege',
                'icon' => 'Privileges',
            ],
        ];

        if (empty($tabs['properties']['urlvars']['table'])) {
            unset($tabs['properties']['urlvars']['table']);
        }

        if (empty($tabs['privileges']['urlvars']['table'])) {
            unset($tabs['privileges']['urlvars']['table']);
        }

        return $tabs;
    }

    public function getTabsFulltext($data)
    {
        $lang = $this->lang;

        return [
            'ftsconfigs' => [
                'title' => $lang['strftstabconfigs'],
                'url' => 'fulltext',
                'urlvars' => ['subject' => 'schema'],
                'hide' => !$data->hasFTS(),
                'help' => 'pg.ftscfg',
                'tree' => true,
                'icon' => 'FtsCfg',
            ],
            'ftsdicts' => [
                'title' => $lang['strftstabdicts'],
                'url' => 'fulltext',
                'urlvars' => ['subject' => 'schema', 'action' => 'viewdicts'],
                'hide' => !$data->hasFTS(),
                'help' => 'pg.ftsdict',
                'tree' => true,
                'icon' => 'FtsDict',
            ],
            'ftsparsers' => [
                'title' => $lang['strftstabparsers'],
                'url' => 'fulltext',
                'urlvars' => ['subject' => 'schema', 'action' => 'viewparsers'],
                'hide' => !$data->hasFTS(),
                'help' => 'pg.ftsparser',
                'tree' => true,
                'icon' => 'FtsParser',
            ],
        ];
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
        $data = $this->getDatabaseAccessor();
        $lang = $this->lang;

        $hide_advanced = (false === $this->conf['show_advanced']);
        $tabs = [];

        switch ($section) {
            case 'root':$tabs = $this->getTabsRoot($data);

                break;
            case 'server':$tabs = $this->getTabsServer($data);

                break;
            case 'database':$tabs = $this->getTabsDatabase($data);

                break;
            case 'schema':$tabs = $this->getTabsSchema($data);

                break;
            case 'table':$tabs = $this->getTabsTable($data);

                break;
            case 'view':$tabs = $this->getTabsView($data);

                break;
            case 'matview':$tabs = $this->getTabsMatview($data);

                break;
            case 'function':$tabs = $this->getTabsFunction($data);

                break;
            case 'aggregate':$tabs = $this->getTabsAggregate($data);

                break;
            case 'role':$tabs = $this->getTabsRole($data);

                break;
            case 'popup':$tabs = $this->getTabsPopup($data);

                break;
            case 'column':$tabs = $this->getTabsColumn($data);

                break;
            case 'fulltext':$tabs = $this->getTabsFulltext($data);

                break;
        }

        // Tabs hook's place
        $plugin_functions_parameters = [
            'tabs' => &$tabs,
            'section' => $section,
        ];

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
            $tab = \reset($tabs);
        }
        // $this->prtrace(['section' => $section, 'tabs' => $tabs, 'tab' => $tab]);

        return isset($tab['url']) ? $tab : null;
    }

    abstract public function getDatabaseAccessor($database = '', $server_id = null);

    abstract public function isDumpEnabled($all = false);

    abstract public function prtrace();
}
