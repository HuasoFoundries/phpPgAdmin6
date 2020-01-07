<?php

/**
 * PHPPgAdmin v6.0.0-RC1
 */

namespace PHPPgAdmin\Decorators;

class IfEmptyDecorator extends Decorator
{
    public function __construct($value, $empty, $full = null)
    {
        $this->v = $value;
        $this->e = $empty;
        if (null !== $full) {
            $this->f = $full;
        }
    }

    public function value($fields)
    {
        $val = Decorator::get_sanitized_value($this->v, $fields);
        if (empty($val)) {
            return Decorator::get_sanitized_value($this->e, $fields);
        }

        return isset($this->f) ? Decorator::get_sanitized_value($this->f, $fields) : $val;
    }
}
