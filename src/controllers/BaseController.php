<?php

namespace PHPPgAdmin\Controller;

/**
 * Base controller class
 */
class BaseController
{

    use \PHPPgAdmin\HelperTrait;

    protected $container         = null;
    protected $_connection       = null;
    protected $app               = null;
    protected $data              = null;
    protected $database          = null;
    protected $server_id         = null;
    public $appLangFiles         = [];
    public $appThemes            = [];
    public $appName              = '';
    public $appVersion           = '';
    public $form                 = '';
    public $href                 = '';
    public $lang                 = [];
    public $action               = '';
    public $_name                = 'BaseController';
    public $_title               = 'base';
    protected $table_controller  = null;
    protected $trail_controller  = null;
    protected $tree_controller   = null;
    protected $footer_controller = null;
    protected $header_controller = null;
    protected $scripts           = '';
    public $msg                  = '';

    /**
     * Constructs the base controller (common for almost all controllers)
     * @param \Slim\Container $container        the $app container
     * @param boolean         $no_db_connection [optional] if true, sets  $this->misc->setNoDBConnection(true);
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

        if ($no_db_connection === true) {
            $this->misc->setNoDBConnection(true);
        }

        if ($this->misc->getNoDBConnection() === false) {
            if ($this->misc->getServerId() === null) {
                echo $this->lang['strnoserversupplied'];
                exit;
            }
            $_server_info = $this->misc->getServerInfo();
            // Redirect to the login form if not logged in
            if (!isset($_server_info['username'])) {

                $login_controller = new \PHPPgAdmin\Controller\LoginController($container);
                echo $login_controller->doLoginForm($msg);

                exit;
            }
        }

        //\PC::debug(['name' => $this->_name, 'no_db_connection' => $this->misc->getNoDBConnection()], 'instanced controller');
    }

    public function render()
    {
        $misc   = $this->misc;
        $lang   = $this->lang;
        $action = $this->action;

        $this->printHeader($lang[$this->_title]);
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
        if ($this->table_controller === null) {
            $this->table_controller = new \PHPPgAdmin\XHtml\HTMLTableController($this->getContainer(), $this->_name);
        }
        return $this->table_controller;
    }

    private function getFooterController()
    {
        if ($this->footer_controller === null) {
            $this->footer_controller = new \PHPPgAdmin\XHtml\HTMLFooterController($this->getContainer(), $this->_name);
        }
        return $this->footer_controller;
    }

    private function getHeaderController()
    {
        if ($this->header_controller === null) {
            $this->header_controller = new \PHPPgAdmin\XHtml\HTMLHeaderController($this->getContainer(), $this->_name);
        }
        return $this->header_controller;
    }

    private function getNavbarController()
    {
        if ($this->trail_controller === null) {
            $this->trail_controller = new \PHPPgAdmin\XHtml\HTMLNavbarController($this->getContainer(), $this->_name);
        }

        return $this->trail_controller;
    }

    private function getTreeController()
    {
        if ($this->tree_controller === null) {
            $this->tree_controller = new \PHPPgAdmin\XHtml\TreeController($this->getContainer(), $this->_name);
        }

        return $this->tree_controller;
    }

    /**
     * Instances an HTMLTable and returns its html content
     * @param  [type] &$tabledata [description]
     * @param  [type] &$columns   [description]
     * @param  [type] &$actions   [description]
     * @param  [type] $place      [description]
     * @param  [type] $nodata     [description]
     * @param  [type] $pre_fn     [description]
     * @return [type]             [description]
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
        if ($from === null) {
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
     * Outputs JavaScript to set default focus
     * @param $object eg. forms[0].username
     */
    public function setFocus($object)
    {
        $footer_controller = $this->getFooterController();
        return $footer_controller->setFocus($object);
    }

    /**
     * Outputs JavaScript to set the name of the browser window.
     * @param $name the window name
     * @param $addServer if true (default) then the server id is
     *        attached to the name.
     */
    public function setWindowName($name, $addServer = true)
    {
        $footer_controller = $this->getFooterController();
        return $footer_controller->setWindowName($name, $addServer);
    }

    public function setNoOutput($flag)
    {
        $header_controller = $this->getHeaderController();
        return $header_controller->setNoOutput(boolval($flag));
    }

    public function printHeader($title = '', $script = null, $do_print = true, $template = 'header.twig')
    {
        $header_controller = $this->getHeaderController();
        return $header_controller->printHeader($title, $script, $do_print, $template);

    }

    public function printBody($doBody = true, $bodyClass = 'detailbody')
    {
        $header_controller = $this->getHeaderController();
        return $header_controller->printBody($doBody, $bodyClass);

    }

    public function printTitle($title, $help = null, $do_print = true)
    {
        $header_controller = $this->getHeaderController();
        return $header_controller->printTitle($title, $help, $do_print);

    }

    /**
     * Print out a message
     *
     * @param      $msg The message to print
     * @param bool $do_print
     * @return string
     */
    public function printMsg($msg, $do_print = true)
    {
        $html = '';
        $msg  = htmlspecialchars(\PHPPgAdmin\HelperTrait::br2ln($msg));
        if ($msg != '') {

            $html .= '<p class="message">' . nl2br($msg) . '</p>' . "\n";
        }
        if ($do_print) {
            echo $html;
        } else {
            return $html;
        }

    }

}
