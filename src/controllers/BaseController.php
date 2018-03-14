<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class BaseController
{
    use \PHPPgAdmin\HelperTrait;

    protected $container;
    protected $_connection;
    protected $app;
    protected $data;
    protected $database;
    protected $server_id;
    public $appLangFiles     = [];
    public $appThemes        = [];
    public $appName          = '';
    public $appVersion       = '';
    public $form             = '';
    public $href             = '';
    public $lang             = [];
    public $action           = '';
    public $controller_name  = 'BaseController';
    public $controller_title = 'base';
    protected $table_controller;
    protected $trail_controller;
    protected $tree_controller;
    protected $footer_controller;
    protected $header_controller;
    protected $scripts = '';
    public $msg        = '';
    public $view;
    public $plugin_manager;
    public $misc;
    public $conf;
    public $phpMinVer;

    /**
     * Constructs the base controller (common for almost all controllers).
     *
     * @param \Slim\Container $container        the $app container
     * @param bool            $no_db_connection [optional] if true, sets  $this->misc->setNoDBConnection(true);
     */
    public function __construct(\Slim\Container $container, $no_db_connection = false)
    {
        $this->container = $container;
        $this->lang      = $container->get('lang');

        $this->view           = $container->get('view');
        $this->plugin_manager = $container->get('plugin_manager');
        $this->msg            = $container->get('msg');
        $this->appLangFiles   = $container->get('appLangFiles');

        $this->misc = $container->get('misc');
        $this->conf = $this->misc->getConf();

        $this->appThemes = $container->get('appThemes');
        $this->action    = $container->get('action');

        $this->appName          = $container->get('settings')['appName'];
        $this->appVersion       = $container->get('settings')['appVersion'];
        $this->postgresqlMinVer = $container->get('settings')['postgresqlMinVer'];
        $this->phpMinVer        = $container->get('settings')['phpMinVer'];

        $msg = $container->get('msg');

        if (true === $no_db_connection) {
            $this->misc->setNoDBConnection(true);
        }

        if (false === $this->misc->getNoDBConnection()) {
            if (null === $this->misc->getServerId()) {
                $servers_controller = new \PHPPgAdmin\Controller\ServersController($container, true);

                return $servers_controller->render();
            }
            $_server_info = $this->misc->getServerInfo();
            // Redirect to the login form if not logged in
            if (!isset($_server_info['username'])) {
                $msg = sprintf($this->lang['strlogoutmsg'], $_server_info['desc']);

                $servers_controller = new \PHPPgAdmin\Controller\ServersController($container, true);

                return $servers_controller->render();
            }
        }

        //\PC::debug(['name' => $this->controller_name, 'no_db_connection' => $this->misc->getNoDBConnection()], 'instanced controller');
    }

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $this->misc = $this->misc;
        $lang       = $this->lang;
        $action     = $this->action;

        $this->printHeader($lang[$this->controller_title]);
        $this->printBody();

        switch ($action) {
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

    public function getContainer()
    {
        return $this->container;
    }

    private function getTableController()
    {
        if (null === $this->table_controller) {
            $this->table_controller = new \PHPPgAdmin\XHtml\HTMLTableController($this->getContainer(), $this->controller_name);
        }

        return $this->table_controller;
    }

    private function getFooterController()
    {
        if (null === $this->footer_controller) {
            $this->footer_controller = new \PHPPgAdmin\XHtml\HTMLFooterController($this->getContainer(), $this->controller_name);
        }

        return $this->footer_controller;
    }

    private function getHeaderController()
    {
        if (null === $this->header_controller) {
            $this->header_controller = new \PHPPgAdmin\XHtml\HTMLHeaderController($this->getContainer(), $this->controller_name);
        }

        return $this->header_controller;
    }

    private function getNavbarController()
    {
        if (null === $this->trail_controller) {
            $this->trail_controller = new \PHPPgAdmin\XHtml\HTMLNavbarController($this->getContainer(), $this->controller_name);
        }

        return $this->trail_controller;
    }

    private function getTreeController()
    {
        if (null === $this->tree_controller) {
            $this->tree_controller = new \PHPPgAdmin\XHtml\TreeController($this->getContainer(), $this->controller_name);
        }

        return $this->tree_controller;
    }

    /**
     * Instances an HTMLTable and returns its html content.
     *
     * @param [type] &$tabledata [description]
     * @param [type] &$columns   [description]
     * @param [type] &$actions   [description]
     * @param [type] $place      [description]
     * @param [type] $nodata     [description]
     * @param [type] $pre_fn     [description]
     *
     * @return [type] [description]
     */
    public function printTable(&$tabledata, &$columns, &$actions, $place, $nodata = null, $pre_fn = null)
    {
        $html_table = $this->getTableController();

        return $html_table->printTable($tabledata, $columns, $actions, $place, $nodata, $pre_fn);
    }

    public function adjustTabsForTree($tabs)
    {
        $tree = $this->getTreeController();

        return $tree->adjustTabsForTree($tabs);
    }

    public function printTree(&$_treedata, &$attrs, $section, $print = true)
    {
        $tree = $this->getTreeController();

        return $tree->printTree($_treedata, $attrs, $section, $print);
    }

    public function printTrail($trail = [], $do_print = true)
    {
        $from       = __METHOD__;
        $html_trail = $this->getNavbarController();

        return $html_trail->printTrail($trail, $do_print, $from);
    }

    public function printNavLinks($navlinks, $place, $env = [], $do_print = true)
    {
        $from       = __METHOD__;
        $html_trail = $this->getNavbarController();

        return $html_trail->printNavLinks($navlinks, $place, $env, $do_print, $from);
    }

    public function printTabs($tabs, $activetab, $do_print = true)
    {
        $from       = __METHOD__;
        $html_trail = $this->getNavbarController();

        return $html_trail->printTabs($tabs, $activetab, $do_print, $from);
    }

    public function getLastTabURL($section)
    {
        $html_trail = $this->getNavbarController();

        return $html_trail->getLastTabURL($section);
    }

    public function printLink($link, $do_print = true, $from = null)
    {
        if (null === $from) {
            $from = __METHOD__;
        }

        $html_trail = $this->getNavbarController();

        return $html_trail->printLink($link, $do_print, $from);
    }

    public function setReloadDropDatabase($flag)
    {
        $footer_controller = $this->getFooterController();

        return $footer_controller->setReloadDropDatabase($flag);
    }

    public function setNoBottomLink($flag)
    {
        $footer_controller = $this->getFooterController();

        return $footer_controller->setNoBottomLink($flag);
    }

    public function printFooter($doBody = true, $template = 'footer.twig')
    {
        $footer_controller = $this->getFooterController();

        return $footer_controller->printFooter($doBody, $template);
    }

    public function printReload($database, $do_print = true)
    {
        $footer_controller = $this->getFooterController();

        return $footer_controller->printReload($database, $do_print);
    }

    /**
     * Outputs JavaScript to set default focus.
     *
     * @param mixed $object eg. forms[0].username
     */
    public function setFocus($object)
    {
        $footer_controller = $this->getFooterController();

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
        $footer_controller = $this->getFooterController();

        return $footer_controller->setWindowName($name, $addServer);
    }

    public function setNoOutput($flag)
    {
        $header_controller = $this->getHeaderController();

        return $header_controller->setNoOutput((bool) $flag);
    }

    public function printHeader($title = '', $script = null, $do_print = true, $template = 'header.twig')
    {
        $header_controller = $this->getHeaderController();

        return $header_controller->printHeader($title, $script, $do_print, $template);
    }

    public function printBody($doBody = true, $bodyClass = 'detailbody', $onloadInit = false)
    {
        $header_controller = $this->getHeaderController();

        return $header_controller->printBody($doBody, $bodyClass, $onloadInit);
    }

    public function printTitle($title, $help = null, $do_print = true)
    {
        $header_controller = $this->getHeaderController();

        return $header_controller->printTitle($title, $help, $do_print);
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
        $msg  = htmlspecialchars(\PHPPgAdmin\HelperTrait::br2ln($msg));
        if ('' != $msg) {
            $html .= '<p class="message">'.nl2br($msg).'</p>'."\n";
        }
        if ($do_print) {
            echo $html;

            return $html;
        }

        return $html;
    }
}
