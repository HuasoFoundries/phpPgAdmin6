<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

use IteratorAggregate;
use PHPPgAdmin\ArrayRecordSet;
use PHPPgAdmin\ContainerUtils;
use PHPPgAdmin\Misc;
use PHPPgAdmin\Traits\HelperTrait;
use PHPPgAdmin\ViewManager;
use PHPPgAdmin\XHtml;
use PHPPgAdmin\XHtml\HTMLFooterController;
use PHPPgAdmin\XHtml\HTMLHeaderController;
use PHPPgAdmin\XHtml\HTMLNavbarController;
use PHPPgAdmin\XHtml\HTMLTableController;

/**
 * Base controller class.
 */
class BaseController
{
    use HelperTrait;

    public $postgresqlMinVer;

    public $appLangFiles = [];

    public $appThemes = [];

    public $appName = '';

    public $appVersion = '';

    public $form = '';

    public $href = '';

    public $lang = [];

    public $action = '';

    public $controller_name;

    /**
     * Used.
     *
     * @var string
     */
    public $view_name;

    /**
     * Used to print the title passing its value to $lang.
     *
     * @var string
     */
    public $controller_title = 'base';

    public $msg = '';

    /**
     * @var ViewManager
     */
    public $view;

    /**
     * @var Misc
     */
    public $misc;

    public $conf;

    public $phpMinVer;

    /**
     * @var ContainerUtils
     */
    protected $container;

    protected $script;

    protected $data;

    protected $database;

    protected $server_id;

    /**
     * @var XHtml\HTMLTableController
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $_table_controller;

    /**
     * @var XHtml\HTMLFooterController
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $_footer_controller;

    /**
     * @var XHtml\HTMLHeaderController
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $_header_controller;

    /**
     * @var XHtml\HTMLNavbarController
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $_trail_controller;

    /**
     * @var TreeController
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $_tree_controller;

    protected $scripts = '';

    protected $no_db_connection = false;

    /**
     * Constructs the base controller (common for almost all controllers).
     *
     * @param ContainerUtils $container the $app container
     */
    public function __construct(ContainerUtils $container)
    {
        $this->container = $container;
        $this->lang = $container->get('lang');

        $this->controller_name = \str_replace(__NAMESPACE__ . '\\', '', \get_class($this));
        $this->view_name = \str_replace('controller', '', \mb_strtolower($this->controller_name));
        $this->script = $this->view_name;

        $this->view = $container->get('view');

        $this->msg = $container->get('msg');
        $this->appLangFiles = $container->get('appLangFiles');

        $this->misc = $container->get('misc');
        $this->conf = $this->misc->getConf();

        $this->appThemes = $container->get('appThemes');
        $this->action = $container->get('action');

        $this->appName = $container->get('settings')['appName'];
        $this->appVersion = $container->get('settings')['appVersion'];
        $this->postgresqlMinVer = $container->get('settings')['postgresqlMinVer'];
        $this->phpMinVer = $container->get('settings')['phpMinVer'];

        $msg = $container->get('msg');

        if (true === $this->no_db_connection) {
            $this->misc->setNoDBConnection(true);
        }

        if (!$this->container->IN_TEST) {
            $this->renderInitialPageIfNotLogged();
        }
    }

    /**
     * Default method to render the controller according to the action parameter. It should return with a PSR
     * responseObject but it prints texts whatsoeever.
     */
    public function render()
    {
        $this->misc = $this->misc;

        $this->printHeader();
        $this->printBody();
        $this->doDefault();

        $this->printFooter();
    }

    /**
     * @return string
     */
    public function doDefault()
    {
        $html = '<div><h2>Section title</h2> <p>Main content</p></div>';
        echo $html;

        return $html;
    }

    /**
     * Returns the page title for each controller.
     *
     * @param string $title  The title
     * @param string $prefix The prefix
     * @param string $suffix The suffix
     *
     * @return string the page title
     */
    public function headerTitle($title = '', $prefix = '', $suffix = '')
    {
        $title = '' !== $title ? $title : $this->controller_title;

        return $prefix . $this->lang[$title] . ('' !== $suffix ? ': ' . $suffix : '');
    }

    /**
     * @return ContainerUtils
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Display a table of data.
     *
     * @param IteratorAggregate $tabledata a set of data to be formatted
     * @param array             $columns   An associative array of columns to be displayed:
     * @param array             $actions   Actions that can be performed on each object:
     * @param string            $place     Place where the $actions are displayed. Like 'display-browse',
     * @param string            $nodata    (optional) Message to display if data set is empty
     * @param callable          $pre_fn    (optional) callback closure for each row
     *
     * @return string the html of the table
     */
    public function printTable(IteratorAggregate &$tabledata, &$columns, &$actions, $place, $nodata = '', $pre_fn = null)
    {
        $html_table = $this->_getTableController();

        $html_table->initialize($tabledata, $columns, $actions, $place, $nodata, $pre_fn);

        return $html_table->printTable();
    }

    public static function isRecordSet($variable)
    {
        return $variable instanceof IteratorAggregate && \method_exists($variable, 'MoveNext');
    }

    /**
     * Hides or show tree tabs according to their properties.
     *
     * @param array $tabs The tabs
     *
     * @return ArrayRecordSet filtered tabs in the form of an ArrayRecordSet
     */
    public function adjustTabsForTree(&$tabs)
    {
        $tree = $this->_getTreeController();

        return $tree->adjustTabsForTree($tabs);
    }

    /**
     * Produce JSON data for the browser tree.
     *
     * @param \ADORecordSet|\PHPPgAdmin\Interfaces\Recordset
     * @param false|string $section
     * @param bool         $print   either to return or echo the result
     *
     * @return (array|bool|string)[]
     *
     * @psalm-return array<int|string, array<string, mixed>|bool|string>
     */
    public function printTree(&$_treedata, &$attrs, $section, $print = true)
    {
        $tree = $this->_getTreeController();

        return $tree->printTree($_treedata, $attrs, $section, $print);
    }

    /**
     * Prints a trail.
     *
     * @param array|string $trail
     * @param bool         $do_print The do print
     *
     * @return string ( description_of_the_return_value )
     */
    public function printTrail($trail = [], bool $do_print = true)
    {
        $from = __METHOD__;
        $html_trail = $this->_getNavbarController();

        return $html_trail->printTrail($trail, $do_print, $from);
    }

    /**
     * @param (array[][]|mixed)[][] $navlinks
     * @param mixed                 $do_print
     *
     * @return null|string
     */
    public function printNavLinks(array $navlinks, string $place, array $env = [], $do_print = true)
    {
        $from = __METHOD__;
        $footer_controller = $this->_getFooterController();

        return $footer_controller->printNavLinks($navlinks, $place, $env, $do_print, $from);
    }

    public function printTabs(string $tabs, string $activetab, bool $do_print = true)
    {
        $from = __METHOD__;
        $html_trail = $this->_getNavbarController();

        return $html_trail->printTabs($tabs, $activetab, $do_print, $from);
    }

    /**
     * @param true  $do_print
     * @param mixed $link
     *
     * @return null|string
     */
    public function printLink($link, bool $do_print = true, ?string $from = null)
    {
        if (null === $from) {
            $from = __METHOD__;
        }

        $html_trail = $this->_getNavbarController();

        return $html_trail->printLink($link, $do_print, $from);
    }

    /**
     * @param true $flag
     *
     * @return HTMLFooterController
     */
    public function setReloadDropDatabase(bool $flag)
    {
        $footer_controller = $this->_getFooterController();

        return $footer_controller->setReloadDropDatabase($flag);
    }

    /**
     * @param true $flag
     *
     * @return HTMLFooterController
     */
    public function setNoBottomLink(bool $flag)
    {
        $footer_controller = $this->_getFooterController();

        return $footer_controller->setNoBottomLink($flag);
    }

    /**
     * @return null|string
     */
    public function printFooter(bool $doBody = true, string $template = 'footer.twig')
    {
        $footer_controller = $this->_getFooterController();

        return $footer_controller->printFooter($doBody, $template);
    }

    public function printReload($database, $do_print = true)
    {
        $footer_controller = $this->_getFooterController();

        return $footer_controller->printReload($database, $do_print);
    }

    /**
     * Outputs JavaScript to set default focus.
     *
     * @param mixed $object eg. forms[0].username
     */
    public function setFocus($object)
    {
        $footer_controller = $this->_getFooterController();

        return $footer_controller->setFocus($object);
    }

    /**
     * Outputs JavaScript to set the name of the browser window.
     *
     * @param string $name      the window name
     * @param bool   $addServer if true (default) then the server id is
     *                          attached to the name
     */
    public function setWindowName($name, $addServer = true)
    {
        $footer_controller = $this->_getFooterController();

        return $footer_controller->setWindowName($name, $addServer);
    }

    /**
     * @param true $flag
     *
     * @return HTMLHeaderController
     */
    public function setNoOutput(bool $flag)
    {
        $header_controller = $this->_getHeaderController();

        return $header_controller->setNoOutput((bool) $flag);
    }

    /**
     * @return string
     */
    public function printHeader(string $title = '', ?string $script = null, bool $do_print = true, string $template = 'header.twig')
    {
        $title = '' !== $title ? $title : $this->headerTitle();
        $header_controller = $this->_getHeaderController();

        return $header_controller->printHeader($title, $script, $do_print, $template);
    }

    /**
     * Undocumented function.
     *
     * @param bool $includeJsTree either to add the jsTree in the root body. By default is inserted using an iframe
     *
     * @return string
     */
    public function printBody(bool $doBody = true, string $bodyClass = 'detailbody', bool $onloadInit = false, bool $includeJsTree = true)
    {
        $header_controller = $this->_getHeaderController();

        return $header_controller->printBody($doBody, $bodyClass, $onloadInit, $includeJsTree);
    }

    /**
     * @return string
     */
    public function printTitle(string $title, ?string $help = null, bool $do_print = true)
    {
        $header_controller = $this->_getHeaderController();

        return $header_controller->printTitle($title, $help, $do_print);
    }

    /**
     * Retrieves a request parameter either from the body or query string.
     */
    public function getRequestParam(string $key, ?string $default = null)
    {
        return \requestInstance()->getParam($key, $default);
    }

    /**
     * @param null|array|bool|float|int|string $default
     *
     * @return bool| null|array|string|int|float
     */
    public function getPostParam(string $key, $default = null)
    {
        return \requestInstance()->getParsedBodyParam($key, $default);
    }

    /**
     * @param string                      $key
     * @param null|array|float|int|string $default
     *
     * @return null|array|float|int|string
     */
    public function getQueryStringParam($key, $default = null)
    {
        return \requestInstance()->getQueryParam($key, $default);
    }

    /**
     * Print out a message.
     *
     * @param string $msg      The message
     * @param bool   $do_print if true, print the message. Return the string otherwise
     *
     * @return string a paragraph containing the message, whose linebreaks are replaced by <br> elements
     */
    public function printMsg($msg, $do_print = true)
    {
        $html = '';
        $msg = \htmlspecialchars(ContainerUtils::br2ln($msg));

        if ('' !== $msg) {
            $html .= '<p class="message">' . \nl2br($msg) . '</p>' . \PHP_EOL;
        }

        if ($do_print) {
            echo $html;

            return $html;
        }

        return $html;
    }

    private function renderInitialPageIfNotLogged(): void
    {
        if (!$this->misc->getNoDBConnection()) {
            if (null === $this->misc->getServerId()) {
                $servers_controller = new ServersController($this->container);

                $servers_controller->render();
            } else {
                $_server_info = $this->misc->getServerInfo();
                // Redirect to the login form if not logged in
                if (!isset($_server_info['username'])) {
                    $msg = \sprintf(
                        $this->lang['strlogoutmsg'],
                        $_server_info['desc']
                    );

                    $servers_controller = new ServersController($this->container);

                    $servers_controller->render();
                }
            }
        }
    }

    /**
     * @return HTMLTableController
     */
    private function _getTableController()
    {
        if (null === $this->_table_controller) {
            $this->_table_controller = new HTMLTableController($this->getContainer(), $this->controller_name);
        }

        return $this->_table_controller;
    }

    /**
     * @return HTMLFooterController
     */
    private function _getFooterController()
    {
        if (null === $this->_footer_controller) {
            $this->_footer_controller = new HTMLFooterController($this->getContainer(), $this->controller_name);
        }

        return $this->_footer_controller;
    }

    /**
     * @return HTMLHeaderController
     */
    private function _getHeaderController()
    {
        if (null === $this->_header_controller) {
            $this->_header_controller = new HTMLHeaderController($this->getContainer(), $this->controller_name);
        }

        return $this->_header_controller;
    }

    /**
     * @return HTMLNavbarController
     */
    private function _getNavbarController()
    {
        if (null === $this->_trail_controller) {
            $this->_trail_controller = new HTMLNavbarController($this->getContainer(), $this->controller_name);
        }

        return $this->_trail_controller;
    }

    /**
     * @return TreeController
     */
    private function _getTreeController()
    {
        if (null === $this->_tree_controller) {
            $this->_tree_controller = new TreeController($this->getContainer(), $this->controller_name);
        }

        return $this->_tree_controller;
    }
}
