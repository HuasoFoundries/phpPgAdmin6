<?php

/**
 * PHPPgAdmin v6.0.0-RC1.
 */

namespace PHPPgAdmin\Decorators;

class ArrayMergeDecorator extends Decorator
{
    public function __construct($arrays)
    {
        $this->m = $arrays;
    }

    public function value($fields)
    {
        $accum = [];
        foreach ($this->m as $var) {
            $accum = array_merge($accum, Decorator::get_sanitized_value($var, $fields));
        }

        return $accum;
    }
}
