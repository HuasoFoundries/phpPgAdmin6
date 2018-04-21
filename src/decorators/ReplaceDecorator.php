<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
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
            $str = str_replace($k, Decorator::get_sanitized_value($v, $fields), $str);
        }

        return $str;
    }
}
