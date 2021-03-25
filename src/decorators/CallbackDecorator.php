<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Decorators;

use Closure;

class CallbackDecorator extends Decorator
{
    /**
     * @var \Closure|mixed
     */
    public $fn;

    public $p;

    public function __construct(Closure $callback, $param = null)
    {
        $this->fn = $callback;
        $this->p = $param;
    }

    public function value($fields)
    {
        return \call_user_func($this->fn, $fields, $this->p);
    }
}
