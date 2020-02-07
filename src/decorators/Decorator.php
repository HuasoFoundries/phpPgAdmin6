<?php

/**
 * PHPPgAdmin v6.0.0-RC8.
 */

namespace PHPPgAdmin\Decorators;

class Decorator
{
    use \PHPPgAdmin\Traits\HelperTrait;

    public function __construct($value)
    {
        $this->val = $value;
    }

    public function value($fields)
    {
        return $this->val;
    }

    public static function get_sanitized_value(&$var, &$fields, $esc = null)
    {
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
                        "'" => '&apos;',
                        '"' => '&quot;',
                        '<' => '&lt;',
                        '>' => '&gt;',
                    ]);
                case 'html':
                    return htmlentities($val, ENT_COMPAT, 'UTF-8');
                case 'url':
                    return urlencode($val);
            }
        }

        return $val;
    }

    public static function callback($callback, $params = null)
    {
        return new \PHPPgAdmin\Decorators\CallbackDecorator($callback, $params);
    }

    public static function value_url(&$var, &$fields)
    {
        return self::get_sanitized_value($var, $fields, 'url');
    }

    public static function concat(/* ... */)
    {
        return new \PHPPgAdmin\Decorators\ConcatDecorator(func_get_args());
    }

    public static function replace($str, $params)
    {
        return new \PHPPgAdmin\Decorators\ReplaceDecorator($str, $params);
    }

    public static function field($fieldName, $default = null)
    {
        return new FieldDecorator($fieldName, $default);
    }

    public static function branchurl($base, $vars = null/* ... */)
    {
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

    public static function actionurl($base, $vars = null/* ... */)
    {
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

    public static function redirecturl($base, $vars = null/* ... */)
    {
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

    public static function url($base, $vars = null/* ... */)
    {
        // If more than one array of vars is given,
        // use an ArrayMergeDecorator to have them merged
        // at value evaluation time.

        if (func_num_args() > 2) {
            $v = func_get_args();
            $base = array_shift($v);

            return new UrlDecorator($base, new ArrayMergeDecorator($v));
        }

        return new UrlDecorator($base, $vars);
    }

    public static function ifempty($value, $empty, $full = null)
    {
        return new IfEmptyDecorator($value, $empty, $full);
    }
}
