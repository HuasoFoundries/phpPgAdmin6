<?php

/**
 * PHPPgAdmin 6.1.2
 */

/**
 * @internal
 * @coversNothing
 */
class ViewTest extends \Codeception\Test\Unit
{
    protected static $BASE_PATH;

    protected $_container;

    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests

    public function testAcinsertView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/acinsert.php';
        $controller = acinsertFactory($_container);
        self::assertSame($controller->controller_name, 'AcinsertController', 'controller name should be AcinsertController');
    }

    public function testAggregatesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/aggregates.php';
        $controller = aggregatesFactory($_container);
        self::assertSame($controller->controller_name, 'AggregatesController', 'controller name should be AggregatesController');
    }

    public function testCastsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/casts.php';
        $controller = castsFactory($_container);
        self::assertSame($controller->controller_name, 'CastsController', 'controller name should be CastsController');
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

    public function testConversionsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/conversions.php';
        $controller = conversionsFactory($_container);
        self::assertSame($controller->controller_name, 'ConversionsController', 'controller name should be ConversionsController');
    }

    public function testDomainsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/domains.php';
        $controller = domainsFactory($_container);
        self::assertSame($controller->controller_name, 'DomainsController', 'controller name should be DomainsController');
    }

    public function testFulltextView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/fulltext.php';
        $controller = fulltextFactory($_container);
        self::assertSame($controller->controller_name, 'FulltextController', 'controller name should be FulltextController');
    }

    public function testFunctionsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/functions.php';
        $controller = functionsFactory($_container);
        self::assertSame($controller->controller_name, 'FunctionsController', 'controller name should be FunctionsController');
    }

    public function testIndexesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/indexes.php';
        $controller = indexesFactory($_container);
        self::assertSame($controller->controller_name, 'IndexesController', 'controller name should be IndexesController');
    }

    public function testLanguagesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/languages.php';
        $controller = languagesFactory($_container);
        self::assertSame($controller->controller_name, 'LanguagesController', 'controller name should be LanguagesController');
    }

    public function testOpclassesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/opclasses.php';
        $controller = opclassesFactory($_container);
        self::assertSame($controller->controller_name, 'OpclassesController', 'controller name should be OpclassesController');
    }

    public function testOperatorsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/operators.php';
        $controller = operatorsFactory($_container);
        self::assertSame($controller->controller_name, 'OperatorsController', 'controller name should be OperatorsController');
    }

    public function testRulesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/rules.php';
        $controller = rulesFactory($_container);
        self::assertSame($controller->controller_name, 'RulesController', 'controller name should be RulesController');
    }

    public function testSequencesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/sequences.php';
        $controller = sequencesFactory($_container);
        self::assertSame($controller->controller_name, 'SequencesController', 'controller name should be SequencesController');
    }

    public function testTriggersView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/triggers.php';
        $controller = triggersFactory($_container);
        self::assertSame($controller->controller_name, 'TriggersController', 'controller name should be TriggersController');
    }

    public function testTypesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/types.php';
        $controller = typesFactory($_container);
        self::assertSame($controller->controller_name, 'TypesController', 'controller name should be TypesController');
    }

    protected function _before(): void
    {
        $Helper = $this->getModule('\Helper\Unit');
        $this->container = $Helper::getContainer();
        self::$BASE_PATH = self::$BASE_PATH = $this->container->BASE_PATH;
        $this->container->get('misc')->setNoDBConnection(true);
        // Helper
        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);
    }
}
