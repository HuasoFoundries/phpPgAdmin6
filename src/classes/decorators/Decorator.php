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

}
