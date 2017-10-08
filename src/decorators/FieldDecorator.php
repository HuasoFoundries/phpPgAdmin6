<?php

namespace PHPPgAdmin\Decorators;

class FieldDecorator extends Decorator
{
    public function __construct($fieldName, $default = null)
    {
        $this->f = $fieldName;
        if ($default !== null) {
            $this->d = $default;
        }
    }

    public function value($fields)
    {
        if (isset($fields[$this->f])) {
            return Decorator::get_sanitized_value($fields[$this->f], $fields);
        } elseif (isset($this->d)) {
            return $this->d;
        } else {
            return null;
        }
    }
}
