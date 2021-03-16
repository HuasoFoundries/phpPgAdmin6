<?php

/**
 * PHPPgAdmin6
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

    protected ?\PHPPgAdmin\ContainerUtils $container;

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

    public function testColpropertiesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/colproperties.php';
        $controller = colpropertiesFactory($_container);
        self::assertSame($controller->controller_name, 'ColpropertiesController', 'controller name should be ColpropertiesController');
    }

    public function testConstraintsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/constraints.php';
        $controller = constraintsFactory($_container);
        self::assertSame($controller->controller_name, 'ConstraintsController', 'controller name should be ConstraintsController');
    }

    public function testSequencesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/sequences.php';
        $controller = sequencesFactory($_container);
        self::assertSame($controller->controller_name, 'SequencesController', 'controller name should be SequencesController');
    }

    public function testIndexesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/indexes.php';
        $controller = indexesFactory($_container);
        self::assertSame($controller->controller_name, 'IndexesController', 'controller name should be IndexesController');
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
        $this->container = containerInstance();
        self::$BASE_PATH = $this->container->BASE_PATH;
        $this->container->get('misc')->setNoDBConnection(true);
    }

    protected function _after(): void
    {
    }
}
