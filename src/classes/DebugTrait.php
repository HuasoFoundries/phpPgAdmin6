<?php
namespace PHPPgAdmin;

trait DebugTrait {
	/**
	 * Receives N parameters and sends them to the console adding where was it called from
	 * @return [type] [description]
	 */
	public function prtrace() {

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		$btarray0 = ([
			'class' => $backtrace[1]['class'],
			'type' => $backtrace[1]['type'],
			'function' => $backtrace[1]['function'],
			'spacer' => ' ',
			'line' => $backtrace[0]['line'],
		]);

		$tag = implode('', $btarray0);

		\PC::debug(func_get_args(), $tag);
	}

}