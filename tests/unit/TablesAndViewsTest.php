<?php

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
 */

class TablesAndViewsTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $_container;

    protected function _before()
    {
        $Helper          = $this->getModule('\Helper\Unit');
        $this->container = $Helper::getContainer();

        $this->container->get('misc')->setNoDBConnection(true);
        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);
    }

    protected function _after()
    {
    }

    public function testMaterializedviewpropertiesView()
    {
        $_container = $this->container;
        require BASE_PATH . '/tests/views/materializedviewproperties.php';
        $controller = materializedviewpropertiesFactory($_container);
        $this->assertSame($controller->controller_name, 'MaterializedviewpropertiesController', 'controller name should be MaterializedviewpropertiesController');
    }

    public function testMaterializedviewsView()
    {
        $_container = $this->container;
        require BASE_PATH . '/tests/views/materializedviews.php';
        $controller = materializedviewsFactory($_container);
        $this->assertSame($controller->controller_name, 'MaterializedviewsController', 'controller name should be MaterializedviewsController');
    }

    public function testTablesView()
    {
        $_container = $this->container;
        require BASE_PATH . '/tests/views/tables.php';
        $controller = tablesFactory($_container);
        $this->assertSame($controller->controller_name, 'TablesController', 'controller name should be TablesController');
    }

    public function testTblpropertiesView()
    {
        $_container = $this->container;
        require BASE_PATH . '/tests/views/tblproperties.php';
        $controller = tblpropertiesFactory($_container);
        $this->assertSame($controller->controller_name, 'TblpropertiesController', 'controller name should be TblpropertiesController');
    }

    public function testViewpropertiesView()
    {
        $_container = $this->container;
        require BASE_PATH . '/tests/views/viewproperties.php';
        $controller = viewpropertiesFactory($_container);
        $this->assertSame($controller->controller_name, 'ViewpropertiesController', 'controller name should be ViewpropertiesController');
    }

    public function testViewsView()
    {
        $_container = $this->container;
        require BASE_PATH . '/tests/views/views.php';
        $controller = viewsFactory($_container);
        $this->assertSame($controller->controller_name, 'ViewsController', 'controller name should be ViewsController');
    }
}
