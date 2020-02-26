<?php

// declare(strict_types=1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 */

namespace PHPPgAdmin\Middleware;

class Middleware
{
    use \PHPPgAdmin\Traits\HelperTrait;

    protected $container;

    protected $router;

    public function __construct($container)
    {
        $this->container = $container;
        $this->router    = $container->get('router');
    }

    public function __get($property)
    {
        if (isset($this->container->{$property})) {
            return $this->container->{$property};
        }
    }
}
