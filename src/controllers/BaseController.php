<?php

<<<<<<< HEAD
namespace PHPPgAdmin\Controller;

/**
 * Base controller class
 */
class BaseController
{

    use \PHPPgAdmin\HelperTrait;

    private $container        = null;
    private $_connection      = null;
    private $app              = null;
    private $data             = null;
    private $database         = null;
    private $server_id        = null;
    public $appLangFiles      = [];
    public $appThemes         = [];
    public $appName           = '';
    public $appVersion        = '';
    public $form              = '';
    public $href              = '';
    public $lang              = [];
    public $action            = '';
    public $_name             = 'BaseController';
    public $_title            = 'base';
    private $table_controller = null;
    private $trail_controller = null;
    private $tree_controller  = null;
    private $_no_output       = false;
    public $msg               = '';

    /* Constructor */
    public function __construct(\Slim\Container $container)
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
        if ($this->misc->getNoDBConnection() === false) {
            if ($this->misc->getServerId() === null) {
                echo $lang['strnoserversupplied'];
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

        $misc->printFooter();
    }

    public function doDefault()
    {
        $html = '<div><h2>Section title</h2> <p>Main content</p></div>';
        echo $html;
        return $html;
    }

    public function setNoOutput($flag)
    {
        $this->_no_output = boolval($flag);
        $this->misc->setNoOutput(boolval($flag));
        return $this;
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

    public function printTree(&$_treedata, &$attrs, $section)
    {
        $tree = $this->getTreeController();
        return $tree->printTree($_treedata, $attrs, $section);
    }

    public function printTrail($trail = [], $do_print = true)
    {
        $html_trail = $this->getNavbarController();
        return $html_trail->printTrail($trail, $do_print);
    }

    public function printNavLinks($navlinks, $place, $env = [], $do_print = true)
    {
        $html_trail = $this->getNavbarController();
        return $html_trail->printNavLinks($navlinks, $place, $env, $do_print);
    }

    public function printTabs($tabs, $activetab, $do_print = true)
    {
        $html_trail = $this->getNavbarController();
        return $html_trail->printTabs($tabs, $activetab, $do_print);
    }

    public function getLastTabURL($section)
    {
        $html_trail = $this->getNavbarController();
        return $html_trail->getLastTabURL($section);
    }

    public function printLink($link, $do_print = true)
    {
        $html_trail = $this->getNavbarController();
        return $html_trail->printLink($link, $do_print);
    }

}
=======
    namespace PHPPgAdmin\Controller;

    /**
     * Base controller class
     */
    class BaseController
    {

        use \PHPPgAdmin\HelperTrait;

        public  $appLangFiles     = [];
        public  $appThemes        = [];
        public  $appName          = '';
        public  $appVersion       = '';
        public  $form             = '';
        public  $href             = '';
        public  $lang             = [];
        public  $action           = '';
        public  $_name            = 'BaseController';
        public  $_title           = 'base';
        public  $msg              = '';
        private $container        = null;
        private $_connection      = null;
        private $app              = null;
        private $data             = null;
        private $database         = null;
        private $server_id        = null;
        private $table_controller = null;
        private $trail_controller = null;
        private $tree_controller  = null;
        private $_no_output       = false;

        /* Constructor */

        public function __construct(\Slim\Container $container)
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
            if ($this->misc->getNoDBConnection() === false) {
                if ($this->misc->getServerId() === null) {
                    echo $lang['strnoserversupplied'];
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

            $misc->printFooter();
        }

        public function doDefault()
        {
            $html = '<div><h2>Section title</h2> <p>Main content</p></div>';
            echo $html;

            return $html;
        }

        public function setNoOutput($flag)
        {
            $this->_no_output = boolval($flag);
            $this->misc->setNoOutput(boolval($flag));

            return $this;
        }

        /**
         * Instances an HTMLTable and returns its html content
         *
         * @param      $tabledata
         * @param      $columns
         * @param      $actions
         * @param      $place
         * @param null $nodata
         * @param null $pre_fn
         * @return string [type]             [description]
         * @internal param $ [type] &$tabledata [description]
         * @internal param $ [type] &$columns   [description]
         * @internal param $ [type] &$actions   [description]
         * @internal param $ [type] $place      [description]
         * @internal param $ [type] $nodata     [description]
         * @internal param $ [type] $pre_fn     [description]
         */
        public function printTable(&$tabledata, &$columns, &$actions, $place, $nodata = null, $pre_fn = null)
        {
            $html_table = $this->getTableController();

            return $html_table->printTable($tabledata, $columns, $actions, $place, $nodata, $pre_fn);
        }

        private function getTableController()
        {
            if ($this->table_controller === null) {
                $this->table_controller = new \PHPPgAdmin\XHtml\HTMLTableController($this->getContainer(), $this->_name);
            }

            return $this->table_controller;
        }

        public function getContainer()
        {
            return $this->container;
        }

        public function adjustTabsForTree($tabs)
        {
            $tree = $this->getTreeController();

            return $tree->adjustTabsForTree($tabs);
        }

        private function getTreeController()
        {
            if ($this->tree_controller === null) {
                $this->tree_controller = new \PHPPgAdmin\XHtml\TreeController($this->getContainer(), $this->_name);
            }

            return $this->tree_controller;
        }

        public function printTree(&$_treedata, &$attrs, $section)
        {
            $tree = $this->getTreeController();

            return $tree->printTree($_treedata, $attrs, $section);
        }

        public function printTrail($trail = [], $do_print = true)
        {
            $html_trail = $this->getNavbarController();

            return $html_trail->printTrail($trail, $do_print);
        }

        private function getNavbarController()
        {
            if ($this->trail_controller === null) {
                $this->trail_controller = new \PHPPgAdmin\XHtml\HTMLNavbarController($this->getContainer(), $this->_name);
            }

            return $this->trail_controller;
        }

        public function printNavLinks($navlinks, $place, $env = [], $do_print = true)
        {
            $html_trail = $this->getNavbarController();

            return $html_trail->printNavLinks($navlinks, $place, $env, $do_print);
        }

        public function printTabs($tabs, $activetab, $do_print = true)
        {
            $html_trail = $this->getNavbarController();

            return $html_trail->printTabs($tabs, $activetab, $do_print);
        }

        public function getLastTabURL($section)
        {
            $html_trail = $this->getNavbarController();

            return $html_trail->getLastTabURL($section);
        }

        public function printLink($link, $do_print = true)
        {
            $html_trail = $this->getNavbarController();

            return $html_trail->printLink($link, $do_print);
        }

    }
>>>>>>> develop
