<?php

/**
 * PHPPgAdmin 6.1.3
 */

/**
 * @internal
 * @coversNothing
 */
class DataEntitiesTest extends \Codeception\Test\Unit
{
    protected static $BASE_PATH;

    protected ?\PHPPgAdmin\ContainerUtils $container;

    //const BASE_PATH = self::BASE_PATH;

    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests

    public function testDataexportView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/dataexport.php';
        $controller = dataexportFactory($_container);
        self::assertSame($controller->controller_name, 'DataexportController', 'controller name should be DataexportController');
    }

    public function testDataimportView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/dataimport.php';
        $controller = dataimportFactory($_container);
        self::assertSame($controller->controller_name, 'DataimportController', 'controller name should be DataimportController');
    }

    public function testDbexportView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/dbexport.php';
        $controller = dbexportFactory($_container);
        self::assertSame($controller->controller_name, 'DbexportController', 'controller name should be DbexportController');
    }

    public function testDisplayView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/display.php';
        $controller = displayFactory($_container);
        self::assertSame($controller->controller_name, 'DisplayController', 'controller name should be DisplayController');
    }

    protected function _before(): void
    {
        $this->container = containerInstance();
        self::$BASE_PATH = $this->container->BASE_PATH;
        $this->container->get('misc')->setNoDBConnection(true);
    }
}
