<?php

    namespace PHPPgAdmin\Decorators;

class CallbackDecorator extends Decorator
{
    public function __construct($callback, $param = null)
    {
        $this->fn = $callback;
        $this->p  = $param;
    }

    public function value($fields)
    {
        return call_user_func($this->fn, $fields, $this->p);
    }
}
