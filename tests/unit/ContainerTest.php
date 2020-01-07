<?php

/**
 * PHPPgAdmin v6.0.0-RC1.
 */
class ContainerTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $container;

    protected function _before()
    {
        $Helper = $this->getModule('\Helper\Unit');
        $this->container = $Helper->getContainer();
        $this->container->misc->setNoDBConnection(true);
        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);
    }

    protected function _after()
    {
    }

    public function testContainerValidity()
    {
        $utils = $this->container['utils'];
        $this->assertTrue(
            $this->container instanceof \Psr\Container\ContainerInterface,
            '$container must be an instance of \Psr\Container\ContainerInterface'
        );
    }

    public function testContainerUtils()
    {
        $utils = $this->container['utils'];
        $this->assertTrue(
            $this->container->utils instanceof \PHPPgAdmin\ContainerUtils,
            '$container->utils must be an instance of PHPPgAdmin\ContainerUtils'
        );
    }

    public function testContainerplugin_manager()
    {
        $this->assertTrue(
            $this->container->plugin_manager instanceof \PHPPgAdmin\PluginManager,
            '$container->plugin_manager must be an instance of nstanceof  \PHPPgAdmin\PluginManager'
        );
    }

    public function testContainermisc()
    {
        $this->assertTrue(
            $this->container->misc instanceof \PHPPgAdmin\Misc,
            '$container->misc must be an instance of \PHPPgAdmin\Misc'
        );
    }

    public function testContainerview()
    {
        $this->assertTrue(
            $this->container->view instanceof \Slim\Views\Twig,
            '$container->view must be an instance of \Slim\Views\Twig'
        );
    }
}
