<?php

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
 */

class ViewTest extends \Codeception\Test\Unit
{
    const BASE_PATH = \Codeception\Util::BASE_PATH;
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

    // tests

    public function testAcinsertView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/acinsert.php';
        $controller = acinsertFactory($_container);
        $this->assertSame($controller->controller_name, 'AcinsertController', 'controller name should be AcinsertController');
    }

    public function testAggregatesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/aggregates.php';
        $controller = aggregatesFactory($_container);
        $this->assertSame($controller->controller_name, 'AggregatesController', 'controller name should be AggregatesController');
    }

    public function testAlldbView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/alldb.php';
        $controller = alldbFactory($_container);
        $this->assertSame($controller->controller_name, 'AlldbController', 'controller name should be AlldbController');
    }

    public function testBrowserView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/browser.php';
        $controller = browserFactory($_container);
        $this->assertSame($controller->controller_name, 'BrowserController', 'controller name should be BrowserController');
    }

    public function testCastsView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/casts.php';
        $controller = castsFactory($_container);
        $this->assertSame($controller->controller_name, 'CastsController', 'controller name should be CastsController');
    }

    public function testColpropertiesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/colproperties.php';
        $controller = colpropertiesFactory($_container);
        $this->assertSame($controller->controller_name, 'ColpropertiesController', 'controller name should be ColpropertiesController');
    }

    public function testConstraintsView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/constraints.php';
        $controller = constraintsFactory($_container);
        $this->assertSame($controller->controller_name, 'ConstraintsController', 'controller name should be ConstraintsController');
    }

    public function testConversionsView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/conversions.php';
        $controller = conversionsFactory($_container);
        $this->assertSame($controller->controller_name, 'ConversionsController', 'controller name should be ConversionsController');
    }

    public function testDatabaseView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/database.php';
        $controller = databaseFactory($_container);
        $this->assertSame($controller->controller_name, 'DatabaseController', 'controller name should be DatabaseController');
    }

    public function testDataexportView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/dataexport.php';
        $controller = dataexportFactory($_container);
        $this->assertSame($controller->controller_name, 'DataexportController', 'controller name should be DataexportController');
    }

    public function testDataimportView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/dataimport.php';
        $controller = dataimportFactory($_container);
        $this->assertSame($controller->controller_name, 'DataimportController', 'controller name should be DataimportController');
    }

    public function testDbexportView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/dbexport.php';
        $controller = dbexportFactory($_container);
        $this->assertSame($controller->controller_name, 'DbexportController', 'controller name should be DbexportController');
    }

    public function testDisplayView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/display.php';
        $controller = displayFactory($_container);
        $this->assertSame($controller->controller_name, 'DisplayController', 'controller name should be DisplayController');
    }

    public function testDomainsView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/domains.php';
        $controller = domainsFactory($_container);
        $this->assertSame($controller->controller_name, 'DomainsController', 'controller name should be DomainsController');
    }

    public function testFulltextView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/fulltext.php';
        $controller = fulltextFactory($_container);
        $this->assertSame($controller->controller_name, 'FulltextController', 'controller name should be FulltextController');
    }

    public function testFunctionsView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/functions.php';
        $controller = functionsFactory($_container);
        $this->assertSame($controller->controller_name, 'FunctionsController', 'controller name should be FunctionsController');
    }

    public function testGroupsView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/groups.php';
        $controller = groupsFactory($_container);
        $this->assertSame($controller->controller_name, 'GroupsController', 'controller name should be GroupsController');
    }

    public function testHelpView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/help.php';
        $controller = helpFactory($_container);
        $this->assertSame($controller->controller_name, 'HelpController', 'controller name should be HelpController');
    }

    public function testHistoryView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/history.php';
        $controller = historyFactory($_container);
        $this->assertSame($controller->controller_name, 'HistoryController', 'controller name should be HistoryController');
    }

    public function testIndexesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/indexes.php';
        $controller = indexesFactory($_container);
        $this->assertSame($controller->controller_name, 'IndexesController', 'controller name should be IndexesController');
    }

    public function testInfoView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/info.php';
        $controller = infoFactory($_container);
        $this->assertSame($controller->controller_name, 'InfoController', 'controller name should be InfoController');
    }

    public function testIntroView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/intro.php';
        $controller = introFactory($_container);
        $this->assertSame($controller->controller_name, 'IntroController', 'controller name should be IntroController');
    }

    public function testLanguagesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/languages.php';
        $controller = languagesFactory($_container);
        $this->assertSame($controller->controller_name, 'LanguagesController', 'controller name should be LanguagesController');
    }

    public function testLoginView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/login.php';
        $controller = loginFactory($_container);
        $this->assertSame($controller->controller_name, 'LoginController', 'controller name should be LoginController');
    }

    public function testOpclassesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/opclasses.php';
        $controller = opclassesFactory($_container);
        $this->assertSame($controller->controller_name, 'OpclassesController', 'controller name should be OpclassesController');
    }

    public function testOperatorsView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/operators.php';
        $controller = operatorsFactory($_container);
        $this->assertSame($controller->controller_name, 'OperatorsController', 'controller name should be OperatorsController');
    }

    public function testPrivilegesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/privileges.php';
        $controller = privilegesFactory($_container);
        $this->assertSame($controller->controller_name, 'PrivilegesController', 'controller name should be PrivilegesController');
    }

    public function testRolesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/roles.php';
        $controller = rolesFactory($_container);
        $this->assertSame($controller->controller_name, 'RolesController', 'controller name should be RolesController');
    }

    public function testRulesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/rules.php';
        $controller = rulesFactory($_container);
        $this->assertSame($controller->controller_name, 'RulesController', 'controller name should be RulesController');
    }

    public function testSchemasView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/schemas.php';
        $controller = schemasFactory($_container);
        $this->assertSame($controller->controller_name, 'SchemasController', 'controller name should be SchemasController');
    }

    public function testSequencesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/sequences.php';
        $controller = sequencesFactory($_container);
        $this->assertSame($controller->controller_name, 'SequencesController', 'controller name should be SequencesController');
    }

    public function testServersView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/servers.php';
        $controller = serversFactory($_container);
        $this->assertSame($controller->controller_name, 'ServersController', 'controller name should be ServersController');
    }

    public function testSqleditView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/sqledit.php';
        $controller = sqleditFactory($_container);
        $this->assertSame($controller->controller_name, 'SqleditController', 'controller name should be SqleditController');
    }

    public function testSqlView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/sql.php';
        $controller = sqlFactory($_container);
        $this->assertSame($controller->controller_name, 'SqlController', 'controller name should be SqlController');
    }

    public function testTablespacesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/tablespaces.php';
        $controller = tablespacesFactory($_container);
        $this->assertSame($controller->controller_name, 'TablespacesController', 'controller name should be TablespacesController');
    }

    public function testTriggersView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/triggers.php';
        $controller = triggersFactory($_container);
        $this->assertSame($controller->controller_name, 'TriggersController', 'controller name should be TriggersController');
    }

    public function testTypesView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/types.php';
        $controller = typesFactory($_container);
        $this->assertSame($controller->controller_name, 'TypesController', 'controller name should be TypesController');
    }

    public function testUsersView()
    {
        $_container = $this->container;
        require self::BASE_PATH . '/tests/views/users.php';
        $controller = usersFactory($_container);
        $this->assertSame($controller->controller_name, 'UsersController', 'controller name should be UsersController');
    }
}
