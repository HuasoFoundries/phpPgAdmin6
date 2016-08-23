<?php

namespace PHPPgAdmin\Controller;
use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class LangController extends BaseController {
	public $_name = 'LangController';

/**
 * Show default list of languages in the database
 */
	function doDefault($msg = '') {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		$misc->printTrail('database');
		$misc->printTabs('database', 'languages');
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

		echo $misc->printTable($languages, $columns, $actions, 'languages-languages', $lang['strnolanguages']);
	}
}
