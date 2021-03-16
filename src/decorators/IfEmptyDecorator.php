<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Decorators;

class IfEmptyDecorator extends Decorator
{
    public $val;
    public $empty;
    /**
     * @var mixed
     */
    public $full;
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

        return property_exists($this, 'full') && $this->full !== null ? Decorator::get_sanitized_value($this->full, $fields) : $val;
    }
}
