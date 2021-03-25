<?php

/**
 * PHPPgAdmin6
 */

namespace Tests\Unit;

use PHPPgAdmin\ContainerUtils;
use PHPPgAdmin\Misc;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

beforeEach(function () {
    $this->container = containerInstance();
    $this->container->get('misc')->setNoDBConnection(true);
});

    it('Ensures container is instance of Slim ContainerInterface', function () {
        expect($this->container)->toBeInstanceOf(ContainerInterface::class);
    });

    it('Ensures container is instance of ContainerUtils', function () {
        expect($this->container)->toBeInstanceOf(ContainerUtils::class);
    });

    it('Ensures container->misc is instance of PHPPgAdmin\Misc', function () {
        expect($this->container->misc)->toBeInstanceOf(Misc::class);
    });

    it('Ensures container->view is an instance of Slim\Views\Twig', function () {
        expect($this->container->view)->toBeInstanceOf(Twig::class);
    });
