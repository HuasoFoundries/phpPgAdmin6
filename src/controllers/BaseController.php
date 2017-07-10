<?php

namespace PHPPgAdmin\Controller;

/**
 * Base controller class
 */
class BaseController {

	use \PHPPgAdmin\DebugTrait;

	private $container = null;
	private $_connection = null;
	private $app = null;
	private $data = null;
	private $database = null;
	private $server_id = null;
	public $appLangFiles = [];
	public $appThemes = [];
	public $appName = '';
	public $appVersion = '';
	public $form = '';
	public $href = '';
	public $lang = [];
	public $action = '';
	public $_name = 'BaseController';
	public $_title = 'base';
	private $table_controller = null;
	private $trail_controller = null;
	private $tree_controller = null;
	private $_no_output = false;
	public $msg = '';

	/* Constructor */
	function __construct(\Slim\Container $container) {
		$this->container = $container;
		$this->lang = $container->get('lang');

		$this->view = $container->get('view');
		$this->plugin_manager = $container->get('plugin_manager');
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

	function setNoOutput($flag) {
		$this->_no_output = boolval($flag);
		$this->misc->setNoOutput(boolval($flag));
		return $this;
	}

	public function getContainer() {
		return $this->container;
	}

	private function getTableController() {
		if ($this->table_controller === null) {
			$this->table_controller = new \PHPPgAdmin\XHtml\HTMLTableController($this->getContainer(), $this->_name);
		}
		return $this->table_controller;
	}

	private function getNavbarController() {
		if ($this->trail_controller === null) {
			$this->trail_controller = new \PHPPgAdmin\XHtml\HTMLNavbarController($this->getContainer(), $this->_name);
		}

		return $this->trail_controller;
	}

	private function getTreeController() {
		if ($this->tree_controller === null) {
			$this->tree_controller = new \PHPPgAdmin\XHtml\TreeController($this->getContainer(), $this->_name);
		}

		return $this->tree_controller;
	}

	/**
	 * Prints the page header.  If member variable $this->_no_output is
	 * set then no header is drawn.
	 * @param $title The title of the page
	 * @param $script script tag
	 * @param $do_print boolean if false, the function will return the header content
	 */
	function printHeader($title = '', $script = null, $do_print = true, $template = 'header.twig') {

		if (function_exists('newrelic_disable_autorum')) {
			newrelic_disable_autorum();
		}
		$appName = $this->appName;
		$lang = $this->lang;
		$plugin_manager = $this->plugin_manager;

		$this->prtrace('appName', $appName);

		$viewVars = [];
		//$viewVars = $this->lang;
		if (isset($_SESSION['isolang'])) {
			$viewVars['isolang'] = $_SESSION['isolang'];
			$viewVars['applocale'] = $lang['applocale'];
		}

		$viewVars['dir'] = (strcasecmp($lang['applangdir'], 'ltr') != 0) ? ' dir="' . htmlspecialchars($lang['applangdir']) . '"' : '';
		$viewVars['headertemplate'] = $template;
		$viewVars['title'] = $title;
		$viewVars['appName'] = htmlspecialchars($this->appName) . (($title != '') ? htmlspecialchars(" - {$title}") : '');

		$this->prtrace($viewVars);
		$header_html = $this->view->fetch($template, $viewVars);

		if ($script) {
			$header_html .= "{$script}\n";
		}

		$plugins_head = [];
		$_params = ['heads' => &$plugins_head];

		$plugin_manager->do_hook('head', $_params);

		foreach ($plugins_head as $tag) {
			$header_html .= $tag;
		}

		$header_html .= "</head>\n";

		if (!$this->_no_output && $do_print) {

			header("Content-Type: text/html; charset=utf-8");
			echo $header_html;

		} else {
			return $header_html;
		}
	}

	/**
	 * Prints the page body.
	 * @param $doBody True to output body tag, false to return
	 * @param $bodyClass - name of body class
	 */
	function printBody($doBody = true, $bodyClass = 'detailbody') {

		$bodyClass = htmlspecialchars($bodyClass);
		$bodyHtml = '<body data-controller="' . $this->_name . '" class="' . $bodyClass . '" >';
		$bodyHtml .= "\n";

		if (!$this->_no_output && $doBody) {
			echo $bodyHtml;
		} else {
			return $bodyHtml;
		}
	}

	/**
	 * Displays link to the context help.
	 * @param $str   - the string that the context help is related to (already escaped)
	 * @param $help  - help section identifier
	 * @param $do_print true to echo, false to return
	 */
	function printHelp($str, $help = null, $do_print = true) {
		//\PC::debug(['str' => $str, 'help' => $help], 'printHelp');
		if ($help !== null) {
			$helplink = $this->getHelpLink($help);
			$str .= '<a class="help" href="' . $helplink . '" title="' . $this->lang['strhelp'] . '" target="phppgadminhelp">' . $this->lang['strhelpicon'] . '</a>';

		}
		if ($do_print) {
			echo $str;
		} else {
			return $str;
		}
	}

	function getHelpLink($help) {
		return htmlspecialchars("/src/views/help.php?help=" . urlencode($help) . "&server=" . urlencode($this->server_id));

	}

	/**
	 * Print out the page heading and help link
	 * @param $title Title, already escaped
	 * @param $help (optional) The identifier for the help link
	 */
	function printTitle($title, $help = null, $do_print = true) {
		$data = $this->data;
		$lang = $this->lang;

		$title_html = "<h2>";
		$title_html .= $this->printHelp($title, $help, false);
		$title_html .= "</h2>\n";

		if ($do_print) {
			echo $title_html;
		} else {
			return $title_html;
		}
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
	function printTable(&$tabledata, &$columns, &$actions, $place, $nodata = null, $pre_fn = null) {
		$html_table = $this->getTableController();
		return $html_table->printTable($tabledata, $columns, $actions, $place, $nodata, $pre_fn);
	}

	function adjustTabsForTree($tabs) {
		$tree = $this->getTreeController();
		return $tree->adjustTabsForTree($tabs);
	}

	function printTree(&$_treedata, &$attrs, $section) {
		$tree = $this->getTreeController();
		return $tree->printTree($_treedata, $attrs, $section);
	}

	function printTrail($trail = [], $do_print = true) {
		$html_trail = $this->getNavbarController();
		return $html_trail->printTrail($trail, $do_print);
	}

	function printNavLinks($navlinks, $place, $env = [], $do_print = true) {
		$html_trail = $this->getNavbarController();
		return $html_trail->printNavLinks($navlinks, $place, $env, $do_print);
	}

	function printTabs($tabs, $activetab, $do_print = true) {
		$html_trail = $this->getNavbarController();
		return $html_trail->printTabs($tabs, $activetab, $do_print);
	}
	function getLastTabURL($section) {
		$html_trail = $this->getNavbarController();
		return $html_trail->getLastTabURL($section);
	}

	function printLink($link, $do_print = true) {
		$html_trail = $this->getNavbarController();
		return $html_trail->printLink($link, $do_print);
	}

	/**
	 * Outputs JavaScript to set default focus
	 * @param $object eg. forms[0].username
	 */
	function setFocus($object) {
		echo "<script type=\"text/javascript\">\n";
		echo "   document.{$object}.focus();\n";
		echo "</script>\n";
	}

	/**
	 * Outputs JavaScript to set the name of the browser window.
	 * @param $name the window name
	 * @param $addServer if true (default) then the server id is
	 *        attached to the name.
	 */
	function setWindowName($name, $addServer = true) {
		echo "<script type=\"text/javascript\">\n";
		echo "//<![CDATA[\n";
		echo "   window.name = '{$name}", ($addServer ? ':' . htmlspecialchars($this->server_id) : ''), "';\n";
		echo "//]]>\n";
		echo "</script>\n";
	}

	public function render() {
		$misc = $this->misc;
		$lang = $this->lang;
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

	public function doDefault() {
		$html = '<div><h2>Section title</h2> <p>Main content</p></div>';
		echo $html;
		return $html;
	}
}