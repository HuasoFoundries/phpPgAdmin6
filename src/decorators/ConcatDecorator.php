<?php

/**
 * PHPPgAdmin v6.0.0-RC1.
 */

namespace PHPPgAdmin\Decorators;

class ConcatDecorator extends Decorator
{
    public function __construct($values)
    {
        $this->c = $values;
    }

    public function value($fields)
    {
        $accum = '';
        foreach ($this->c as $var) {
            $accum .= Decorator::get_sanitized_value($var, $fields);
        }

        return trim($accum);
    }
}
