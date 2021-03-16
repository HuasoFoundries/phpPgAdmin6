<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Decorators;

class FieldDecorator extends Decorator
{
    /**
     * @var mixed|mixed[]
     */
    public $fieldName;
    public $defaultValue;
    public function __construct($fieldName, $defaultValue = null)
    {
        $this->fieldName = $fieldName;

        $this->defaultValue = $defaultValue;
    }

    public function value($fields)
    {
        if (isset($fields[$this->fieldName])) {
            return Decorator::get_sanitized_value($fields[$this->fieldName], $fields);
        }

        return $this->defaultValue;
    }
}
