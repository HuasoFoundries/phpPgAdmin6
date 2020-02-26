<?php

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 */

namespace PHPPgAdmin\Decorators;

class FieldDecorator extends Decorator
{
    public function __construct($fieldName, $default = null)
    {
        $this->f = $fieldName;

        $this->d = $default;
    }

    public function value($fields)
    {
        if (isset($fields[$this->f])) {
            return Decorator::get_sanitized_value($fields[$this->f], $fields);
        }

        return $this->d;
    }
}
