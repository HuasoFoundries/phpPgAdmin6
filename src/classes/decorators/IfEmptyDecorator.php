<?php
namespace PHPPgAdmin\Decorators;

class IfEmptyDecorator extends Decorator {
	function __construct($value, $empty, $full = null) {
		$this->v = $value;
		$this->e = $empty;
		if ($full !== null) {
			$this->f = $full;
		}

	}

	function value($fields) {
		$val = value($this->v, $fields);
		if (empty($val)) {
			return value($this->e, $fields);
		} else {
			return isset($this->f) ? value($this->f, $fields) : $val;
		}

	}
}