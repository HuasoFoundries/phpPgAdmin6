<?php

/**
 * PHPPgAdmin v6.0.0-beta.45
 */

namespace PHPPgAdmin\Decorators;

class FieldDecorator extends Decorator
{
    public function __construct($fieldName, $default = null)
    {
        $this->f = $fieldName;
        if (null !== $default) {
            $this->d = $default;
        }
    }

    public function value($fields)
    {
        if (isset($fields[$this->f])) {
            return Decorator::get_sanitized_value($fields[$this->f], $fields);
        }
        if (isset($this->d)) {
            return $this->d;
        }

        return null;
    }
}
