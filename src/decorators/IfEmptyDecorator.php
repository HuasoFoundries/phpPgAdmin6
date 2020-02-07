<?php

/**
 * PHPPgAdmin v6.0.0-RC8.
 */

namespace PHPPgAdmin\Decorators;

class IfEmptyDecorator extends Decorator
{
    public function __construct($value, $empty, $full = null)
    {
        $this->val = $value;
        $this->empty = $empty;
        if (null !== $full) {
            $this->full = $full;
        }
    }

    public function value($fields)
    {
        $val = Decorator::get_sanitized_value($this->val, $fields);
        if (empty($val)) {
            return Decorator::get_sanitized_value($this->empty, $fields);
        }

        return isset($this->full) ? Decorator::get_sanitized_value($this->full, $fields) : $val;
    }
}
