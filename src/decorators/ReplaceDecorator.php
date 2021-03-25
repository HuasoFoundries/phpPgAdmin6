<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Decorators;

class ReplaceDecorator extends Decorator
{
    /**
     * @var mixed
     */
    public $s;

    public $p;

    public function __construct($str, $params)
    {
        $this->s = $str;
        $this->p = $params;
    }

    public function value($fields)
    {
        $str = $this->s;

        foreach ($this->p as $k => $v) {
            $str = \str_replace($k, Decorator::get_sanitized_value($v, $fields), $str);
        }

        return $str;
    }
}
