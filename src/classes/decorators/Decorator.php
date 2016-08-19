<?php
namespace PHPPgAdmin\Decorators;

class Decorator {
	function __construct($value) {
		$this->v = $value;
	}

	function value($fields) {
		return $this->v;
	}

	public static function field($fieldName, $default = null) {
		return new FieldDecorator($fieldName, $default);
	}

	public static function branchurl($base, $vars = null/* ... */) {
		// If more than one array of vars is given,
		// use an ArrayMergeDecorator to have them merged
		// at value evaluation time.
		if (func_num_args() > 2) {
			$v = func_get_args();
			array_shift($v);

			return new BranchUrlDecorator($base, new ArrayMergeDecorator($v));
		}
		return new BranchUrlDecorator($base, $vars);
	}
	public static function actionurl($base, $vars = null/* ... */) {
		// If more than one array of vars is given,
		// use an ArrayMergeDecorator to have them merged
		// at value evaluation time.
		if (func_num_args() > 2) {
			$v = func_get_args();
			array_shift($v);

			return new ActionUrlDecorator($base, new ArrayMergeDecorator($v));
		}
		return new ActionUrlDecorator($base, $vars);
	}

	public static function redirecturl($base, $vars = null/* ... */) {
		// If more than one array of vars is given,
		// use an ArrayMergeDecorator to have them merged
		// at value evaluation time.
		if (func_num_args() > 2) {
			$v = func_get_args();
			array_shift($v);

			return new RedirectUrlDecorator($base, new ArrayMergeDecorator($v));
		}
		return new RedirectUrlDecorator($base, $vars);
	}

	public static function url($base, $vars = null/* ... */) {
		// If more than one array of vars is given,
		// use an ArrayMergeDecorator to have them merged
		// at value evaluation time.
		if (func_num_args() > 2) {
			$v = func_get_args();
			array_shift($v);
			return new UrlDecorator($base, new ArrayMergeDecorator($v));
		}
		return new UrlDecorator($base, $vars);
	}

	public static function ifempty($value, $empty, $full = null) {
		return new IfEmptyDecorator($value, $empty, $full);
	}

}
