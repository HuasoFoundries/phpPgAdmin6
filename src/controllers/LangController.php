<?php

namespace PHPPgAdmin\Controller;
use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class LangController extends BaseController {
	public $_name = 'LangController';

	public function render() {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$action = $this->action;
		if ($action == 'tree') {
			return $this->doTree();
		}

		$this->printHeader($lang['strlanguages']);
		$this->printBody();

		switch ($action) {
		default:
			$this->doDefault();
			break;
		}

		$misc->printFooter();

	}

	/**
	 * Show default list of languages in the database
	 */
	public function doDefault($msg = '') {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		$this->printTrail('database');
		$this->printTabs('database', 'languages');
		$misc->printMsg($msg);

		$languages = $data->getLanguages();

		$columns = [
			'language' => [
				'title' => $lang['strname'],
				'field' => Decorator::field('lanname'),
			],
			'trusted' => [
				'title' => $lang['strtrusted'],
				'field' => Decorator::field('lanpltrusted'),
				'type' => 'yesno',
			],
			'function' => [
				'title' => $lang['strfunction'],
				'field' => Decorator::field('lanplcallf'),
			],
		];

		$actions = [];

		echo $this->printTable($languages, $columns, $actions, 'languages-languages', $lang['strnolanguages']);
	}

	/**
	 * Generate XML for the browser tree.
	 */
	function doTree() {

		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		$languages = $data->getLanguages();

		$attrs = [
			'text' => Decorator::field('lanname'),
			'icon' => 'Language',
		];

		return $this->printTree($languages, $attrs, 'languages');

	}

}
