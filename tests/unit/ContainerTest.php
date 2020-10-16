
<?php

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f.
 *
 * @internal
 * @coversNothing
 */
class ContainerTest extends \Codeception\Test\Unit
{
    protected static $BASE_PATH;

    /**
     * @var \UnitTester
     */
    protected $tester;

    protected $container;

    public function testContainerValidity(): void
    {
         self::assertTrue(
            $this->container instanceof \Psr\Container\ContainerInterface,
            '$container must be an instance of \Psr\Container\ContainerInterface'
        );
    }

    public function testContainerUtils(): void
    {
         self::assertTrue(
            $this->container instanceof \PHPPgAdmin\ContainerUtils,
            '$container->utils must be an instance of PHPPgAdmin\ContainerUtils'
        );
    }

    public function testContainermisc(): void
    {
        self::assertTrue(
            $this->container->misc instanceof \PHPPgAdmin\Misc,
            '$container->misc must be an instance of \PHPPgAdmin\Misc'
        );
    }

    public function testContainerview(): void
    {
        self::assertTrue(
            $this->container->view instanceof \Slim\Views\Twig,
            '$container->view must be an instance of \Slim\Views\Twig'
        );
    }

    protected function _before(): void
    {
        $Helper = $this->getModule('\Helper\Unit');
        $this->container = $Helper::getContainer();
        self::$BASE_PATH = $this->container->BASE_PATH;
        $this->container->misc->setNoDBConnection(true);
    }

    protected function _after(): void
    {
    }
}
