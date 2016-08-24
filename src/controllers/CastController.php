<?php

namespace PHPPgAdmin\Controller;
use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class
 */
class CastController extends BaseController {
	public $_name = 'CastController';

/**
 * Show default list of casts in the database
 */
	public function doDefault($msg = '') {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		function renderCastContext($val) {
			global $lang;
			switch ($val) {
				case 'e':return $lang['strno'];
				case 'a':return $lang['strinassignment'];
				default:return $lang['stryes'];
			}
		}

		$misc->printTrail('database');
		$misc->printTabs('database', 'casts');
		$misc->printMsg($msg);

		$casts = $data->getCasts();

		$columns = [
			'source_type' => [
				'title' => $lang['strsourcetype'],
				'field' => Decorator::field('castsource'),
			],
			'target_type' => [
				'title' => $lang['strtargettype'],
				'field' => Decorator::field('casttarget'),
			],
			'function' => [
				'title' => $lang['strfunction'],
				'field' => Decorator::field('castfunc'),
				'params' => ['null' => $lang['strbinarycompat']],
			],
			'implicit' => [
				'title' => $lang['strimplicit'],
				'field' => Decorator::field('castcontext'),
				'type' => 'callback',
				'params' => ['function' => 'renderCastContext', 'align' => 'center'],
			],
			'comment' => [
				'title' => $lang['strcomment'],
				'field' => Decorator::field('castcomment'),
			],
		];

		$actions = [];

		echo $misc->printTable($casts, $columns, $actions, 'casts-casts', $lang['strnocasts']);
	}
}
