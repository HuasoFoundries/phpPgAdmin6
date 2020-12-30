<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Middleware;

use PHPPgAdmin\Traits\HelperTrait;

class Middleware
{
    use HelperTrait;

    protected $container;

    protected $router;

    public function __construct($container)
    {
        $this->container = $container;
        $this->router = $container->get('router');
    }

    public function __get($property)
    {
        if (isset($this->container->{$property})) {
            return $this->container->{$property};
        }
    }
}
