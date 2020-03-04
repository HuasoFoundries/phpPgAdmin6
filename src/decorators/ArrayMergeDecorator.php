<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
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
            $accum = \array_merge($accum, Decorator::get_sanitized_value($var, $fields));
        }

        return $accum;
    }
}
