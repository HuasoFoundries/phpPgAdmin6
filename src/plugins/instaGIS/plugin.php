<?php

class instaGIS extends \PHPPgAdmin\Plugin {

	/**
	 * Attributes
	 */
	protected $name = 'instaGIS';
	protected $lang;

	/**
	 * Constructor
	 * Call parent constructor, passing the language that will be used.
	 * @param $language Current phpPgAdmin language. If it was not found in the plugin, English will be used.
	 */
	function __construct($language) {
		parent::__construct($language);
	}

	/**
	 * This method returns the functions to hook in the phpPgAdmin core.
	 * To do include a function just put in the $hooks array the follwing code:
	 *   '<hook_name>' => array('function1', 'function2').
	 *
	 * Example:
	 * $hooks = array(
	 *     'toplinks' => array('add_plugin_toplinks'),
	 *     'tabs' => array('add_tab_entry'),
	 *     'action_buttons' => array('add_more_an_entry')
	 * );
	 *
	 * @return $hooks
	 */
	function get_hooks() {
		$hooks = [

			/*'toplinks' => array(
					'add_plugin_toplinks'
				) ,
				'navlinks' => array(
					'add_plugin_navlinks'
			*/

			'head' => [
				'add_plugin_head',
			],

			/*
				'tabs' => array('...'),
				'trail' => array('...'),

				'actionbuttons' => array('...')
				'logout' => array('...')
			*/
		];
		return $hooks;
	}

	/**
	 * This method returns the functions that will be used as actions.
	 * To do include a function that will be used as action, just put in the $actions array the following code:
	 *
	 * $actions = array(
	 *	'show_page',
	 *	'show_error',
	 * );
	 *
	 * @return $actions
	 */
	function get_actions() {
		$actions = [
			'some_action...',
		];
		return $actions;
	}

	function add_plugin_toplinks(&$plugin_functions_parameters) {
		global $misc;

		$link = [
			'url' => '/plugin.php',
			//php file's name. Every link to a plugin must point to plugin.php
			'urlvars' => [
				//array with the url variables
				'plugin' => $this->name,
				//Every link to a plugin must have its name in it.
				'subject' => 'server',
				'action' => 'show_page',
			],
		];

		$toplink = [
			'bettersql' => [
				'attr' => [
					'href' => [
						'url' => '/sqledit.php',
						'urlvars' => array_merge($reqvars, [
							'action' => 'sql',
						]),
					],
					'target' => "sqledit",
					'id' => 'toplink_sql',
				],
				'content' => 'BetterSQL',
			],
		];

		$plugin_functions_parameters['toplinks']['betterSQL'] = [
			'attr' => [
				'href' => [
					'url' => '/sqledit.php',
					'urlvars' => array_merge($reqvars, [
						'action' => 'sql',
					]),
				],
			],
			'content' => 'BetterSQL',
		];
	}
	/**
	 * Prints HTML code to include plugin's js file
	 *
	 * @return string HTML code of the included javascript
	 */
	private function include_js() {
		return '<script type="text/javascript" src="plugins/' . $this->name . '/js/dom.js"></script>';
	}

	function add_plugin_head(&$plugin_functions_parameters) {
		global $misc;
		$plugin_functions_parameters['heads']['bootstrap']       = '<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css">';
		$plugin_functions_parameters['heads']['bootstrap.theme'] = '<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap-theme.min.css">';
		$plugin_functions_parameters['heads']['fonts']           = '<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Dosis:600|Open+Sans:300,600,400,700|Roboto:300italic,400italic">';
		$plugin_functions_parameters['heads']['bootstrap.js']    = '<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>';

		$plugin_functions_parameters['heads']['include_js'] = $this->include_js();

		return;
	}

	function add_plugin_navlinks(&$plugin_functions_parameters) {
		global $misc;

		$navlinks = [];
		switch ($plugin_functions_parameters['place']) {
			case 'display-browse':
				echo '<iframe src="http://phppga.instagis.com/sqledit.php?subject=table&server=postgismaster.instagis.com%3A5432%3Aallow&database=pstn_db&schema=asistente&action=sql" width="100%" height="200"></iframe>';
				$link = [
					'url' => 'plugin.php',
					'urlvars' => [
						'plugin' => $this->name,
						'subject' => 'show_page',
						'action' => 'show_display_extension',
						'database' => field('database'),
						'table' => field('table'),
					],
				];

				$plugin_functions_parameters['navlinks']['query'] = [
					'attr' => [
						'href' => $link,
					],
					'content' => 'QUERY',
				];

				/*echo 'PLUGIN NAVLINKS <pre>';
					print_r($plugin_functions_parameters['navlinks']);
				*/
				break;

			case 'all_db-databases':
				$navlinks[] = [
					'attr' => [
						'href' => [
							'url' => 'plugin.php',
							'urlvars' => [
								'plugin' => $this->name,
								'subject' => 'show_page',
								'action' => 'show_databases_extension',
							],
						],
					],
					'content' => $this->lang['strdbext'],
				];
				break;
		}

		if (count($navlinks) > 0) {

			//Merge the original navlinks array with Examples' navlinks
			$plugin_functions_parameters['navlinks'] = array_merge($plugin_functions_parameters['navlinks'], $navlinks);
		}
	}
}
