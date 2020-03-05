<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\ContainerUtils;
use PHPPgAdmin\XHtml;

\ini_set('display_errors', ContainerUtils::DEBUGMODE);
/**
 * Base controller class.
 */
class BaseController
{
    use \PHPPgAdmin\Traits\HelperTrait;
    /**
     * @var string
     */
    const BASE_PATH = ContainerUtils::BASE_PATH;
    /**
     * @var string
     */
    const SUBFOLDER = ContainerUtils::SUBFOLDER;
    /**
     * @var string
     */
    const DEBUGMODE = ContainerUtils::DEBUGMODE;

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

    public $view;

    public $misc;

    public $conf;

    public $phpMinVer;

    protected $script;

    protected $container;

    protected $app;

    protected $data;

    protected $database;

    protected $server_id;

    /**
     * @var XHtml\HTMLTableController
     */
    protected $_table_controller;

    /**
     * @var XHtml\HTMLFooterController
     */
    protected $_footer_controller;

    /**
     * @var XHtml\HTMLHeaderController
     */
    protected $_header_controller;

    /**
     * @var XHtml\HTMLNavbarController
     */
    protected $_trail_controller;

    /**
     * @var TreeController
     */
    protected $_tree_controller;

    protected $scripts = '';

    protected $no_db_connection = false;

    /**
     * Constructs the base controller (common for almost all controllers).
     *
     * @param \Slim\Container $container        the $app container
     * @param bool            $no_db_connection [optional] if true, sets  $this->misc->setNoDBConnection(true);
     */
    public function __construct(\Slim\Container $container)
    {
        $this->container = $container;
        $this->lang      = $container->get('lang');

        $this->controller_name = \str_replace(__NAMESPACE__ . '\\', '', \get_class($this));
        $this->view_name       = \str_replace('controller', '', \mb_strtolower($this->controller_name));
        $this->script          = $this->view_name;

        $this->view = $container->get('view');

        $this->msg          = $container->get('msg');
        $this->appLangFiles = $container->get('appLangFiles');

        $this->misc = $container->get('misc');
        $this->conf = $this->misc->getConf();

        $this->appThemes = $container->get('appThemes');
        $this->action    = $container->get('action');

        $this->appName          = $container->get('settings')['appName'];
        $this->appVersion       = $container->get('settings')['appVersion'];
        $this->postgresqlMinVer = $container->get('settings')['postgresqlMinVer'];
        $this->phpMinVer        = $container->get('settings')['phpMinVer'];

        $msg = $container->get('msg');

        if (true === $this->no_db_connection) {
            $this->misc->setNoDBConnection(true);
        }

        if (false === $this->misc->getNoDBConnection()) {
            if (null === $this->misc->getServerId()) {
                $servers_controller = new \PHPPgAdmin\Controller\ServersController($container);

                return $servers_controller->render();
            }
            $_server_info = $this->misc->getServerInfo();
            // Redirect to the login form if not logged in
            if (!isset($_server_info['username'])) {
                $msg = \sprintf($this->lang['strlogoutmsg'], $_server_info['desc']);

                $servers_controller = new \PHPPgAdmin\Controller\ServersController($container);

                return $servers_controller->render();
            }
        }
    }

    /**
     * Default method to render the controller according to the action parameter.
     *
     * @return void
     */
    public function render()
    {
        $this->misc = $this->misc;

        $this->printHeader();
        $this->printBody();

        switch ($this->action) {
            default:
                $this->doDefault();

                break;
        }

        $this->printFooter();
    }

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
        $title = $title ? $title : $this->controller_title;

        return $prefix . $this->lang[$title] . ($suffix ? ': ' . $suffix : '');
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Display a table of data.
     *
     * @param \PHPPgAdmin\ADORecordSet|\PHPPgAdmin\ArrayRecordSet $tabledata a set of data to be formatted
     * @param array                                               $columns   An associative array of columns to be displayed:
     * @param array                                               $actions   Actions that can be performed on each object:
     * @param string                                              $place     Place where the $actions are displayed. Like 'display-browse',
     * @param string                                              $nodata    (optional) Message to display if data set is empty
     * @param callable                                            $pre_fn    (optional) callback closure for each row
     *
     * @return string the html of the table
     */
    public function printTable(&$tabledata, &$columns, &$actions, $place, $nodata = '', $pre_fn = null)
    {
        $html_table = $this->_getTableController();

        $html_table->initialize($tabledata, $columns, $actions, $place, $nodata, $pre_fn);

        return $html_table->printTable();
    }

    /**
     * Hides or show tree tabs according to their properties.
     *
     * @param array $tabs The tabs
     *
     * @return \PHPPgAdmin\ArrayRecordSet filtered tabs in the form of an ArrayRecordSet
     */
    public function adjustTabsForTree(&$tabs)
    {
        $tree = $this->_getTreeController();

        return $tree->adjustTabsForTree($tabs);
    }

    /**
     * Produce JSON data for the browser tree.
     *
     * @param \PHPPgAdmin\ArrayRecordSet $_treedata a set of records to populate the tree
     * @param array                      $attrs     Attributes for tree items
     * @param string                     $section   The section where the branch is linked in the tree
     * @param bool                       $print     either to return or echo the result
     *
     * @return \Slim\Http\Response|string the json rendered tree
     */
    public function printTree(&$_treedata, &$attrs, $section, $print = true)
    {
        $tree = $this->_getTreeController();

        return $tree->printTree($_treedata, $attrs, $section, $print);
    }

    /**
     * Prints a trail.
     *
     * @param array|string  $trail
     * @param boolean       $do_print  The do print
     *
     * @return string  ( description_of_the_return_value )
     */
    public function printTrail($trail = [], bool $do_print = true)
    {
        $from       = __METHOD__;
        $html_trail = $this->_getNavbarController();

        return $html_trail->printTrail($trail, $do_print, $from);
    }

    /**
     * @param (array|mixed)[][] $navlinks
     */
    public function printNavLinks(array $navlinks, string $place, array $env = [], $do_print = true)
    {
        $from              = __METHOD__;
        $footer_controller = $this->_getFooterController();

        return $footer_controller->printNavLinks($navlinks, $place, $env, $do_print, $from);
    }

    public function printTabs(string $tabs, string $activetab, bool $do_print = true)
    {
        $from       = __METHOD__;
        $html_trail = $this->_getNavbarController();

        return $html_trail->printTabs($tabs, $activetab, $do_print, $from);
    }

    /**
     * @param true $do_print
     * @param null|string $from
     */
    public function printLink($link, bool $do_print = true, ? string $from = null)
    {
        if (null === $from) {
            $from = __METHOD__;
        }

        $html_trail = $this->_getNavbarController();

        return $html_trail->printLink($link, $do_print, $from);
    }

    /**
     * @param true $flag
     */
    public function setReloadDropDatabase(bool $flag)
    {
        $footer_controller = $this->_getFooterController();

        return $footer_controller->setReloadDropDatabase($flag);
    }

    /**
     * @param true $flag
     */
    public function setNoBottomLink(bool $flag)
    {
        $footer_controller = $this->_getFooterController();

        return $footer_controller->setNoBottomLink($flag);
    }

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
     */
    public function setNoOutput(bool $flag)
    {
        $header_controller = $this->_getHeaderController();

        return $header_controller->setNoOutput((bool) $flag);
    }

    /**
     * @param null|string $script
     */
    public function printHeader(string $title = '', ? string $script = null, bool $do_print = true, string $template = 'header.twig')
    {
        $title             = $title ? $title : $this->headerTitle();
        $header_controller = $this->_getHeaderController();

        return $header_controller->printHeader($title, $script, $do_print, $template);
    }

    public function printBody(bool $doBody = true, string $bodyClass = 'detailbody', bool $onloadInit = false)
    {
        $header_controller = $this->_getHeaderController();

        return $header_controller->printBody($doBody, $bodyClass, $onloadInit);
    }

    /**
     * @param null|string $help
     */
    public function printTitle(string $title, ? string $help = null, bool $do_print = true)
    {
        $header_controller = $this->_getHeaderController();

        return $header_controller->printTitle($title, $help, $do_print);
    }

    /**
     * @param null|string $default
     */
    public function getRequestParam(string $key, ? string $default = null)
    {
        return $this->container->requestobj->getParam($key, $default);
    }

    /**
     * @param array|null|string $default
     */
    public function getPostParam(string $key, $default = null)
    {
        return $this->container->requestobj->getParsedBodyParam($key, $default);
    }

    public function getQueryParam($key, $default = null)
    {
        return $this->container->requestobj->getQueryParam($key, $default);
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
        $msg  = \htmlspecialchars(\PHPPgAdmin\Traits\HelperTrait::br2ln($msg));

        if ('' !== $msg) {
            $html .= '<p class="message">' . \nl2br($msg) . '</p>' . \PHP_EOL;
        }

        if ($do_print) {
            echo $html;

            return $html;
        }

        return $html;
    }

    private function _getTableController()
    {
        if (null === $this->_table_controller) {
            $this->_table_controller = new XHtml\HTMLTableController($this->getContainer(), $this->controller_name);
        }

        return $this->_table_controller;
    }

    private function _getFooterController()
    {
        if (null === $this->_footer_controller) {
            $this->_footer_controller = new XHtml\HTMLFooterController($this->getContainer(), $this->controller_name);
        }

        return $this->_footer_controller;
    }

    private function _getHeaderController()
    {
        if (null === $this->_header_controller) {
            $this->_header_controller = new XHtml\HTMLHeaderController($this->getContainer(), $this->controller_name);
        }

        return $this->_header_controller;
    }

    private function _getNavbarController()
    {
        if (null === $this->_trail_controller) {
            $this->_trail_controller = new XHtml\HTMLNavbarController($this->getContainer(), $this->controller_name);
        }

        return $this->_trail_controller;
    }

    private function _getTreeController()
    {
        if (null === $this->_tree_controller) {
            $this->_tree_controller = new TreeController($this->getContainer(), $this->controller_name);
        }

        return $this->_tree_controller;
    }
}
