<?php

namespace PHPPgAdmin\Controller;

/**
 * Base controller class
 */
class BaseController {

	private $_connection       = null;
	private $_no_db_connection = false;
	private $_reload_browser   = false;
	private $app               = null;
	private $data              = null;
	private $database          = null;
	private $server_id         = null;
	public $appLangFiles       = [];
	public $appThemes          = [];
	public $appName            = '';
	public $appVersion         = '';
	public $form               = '';
	public $href               = '';
	public $lang               = [];
	public $_name              = 'BaseController';

	/* Constructor */
	function __construct(\Slim\Container $container) {

		$this->lang           = $container->get('lang');
		$this->conf           = $container->get('conf');
		$this->view           = $container->get('view');
		$this->plugin_manager = $container->get('plugin_manager');
		$this->appName        = $container->get('settings')['appName'];
		$this->appVersion     = $container->get('settings')['appVersion'];
		$this->appLangFiles   = $container->get('appLangFiles');
		$this->misc           = $container->get('misc');
		$this->appThemes      = $container->get('appThemes');

		\PC::debug($this->_name, 'instanced controller');
	}

	public function doDefault() {
		return $this;
	}
}