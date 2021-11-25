<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Decorators;

use PHPPgAdmin\Traits\HelperTrait;

class Decorator
{
    use HelperTrait;

    public $val;

    public $container;

    public function __construct($value)
    {
        $this->val = $value;
    }

    /**
     * In specific decorators this method has a meaningful purpose.
     * In this class it's just a noop.
     */
    public function value(array $fields)
    {
        return $this->val;
    }

    public function sanitizeFor(array &$fields, ?string $escape = null)
    {
        return $this->value($fields);
    }

    /**
     * Just a factory method to chain decorators nicely.
     *
     * @example Decorator::for($field)->sanitizeFor(['name','value'],'html');
     *
     * @param [type] ...$args
     *
     * @return static
     */
    public static function for(...$args): self
    {
        return new static(...$args);
    }

    /**
     * @param mixed  $var
     * @param string $escape
     */
    public static function get_sanitized_value(&$var, array &$fields, ?string $escape = null)
    {
        return ($var instanceof self) ? $var->value($fields) : self::escape($var, $escape);
    }

    /**
     * @return ConcatDecorator
     */
    public static function concat(/* ... */)
    {
        return new ConcatDecorator(\func_get_args());
    }

    /**
     * @return ReplaceDecorator
     */
    public static function replace(string $str, array $params)
    {
        return new ReplaceDecorator($str, $params);
    }

    /**
     * @param scalar $var
     */
    public static function value_url(&$var, array &$fields)
    {
        return self::get_sanitized_value($var, $fields, 'url');
    }

    /**
     * @return FieldDecorator
     */
    public static function field(string $fieldName, ?array $default = null)
    {
        return new FieldDecorator($fieldName, $default);
    }

    /**
     * @param mixed $base
     *
     * @return BranchUrlDecorator
     */
    public static function branchurl($base, ?array $vars = null/* ... */)
    {
        // If more than one array of vars is given,
        // use an ArrayMergeDecorator to have them merged
        // at value evaluation time.
        if (2 < \func_num_args()) {
            $urlvalue = \func_get_args();
            \array_shift($urlvalue);

            return new BranchUrlDecorator($base, new ArrayMergeDecorator($urlvalue));
        }

        return new BranchUrlDecorator($base, $vars);
    }

    /**
     * @param mixed $base
     *
     * @return ActionUrlDecorator
     */
    public static function actionurl($base, ?array $vars = null/* ... */)
    {
        // If more than one array of vars is given,
        // use an ArrayMergeDecorator to have them merged
        // at value evaluation time.
        if (2 < \func_num_args()) {
            $urlvalue = \func_get_args();
            \array_shift($urlvalue);

            return new ActionUrlDecorator($base, new ArrayMergeDecorator($urlvalue));
        }

        return new ActionUrlDecorator($base, $vars);
    }

    /**
     * @param mixed $base
     *
     * @return RedirectUrlDecorator
     */
    public static function redirecturl($base, ?array $vars = null/* ... */)
    {
        // If more than one array of vars is given,
        // use an ArrayMergeDecorator to have them merged
        // at value evaluation time.
        if (2 < \func_num_args()) {
            $urlvalue = \func_get_args();
            \array_shift($urlvalue);

            return new RedirectUrlDecorator($base, new ArrayMergeDecorator($urlvalue));
        }

        return new RedirectUrlDecorator($base, $vars);
    }

    /**
     * @param FieldDecorator    $value
     * @param null|UrlDecorator $full
     *
     * @return IfEmptyDecorator
     */
    public static function ifempty($value, string $empty, $full = null)
    {
        return new IfEmptyDecorator($value, $empty, $full);
    }

    /**
     * @param mixed $base
     *
     * @return UrlDecorator
     */
    public static function url($base, ?array $vars = null/* ... */)
    {
        // If more than one array of vars is given,
        // use an ArrayMergeDecorator to have them merged
        // at value evaluation time.

        if (2 < \func_num_args()) {
            $urlvalue = \func_get_args();
            $base = \array_shift($urlvalue);

            return new UrlDecorator($base, new ArrayMergeDecorator($urlvalue));
        }

        return new UrlDecorator($base, $vars);
    }
  /**
     * @param ((mixed|string)[]|null) $params
     *
     * @return CallbackDecorator
     */
    public static function callback(Closure $callback, ?array $params = null)
    {
        return new CallbackDecorator($callback, $params);
    }

    private static function escape(&$value, ?string $escape = null)
    {
        if (\is_string($value)) {
            switch ($escape) {
                case 'xml':
                    return \strtr($value, [
                        '&' => '&amp;',
                        "'" => '&apos;',
                        '"' => '&quot;',
                        '<' => '&lt;',
                        '>' => '&gt;',
                    ]);
                case 'html':
                    return \htmlentities($value, \ENT_COMPAT, 'UTF-8');
                case 'url':
                    return \urlencode($value);
            }
        }

        return $value;
    }
}
