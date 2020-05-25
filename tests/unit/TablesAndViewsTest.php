<?php

/**
 * PHPPgAdmin 6.0.1
 */

/**
 * @internal
 * @coversNothing
 */
class TablesAndViewsTest extends \Codeception\Test\Unit
{
    protected static $BASE_PATH;

    /**
     * @var \UnitTester
     */
    protected $tester;

    protected $_container;

    public function testTablespacesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/tablespaces.php';
        $controller = tablespacesFactory($_container);
        self::assertSame($controller->controller_name, 'TablespacesController', 'controller name should be TablespacesController');
    }

    public function testMaterializedviewpropertiesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/materializedviewproperties.php';
        $controller = materializedviewpropertiesFactory($_container);
        self::assertSame($controller->controller_name, 'MaterializedviewpropertiesController', 'controller name should be MaterializedviewpropertiesController');
    }

    public function testMaterializedviewsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/materializedviews.php';
        $controller = materializedviewsFactory($_container);
        self::assertSame($controller->controller_name, 'MaterializedviewsController', 'controller name should be MaterializedviewsController');
    }

    public function testTablesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/tables.php';
        $controller = tablesFactory($_container);
        self::assertSame($controller->controller_name, 'TablesController', 'controller name should be TablesController');
    }

    public function testTblpropertiesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/tblproperties.php';
        $controller = tblpropertiesFactory($_container);
        self::assertSame($controller->controller_name, 'TblpropertiesController', 'controller name should be TblpropertiesController');
    }

    public function testViewpropertiesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/viewproperties.php';
        $controller = viewpropertiesFactory($_container);
        self::assertSame($controller->controller_name, 'ViewpropertiesController', 'controller name should be ViewpropertiesController');
    }

    public function testViewsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/views.php';
        $controller = viewsFactory($_container);
        self::assertSame($controller->controller_name, 'ViewsController', 'controller name should be ViewsController');
    }

    protected function _before(): void
    {
        $Helper = $this->getModule('\Helper\Unit');
        $this->container = $Helper::getContainer();
        self::$BASE_PATH = $Helper::BASE_PATH;
        $this->container->get('misc')->setNoDBConnection(true);
        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);
    }

    protected function _after(): void
    {
    }
}
