<?php
namespace PHPPgAdmin\Decorators;

class ArrayMergeDecorator extends Decorator {
	function __construct($arrays) {
		$this->m = $arrays;
	}

	function value($fields) {
		$accum = array();
		foreach ($this->m as $var) {
			$accum = array_merge($accum, value($var, $fields));
		}
		return $accum;
	}
}