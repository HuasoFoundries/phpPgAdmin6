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
   *
   * @param string $msg
   * @return string|void
   */
	public function doDefault($msg = '') {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		$renderCastContext = function ($val) use ($lang) {

			switch ($val) {
			case 'e':return $lang['strno'];
			case 'a':return $lang['strinassignment'];
			default:return $lang['stryes'];
			}
		};

		$this->printTrail('database');
		$this->printTabs('database', 'casts');
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
				'params' => ['function' => $renderCastContext, 'align' => 'center'],
			],
			'comment' => [
				'title' => $lang['strcomment'],
				'field' => Decorator::field('castcomment'),
			],
		];

		$actions = [];

		echo $this->printTable($casts, $columns, $actions, 'casts-casts', $lang['strnocasts']);
	}

	public function render() {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$action = $this->action;
		if ($action == 'tree') {
			return $this->doTree();
		}
		$data = $misc->getDatabaseAccessor();

		$this->printHeader($lang['strcasts']);
		$this->printBody();

		switch ($action) {

		default:
			$this->doDefault();
			break;
		}

		return $misc->printFooter();

	}

/**
 * Generate XML for the browser tree.
 */
	public function doTree() {

		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		$casts = $data->getCasts();

		$proto = Decorator::concat(Decorator::field('castsource'), ' AS ', Decorator::field('casttarget'));

		$attrs = [
			'text' => $proto,
			'icon' => 'Cast',
		];

		return $this->printTree($casts, $attrs, 'casts');

	}

}
