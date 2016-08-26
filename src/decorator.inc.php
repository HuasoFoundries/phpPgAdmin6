<?php
// $Id: decorator.inc.php,v 1.8 2007/04/05 11:09:38 mr-russ Exp $

// This group of functions and classes provides support for
// resolving values in a lazy manner (ie, as and when required)
// using the Decorator pattern.

###TODO: Better documentation!!!

// Construction functions:

function concat( /* ... */) {
	return new \PHPPgAdmin\Decorators\ConcatDecorator(func_get_args());
}

function callback($callback, $params = null) {
	return new \PHPPgAdmin\Decorators\CallbackDecorator($callback, $params);
}

function ifempty($value, $empty, $full = null) {
	return new \PHPPgAdmin\Decorators\IfEmptyDecorator($value, $empty, $full);
}

function replace($str, $params) {
	return new \PHPPgAdmin\Decorators\replaceDecorator($str, $params);
}

// Resolving functions:

function value(&$var, &$fields, $esc = null) {
	if (is_a($var, 'PHPPgAdmin\Decorators\Decorator')) {
		$val = $var->value($fields);
	} else {
		$val = &$var;
	}

	if (is_string($val)) {
		switch ($esc) {
			case 'xml':
				return strtr($val, [
					'&' => '&amp;',
					"'" => '&apos;', '"' => '&quot;',
					'<' => '&lt;', '>' => '&gt;',
				]);
			case 'html':
				return htmlentities($val, ENT_COMPAT, 'UTF-8');
			case 'url':
				return urlencode($val);
		}
	}
	return $val;
}

function value_xml(&$var, &$fields) {
	return value($var, $fields, 'xml');
}

function value_xml_attr($attr, &$var, &$fields) {
	$val = value($var, $fields, 'xml');
	if (!empty($val)) {
		return " {$attr}=\"{$val}\"";
	} else {
		return '';
	}

}

function value_url(&$var, &$fields) {
	return value($var, $fields, 'url');
}
