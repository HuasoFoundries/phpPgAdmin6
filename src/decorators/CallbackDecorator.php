<?php

/**
 * PHPPgAdmin v6.0.0-RC8-20-g1bc0ba72
 */

namespace PHPPgAdmin\Decorators;

class CallbackDecorator extends Decorator
{
    public function __construct($callback, $param = null)
    {
        $this->fn = $callback;
        $this->p = $param;
    }

    public function value($fields)
    {
        return \call_user_func($this->fn, $fields, $this->p);
    }
}
