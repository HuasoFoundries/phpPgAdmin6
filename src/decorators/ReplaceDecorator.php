<?php

/**
 * PHPPgAdmin v6.0.0-RC8-17-g0a6763af
 */

namespace PHPPgAdmin\Decorators;

class ReplaceDecorator extends Decorator
{
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
